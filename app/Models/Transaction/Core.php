<?php

namespace RZP\Models\Transaction;

use Mail;
use Queue;
use Config;
use Carbon\Carbon;
use Razorpay\Trace\Logger;

use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Jobs\Settlement\Bucket;
use RZP\Jobs\CardsPaymentTransaction;
use RZP\Mail\Merchant\FeeCreditsAlert;
use RZP\Models\Base;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Ledger\ReverseShadow\Payments\Core as ReverseShadowPaymentsCore;
use RZP\Models\Ledger\SettlementJournalEvents;
use RZP\Trace\Tracer;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Dispute;
use RZP\Models\Reversal;
use RZP\Models\Currency;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Payout;
use RZP\Models\CreditTransfer;
use RZP\Models\Payment\Refund;
use RZP\Models\Pricing;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Transaction;
use RZP\Models\Settlement;
use RZP\Models\Adjustment;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Schedule\Library as ScheduleLibrary;
use RZP\Models\Schedule\Task as ScheduleTask;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer;
use RZP\Models\Feature;
use RZP\Models\Merchant\Credits;
use RZP\Models\Merchant\FeeModel;
use RZP\Models\Merchant\Balance;
use RZP\Constants\Entity as E;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Processor\Processor;
use Neves\Events\TransactionalClosureEvent;
use RZP\Models\Merchant\Balance\BalanceConfig;
use RZP\Jobs\Ledger\CreateLedgerJournal as LedgerEntryJob;
use RZP\Models\Transaction\Processor as TransactionProcessor;

class Core extends Base\Core
{
    // July 1st, 2016 00:00:00 IST
    const JULY_FIRST_EPOCH = '1467311400';

    protected $merchantBalance = null;

    protected $nodalBalance = null;

    protected $merchant;

    use Payment\Processor\Capture;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    public function getFactory(Base\Entity $source): TransactionProcessor\Base
    {
        $type = $source->getEntityName();

        $processor = __NAMESPACE__ ;

        $processor .= '\\Processor\\' .studly_case($type);

        return new $processor($source);
    }

    public function createTransactionForSource(Base\Entity $source, $txnId=null)
    {
        $txnProcessor = $this->getFactory($source);

        return $txnProcessor->createTransaction($txnId);
    }

    /**
     * This will be called only in case of Non Auth Capture Flow
     * We will create a dummy transaction with no fee split.
     * The actual fee split will be calculated at the time of payment capture
     * @param  Payment\Entity $payment
     * @return array [Transaction\Entity $txn, PublicCollection $feesSplit]
     */
    public function createFromPaymentAuthorized(Payment\Entity $payment, $txnId = null)
    {
        return $this->createTransactionForSource($payment, $txnId);

        // old code, will delete port refactoring all entites
        $this->trace->info(
            TraceCode::PAYMENT_AUTHORIZE_CREATE_TRANSACTION,
            [
                'payment_id' => $payment->getId()
            ]);

        $merchant = $payment->merchant;

        list($txn, $feesSplit) = $this->txnCreationFromPaymentOperation($payment, false);

        return [$txn, $feesSplit];
    }

    /**
     * Update the corresponding transaction when
     * hold attributes of a Payment are updated
     *
     * @param  Payment\Entity $payment
     *
     * @return Transaction\Entity
     * @throws Exception\BadRequestException
     */
    public function updateOnHoldToggle(Payment\Entity $payment)
    {
        $txn = $payment->transaction;

        if ($txn->isSettled() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_UPDATE_ON_HOLD_ALREADY_SETTLED);
        }

        $txn->setOnHold($payment->getOnHold());

        $this->trace->info(
            TraceCode::PAYMENT_HOLD_TOGGLE_UPDATE_TRANSACTION,
            [
                'payment_id'     => $payment->getId(),
                'transaction_id' => $txn->getId(),
                'on_hold'        => $payment->getOnHold()
            ]
        );

        return $txn;
    }

    public function createOrUpdateFromPaymentCaptured(Payment\Entity $payment, $txnId = null)
    {
        list($txn, $feesSplit) = $this->createTransactionForSource($payment, $txnId);

        $shouldDispatchSettlementBucket = true;

        // We need not to dispatch for settlement if ASYNC_TXN_FILL_DETAILS is enabled and payment is processed in rearch
        if((($payment->merchant->isFeatureEnabled(Feature\Constants::ASYNC_TXN_FILL_DETAILS) === true) and
            ($payment->isExternal() === false)))
        {
            $shouldDispatchSettlementBucket = false;
        }

        if($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
        {
            $shouldDispatchSettlementBucket = true;
        }

        if ($shouldDispatchSettlementBucket === true)
        {
            // dispatch this transaction for settlement.
            $this->dispatchForSettlementBucketing($txn);
        }
        return [$txn, $feesSplit];
    }

    public function createUpdateLedgerTransaction(Payment\Entity $payment, $txnId =null)
    {
        $merchant =  $this->repo->merchant->findByPublicId($payment->getMerchantId());

        $payment->merchant()->associate($merchant);

        $terminal = $this->repo->terminal->findOrFail($payment->getTerminalId());

        $payment->terminal()->associate($terminal);

        if ($payment->getStatus() == "captured")
        {
            $paymentTxn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($payment);

            if (($paymentTxn !== null) and
                ($paymentTxn->isBalanceUpdated() === true))
            {
                return $paymentTxn;
            }

            $txn =  $this->createTransactionForCapturedPayment($payment, $txnId);

            //For rearch card payments, journals are created in payments-card microservice in reverse-shadow mode
            if(($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true) and
                ($payment->getCpsRoute() !== Payment\Entity::REARCH_CARD_PAYMENT_SERVICE))
            {
                $this->createPaymentLedgerEntriesInReverseShadow($payment);
            }
            else
            {
                $this->createLedgerEntriesForGatewayCapture($payment);
                $this->createLedgerEntriesForMerchantCapture($payment, $txn);
            }

            $this->repo->transaction(function() use ($payment,$txn)
            {

                $processor = new Processor($payment->merchant);

                $processor->createPartnerCommission($payment);
            });
        }
        else if ($payment->getStatus() == "authorized")
        {
            $txn =  $this->createTransactionForAuthorizedPayment($payment);
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException("Payment Status not in correct status for transaction creation");
        }

        // Note: If pg_ledger_reverse_shadow flag is enabled for merchant and payment is a rearch card payment,
        // then we need not dispatch updated transaction to CPS as this is a sync call in payments-card microservice for reverse-shadow mode
        if(($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true) and
            ($payment->getCpsRoute() === Payment\Entity::REARCH_CARD_PAYMENT_SERVICE))
        {
            $this->trace->info(
                TraceCode::TRANSACTION_NOT_DISPATCHED_FOR_REARCH_CARD_PAYMENT_IN_REVERSE_SHADOW,
                [
                    'payment_id'     => $payment->getId(),
                    'transaction_id' => $txn->getId(),
                    'merchant_id'        => $merchant->getId(),
                ]
            );
        }
        else
        {
            $this->dispatchUpdatedTransactionToCPS($txn, $payment);
        }

        return $txn;
    }

    public function createPaymentLedgerEntriesInReverseShadow(Payment\Entity $payment)
    {
        // create journals in reverse shadow mode until reverse shadow implementation for rearch payments is complete
        try
        {
            (new ReverseShadowPaymentsCore())->createLedgerEntryForGatewayCaptureReverseShadow($payment);

            $paymentProcessor = new Payment\Processor\Processor($payment->merchant);

            $discount = $paymentProcessor->getDiscountIfApplicableForLedger($payment);

            [$fee, $tax] = (new ReverseShadowPaymentsCore())->createLedgerEntryForMerchantCaptureReverseShadow($payment, $discount);

            $this->trace->info(TraceCode::REARCH_PAYMENT_MERCHANT_CAPTURED_REVERSE_SHADOW, [
                LedgerConstants::PAYMENT_ID     => $payment->getPublicId(),
                LedgerConstants::FEES           => $fee,
                LedgerConstants::TAX            => $tax,
                LedgerConstants::MERCHANT_ID    => $payment->merchant->getId(),
            ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::REARCH_PAYMENT_MERCHANT_CAPTURED_REVERSE_SHADOW_FAILED,
                [
                    LedgerConstants::PAYMENT_ID     => $payment->getPublicId(),
                    LedgerConstants::MERCHANT_ID    => $payment->merchant->getId(),
                ]);
        }

    }
    public function dispatchUpdatedTransactionToCPS($txn, $payment)
    {
        $transactionData = [
            "fee" => $txn->getFee(),
            "mdr" => $txn->getAttribute(Entity::MDR) ?? $txn->getFee(),
            "tax" => $txn->getTax(),
            "transaction_id" => $txn->getAttribute(Entity::ID),
            "credit_type" => $txn->getCreditType(),
            "pricing_id" => $txn->getPricingRule(),
            "settled_by" => $payment->getSettledBy(),
            "credit_amount" => $txn->getCredit(),
        ];

        $data = [
            "entity_type" => "transaction",
            "payment_id" => $txn->getEntityId(),
            "transaction" => $transactionData,
            "mode" => $this->mode
        ];

        if ($this->app->runningUnitTests() === false)
        {
            if ($payment->isCard() === true)
            {
                CardsPaymentTransaction::dispatch($data);
            }
            else if ($payment->isNetbanking() === true || $payment->isFpx() === true || $payment->isWallet() === true)
            {
                $queueName = $this->app['config']->get('queue.payment_nbplus_api_reconciliation.' . $this->mode);

                $data["entity_name"] = "transaction";

                $this->app['queue']->connection('sqs')->pushRaw(json_encode($data), $queueName);

                $this->trace->info(
                    TraceCode::TRANSACTION_INFO,
                    [
                        'queue'      => $queueName,
                        'payment_id' => $data['payment_id'],
                        'gateway'    => $payment->getGateway(),
                    ]
                );
            }
            else if (($payment->isUpi() === true) and
                    ($payment->isRoutedThroughPaymentsUpiPaymentService() === true))
            {
                $action = 'transaction_upsert';

                $this->app['upi.payments']->action($action, $data, $payment->getGateway());
            }
        }
    }

    private function createTransactionForAuthorizedPayment(Payment\Entity $payment)
    {
        list($txn, $feesSplit) = (new Transaction\Core)->createFromPaymentAuthorized($payment);

        $this->repo->saveOrFail($txn);

        return $txn;
    }

    private function createTransactionForCapturedPayment(Payment\Entity $payment, $txnId = null)
    {
        return $this->repo->transaction(function() use ($payment, $txnId)
        {
            list($txn, $feesSplit) = $this->createOrUpdateFromPaymentCaptured($payment, $txnId);

            // $merchantBalance is required in the caller function only if lateBalanceUpdate is set to true.

            $merchantBalance = null;

            if ($payment->isLateBalanceUpdate() === true)
            {
                $merchantId = $txn->getMerchantId();

                $merchantBalance = $this->repo->balance->findOrFail($merchantId);

                $txn->accountBalance()->associate($merchantBalance);
            }

            $processor = new Processor($payment->merchant);

            $processor->calculateAndSetMdrFeeIfApplicable($payment, $txn);

            $this->trace->debug(TraceCode::TRANSACTION_DETAILS,
                [
                    'transaction_id'        => $txn->getId(),
                    'payment_id'            => $txn->getEntityId(),
                    'transaction_credit'    => $txn->getCredit(),
                    'transaction_debit'     => $txn->getDebit(),
                    'transaction_amount'    => $txn->getAmount(),
                    'transaction_fee'       => $txn->getFee()
                ]
            );

            $this->repo->saveOrFail($txn);

            $this->saveFeeDetails($txn, $feesSplit);

            return $txn;
        });
    }

    /**
     * Creates a Transaction record for a Payment transfer credit to account
     * Updates Marketplace balance
     *
     * @param  Payment\Entity $payment
     * @return array
     */
    public function createFromPaymentTransferred(Payment\Entity $payment) : array
    {
        list($txn, $feesSplit) = Tracer::inSpan(['name' => 'transfer.process.create_transfer_payment.create_transaction'], function() use ($payment)
        {
            return $this->txnCreationFromPaymentOperation($payment);
        });

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_CREATE_TRANSACTION,
            [
                'type'          => 'linked_account_credit',
                'payment_id'     => $payment->getId(),
                'transaction_id' => $txn->getId()
            ]);

        $settledAt = $this->getSettledAtTimestamp($payment);

        $onHold = $payment->getOnHold() ?? false;

        $txn->setReconciledAt(time());

        $txn->setReconciledType(ReconciledType::NA);

        $txn->setAttribute(Entity::SETTLED_AT, $settledAt);

        $txn->setAttribute(Entity::ON_HOLD, $onHold);

        $this->updateCredits($txn, $payment);

        $this->updateBalances($txn, false, true);

        $this->dispatchForSettlementBucketing($txn);

        return [$txn, $feesSplit];
    }

    public function markGratisTransactionPostpaid(Entity $txn, Merchant\Entity $merchant)
    {
        $this->repo->transaction(function() use ($txn, $merchant)
        {
            $payment = $txn->source;

            $merchantBalance = $this->repo->balance->getMerchantBalance($merchant);

            $this->merchantBalance = $merchantBalance;

            $feesSplit = new Base\PublicCollection;

            $feeBreakUps = $this->repo->fee_breakup->fetchByTransactionId($txn->getId());

            foreach ($feeBreakUps as $feeBreakUp)
            {
                $this->repo->fee_breakup->deleteFeeBreakupForId($feeBreakUp->getId());
            }

            list($credit, $fee, $serviceTax, $feesSplit) = $this->calculatePostpaidFee($txn);

            $txn->setCredit($credit);
            $txn->setDebit(0);
            $txn->setFee($fee);
            $txn->setFeeModel(FeeModel::POSTPAID);
            $txn->setGratis(false);
            $txn->setCreditType(Transaction\CreditType::DEFAULT);
            $txn->setPricingRule(null);

            if ($payment->isFeeBearerCustomer() === false)
            {
                //set and fee values from txn
                $payment->setFee($fee);
            }

            foreach ($feesSplit as $feeSplit)
            {
                $feeSplit->transaction()->associate($txn);

                $this->repo->saveOrFail($feeSplit);
            }

            $this->repo->saveOrFail($payment);

            $this->repo->saveOrFail($txn);

            $this->saveFeeDetails($txn, $feesSplit);
        });
    }

    public function markTransactionPostpaid(Entity $txn)
    {
        $this->markGratisTransactionPostpaid($txn, $txn->merchant);
    }

    public function updateReconciliationData(Entity $transaction)
    {
        $reconciled = $transaction->isReconciled();

        if ($reconciled === true)
        {
            return false;
        }

        $transaction->setReconciledAt(time());
        $transaction->setReconciledType(ReconciledType::NA);
        $transaction->setGatewayFee(0);
        $transaction->setGatewayServiceTax(0);

        $this->repo->saveOrFail($transaction);

        return true;
    }

    protected function txnCreationFromPaymentOperation(Payment\Entity $payment, bool $updateFees = true)
    {
        $txn = new Transaction\Entity;

        // Case 1: Non AuthCapture flow, a txn already exists with min data.
        // Case 2: Auth-capture flow, we create a txn and associate merchant, payment with it.
        if ($payment->hasTransaction() === true)
        {
            $txn = $this->repo->transaction->fetchByEntityAndAssociateMerchant($payment);
        }
        else
        {
            $txn->generateId();

            $txn->sourceAssociate($payment);

            $txn->merchant()->associate($payment->merchant);
        }

        list($txn, $feesSplit) = $this->fillTxnFeesAndAmount($txn, $payment, $updateFees);

        $txnData = [
            Transaction\Entity::TYPE            => Transaction\Type::PAYMENT,
            Transaction\Entity::CURRENCY        => Currency\Currency::INR,
            Transaction\Entity::CHANNEL         => $payment->merchant->getChannel(),
        ];

        if ($payment->getGateway() === Payment\Gateway::WALLET_OPENWALLET)
        {
            $txnData[Entity::RECONCILED_AT]     = time();
            $txnData[Entity::RECONCILED_TYPE]   = ReconciledType::NA;
        }

        $txn->fill($txnData);

        $this->trace->info(
            TraceCode::TRANSACTION_CREATED,
            [
                'payment_id'     => $payment->getId(),
                'transaction_id' => $txn->getId(),
            ]);

        return [$txn, $feesSplit];
    }

    protected function fillEmptyTxnFeesAndAmount(Transaction\Entity $txn, Payment\Entity $payment)
    {
        $amount = $payment->getBaseAmount();

        $values = [
            Transaction\Entity::DEBIT               => 0,
            Transaction\Entity::CREDIT              => 0,
            Transaction\Entity::FEE                 => 0,
            Transaction\Entity::TAX                 => 0,
            Transaction\Entity::AMOUNT              => $amount,
        ];

        $txn->fill($values);

        return [$txn, new Base\PublicCollection];
    }

    protected function fillTxnFeesAndAmount(Transaction\Entity $txn, Payment\Entity $payment, bool $updateFees = true)
    {
        if ($updateFees === false)
        {
            return $this->fillEmptyTxnFeesAndAmount($txn, $payment);
        }

        $pricingRuleId = null;

        $merchant = $payment->merchant;

        $oldTransaction = $this->checkIfOldPayment($payment);

        $txn->setFeeModel($merchant->getFeeModel());

        $txn->setFeeBearer($payment->getFeeBearer());

        $amount = $payment->getBaseAmount();

        $txn->setAmount($amount);

        $feesSplit = new Base\PublicCollection;

        if ($oldTransaction === true)
        {
            $pricingRuleId = (new Pricing\Fee)->getZeroPricingPlanRule($payment)->getId();

            $fee = 0;
            $tax = 0;
            $credit = $amount;

            $txn->setPricingRule($pricingRuleId);
        }
        else if ($merchant->isPrepaid() === true)
        {
            list($credit, $fee, $tax, $feesSplit) = $this->calculatePrepaidFee($txn);
        }
        else
        {
            list($credit, $fee, $tax, $feesSplit) = $this->calculatePostpaidFee($txn);
        }

        $txn->setCredit(0);
        $txn->setDebit(0);

        if ($credit >= 0)
        {
            $txn->setCredit($credit);
        }
        else
        {
            $debit = -1 * $credit;

            $txn->setDebit($debit);
        }

        $txn->setFee($fee);
        $txn->setTax($tax);

        return [$txn, $feesSplit];
    }

    /**
     * Calculate Fee for Prepaid Fee Model
     * Merchant can be a fee_bearer customer or platform
     *
     * @param  Entity      $transaction
     *
     * @return array
     */
    protected function calculatePrepaidFee(Entity $transaction)
    {
        $merchantId = $transaction->getMerchantId();

        $merchantBalance = $this->getBalanceLockForUpdate($merchantId);

        list($amountCredits, $feeCredits) = $this->getMerchantCredits($transaction->merchant);

        list($fee, $tax, $feesSplit) = $this->calculateMerchantFees($transaction);

        $entity = $transaction->source;

        switch (true)
        {
            case ($transaction->isFeeBearerCustomer()):
                return $this->calculateFeeForPrepaidDefault($transaction);

            // @todo: Need to rethink this.
            case (($amountCredits > 0) and ($entity->getAmount() !== 0) and ($amountCredits >= $transaction->getAmount())):
                return $this->calculateFeeForAmountCredit($transaction);

            case ($feeCredits >= $fee):
                return $this->calculateFeeForFeeCredit($transaction);

            default:
                return $this->calculateFeeForPrepaidDefault($transaction);
        }
    }

    /**
     * Calculate Fee for Postpaid Fee Model
     * Merchant can only be a fee_bearer platform
     *
     * @param  Entity      $transaction
     * @return array
     */
    protected function calculatePostpaidFee(Entity $transaction)
    {
        $merchantId = $transaction->getMerchantId();

        $merchantBalance = $this->getBalanceLockForUpdate($merchantId);

        list($amountCredits, $feeCredits) = $this->getMerchantCredits($transaction->merchant);

        list($fee, $tax, $feesSplit) = $this->calculateMerchantFees($transaction);

        $entity = $transaction->source;

        switch (true)
        {
            case (($amountCredits > 0) and ($entity->getAmount() !== 0) and ($amountCredits >= $transaction->getAmount())):
                return $this->calculateFeeForAmountCredit($transaction);

            case ($feeCredits >= $fee):
                return $this->calculateFeeForFeeCredit($transaction);

            default:
                return $this->calculateFeeForPostpaidDefault($transaction);
        }
    }

    /**
     * Calculate Fee for Amount Credit
     * Credit = amount, fee & ST = 0
     *
     * @param Transaction\Entity $transaction
     *
     * @return array
     */
    protected function calculateFeeForAmountCredit(Entity $transaction)
    {
        $amount = $transaction->getAmount();

        $source = $transaction->source;

        $this->trace->info(
            TraceCode::TRANSACTION_AMOUNT_CREDITS,
            [
                'source_type'    => $transaction->getType(),
                'source_id'      => $source->getId(),
                'amount'         => $amount,
            ]
        );

        $pricingRuleId = (new Pricing\Fee)->getZeroPricingPlanRule($source)->getId();

        $transaction->setPricingRule($pricingRuleId);

        $credit = $amount;
        $fee = 0;
        $tax = 0;

        $transaction->setGratis(true);

        $transaction->setCreditType(Transaction\CreditType::AMOUNT);

        return [$credit, $fee, $tax, new Base\PublicCollection];
    }

    /**
     * Calculate Fee for Fee Credit
     * credit = amount, fee_credit = fee
     *
     * @param Transaction\Entity $transaction
     *
     * @return array
     */
    protected function calculateFeeForFeeCredit(Entity $transaction)
    {
        list($fee, $tax, $feesSplit) = $this->calculateMerchantFees($transaction);

        $amount = $transaction->getAmount();

        $credit = $amount;
        $feeCredits = $fee;

        $transaction->setCredits($feeCredits);
        $transaction->setCreditType(Transaction\CreditType::FEE);

        return [$credit, $fee, $tax, $feesSplit];
    }

    /**
     * Calculate Prepaid Fee for Default credit type
     * credit = amount - fee
     *
     * @param Transaction\Entity $transaction
     *
     * @return array
     */
    protected function calculateFeeForPrepaidDefault(Entity $transaction)
    {
        list($fee, $tax, $feesSplit) = $this->calculateMerchantFees($transaction);

        $amount = $transaction->getAmount();

        $credit = $amount - $fee;

        $transaction->setCreditType(Transaction\CreditType::DEFAULT);

        return [$credit, $fee, $tax, $feesSplit];
    }

    /**
     * Calculate Postpaid Fee for Default credit type
     * credit = amount
     *
     * @param  Transaction\Entity $transaction
     *
     * @return array
     */
    protected function calculateFeeForPostpaidDefault(Entity $transaction)
    {
        list($fee, $tax, $feesSplit) = $this->calculateMerchantFees($transaction);

        $amount = $transaction->getAmount();

        $credit = $amount;

        $transaction->setCreditType(Transaction\CreditType::DEFAULT);

        return [$credit, $fee, $tax, $feesSplit];
    }

    protected function checkIfOldPayment($payment)
    {
        if (($payment->getCreatedAt() < self::JULY_FIRST_EPOCH) and
            ($payment->transaction === null) and
            ($payment->isAuthorized() === true))
        {
            $this->trace->info(
                TraceCode::PAYMENT_TRANSACTION_OLD,
                [
                    'payment_id'      => $payment->getId(),
                    'payment_created' => Carbon::createFromTimestamp($payment->getCreatedAt())
                                               ->toDateTimeString()
                ]
            );

            return true;
        }

        return false;
    }

    public function createFromRefund(Refund\Entity $refund, $txnId = null)
    {
        // refund's payment must have transaction
        $payment = $refund->payment;

        // if merchant has pg_ledger_reverse_shadow enabled, we will not be asserting if payment txn exists.
        if ($refund->merchant->isFeatureEnabled(FeatureConstants::PG_LEDGER_REVERSE_SHADOW) === false)
        {
            assertTrue ($payment->hasTransaction() === true);
        }

        return $this->createTransactionForSource($refund, $txnId);
    }

    public function createFromRefundReversal(Reversal\Entity $reversal, $txnId = null)
    {
        $txnProcessor = (new TransactionProcessor\Reversal($reversal));

        list($txn, $feesSplit) = $txnProcessor->createTransaction($txnId);

        return [$txn, $feesSplit];
    }

    public function createFromReversal(Reversal\Entity $reversal)
    {
        $txnProcessor = (new TransactionProcessor\Reversal($reversal));

        list($txn, $feesSplit) = $txnProcessor->createTransaction();

        return [$txn, $feesSplit];
    }

    public function createFromAdjustment(Adjustment\Entity $adj, $txnId = null)
    {
        list($txn, $feeSplit) = $this->createTransactionForSource($adj, $txnId);

        return $txn;
    }

    public function createFromCreditTransfer(CreditTransfer\Entity $ct)
    {
        list($txn, $feeSplit) = $this->createTransactionForSource($ct);

        return $txn;
    }

    /**
     * Record and associate a transaction for a payment transfer.
     *
     * @param  Transfer\Entity $transfer Transfer entity
     *
     * @return array
     */
    public function createFromTransfer(Transfer\Entity $transfer)
    {
        $txn = new Transaction\Entity;

        $merchant = $transfer->merchant;

        $txn->generateId();

        $txn->sourceAssociate($transfer);

        $txn->merchant()->associate($merchant);

        $amount = $transfer->getAmount();

        $txn->setAmount($amount);

        if($transfer->isBalanceTransfer() === true)
        {
            list($debit, $fee, $tax, $feesSplit) = [$transfer->getAmount(), 0, 0, new PublicCollection()];
        }
        else
        {
            list($debit, $fee, $tax, $feesSplit) = $this->calculateTransferFees($txn);
        }

        $txn->setCredit(0);
        $txn->setDebit($debit);
        $txn->setFee($fee);
        $txn->setTax($tax);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        //
        // We're checking for available balance here and not earlier because
        // fees needs to be calculated first. Unlike payments, in the case of
        // transfers, amount+fee is what will be debited from the merchant balance
        //
        $merchantBalance = $this->repo->balance->getMerchantBalance($merchant);

        $transfer->getValidator()->validateMerchantBalanceForTransfer($merchant, $merchantBalance);

        //
        // For transfers from a payment, if the source payment is not
        // settled yet, delay the settled_at timestamp to avoid this txn
        // from being picked up for settlement immediately.
        //
        // Without this, the transfer txn would get picked up for settlement
        // before the payment txn, leading to a overall negative settlement
        // that is then skipped.
        //
        if ($transfer->getSourceType() === E::PAYMENT)
        {
            $paymentTxn = $transfer->source->transaction;

            // Setting current timestamp to transfer settled_at when $paymentTxn->getSettledAt() is null to support async_txn_fill_details feature
            // Slack ref - https://razorpay.slack.com/archives/CNXC0JHQF/p1649241605237939?thread_ts=1648804095.677009&cid=CNXC0JHQF
            if ($paymentTxn->isSettled() === false && $paymentTxn->getSettledAt() !== null)
            {
                $settledAt = $paymentTxn->getSettledAt();
            }
        }

        $values = [
            Transaction\Entity::CURRENCY        => $transfer->getCurrency(),
            Transaction\Entity::GATEWAY_FEE     => 0,
            Transaction\Entity::API_FEE         => $fee,
            Transaction\Entity::RECONCILED_AT   => time(),
            Transaction\Entity::RECONCILED_TYPE => ReconciledType::NA,
            Transaction\Entity::SETTLED         => 0,
            Transaction\Entity::SETTLED_AT      => $settledAt,
            Transaction\Entity::TYPE            => Transaction\Type::TRANSFER,
            Transaction\Entity::CHANNEL         => $transfer->merchant->getChannel(),
        ];

        $txn->fill($values);

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_CREATE_TRANSACTION,
            [
                'type'           => 'merchant_debit',
                'transaction_id' => $txn->getId()
            ]);

        //
        // Saving the transaction entity here because we create a
        // credit_transactions record in the next statement which
        // has a foreign key relation to transaction,
        //
        $this->repo->saveOrFail($txn);

        $this->updateCredits($txn, $transfer);

        $this->updateBalances($txn, false, true);

        $this->dispatchForSettlementBucketing($txn);

        return [$txn, $feesSplit];
    }

    /**
     * Create transaction and update balances for a reversal
     * with entity=`transfer`
     *
     * @param  Reversal\Entity   $reversal
     * @return Entity
     */
    public function createFromTransferReversal(Reversal\Entity $reversal)
    {
        $txn = new Transaction\Entity;

        $amount = $reversal->getAmount();

        // Compute the `settled_at` timestamp
        $settleTimestamp = $this->getTransferReversalSettledAtTimestamp($reversal);

        $data = [
            Transaction\Entity::DEBIT           => 0,
            Transaction\Entity::CREDIT          => $amount,
            Transaction\Entity::CURRENCY        => Currency\Currency::INR,
            Transaction\Entity::GATEWAY_FEE     => 0,
            Transaction\Entity::API_FEE         => 0,
            Transaction\Entity::RECONCILED_AT   => $settleTimestamp,
            Transaction\Entity::RECONCILED_TYPE => ReconciledType::NA,
            Transaction\Entity::SETTLED         => 0,
            Transaction\Entity::SETTLED_AT      => $settleTimestamp,
            Transaction\Entity::FEE             => 0,
            Transaction\Entity::TAX             => 0,
            Transaction\Entity::AMOUNT          => $amount,
            Transaction\Entity::TYPE            => Transaction\Type::REVERSAL,
            Transaction\Entity::CHANNEL         => $reversal->merchant->getChannel(),
        ];

        $txn->fillAndGenerateId($data);

        $txn->merchant()->associate($reversal->merchant);

        $txn->sourceAssociate($reversal);

        $this->updateBalances($txn, false);

        $this->dispatchForSettlementBucketing($txn);

        return $txn;
    }

    public function createFromOndemandPartialReversal(Reversal\Entity $reversal): Entity
    {
        $txn = new Transaction\Entity;

        $amount = $reversal->getAmount();

        $data = [
            Transaction\Entity::DEBIT           => 0,
            Transaction\Entity::CREDIT          => $amount,
            Transaction\Entity::CURRENCY        => Currency\Currency::INR,
            Transaction\Entity::GATEWAY_FEE     => 0,
            Transaction\Entity::API_FEE         => 0,
            Transaction\Entity::RECONCILED_AT   => null,
            Transaction\Entity::RECONCILED_TYPE => ReconciledType::NA,
            Transaction\Entity::SETTLED         => 0,
            Transaction\Entity::SETTLED_AT      => time(),
            Transaction\Entity::FEE             => 0,
            Transaction\Entity::TAX             => 0,
            Transaction\Entity::AMOUNT          => $amount,
            Transaction\Entity::TYPE            => Transaction\Type::REVERSAL,
            Transaction\Entity::CHANNEL         => $reversal->merchant->getChannel(),
        ];

        $txn->fillAndGenerateId($data);

        $txn->merchant()->associate($reversal->merchant);

        $txn->sourceAssociate($reversal);

        $this->updateBalances($txn, false);

        $this->dispatchForSettlementBucketing($txn);

        return $txn;
    }

    public function createFromPayoutReversal(Reversal\Entity $reversal): Entity
    {
        $txnProcessor = (new TransactionProcessor\Reversal($reversal));

        list($txn, $feesSplit) = $txnProcessor->createTransaction();

        return $txn;
    }

    public function createFromDispute(Dispute\Entity $dispute): Entity
    {
        $txn = new Entity;

        $nowTimestamp = Carbon::now()->getTimestamp();

        $data = [
            Entity::DEBIT         => $dispute->getAmountDeducted(),
            Entity::CREDIT        => 0,
            Entity::CURRENCY      => $dispute->getCurrency(),
            Entity::GATEWAY_FEE   => 0,
            Entity::API_FEE       => 0,
            Entity::SETTLED       => 0,
            Entity::SETTLED_AT    => $nowTimestamp,
            Entity::FEE           => 0,
            Entity::TAX           => 0,
            Entity::AMOUNT        => $dispute->getAmountDeducted(),
            Entity::TYPE          => Type::DISPUTE,
            Entity::CHANNEL       => $dispute->merchant->getChannel(),
        ];

        $txn->fillAndGenerateId($data);

        $txn->merchant()->associate($dispute->merchant);

        $txn->sourceAssociate($dispute);

        $this->updateBalances($txn);

        $this->dispatchForSettlementBucketing($txn);

        return $txn;
    }

    public function createFromSettlement(Settlement\Entity $settlement, $journalID)
    {
        list($txn, $feeSplit) = $this->createTransactionForSource($settlement, $journalID);

        $this->createLedgerEntryForSettlement($txn, $settlement);

        return $txn;
    }

    private function createLedgerEntryForSettlement(Transaction\Entity $txn, Settlement\Entity $settlement)
    {
        if($txn->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === false)
        {
            return;
        }

        $balance = $txn->accountBalance;

        if ((isset($balance) === true) and
            ($balance->getType() !== Balance\Type::PRIMARY))
        {
            return;
        }

        try
        {
            $transactionMessage = SettlementJournalEvents::createTransactionMessageForSettlement($settlement, $txn);

            \Event::dispatch(new TransactionalClosureEvent(function () use ($transactionMessage)
            {
                LedgerEntryJob::dispatchNow($this->mode, $transactionMessage);
            }));
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PG_LEDGER_ENTRY_FAILED,
                []);
        }
    }

    public function createFromSettlementTransfer(Settlement\Transfer\Entity $transfer)
    {
        list($txn, $feeSplit) = $this->createTransactionForSource($transfer);

        return $txn;
    }

    public function createFromPayout(Payout\Entity $payout)
    {
        $txn = new Transaction\Entity;

        $txn->generateId();

        $txn->sourceAssociate($payout);

        $txn->merchant()->associate($payout->merchant);

        list($fee, $tax, $feesSplit) = $this->calculateMerchantFees($txn);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $amount = $payout->getAmount();

        if ($payout->getPayoutType() === Payout\Entity::ON_DEMAND)
        {
            $payoutAmount = $amount - $fee;

            if ($payoutAmount < 100)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYOUT_LESS_THAN_MIN_AMOUNT,
                    null,
                    [
                      'amount' => $amount,
                      'fee'    => $fee
                    ]);
            }

            $debitAmount = $amount;

            // Here, payout amount is the amount requested by merchant for payout and fees is
            // levied over it. Also, this fees is deducted from merchant balance. This happens for
            // merchants who do not have 'es_on_demand' feature enabled. In case of 'es_on_demand'
            // merchants, payout fees will be deducted from payout amount requested by the merchant.
            // This is done to allow a merchant to do a payout on requested amount, rather than
            // calculating fees over it and failing a transaction if merchant does not have enough balance.
            $payout->setAmount($payoutAmount);
        }
        else
        {
            $payoutAmount = $amount + $fee;

            $debitAmount = $payoutAmount;
        }

        $values = [
            Transaction\Entity::DEBIT               => $debitAmount,
            Transaction\Entity::CREDIT              => 0,
            Transaction\Entity::CURRENCY            => 'INR',
            Transaction\Entity::GATEWAY_FEE         => 0,
            Transaction\Entity::GATEWAY_SERVICE_TAX => 0,
            Transaction\Entity::API_FEE             => $fee,
            Transaction\Entity::RECONCILED_AT       => time(),
            Transaction\Entity::RECONCILED_TYPE     => ReconciledType::NA,
            Transaction\Entity::SETTLED             => 0,
            Transaction\Entity::SETTLED_AT          => $settledAt,
            Transaction\Entity::FEE                 => $fee,
            Transaction\Entity::TAX                 => $tax,
            Transaction\Entity::AMOUNT              => $payoutAmount,
            Transaction\Entity::TYPE                => Transaction\Type::PAYOUT,
            Transaction\Entity::CHANNEL             => $payout->getChannel(),
        ];

        $txn->fill($values);

        return $txn;
    }

    protected function calculateMerchantFees(Entity $transaction)
    {
        $source = $transaction->source;

        return (new Pricing\Fee)->calculateMerchantFees($source);
    }

    public function updateBalances(Transaction\Entity $txn, $updateNodalBalance = true, $oldBalanceCheck = false)
    {
        $negativeLimit = (new Balance\Core)->getNegativeLimit($txn);

        $txn = $this->updateMerchantBalance($txn, $negativeLimit, $oldBalanceCheck);

        // if ($updateNodalBalance === true)
        // {
        //     $txn = $this->updateNodalBalance($txn);
        // }
        // else
        // {
        //     $nodalBalance = $this->repo->balance->getNodalBalance($txn->getChannel());
        //
        //     $txn->setEscrowBalance($nodalBalance->getBalance());
        // }

        return $txn;
    }

    public function updateMerchantBalance(Transaction\Entity $txn, int $negativeLimit = 0, $oldBalanceCheck = false)
    {
        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_BEFORE_MERCHANT_BALANCE,
            [
                'merchant_id'       => $txn->getMerchantId(),
                'transaction_id'    => $txn->getId(),
                'payment_id'        => $txn->getEntityId(),
                'method_name'       => 'updateMerchantBalance',
            ]);

        $startTime = microtime(true);

        $merchantBalance = $this->getBalanceLockForUpdate($txn->getMerchantId());

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_AFTER_MERCHANT_BALANCE,
            [   'merchant_id'   => $txn->getMerchantId(),
                'transaction_id'=> $txn->getId(),
                'payment_id'    => $txn->getEntityId(),
                'method_name'   => 'updateMerchantBalance',
                'time_taken'    => (microtime(true) - $startTime) * 1000
            ]);

        $txn->accountBalance()->associate($merchantBalance);

        $oldBalance = $merchantBalance->getBalance();

        $merchantBalance->updateBalance($txn, $negativeLimit);

        $newBalance = $merchantBalance->getBalance();

        $this->trace->info(TraceCode::MERCHANT_BALANCE_DATA,
            [
                'transaction_id'    => $txn->getId(),
                'payment_id'        =>$txn->getEntityId(),
                'new_balance'       => $newBalance,
                'old_balance'       => $oldBalance,
                'method'            => 'updateMerchantBalance',
            ]);

        //
        // For Route transfer processing, we are observing dirty reads on the balance entity for a few
        // merchants, especially at high traffic. To ensure that writes don't happen in case of dirty
        // reads, we are modifying the flow to include a balance check in the MySQL query. The modified flow
        // is only being applied to transfers, all other transactions will continue on the existing flow.
        //
        if ($oldBalanceCheck === true)
        {
            $rowsUpdated = $this->repo->balance->updateBalanceWithOldBalanceCheck($merchantBalance, $oldBalance);

            if ($rowsUpdated === 0)
            {
                throw new Exception\LogicException(
                    Transfer\Constant::BALANCE_UPDATE_WITH_OLD_BALANCE_CHECK_FAILED,
                    null,
                    [
                        'merchant_id'   => $txn->getMerchantId(),
                        'txn_entity_id' => $txn->getEntityId(),
                        'balance_id'    => $merchantBalance->getId(),
                        'old_balance'   => $oldBalance,
                        'new_balance'   => $newBalance,
                    ]
                );
            }

            $this->trace->info(
                TraceCode::BALANCE_UPDATE_WITH_OLD_BALANCE_CHECK_SUCCESS,
                [
                    'merchant_id'   => $txn->getMerchantId(),
                    'txn_entity_id' => $txn->getEntityId(),
                    'balance_id'    => $merchantBalance->getId(),
                    'old_balance'   => $oldBalance,
                    'new_balance'   => $newBalance,
                    'rows_updated'  => $rowsUpdated,
                ]
            );
        }
        else
        {
            $this->repo->balance->updateBalance($merchantBalance);
        }

        $checkNegativeLimit = $oldBalance >= $newBalance;

        $txn->setBalance($merchantBalance->getBalance(), $negativeLimit, $checkNegativeLimit);

        (new Balance\Core)->postProcessingForNegativeBalance($oldBalance, Balance\Entity::BALANCE, $txn->getType(), $merchantBalance);

        return $txn;
    }

    // public function updateNodalBalance(Transaction\Entity $txn)
    // {
    //     $channel = $txn->getChannel();

    //     $nodalBalance = $this->getNodalBalanceLockForUpdate($channel);

    //     $nodalBalance->updateBalance($txn);
    //     $this->repo->balance->updateBalance($nodalBalance);

    //     $txn->setEscrowBalance($nodalBalance->getBalance());

    //     return $txn;
    // }

    public function updateAmountCredits(Transaction\Entity $txn, Base\PublicEntity $entity)
    {
        //
        // Few asserts:
        // 1. This flow should be called only for gratis txn.
        // 2. Fee is not calculated in case it was gratis(amount credits flow)
        //    and so we expect it to be set to 0 always.
        // 3. For txn of type other than transfers(where txn.credit = 0) expectation
        //    is that the credit amount is same as txn amount (as fee is 0).
        //
        assertTrue($txn->isGratis() === true);
        assertTrue($txn->getFee() === 0);
        assertTrue(
            (($txn->isTypePayment() === true) and ($txn->getCredit() === $txn->getAmount())) or
            (($txn->isTypeTransfer() === true) and ($txn->getDebit() === $txn->getAmount())));

        $amount = $txn->getAmount();

        $merchantBalance = $this->getBalanceLockForUpdate($txn->getMerchantId());

        $amountCredits = $this->getMerchantCreditsOfType($merchantBalance, Credits\Type::AMOUNT);

        // Removing Assert for now, as there is a race condition. if 2 payments
        // are authorized at the same time where we create txn on auth with. both
        // will try to set amount credits to zero and one will throw below assert
        // as both txn were marked as gratis on authorization
        // assert($amountCredits > 0);

        $merchantId = $txn->getMerchantId();

        $mode = $this->app['rzp.mode'] ?? 'live';

        $result = $this->app->razorx->getTreatment(
            $merchantId, RazorxTreatment::DISABLE_AMOUNT_CREDITS_FOR_GREATER_THAN_TXN_AMOUNT, $mode);

        $this->trace->info(
            TraceCode::AMOUNT_CREDITS_RAZORX_VARIANT,
            [
                'result'        => $result,
                'mode'          => $mode,
                'merchant_id'   => $merchantId,
            ]);

        // This is for safe guarding the GMV loss
        // Product Doc - https://docs.google.com/document/d/1ja5NzsZJWCDOjn5T_TgzcBNkL6Ubtp5YJwXuxeUSzw4/edit
        if (($amountCredits < $amount) and (strtolower($result) === RazorxTreatment::RAZORX_VARIANT_ON)
            and ($txn->isTypePayment() === true))
        {
            // Error code is not added to make the flow in sync with fee credits.
            throw new Exception\LogicException(
                'AmountCredit should be higher or equal to the payment amount',
                null,
                [
                    'transaction_id'            => $txn->getId(),
                    'merchant_id'               => $txn->getMerchantId(),
                    'amount_credits'            => $amountCredits,
                    'transaction_amount'        => $amount,
                ]);
        }
        else if ($amountCredits < $amount)
        {
            // Even if free credits is less than txn amount, we still give full
            // amount as free credits. However, in balance we only go ahead with
            // updating the actual free credits so that it does not go negative.
            $amount = $amountCredits;
        }

        // $nodalBalance = $this->getNodalBalanceLockForUpdate($txn->getChannel());

        // $nodalBalance->subtractAmountCredits($amount);

        $merchantBalance->subtractAmountCredits($amount);

        //create a credit transaction for the same
        $this->createCreditTransaction($amount, $txn, Credits\Type::AMOUNT);

        // Nodal balance needs to be saved because of amount credit update
        // $this->repo->balance->updateBalance($nodalBalance);
    }

    public function updateFeeCredits(Transaction\Entity $txn)
    {
        // While filling the txn fees and amount, we have not used fee credits.
        if (($txn->isFeeCredits() === false) or
            ($txn->getCredits() === 0))
        {
            return;
        }

        $fee = $txn->getFee();

        $merchantBalance = $this->getBalanceLockForUpdate($txn->getMerchantId());

        $merchantId = $merchantBalance->merchant->getId();

        $feeCreditsThreshold = $merchantBalance->merchant->getFeeCreditsThreshold();

        $feeCredits = $this->getMerchantCreditsOfType($merchantBalance, Credits\Type::FEE);

        if ($feeCredits < $fee)
        {
            throw new Exception\LogicException(
                'FeeCredits should be higher or equal to the fee',
                null,
                [
                    'transaction_id'    => $txn->getId(),
                    'merchant_id'       => $merchantId,
                    'fee_credits'       => $feeCredits,
                    'fee'               => $fee,
                ]);
        }

        // $nodalBalance = $this->getNodalBalanceLockForUpdate($txn->getChannel());

        // $nodalBalance->subtractFeeCredits($fee);

        $merchantBalance->subtractFeeCredits($fee);

        //create a credit transaction for the same
        $this->createCreditTransaction($fee, $txn, Credits\Type::FEE);

        // // Nodal balance needs to be saved because of amount credit update
        // $this->repo->balance->updateBalance($nodalBalance);

        if ($feeCreditsThreshold !== null)
        {
            $this->sendFeeCreditAlertIfNeeded($fee, $feeCredits, $feeCreditsThreshold, $merchantBalance->merchant);
        }
    }


    private function sendFeeCreditAlertIfNeeded(int $fee, int $feeCredits, int $feeCreditsThreshold, Merchant\Entity $merchant)
    {
        $alertRatios = [1, 0.75, 0.5, 0.25, 0.1];

        sort($alertRatios);

        foreach ($alertRatios as $alertRatio)
        {
            if (($feeCredits >= ($alertRatio * $feeCreditsThreshold)) and
                (($feeCredits - $fee) < ($alertRatio * $feeCreditsThreshold)))
            {
                $customBranding = isset($merchant) ? (new Merchant\Core())->isOrgCustomBranding($merchant):false;

                $data = [
                    'alert_ratio'     => $alertRatio,
                    'email'           => $merchant->getTransactionReportEmail(),
                    'merchant_id'     => $merchant->getId(),
                    'merchant_dba'    => $merchant->getBillingLabel(),
                    'fee_credits'     => '₹ '.(($feeCredits - $fee)/100),
                    'org_hostname'    => $merchant->org->getPrimaryHostName(),
                    'timestamp'       => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
                    '$customBranding' => $customBranding,
                ];

                if ($customBranding === true)
                {
                    $org = $merchant->org;

                    $data['email_logo'] = $org->getEmailLogo();
                }

                $this->trace->info(TraceCode::FEE_CREDITS_THRESHOLD_ALERT, $data);

                $createAlertMail = new FeeCreditsAlert($data);

                Mail::queue($createAlertMail);

                break;
            }
        }
    }

    public function updateRefundCredits(Transaction\Entity $txn, int $negativeLimit = 0)
    {
        // While filling the txn fees and amount, we have not used fee credits.
        if ((($txn->isTypeRefund() === false) and
            ($txn->isTypeReversal() === false)) or
            ($txn->isRefundCredits() === false))
        {
            return;
        }

        $amount = $txn->getAmount();

        $merchantBalance = $this->getBalanceLockForUpdate($txn->getMerchantId());

        $merchantId = $merchantBalance->merchant->getId();

        $refundCredits = $this->getMerchantCreditsOfType($merchantBalance, Credits\Type::REFUND);

        if (($negativeLimit === 0) and
            ($refundCredits < $amount))
        {
                throw new Exception\LogicException(
                    'Refund Credits should be higher or equal to the refund amount',
                    null,
                    [
                        'transaction_id' => $txn->getId(),
                        'merchant_id'    => $merchantId,
                        'refund_credits' => $refundCredits,
                        'amount'         => $amount,
                    ]);
        }
        else if (($refundCredits - $amount) < $negativeLimit)
        {
            $data['message'] = TraceCode::getMessage(TraceCode::NEGATIVE_BALANCE_BREACHED);

            $data['negative_limit'] = $negativeLimit;

            throw new BadRequestException(ErrorCode::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED, abs($amount),
                $data);
        }
        $this->merchantBalance->subtractRefundCredits($amount, $negativeLimit);

        (new Balance\Core)->postProcessingForNegativeBalance($refundCredits, Balance\Entity::REFUND_CREDITS,
                                                             $txn->getType(), $merchantBalance);

        //create a credit transaction for the same
        $this->createCreditTransaction($amount, $txn, Credits\Type::REFUND);
    }

    protected function getNodalBalanceLockForUpdate($channel)
    {
        // if ($this->nodalBalance !== null)
        // {
        //     return $this->nodalBalance;
        // }

        // $nodalBalance = $this->repo->balance->getNodalBalanceLockForUpdate($channel);

        // $this->nodalBalance = $nodalBalance;

        // return $nodalBalance;
    }

    /**
     * Note: Passing merchantId instead of the merchant entity because
     * the latter will require the calling methods to access the
     * merchant() relationship of the transaction entity – which
     * either needs to be eager-loaded or will be queried at run-time.
     * Eager-loading uses a lot of memory when the number of
     * transactions are huge; and query at run-time will lead to
     * a steep increase in the execution time, and will increase db-load.
     * Since only merchantId is required in this method, we have let
     * gone of using the merchant entity despite it being the better
     * practice so optimise performance.
     *
     * @param string $merchantId
     * @return null
     */
    protected function getBalanceLockForUpdate(string $merchantId)
    {
        if ($this->merchantBalance !== null)
        {
            $this->trace->info(
                TraceCode::PAYMENT_TRANSFER_RETURNING_MERCHANT_BALANCE_ATTRIBUTE,
                [
                    'merchant_id' => $merchantId,
                    'balance'     => $this->merchantBalance->getBalance(),
                ]);

            return $this->merchantBalance;
        }

        $startTime = microtime(true);

        $merchantBalance = Tracer::inSpan(['name' => 'transfer.process.create_transfer_transaction.balance_lock'], function() use ($merchantId)
        {
            return $this->repo->balance->getBalanceLockForUpdate($merchantId);
        });

        $endTime = microtime(true);

        $this->trace->info(
            TraceCode::BALANCE_LOCK_BY_MID_TIME_TAKEN,
            [
                'merchant_id'   => $merchantId,
                'time_taken'    => $endTime - $startTime,
            ]
        );

        $this->merchantBalance = $merchantBalance;

        return $merchantBalance;
    }

    protected function getSettledAtTimestamp(Payment\Entity $payment)
    {
        $capturedAt = $payment->getAttribute(Payment\Entity::CAPTURED_AT);

        $merchant = $payment->merchant;

        $returnTime = null;

        $scheduleTask = (new ScheduleTask\Core)->getMerchantSettlementSchedule(
            $merchant,
            $payment->getMethod(),
            $payment->isInternational());

        // use schedule from pivot schedule_task if defined and use next run from there
        if ($scheduleTask !== null)
        {
            $schedule = $scheduleTask->schedule;

            $nextRunAt = $scheduleTask->getNextRunAt();

            $returnTime = ScheduleLibrary::getNextApplicableTime(
                                                        $capturedAt,
                                                        $schedule,
                                                        $nextRunAt);
        }
        else
        {
            // Unused as there wont be any merchant without schedule.
            // TODO: fix test cases as this condition will run while running test. remove condition once tests fixed
            $addDays = $payment->isInternational() === true ?
                       Merchant\Entity::INTERNATIONAL_SETTLEMENT_SCHEDULE_DEFAULT_DELAY :
                       Merchant\Entity::DOMESTIC_SETTLEMENT_SCHEDULE_DEFAULT_DELAY;

            //
            // Not handling 24x7 settlements for daily schedules.
            // And since this else block is only for 3 days schedule,
            // we will not be handling it here as of now.
            //
            $returnTime = $this->calculateSettledAtTimestamp($capturedAt, $addDays);
        }

        return $returnTime;
    }

    protected function getSettledAtTimestampForRefund(Refund\Entity $refund)
    {
        $payment = $refund->payment;

        if ($payment->hasBeenCaptured())
        {
            $paymentTxn = $payment->transaction;

            $nowTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

            return ($paymentTxn->isSettled() ? $nowTimestamp : $paymentTxn->getSettledAt());
        }

        return null;
    }

    public function calculateSettledAtTimestamp($capturedAtTimestamp, $addDays, $ignoreBankHolidays = false)
    {
        $capturedAt = Carbon::createFromTimestamp($capturedAtTimestamp, Timezone::IST);

        $returnDay = Holidays::getNthWorkingDayFrom($capturedAt, $addDays, $ignoreBankHolidays);

        return $returnDay->getTimestamp();
    }

    public function updateCredits(Transaction\Entity $txn, Base\PublicEntity $entity, int $negativeLimit = 0)
    {
        if ($txn->isGratis() === true)
        {
            $this->updateAmountCredits($txn, $entity);
        }
        else if ($txn->isFeeCredits() === true)
        {
            $this->updateFeeCredits($txn);
        }
        else if ($txn->isRefundCredits() === true)
        {
            $this->updateRefundCredits($txn, $negativeLimit);
        }
    }

    protected function getMerchantCreditsOfType(Merchant\Balance\Entity $merchantBalance, string $type)
    {
        $merchant = $merchantBalance->merchant;

        $feature = Feature\Constants::OLD_CREDITS_FLOW;

        if ($merchant->isFeatureEnabled($feature) === true)
        {
            if ($type === Credits\Type::FEE)
            {
                $credits = $merchantBalance->getFeeCredits();
            }
            else if ($type === Credits\Type::REFUND)
            {
                $credits = $merchantBalance->getRefundCredits();
            }
            else
            {
                $credits = $merchantBalance->getAmountCredits();
            }
        }
        else
        {
            $merchantId = $merchant->getId();

            $credits = $this->repo->credits->getMerchantCreditsOfType($merchantId, $type);
        }

        return $credits;
    }

    protected function getMerchantCredits(Merchant\Entity $merchant): array
    {
        $feature = Feature\Constants::OLD_CREDITS_FLOW;

        if ($merchant->isFeatureEnabled($feature) === true)
        {
            $merchantBalance = $this->repo->balance->getMerchantBalance($merchant);

            $amountCredits = $merchantBalance->getAmountCredits();

            $feeCredits = $merchantBalance->getFeeCredits();
        }
        else
        {
            $credits = $this->repo->credits->getTypeAggregatedNonRefundMerchantCredits($merchant);

            $amountCredits =  $credits[Credits\Type::AMOUNT] ?? 0;

            $feeCredits = $credits[Credits\Type::FEE] ?? 0;
        }

        return [$amountCredits, $feeCredits];
    }

    protected function createCreditTransaction(int $amount, Entity $txn, string $creditType)
    {
        try
        {
            (new Credits\Transaction\Core)->createCreditTransaction($amount, $txn, $creditType);
        }
        catch (\Throwable $e)
        {
            $data = [
                'credit_amount'  => $amount,
                'transaction_id' => $txn->getId(),
                'credit_type'    => $creditType,
            ];

            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::CREDITS_TRANSACTION_FAILED,
                $data);
        }
    }

    /*
     *  We faced the deadlocks issue due order of DB locks
     *  Always take the lock on credits and then in merchant balance
     */
    protected function calculateTransferFees(Entity $transaction)
    {
        $merchant = $transaction->merchant;

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_BEFORE_MERCHANT_BALANCE,
            [
                'merchant_id'       => $transaction->getMerchantId(),
                'transaction_id'    => $transaction->getId(),
                'payment_id'        => $transaction->getEntityId(),
                'method_name'       => "calculateTransferFees"
            ]);

        $startTime = microtime(true);

        // taking lock on credits
        list($amountCredits, $feeCredits) = $this->getMerchantCredits($merchant);

        // taking lock on merchant balance
        $this->getBalanceLockForUpdate($merchant->getId());

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_AFTER_MERCHANT_BALANCE,
            [   'merchant_id'   => $transaction->getMerchantId(),
                'transaction_id'=> $transaction->getId(),
                'payment_id'    => $transaction->getEntityId(),
                'method_name'       => "calculateTransferFees",
                'time_taken'    => (microtime(true) - $startTime) * 1000
            ]);

        list($fee, $tax, $feesSplit) = Tracer::inSpan(['name' => 'transfer.process.create_transfer_transaction.calculate_fees'], function() use ($transaction)
        {
            return $this->calculateMerchantFees($transaction);
        });

        $transaction->setFeeModel($merchant->getFeeModel());

        $this->trace->info(
            TraceCode::TRANSFER_FEE_DEDUCTION,
            [
                'merchant_id'       => $merchant->getId(),
                'amount_credits'    => $amountCredits,
                'fee_credits'       => $feeCredits,
                'fee'               => $fee,
            ]
        );

        switch (true)
        {
            case ($amountCredits > 0):
                return $this->calculateFeeForAmountCredit($transaction);

            case ($feeCredits >= $fee):
                return $this->calculateFeeForFeeCredit($transaction);

            default:
                $amount    = $transaction->getAmount();
                $isPrepaid = $merchant->isPrepaid();

                // Add fee to debit only for prepaid merchants
                $debit  = ($isPrepaid === true) ? abs($amount + $fee) : $amount;

                $transaction->setCreditType(Transaction\CreditType::DEFAULT);

                return [$debit, $fee, $tax, $feesSplit];
        }
    }

    /**
     * Compute and return the settled_at timestamp for a transfer
     * reversal transaction
     *
     * @param Reversal\Entity $reversal
     *
     * @return int
     * @throws Exception\LogicException
     */
    protected function getTransferReversalSettledAtTimestamp(Reversal\Entity $reversal): int
    {
        $scheduleTaskCore = new ScheduleTask\Core;

        $nextSettlementTime = Carbon::tomorrow(Timezone::IST)->getTimestamp();

        //
        // `source` will always be `Transfer/Entity` since this flow
        // is invoked only on Transfer Reversal creation
        //
        $transfer = $reversal->entity;

        if (($transfer->isPaymentTransfer() === true) or
            ($transfer->isOrderTransfer() === true))
        {
            //
            // For payment transfers, the transfer txn's `settled_at` is
            // already set to at-least the payment's settlement timestamp,
            // (refer `createFromTransfer()` above) and is hence delayed to
            // after the merchant settlement schedule
            //
            // Therefore: Delay the reversal txn to the max of
            // - Transfer txn settled_at OR
            // - Next available settlement slot as per schedule
            //
            $transferSettledAt = $transfer->transaction->getSettledAt();

            return max($transferSettledAt, $nextSettlementTime);
        }
        else
        {
            if ($transfer->isDirectTransfer() === true)
            {
                //
                // For direct transfers, there's no source payment to look at.
                // Hence, we look at the transfer created_at timestamp and add
                // the merchants settlement schedule to it (calling this -
                // `transferDelayTime`)
                //
                // We then set the reversal txn settled_at to the max of either
                // - transferDelayTime OR
                // - Next available settlement slot as per schedule
                //
                $transferCreatedAt = $transfer->getCreatedAt();

                $transferDelayTime = $scheduleTaskCore->getNextApplicableTimeForMerchant(
                    $transferCreatedAt,
                    $reversal->merchant);

                return max($transferDelayTime, $nextSettlementTime);
            }
            else
            {
                // Invalid case
                throw new Exception\LogicException('Invalid transfer type', null, ['transfer' => $transfer]);
            }
        }
    }

    /**
     * Dispatches webhook, sms and/or email for newly created transaction.
     * This is a safe method i.e. it is not expected to throw any exceptions.
     * Notifier and webhook dispatcher used here suppress and log exceptions if any.
     *
     * @param Entity $txn
     */
    public function dispatchEventForTransactionCreated(Entity $txn)
    {
        (new Notifier($txn))->notify();

        $this->app->events->dispatch('api.transaction.created', $txn);
    }

    public function dispatchEventForTransactionCreatedWithoutEmailOrSmsNotification(Entity $txn)
    {
        $this->app->events->dispatch('api.transaction.created', $txn);
    }

    public function dispatchEventForTransactionUpdated(Entity $txn)
    {
        $this->app->events->dispatch('api.transaction.updated', $txn);
    }

    public function saveFeeDetails(Transaction\Entity $txn, PublicCollection $feesSplit)
    {
        if ($feesSplit->isEmpty() === true)
        {
            return;
        }

        $this->trace->info(
            TraceCode::CREATING_FEES_BREAKUP,
            [
                'transaction_id'    => $txn->getId(),
                'source_id'         => $txn->getEntityId(),
                'fee_split'         => $feesSplit->toArrayPublic(),
            ]);

        $deadlockRetryAttempts = 2;

        try
        {
            $this->repo->transaction(function() use ($txn, $feesSplit)
            {
                foreach ($feesSplit as $feeSplit)
                {
                    $feeSplit->transaction()->associate($txn);

                    $this->repo->saveOrFail($feeSplit);

                    $this->trace->info(
                        TraceCode::FEES_BREAKUP_DETAILS,
                        [
                            'id'             => $feeSplit->getId(),
                            'source_id'      => $txn->source->getPublicId(),
                            'transaction_id' => $txn->getId(),
                        ]);
                }

                $this->trace->info(
                    TraceCode::FEES_BREAKUP_CREATED,
                    [
                        'transaction_id' => $txn->getId(),
                        'source_id'      => $txn->source->getPublicId(),
                    ]);
            }, $deadlockRetryAttempts);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex, Trace::CRITICAL,
                TraceCode::FEES_BREAKUP_CREATION_FAILED,
                [
                    'transaction_id' => $txn->getId(),
                    'source_id'      => $txn->getEntityId()
                ]);

            throw new Exception\LogicException(
                'Error while recording fee breakup',
                ErrorCode::SERVER_ERROR_FEE_BREAKUP_CREATION_FAILED,
                [
                    'transaction_id'    => $txn->getId(),
                    'payment_id'        => $txn->getEntityId(),
                    'fee_split'         => $feesSplit->toArrayPublic(),
                ]);
        }
    }

    //Async Update Merchant Balance
    public function asyncUpdateMerchantBalance($payment, $txn)
    {
        $processor = $this->getFactory($payment);

        $processor->setTransaction($txn);

        if ($payment->merchant->isFeatureEnabled(Feature\Constants::ASYNC_TXN_FILL_DETAILS) === true)
        {
            $processor->setCreditDebitDetails($processor);

            $processor->fillSettledAtInfo();
        }
        else
        {
            $credits = $this->repo->credits->getTypeAggregatedMerchantCreditsForPayment($txn->merchant);
        }

        $negativeLimit = (new Balance\Core)->getNegativeLimit($txn);

        $startTime = microtime(true);

        $processor->setMerchantBalanceLockForUpdate();

        $processor->updateCredits($negativeLimit);

        $processor->updateBalances($negativeLimit);

        $this->trace->info(TraceCode::MERCHANT_BALANCE_UPDATE_TIME_TAKEN,
            [
                'merchant_id'           => $txn->getMerchantId(),
                'payment_id'            => $txn->getEntityId(),
                'txn_type'              => $txn->getType(),
                'async_update'          => true,
                'balance_update_time'  => (microtime(true) - $startTime) * 1000
            ]
        );

        $txn->setBalance(null, 0, true);

        $txn->setBalanceUpdated(true);

        $this->repo->saveOrFail($txn);
    }

    /**
     * It'll dispatch the job to update settlement bucket for merchant
     * This will also suppress the any error occurred at this stage
     * if settled at is null then it wont dispatch the job
     *
     * @param Entity $txn
     * @throws \Throwable
     */
    public function dispatchForSettlementBucketing(Entity $txn)
    {

        // in case the transaction is eligible for settlement then
        // settled_at will have some number else it will be null
        $this->trace->debug(TraceCode::SETTLEMENT_TXN_BUCKET_LOG, [
            'transaction_id' => $txn->getId(),
            'credit'         => $txn->getCredit(),
            'debit'          => $txn->getDebit(),
            'settled_at'     => $txn->getSettledAt(),
            'message'        => 'txn pushed by source for bucketing or recording'
        ]);

        if ($txn->getSettledAt() === null)
        {
            return;
        }

        try
        {
            //
            // if not sent settledAt will be null in job as this entire thing is in a transaction
            //
            Bucket::dispatch($this->mode, $txn->getId());
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FAILED_TO_ENQUEUE_MERCHANT_FOR_SETTLEMENT
            );
        }
    }

    /**
     * it will update the on_hold status of the transaction with respect to holdFlag
     * @param array $transactionIds
     * @param bool $holdFlag
     * @param string $reason
     * @return array
     */
    public function toggleTransactionOnHold(array $transactionIds, bool $holdFlag, $reason = null)
    {
        $failedTransactionUpdate = [];

        $mapForSettlementService = [];

        $txnMapForSettlementService = [];

        foreach($transactionIds as $transactionId)
        {
            try
            {
                $txn = $this->repo->transaction(function() use ($transactionId, $holdFlag)
                    {
                        $txn = $this->repo->transaction->lockForUpdate($transactionId);

                        if($txn->isSettled() === true)
                        {
                            throw new Exception\LogicException('settled transaction can not be put on hold');
                        }

                        $txn->setOnHold($holdFlag);

                        $this->repo->saveOrFail($txn);

                        return $txn;
                });

                $bucketCore = new Settlement\Bucket\Core;

                if (isset($mapForSettlementService[$txn->getMerchantId()]) === false)
                {
                    $balance = $txn->accountBalance;

                    if($bucketCore->shouldProcessViaNewService($txn->getMerchantId(), $balance) === true)
                    {
                        $mapForSettlementService[$txn->getMerchantId()] = true;
                    }
                    else
                    {
                        $mapForSettlementService[$txn->getMerchantId()] = false;
                    }
                }

                if($mapForSettlementService[$txn->getMerchantId()] === true)
                {
                    $txnMapForSettlementService[] = $transactionId;
                }
            }
            catch(\Throwable $e)
            {
                $failedTransactionUpdate[] = $transactionId;

                $this->trace->traceException(
                    $e,
                    Logger::ERROR,
                    TraceCode::TOGGLE_TRANSACTION_UPDATE_FAILED,
                    [
                        'failed_transaction_id' => $transactionId,
                    ]);
            }
        }

        if(sizeof($txnMapForSettlementService) > 0)
        {
            try
            {
                if ($holdFlag === true)
                {
                    app('settlements_dashboard')->transactionHold([
                        "ids" => $txnMapForSettlementService,
                        "reason" => $reason,
                    ]);
                }
                else
                {
                    app('settlements_dashboard')->transactionRelease([
                        "ids" => $txnMapForSettlementService,
                    ]);
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::SETTLEMENT_SERVICE_CALL_FOR_TXN_ON_HOLD_CLEAR_FAILED,
                    [
                        'transaction_ids' => $txnMapForSettlementService,
                        'reason' => $reason,
                        'toggle_flag' => $holdFlag,
                    ]);

                $operation = 'Transactions on hold toggle failed to update in new settlement service';

                (new SlackNotification)->send(
                    $operation,
                    $txnMapForSettlementService,
                    $e,
                    1,
                    'settlement_alerts');
            }
        }

        return $failedTransactionUpdate ;
    }

    public function updatePostedDate(Base\Entity $source, int $postedDate)
    {
        $processor = $this->getFactory($source);

        $processor->setTransaction($source->transaction);

        $processor->updatePostedDate($postedDate);

        $this->repo->saveOrFail($source->transaction);
    }
}
