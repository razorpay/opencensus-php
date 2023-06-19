<?php

namespace RZP\Models\Reversal;

use Razorpay\Trace\Logger;
use RZP\Exception;
use RZP\Constants;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Transfer;
use RZP\Models\Reversal;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Jobs\LedgerStatus;
use RZP\Models\Adjustment;
use RZP\Jobs\Transactions;
use RZP\Models\Transaction;
use RZP\Models\Payment\Refund;
use RZP\Constants\Entity as E;
use RZP\Services\PayoutService;
use RZP\Models\Merchant\Credits;
use RZP\Models\Merchant\Balance;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Ondemand;
use RZP\Models\Settlement\OndemandPayout;
use RZP\Models\Ledger\RefundJournalEvents;
use RZP\Exception\GatewayTimeoutException;
use Neves\Events\TransactionalClosureEvent;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\BankingAccountStatement\Channel;
use RZP\Models\Adjustment\Core as AdjustmentCore;
use RZP\Models\Ledger\RouteReversalJournalEvents;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Jobs\Ledger\CreateLedgerJournal as LedgerEntryJob;
use RZP\Models\FundAccount\Validation as FundAccountValidation;
use RZP\Models\Transaction\Processor\Ledger\Payout as PayoutLedger;
use RZP\Models\Transaction\Processor\Ledger\FundAccountValidation as FavLedger;
use RZP\Models\Ledger\ReverseShadow\Reversals\Core as ReverseShadowReversalsCore;

class Core extends Base\Core
{
    /**
     * @var int
     */
    protected $payoutServiceMutexTTLForReversal = 120;

    // Payout Service Mutex Keys
    const REVERSAL_CREATION_PAYOUT_SERVICE = 'reversal_creation_payout_service_';

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Create a reversal for a Marketplace refund,
     * and a transaction that updates the Marketplace balance
     *
     * @param Transfer\Entity $transfer
     * @param Merchant\Entity $merchant
     * @param Refund\Entity $refund
     * @param array $input
     * @param Merchant\Entity $initiator Route Merchant / Linked Account initiating the reversal
     *
     * @return array
     * @throws Exception\LogicException
     */
    public function createForMarketplaceRefund(
        Transfer\Entity $transfer,
        Merchant\Entity $merchant,
        Refund\Entity $refund,
        array $input,
        Merchant\Entity $initiator = null)
    {
        $this->trace->info(
            TraceCode::TRANSFER_REVERSAL_REQUEST,
            [
                'transfer_id' => $transfer->getId(),
                'input'       => $input
            ]);

        $transfer->reverseAmount($input[Entity::AMOUNT]);

        $this->repo->saveOrFail($transfer);

        $input[Entity::CURRENCY] = $transfer->getCurrency();

        $reversal = $this->create($input);

        $reversal->merchant()->associate($merchant);

        $reversal->entity()->associate($transfer);

        $reversal->initiator()->associate($initiator);

        $txnCore = (new Transaction\Core);

        $txn = $txnCore->createFromTransferReversal($reversal);

        $this->repo->saveOrFail($txn);

        $reversal->transaction()->associate($txn);

        $this->repo->saveOrFail($reversal);

        $refund->reversal()->associate($reversal);

        $this->repo->saveOrFail($refund);

        $this->traceSuccess(TraceCode::TRANSFER_REVERSAL_SUCCESS, $reversal);

        return array($reversal, $refund);
    }

    /**
     * Create and process a reversal on a transfer
     * Also process refund to the customer if cutomer_refund flag is present in input
     *
     * @param Transfer\Entity $transfer
     * @param array $input
     * @param Merchant\Entity $merchant
     * @param Merchant\Entity|null $initiator Route Merchant / Linked Account initiating the reversal
     *
     * @return Entity
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function reverseForTransferAndCustomerRefund(
        Transfer\Entity $transfer,
        array $input,
        Merchant\Entity $merchant,
        Merchant\Entity $initiator = null): Entity
    {
        // Reversals not handled yet for customer wallet - transfer refunds
        // @todo: Change flow to create reversals for both customer/account transfers
        if ($transfer->getToType() !== E::MERCHANT)
        {
            throw new Exception\LogicException(
                'Reversal attempted on invalid transfer to_type - ' . $transfer->getToType()
            );
        }

        $initiator = $initiator ?? $merchant;

        (new Validator)->validateInitiatorForReversal($transfer, $initiator);

        return $this->mutex->acquireAndRelease(
            $transfer->getId(),
            function() use ($transfer, $input, $merchant, $initiator)
            {
                $this->repo->reload($transfer);

                (new Validator)->validateReversalAmount($transfer, $input);

                $paymentProcessor = (new Payment\Processor\Processor($merchant));

                $result = $this->repo->transaction(function () use ($paymentProcessor, $transfer, $input, $merchant, $initiator)
                {
                    $result = $paymentProcessor->refundPaymentAndReverseTransfer($transfer, $input, $initiator);

                    // result has reversal and refund entity in indexes 0 and 1 respectively
                    $reversal = $result[0] ?? null;

                    $this->traceSuccess(TraceCode::DISPUTE_TRANSFER_SUCCESS, $reversal);

                    $this->customerRefundIfApplicable($transfer, $input, $reversal);

                    $sourcePayment = null;

                    if ($transfer->getSourceType() === E::PAYMENT)
                    {
                        $sourcePayment = $transfer->source;
                    }
                    else if ($transfer->getSourceType() === E::ORDER)
                    {
                        $sourceOrderId = $transfer->getSourceId();

                        $sourcePayment = $this->repo->payment->getCapturedPaymentForOrder($sourceOrderId);

                        // Doing findOrFail explicitly to identify archived payment case and handle save accordingly
                        // Else save would not happen if entity is fetched from TiDB
                        $sourcePayment = $this->repo->payment->findOrFail($sourcePayment->getId());
                    }

                    if ($reversal !== null && $sourcePayment !== null && $sourcePayment->isExternal())
                    {
                        $this->repo->saveOrFail($sourcePayment);
                    }

                    (new Transfer\Metric)->pushReversalSuccessMetrics();

                    return $result;
                });

                // Dispatch refunds to scrooge
                try
                {
                    $refund = $result[1] ?? null;

                    $paymentProcessor->callRefundFunctionOnScrooge($refund);
                }
                catch (\Throwable $e)
                {
                    // Ignoring exception to prevent flow breakage.
                    // We have necessary replay measures in place for misses
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::REFUND_QUEUE_SCROOGE_DISPATCH_FAILED
                    );
                }

                $reversal = $result[0] ?? null;
                (new Reversal\Core())->createLedgerEntriesForRouteReversal($merchant, $reversal, $refund);

                // Return reversal entity
                return $reversal;
            });
    }

    /**
     * Create and process a reversal on a transfer initiated by a Linked Account
     * Also process refund to the customer if customer_refund flag is present in input
     *
     * @param Transfer\Entity $transfer
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function linkedAccountReverseForTransfer(
        Transfer\Entity $transfer,
        array $input,
        Merchant\Entity $merchant): Entity
    {
        if (($transfer->getToId() !== $merchant->getId()) or
            ($transfer->getToType() !== E::MERCHANT) or
            (($transfer->getSourceType() !== E::PAYMENT) and ($transfer->getSourceType() !== E::ORDER)) or
            ($transfer->getMerchantId() !== $merchant->parent->getId()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_FOR_LA_REVERSAL_INVALID,
                null,
                [
                    'transfer_id' => $transfer->getId(),
                    'input'       => $input
                ]);
        }

        return $this->reverseForTransferAndCustomerRefund($transfer, $input, $merchant->parent, $merchant);
    }

    public function createTransactionFromPayoutReversalForHighTpsMerchants(Entity $reversal): Entity
    {
        if ($reversal->hasTransaction() === true)
        {
            throw new Exception\LogicException(
                'Transaction has already been created for the reversal!',
                ErrorCode::SERVER_ERROR_REVERSAL_TXN_ALREADY_CREATED,
                [
                    'reversal_id'       => $reversal->getId(),
                    'transaction_id'    => $reversal->getTransactionId(),
                    'transaction_type'  => $reversal->getTransactionType(),
                ]);
        }

        $txnCore = (new Transaction\Core);

        $txn = $txnCore->createFromPayoutReversal($reversal);

        $this->repo->saveOrFail($txn);

        return $reversal;
    }

    public function createTransactionFromPayoutReversal(Entity $reversal): Entity
    {
        if ($reversal->hasTransaction() === true)
        {
            throw new Exception\LogicException(
                'Transaction has already been created for the reversal!',
                ErrorCode::SERVER_ERROR_REVERSAL_TXN_ALREADY_CREATED,
                [
                    'reversal_id'       => $reversal->getId(),
                    'transaction_id'    => $reversal->getTransactionId(),
                    'transaction_type'  => $reversal->getTransactionType(),
                ]);
        }

        $txnCore = (new Transaction\Core);

        $reversal = $this->repo->transaction(function() use ($reversal, $txnCore)
        {
            $txn = $txnCore->createFromPayoutReversal($reversal);

            $this->repo->saveOrFail($txn);

            $this->repo->saveOrFail($reversal);

            return $reversal;
        });

        return $reversal;
    }

    /**
     * Create a full reversal for a payout
     *
     * @param Payout\Entity $payout
     *
     * @return Entity
     */
    public function reverseForPayout(Payout\Entity $payout): Entity
    {
        if ($payout->isCustomerPayout() === true)
        {
            $reversal = $this->reverseCustomerPayout($payout);
        }
        else
        {
            $reversal = $this->reverseMerchantPayout($payout);
        }

        $this->trace->info(
            TraceCode::PAYOUT_REVERSAL_CREATED,
            [
                'payout_id'   => $payout->getId(),
                'reversal_id' => $reversal->getId(),
            ]);

        return $reversal;
    }

    public function reverseForPayoutForHighTpsMerchants(Payout\Entity $payout): Entity
    {
        if ($payout->isCustomerPayout() === true)
        {
            $reversal = $this->reverseCustomerPayout($payout);
        }
        else
        {
            $reversal = $this->reverseMerchantPayoutForHighTps($payout);
        }

        $this->trace->info(
            TraceCode::PAYOUT_REVERSAL_CREATED_HIGH_TPS,
            [
                'payout_id'   => $payout->getId(),
                'reversal_id' => $reversal->getId(),
            ]);

        return $reversal;
    }

    /**
     * Create a full reversal for a fund account validation
     *
     * @param FundAccountValidation\Entity $fav
     */
    public function reverseForFundAccountValidation(FundAccountValidation\Entity $fav)
    {
        if ($fav->getFees() === 0)
        {
            // we still want to send a request to ledger even though a reversal was not created
            // This is to make sure that other chart of accounts get balanced
            if (FundAccountValidation\Core::shouldFavGoThroughLedgerReverseShadowFlow($fav) === true)
            {
                try
                {
                    $response = (new Ledger\FundAccountValidation())->processValidationAndCreateJournalEntry($fav);
                }
                catch (\Throwable $e)
                {
                    // If an exception is caught here, we ignore it.
                    // TODO: set an alert for exceptions caught in ledger calls
                    // If that exception is found to be a part of this reversal flow, we will make sure that we
                    // create an entry in ledger asynchronously/manually later.
                    $this->trace->traceException(
                        $e,
                        Logger::ERROR,
                        TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_ERROR_IN_CREDIT_FLOW,
                        [
                            'fav_id' => $fav->getId(),
                        ]
                    );
                }

                // No transaction created in API here, we don't do it for FAVs with 0 fees getting marked as failed
                // As no reversal entity was created.
            }

            return;
        }

        $reversalInput = [
            Entity::AMOUNT   => 0,
            Entity::FEE      => $fav->getFees(),
            Entity::TAX      => $fav->getTax(),
            Entity::CURRENCY => $fav->getCurrency(),
        ];

        $reversal = $this->create($reversalInput);

        $reversal->merchant()->associate($fav->merchant);
        $reversal->entity()->associate($fav);

        $reversal->balance()->associate($fav->balance);

        if (FundAccountValidation\Core::shouldFavGoThroughLedgerReverseShadowFlow($fav) === true)
        {
            $this->repo->saveOrFail($reversal);

            try
            {
                $response = (new Ledger\FundAccountValidation())->processValidationAndCreateJournalEntry($fav);
            }
            catch (\Throwable $e)
            {
                // If an exception is caught here, we ignore it.
                // TODO: set an alert for exceptions caught in ledger calls
                // If that exception is found to be a part of this reversal flow, we will make sure that we
                // create an entry in ledger asynchronously/manually later.
                $this->trace->traceException(
                    $e,
                    Logger::ERROR,
                    TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_ERROR_IN_CREDIT_FLOW,
                    [
                        'fav_id' => $fav->getId(),
                    ]
                );
            }

            $this->trace->info(
                TraceCode::FUND_ACCOUNT_VALIDATION_REVERSAL_CREATED,
                [
                    'fav_id'      => $fav->getId(),
                    'reversal_id' => $reversal->getId(),
                ]);

            return;
        }

        $reversal = $this->repo->transaction(function() use ($reversal)
        {
            $txnCore = new Transaction\Core;

            list($txn, $feesSplit) = $txnCore->createFromReversal($reversal);

            $this->repo->saveOrFail($txn);

            $this->repo->saveOrFail($reversal);

            $txnCore->saveFeeDetails($txn, $feesSplit);

            return $reversal;
        });

        $this->trace->info(
            TraceCode::FUND_ACCOUNT_VALIDATION_REVERSAL_CREATED,
            [
                'fav_id' => $fav->getId(),
                'reversal_id' => $reversal->getId(),
            ]);

        return;
    }

    /**
     * Create a full reversal for a refund
     *
     * @param Refund\Entity $refund
     * @param bool $feeOnlyReversal
     *
     * @return Entity
     */
    public function reverseForRefund(Payment\Refund\Entity $refund, bool $feeOnlyReversal, bool $isReversalForVirtualRefund = false): Entity
    {
        $reversalInput = [
            Entity::AMOUNT   => ($feeOnlyReversal === false) ? $refund->getBaseAmount() : 0,
            Entity::FEE      => $refund->getFees(),
            Entity::TAX      => $refund->getTax(),
            Entity::CURRENCY => Currency::INR,
        ];

        $reversal = $this->create($reversalInput);

        $reversal->setChannel($refund->getChannel());

        $reversal->merchant()->associate($refund->merchant);
        $reversal->entity()->associate($refund);

        // Todo: remove null balance check after backfilling is done
        $reversal->balance()->associate($refund->balance ?? $refund->merchant->primaryBalance);

        $txnCore = new Transaction\Core;

        if ($refund->merchant->isFeatureEnabled(FeatureConstants::PG_LEDGER_REVERSE_SHADOW) === true)
        {
            $this->repo->transaction(function () use ($reversal, $refund, $feeOnlyReversal, $isReversalForVirtualRefund) {

                if($isReversalForVirtualRefund === true)
                {
                    $this->stripRefundRelationIfApplicable($reversal);
                }

                $this->repo->saveOrFail($reversal);

                if($isReversalForVirtualRefund === true)
                {
                    $this->associateRefundIfApplicable($reversal, $refund);
                }

//               Note: If merchant has Reverse Shadow flag enabled, create reversal entity and ledger entries only.
//               reversal txn will be created in async via acknowledgement worker
                (new ReverseShadowReversalsCore())->createLedgerEntriesForReversalReverseShadow($reversal, $refund, $refund->payment, $feeOnlyReversal);
            });
        }
        else
        {
            $reversal = $this->repo->transaction(function () use ($reversal, $txnCore, $feeOnlyReversal) {
                list($txn, $feesSplit) = $txnCore->createFromRefundReversal($reversal);

                $this->repo->saveOrFail($txn);

                $this->repo->saveOrFail($reversal);

                $txnCore->saveFeeDetails($txn, $feesSplit);

                $this->createLedgerEntriesForReversals($txn, $reversal, $feeOnlyReversal);

                return $reversal;
            });
        }

        $this->trace->info(
            TraceCode::REFUND_REVERSAL_CREATED,
            [
                'refund_id'   => $refund->getId(),
                'reversal_id' => $reversal->getId(),
                'payment_id'  => $refund->getPaymentId(),
                'balance_id'  => $reversal->balance->getId(),
            ]);

        return $reversal;
    }

    protected function stripRefundRelationIfApplicable(Reversal\Entity $reversal)
    {
        $entity = $reversal->entity;

        if (($entity === null) or
            ($entity->getEntityName() !== E::REFUND))
        {
            return;
        }

        $reversal->entity()->dissociate();

        $reversal->setAttribute(Entity::ENTITY_ID, $entity->getId());

        $reversal->setAttribute(Entity::ENTITY_TYPE, E::REFUND);

        return $entity;
    }

    protected function associateRefundIfApplicable($reversal, $refund = null)
    {
        if ($refund === null)
        {
            return;
        }

        $reversal->entity()->associate($refund);
    }

    public function createReversalTransaction(Entity $reversal, $journalId)
    {
        $txnCore = new Transaction\Core;

        $txn = $this->repo->transaction(function () use ($reversal, $txnCore, $journalId) {

            $txn = $this->repo->transaction->find($journalId);

            if (isset($txn) === true)
            {
                return $txn;
            }

            list($txn, $feesSplit) = $txnCore->createFromRefundReversal($reversal, $journalId);

            $this->repo->saveOrFail($txn);

            $txnCore->saveFeeDetails($txn, $feesSplit);

            $reversal->transaction()->associate($txn);

            $this->repo->saveOrFail($reversal);

            return $txn;
        });

        return $txn;
    }

    private function createLedgerEntriesForReversals(Transaction\Entity $txn, Reversal\Entity $reversal, bool $feeOnlyReversal)
    {
        try
        {
            if ($reversal->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === true)
            {
                list($rule, $moneyParams) = RefundJournalEvents::fetchLedgerRulesAndMoneyParamsForReversal($txn, $reversal->entity, $feeOnlyReversal);
                $transactionMessage = RefundJournalEvents::createTransactionMessageForRefundReversal($reversal, $txn, $moneyParams);
                $transactionMessage[LedgerConstants::ADDITIONAL_PARAMS] = $rule;

                \Event::dispatch(new TransactionalClosureEvent(function () use ($transactionMessage)
                {
                    LedgerEntryJob::dispatchNow($this->mode, $transactionMessage);
                }));
            }
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

    /**
     * Creates a customer reversal and credits merchant fee.
     *
     * @param Payout\Entity $payout
     *
     * @return Entity
     */
    private function reverseCustomerPayout(Payout\Entity $payout): Entity
    {
        // Not taking a mutex lock because we have select for update on customer and merchant balances and
        // Reversals are initiated by internal razorpay FTA recon cron.
        $reversalInput = [
            Entity::AMOUNT   => $payout->getAmount(),
            Entity::CURRENCY => $payout->getCurrency(),
        ];

        $payoutFee = $payout->getFees();

        $reversal = $this->create($reversalInput);

        $reversal->setChannel($payout->getChannel());

        $reversal->merchant()->associate($payout->merchant);

        $reversal->entity()->associate($payout);

        $reversal->customer()->associate($payout->customer);

        $reversal = $this->repo->transaction(function () use ($reversal, $payoutFee)
        {
            // Creates a customer transaction for crediting the amount debited during the payout.
            $customerTxn = (new Customer\Transaction\Core)->createForCustomerCredit($reversal,
                                                                                    $reversal->getAmount(),
                                                                                    $reversal->getCustomerId(),
                                                                                    $reversal->merchant);
            $reversal->transaction()->associate($customerTxn);

            if ($payoutFee > 0)
            {
                // Creating the positive adjustment with source as reversal for the merchant fee charged on payout.
                $this->reverseMerchantFeeForCustomerPayoutReversal($reversal, $payoutFee);
            }

            $this->repo->saveOrFail($reversal);

            return $reversal;
        });

        return $reversal;
    }

    private function reverseMerchantFeeForCustomerPayoutReversal(Entity $reversal, int $payoutFee)
    {
        // For crediting customer payout fee we will create a positive adjustment for the merchant.
        $adjustmentData = [
            Adjustment\Entity::CURRENCY    => $reversal->getCurrency(),
            Adjustment\Entity::AMOUNT      => $payoutFee,
            Adjustment\Entity::DESCRIPTION => 'Credit wallet withdrawal fee amount for payout reversal',
        ];

        // Create merchant adjustment.
        (new AdjustmentCore)->createAdjustmentForSource($adjustmentData, $reversal);
    }

    /**
     * Creates a merchant payout reversal.
     *
     * @param \RZP\Models\Payout\Entity $payout
     *
     * @return Entity
     * @throws Exception\LogicException
     */
    private function reverseMerchantPayout(Payout\Entity $payout): Entity
    {
        // fees and tax recovery for direct type of accounts is
        // handled by fee recovery module. So while creating
        // debit and credit txn, the balance debited and credited
        // will be equal to payout amount excluding fees and tax.
        // Since reversal txn takes amount value from source.
        // i:e reversal in this case. We are modifying reversal amount
        // and making it equal to payout amount.
        // This will also ensure that double credits to the merchant
        // do not happen as we will just return the payout amount
        // while marking payout to reverse.
        if ($payout->balance->isAccountTypeDirect() === true)
        {
            $amount = $payout->getAmount();
        }
        else
        {
            if ($payout->getFeeType() === Transaction\CreditType::REWARD_FEE)
            {
                $amount = $payout->getAmount();
            }
            else
            {
                $amount = $payout->getAmount() + $payout->getFees();
            }
        }

        $reversalInput = [
            Entity::AMOUNT   => $amount,
            Entity::CURRENCY => $payout->getCurrency(),
            Entity::UTR      => ($payout->getReturnUtr() ?? $payout->getUtr()),
        ];

        $reversal = $this->create($reversalInput);

        $reversal->setChannel($payout->getChannel());

        $reversal->merchant()->associate($payout->merchant);

        $reversal->entity()->associate($payout);

        $reversal->balance()->associate($payout->balance);

        if ($this->shouldHandleRewardForReversalsForSource($reversal) === true)
        {
            (new Credits\Transaction\Core)->reverseCreditsForSource(
                $reversal->getEntityId(),
                $reversal->getEntityType(),
                $reversal);
        }

        // This returns true for ledger reverse shadow as well.
        $skipTxn = $this->shouldSkipReversalTransaction($reversal);

        if ($skipTxn === false)
        {
            $reversal = $this->createTransactionFromPayoutReversal($reversal);

            (new Transaction\Core)->dispatchEventForTransactionCreated($reversal->transaction);
        }

        $this->repo->saveOrFail($reversal);

        return $reversal;
    }

    private function reverseMerchantPayoutForHighTps(Payout\Entity $payout): Entity
    {
        $amount = $payout->getAmount() + $payout->getFees();

        $reversalInput = [
            Entity::AMOUNT   => $amount,
            Entity::CURRENCY => $payout->getCurrency(),
            Entity::UTR      => ($payout->getReturnUtr() ?? $payout->getUtr()),
        ];

        $reversal = $this->create($reversalInput);

        $reversal->setChannel($payout->getChannel());

        $reversal->merchant()->associate($payout->merchant);

        $reversal->entity()->associate($payout);

        $reversal->balance()->associate($payout->balance);

        $reversal = $this->createTransactionFromPayoutReversalForHighTpsMerchants($reversal);

        $this->repo->saveOrFail($reversal);

        return $reversal;
    }

    // No DA Handling, as ledger reverse shadow only runs for VA.
    public function createReversalWithoutTransactionForLedgerServiceHandling(Payout\Entity $payout): Entity
    {
        if ($payout->getFeeType() === Transaction\CreditType::REWARD_FEE)
        {
            $amount = $payout->getAmount();
        }
        else
        {
            $amount = $payout->getAmount() + $payout->getFees();
        }

        $reversalInput = [
            Entity::AMOUNT   => $amount,
            Entity::CURRENCY => $payout->getCurrency(),
            Entity::UTR      => ($payout->getReturnUtr() ?? $payout->getUtr()),
        ];

        $reversal = $this->create($reversalInput);

        $reversal->setChannel($payout->getChannel());

        $reversal->merchant()->associate($payout->merchant);

        $reversal->entity()->associate($payout);

        $reversal->balance()->associate($payout->balance);

        // No txns for this reversal!!!!!
        // This reversal is only made as a substitute for payout failed scenarios.
        // This facilitates merchants who have not subscribed to payout failed webhook.
        // Since a payout failing doesn't actually create a transaction, we won't make a transaction here too
        // NOR there will be a ledger entry

        $this->repo->saveOrFail($reversal);

        return $reversal;
    }

    /**
     * This function is only used by payouts.
     * Hence the ledger reverse shadow feature check only uses payout entity based checker
     * If some other entity tries to use this, please make sure to modify the ledger reverse shadow feature check
     * accordingly.
     *
     * @param Entity $reversal
     *
     * @return bool
     */
    protected function shouldHandleRewardForReversalsForSource(Reversal\Entity $reversal)
    {
        // this checks if rewards were used for the payout
        if (($reversal->getEntityType() === E::PAYOUT) and
            ($reversal->entity->getFeeType() === Transaction\CreditType::REWARD_FEE))
        {
            $sourceId = $reversal->getEntityId();

            $sourceType = $reversal->getEntityType();

            // this check if by any flow other flow credits were reversed, then don't
            // reverse credits again
            $creditTxns = (new Credits\Transaction\Core)->getReverseCreditTransactionsForSource(
                                                                            $sourceId,
                                                                            $sourceType);

            if ($creditTxns->count() > 0)
            {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * This function is only used by payouts.
     * Credits can be reversed either via some internal state transition such as queued or
     * can be reversed in case of terminal state such as failed/reversed.
     * EntityType in credit_transaction table is payout for internal state transition cases but if payout is actually
     * failed/reversed then credit EntityType is reversal.
     * So while checking credits are already reversed or not we shall check for reversal entityType.
     *
     * @param Entity $reversal
     *
     * @return bool
     */
    protected function shouldHandleRewardForPayoutReversal(Reversal\Entity $reversal)
    {
        if (($reversal->getEntityType() === E::PAYOUT) and
            ($reversal->entity->getFeeType() === Transaction\CreditType::REWARD_FEE))
        {
            $creditTxns = (new Credits\Transaction\Core)->getReverseCreditTransactionsForSource(
                $reversal->getId(),
                Constants\Entity::REVERSAL);

            if ($creditTxns->count() > 0)
            {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Create reversal for settlement.ondemand entity based on which ondemandPayout got reversed
     *
     * @param Ondemand\Entity $settlementOndemand
     * @param OndemandPayout\Entity $settlementOndemandPayout
     */
    public function partialReversalForSettlementOndemand($settlementOndemand, $settlementOndemandPayout): Entity
    {
        $amount = $settlementOndemandPayout->getAmount();

        $reversalInput = [
            Entity::AMOUNT   => $amount,
            Entity::CURRENCY => $settlementOndemand->getCurrency(),
        ];

        $reversal = $this->create($reversalInput);

        $reversal->merchant()->associate($settlementOndemand->merchant);

        $reversal->entity()->associate($settlementOndemand);

        $txn = (new Transaction\Core)->createFromOndemandPartialReversal($reversal);

        //(new Transaction\Core)->dispatchEventForTransactionCreated($reversal->transaction);

        $this->repo->saveOrFail($txn);

        $this->repo->saveOrFail($reversal);

        $this->updateLedgerEntryToCollectionsForReversal( true,$settlementOndemandPayout,$reversal->getId(),$txn->getId());

        return $reversal;
    }

    public function updateLedgerEntryToCollectionsForReversal(bool $reverse,$settlementOndemandPayout,$reversalId,$transactionId)
    {
        try
        {
            $collectionsService = $this->app['capital_collections'];
            $collectionsService->pushInstantSettlementLedgerUpdateForReversalScenario($reverse,$settlementOndemandPayout,$reversalId,$transactionId);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_PUSH_TO_LEDGER_REVERSAL_FAILURE, [
                'ledger_push_exception'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process Customer Refund if applicable
     *
     * @param Transfer\Entity $transfer
     * @param array $input
     * @param Entity $reversal
     * @throws Exception\BadRequestException
     */
    protected function customerRefundIfApplicable(Transfer\Entity $transfer, array $input, Reversal\Entity $reversal)
    {
        $customerRefund = (bool) ($input[Entity::REFUND_TO_CUSTOMER] ?? false);

        if ($customerRefund === false)
        {
            return;
        }

        unset($input[Entity::REFUND_TO_CUSTOMER]);

        unset($input[Entity::LINKED_ACCOUNT_NOTES]);

        if ($transfer->getSourceType() === E::ORDER)
        {
            $payment = $transfer->source->payments()->where(Payment\Entity::STATUS, Payment\Status::CAPTURED)->first();

            if($payment === null)
            {
                $payment = $transfer->source->payments()->where(Payment\Entity::STATUS, Payment\Status::REFUNDED)->first();
            }

        }
        else
        {
            $payment = $transfer->source;
        }

        $merchant = $payment->merchant;

        if ($merchant->isFeatureEnabled(Feature\Constants::DISABLE_REFUNDS) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REFUND_NOT_ALLOWED);
        }

        if (($merchant->isFeatureEnabled(Feature\Constants::DISABLE_CARD_REFUNDS) === true) and
            ($payment->getMethod() === Payment\Method::CARD))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CARD_REFUND_NOT_ALLOWED);
        }

        $refund = (new Payment\Processor\Processor($merchant))->refundCapturedPayment($payment, $input, null, null, 'off');

        $reversal->customerRefund()->associate($refund);

        $this->repo->saveOrFail($reversal);
    }

    protected function create(array $input) : Entity
    {
        $reversal = (new Entity)->build($input);

        $reversal->generateId();

        return $reversal;
    }

    protected function traceSuccess(string $code, Entity $reversal)
    {
        $traceMessage = [
            'entity_type'       => $reversal->getEntityType(),
            'entity_id'         => $reversal->getEntityId(),
            'reversal_id'       => $reversal->getId(),
            'refund_amount'     => $reversal->getAmount()
        ];

        $this->trace->info($code, $traceMessage);
    }

    /**
     * This function tells if a creating a reversal transaction should be skipped or not
     *
     * IMPORTANT: For now, this function is only called by reverseMerchantPayout() flow
     * , hence checking only for payouts for ledger reverse shadow checks is fine here.
     * This should be corrected in the future if this function is used for other entities.
     *
     * @param Entity $reversal
     * @return bool
     */
    protected function shouldSkipReversalTransaction(Reversal\Entity $reversal): bool
    {
        // shouldSkipReversalTransaction is only called by payouts
        // hence no check on entity type necessary.
        if (Payout\Core::shouldPayoutGoThroughLedgerReverseShadowFlow($reversal->entity) === true)
        {
            return true;
        }

        $balance     = $reversal->balance;

        $type        = optional($balance)->getType();

        $accountType = optional($balance)->getAccountType();

        $channel     = optional($balance)->getChannel();

        //
        // For direct(current) accounts, there are some channels for which we don't create txns
        // when creating reversals, these txns are created while fetching account statement
        // This is different than usual cases because, since the credit/debit is happening at
        // the channel bank, and we use that as the source of truth for transactions. Though the reversal
        // entity may be created when we get the payout status as reversed from FTS. This helps in communicating
        // the same to the merchant as early as possible
        //
        if (($type === Balance\Type::BANKING) and
            ($accountType === Balance\AccountType::DIRECT) and
            (Channel::shouldSkipTransaction($channel) === true))
        {
            return true;
        }

        return false;
    }

    /**
     * Create reversal for payout microservice
     *
     * @param array $input
     * @return mixed
     */
    public function createReversalEntryForPayoutService(array $input)
    {
        $payoutId = $input[Entity::PAYOUT_ID];

        return $this->mutex->acquireAndRelease(
            self::REVERSAL_CREATION_PAYOUT_SERVICE . $payoutId,
            function() use ($input, $payoutId)
            {
                (new Validator)->setStrictFalse()->validateInput(Validator::PAYOUT_SERVICE_REVERSAL_CREATE, $input);

                $reversal = $this->repo->reversal->findReversalForPayout($payoutId);

                if (empty($reversal) === false)
                {
                    $txn = $reversal->transaction;

                    $response =  [
                        Entity::TRANSACTION_ID => $txn->getId(),
                        Entity::FEE            => $txn->getFee(),
                        Entity::TAX            => $txn->getTax(),
                    ];

                    $this->trace->info(TraceCode::PAYOUT_SERVICE_EXISTING_REVERSAL_RESPONSE,
                        ['response' => $response]);

                    return $response;
                }
                else
                {
                    try
                    {
                        $payout = (new Payout\Core)->getAPIModelPayoutFromPayoutService($payoutId);

                        $response = $this->repo->transaction(function() use ($input, $payout)
                        {
                            $reversalInput = [
                                Entity::AMOUNT      => $input[Entity::AMOUNT],
                                Entity::CURRENCY    => $input[Entity::CURRENCY],
                                Entity::UTR         => $input[Entity::UTR],
                                Entity::CHANNEL     => $input[Entity::CHANNEL],
                            ];

                            $reversal = $this->create($reversalInput);

                            if (empty($input[Entity::ID]) === false)
                            {
                                $reversal->setId($input[Entity::ID]);
                            }

                            $reversal->balance()->associate($payout->balance);

                            $reversal->merchant()->associate($payout->merchant);

                            $reversal->entity()->associate($payout);

                            if ($this->shouldHandleRewardForReversalsForSource($reversal) === true)
                            {
                                (new Credits\Transaction\Core)->reverseCreditsForSource(
                                    $reversal->getEntityId(),
                                    $reversal->getEntityType(),
                                    $reversal);
                            }

                            $reversal->setIgnoreRelationsForPayoutServiceReversals();

                            $reversal = $this->createTransactionFromPayoutReversal($reversal);

                            (new Transaction\Core)->dispatchEventForTransactionCreated($reversal->transaction);

                            $txn = $reversal->transaction;

                            return [
                                Entity::TRANSACTION_ID => $txn->getId(),
                                Entity::FEE            => $txn->getFee(),
                                Entity::TAX            => $txn->getTax(),
                            ];
                        });

                        $this->trace->info(
                            TraceCode::PAYOUT_SERVICE_REVERSAL_AND_TRANSACTION_CREATED,
                            [
                                'response' => $response
                            ]);

                    }
                    catch (\Throwable $exception)
                    {
                        $this->trace->traceException(
                            $exception,
                            Trace::ERROR,
                            TraceCode::ERROR_PAYOUT_SERVICE_REVERSAL_AND_TRANSACTION_CREATION_FAILURE,
                            [
                                'input' => $input
                            ]);

                        $response =  [
                            Payout\Entity::ERROR => $exception->getMessage(),
                        ];
                    }
                }

                return $response;
            },
            $this->payoutServiceMutexTTLForReversal,
            ErrorCode::BAD_REQUEST_REVERSAL_CREATION_FOR_PAYOUT_SERVICE_IN_PROGRESS);
    }

    /**
     * Function is called from payout Microservice to reverse credits in api.
     * This is needed because credits are owned by api for now.
     * Once credits are migrated to payout service then this won't be needed.
     *
     * @param array $params
     * @return array
     */
    public function reverseCreditsViaPayoutService(array $params): array
    {
        $this->trace->info(TraceCode::PAYOUT_SERVICE_REVERSE_CREDITS_REQUEST,
            [
                'params' => $params
            ]);

        (new Validator)->validateInput(Validator::REVERSE_CREDITS_VIA_PAYOUT_SERVICE, $params);

        try
        {
            $payoutId = $params[Reversal\Entity::PAYOUT_ID];

            $payout = (new Payout\Entity);

            $payout->setId($payoutId);

            $payout->setFeeType($params[Payout\Entity::FEE_TYPE]);

            $entityType = $params[Reversal\Entity::ENTITY_TYPE];

            $merchant = (new Merchant\Repository)->findOrFail($params[Payout\Entity::MERCHANT_ID]);

            /** @var Balance\Entity $balance */
            $balance = $this->repo->balance->findOrFailById($params[Entity::BALANCE_ID]);

            switch ($entityType)
            {
                case constants\Entity::PAYOUT:

                    $payout->merchant()->associate($merchant);

                    $payout->balance()->associate($balance);

                    // Check if credits already reversed for payout source type. If yes -> skip it.
                    $creditTxns = (new Credits\Transaction\Core)->getReverseCreditTransactionsForSource(
                        $payoutId,
                        Constants\Entity::PAYOUT);

                    if ($creditTxns->count() > 0)
                    {
                        $this->trace->info(TraceCode::PAYOUT_SERVICE_REVERSE_CREDITS_SKIPPED,
                            [
                                'payout_id' => $payoutId,
                            ]);
                    }
                    else
                    {
                        (new Credits\Transaction\Core)->reverseCreditsForSource(
                            $payout->getId(),
                            Constants\Entity::PAYOUT,
                            $payout);
                    }

                    break;

                case constants\Entity::REVERSAL:

                    $reversalId = $params[Reversal\Entity::REVERSAL_ID];

                    $reversal = (new Reversal\Entity);

                    $reversal->setId($reversalId);

                    $reversal->setEntityType($entityType);

                    $reversal->setEntityId($payoutId);

                    $reversal->entity()->associate($payout);

                    $reversal->merchant()->associate($merchant);

                    $reversal->balance()->associate($balance);

                    if ($this->shouldHandleRewardForPayoutReversal($reversal) === true)
                    {
                        (new Credits\Transaction\Core)->reverseCreditsForSource(
                            $reversal->getEntityId(),
                            $reversal->getEntityType(),
                            $reversal);
                    }
                    else
                    {
                        $this->trace->info(TraceCode::PAYOUT_SERVICE_REVERSE_CREDITS_SKIPPED,
                            [
                                'payout_id' => $payoutId,
                                'reversal_id' => $reversalId,
                            ]);
                    }

                    break;
            }

            $response = [
                'success' => true,
            ];

            $this->trace->info(TraceCode::PAYOUT_SERVICE_REVERSE_CREDITS_RESPONSE,
                [
                    'response' => $response
                ]);

            return $response;
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::PAYOUT_SERVICE_REVERSE_CREDITS_REQUEST_FAILED,
                []
            );

            throw new Exception\ServerErrorException(
                'Internal error occurred while reversing merchant credits via payout service.',
                ErrorCode::SERVER_ERROR,
                [
                    'message' => $exception->getMessage()
                ]);
        }
    }

    public function createTransactionInLedgerReverseShadowFlow(string $entityId, array $ledgerResponse)
    {
        $isPayoutServiceReversal = false;

        $reversal = $this->repo->reversal->find($entityId);

        if (empty($reversal) === true)
        {
            $reversal = $this->getAPIModelReversalFromPayoutService($entityId);

            if ((empty($reversal) === false) && empty($reversal->getId() === false))
            {
                $isPayoutServiceReversal = true;
            }
        }

        $featureChecks = (($reversal->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true) or
                          ($reversal->merchant->isFeatureEnabled(Feature\Constants::PAYOUT_SERVICE_ENABLED) === true));

        if ($featureChecks === false)
        {
            throw new Exception\LogicException('Merchant does not have the ledger reverse shadow feature flag enabled'
                , ErrorCode::BAD_REQUEST_MERCHANT_NOT_ON_LEDGER_REVERSE_SHADOW,
                                               ['merchant_id' => $reversal->getMerchantId()]);
        }

        $txn = $this->app['api.mutex']->acquireAndRelease(
            'rvrsl_'.$entityId,
            function () use ($reversal, $ledgerResponse, $isPayoutServiceReversal)
            {
                // No need to make a call to payout service again if reversal belongs there.
                if ($isPayoutServiceReversal === false)
                {
                    $reversal->reload();
                }

                return $this->repo->transaction(function() use ($reversal, $ledgerResponse, $isPayoutServiceReversal)
                {
                    $txnId      = $ledgerResponse[Entity::ID];
                    $newBalance = Transaction\Processor\Ledger\Base::getMerchantBalanceFromLedgerResponse($ledgerResponse);

                    list($txn, $feeSplit) = (new Transaction\Processor\Reversal($reversal))->createTransactionForLedger($txnId, $newBalance);

                    // No need to update reversal if it doesn't exists in api db. PS dual write will take care of it.
                    if ($isPayoutServiceReversal === false)
                    {
                        $reversal->transaction()->associate($txn);

                        $this->repo->saveOrFail($reversal);
                    }

                    if ($feeSplit !== null)
                    {
                        $this->repo->saveOrFail($txn);

                        (new Transaction\Core)->saveFeeDetails($txn, $feeSplit);

                        // TODO: This dispatch has to be moved to some other location once ledger becomes primary
                        // As we will stop the dual write to the transactions table
                        // If fee split is null, it means that duplicate txn was found
                        // so no dispatch necessary again.
                        if (($reversal->getEntityType() === E::PAYOUT) && ($isPayoutServiceReversal === false))
                        {
                            $this->app->events->dispatch('api.transaction.created', $reversal->transaction);
                        }
                    }

                    return $txn;
                });
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        return [
            'entity' => $reversal->getPublicId(),
            'txn'    => $txn->getPublicId(),
        ];
    }

    public function createReversalViaLedgerCronJob(array $blacklistIds, array $whitelistIds, int $limit)
    {
        if(empty($whitelistIds) === false)
        {
            $reversals = $this->repo->reversal->fetchReversalWhereTxnIdNullAndIdsIn($whitelistIds);

            return $this->processReversalViaLedgerCronJob($blacklistIds, $reversals, true);
        }

        for ($i = 0; $i < 3; $i++)
        {
            // Fetch all reversals created in the last 24 hours.
            // Doing this 3 times in for loop to fetch reversals created in last 72 hours.
            // This is done so as to not put extra load on the database while querying.
            $reversals = $this->repo->reversal->fetchReversalAndTxnIdNullBetweenTimestamp($i, $limit);

            $this->processReversalViaLedgerCronJob($blacklistIds, $reversals);
        }
    }

    private function processReversalViaLedgerCronJob(array $blacklistIds, $reversals, bool $skipChecks = false)
    {
        foreach ($reversals as $rev)
        {
            $sourceId = $rev->getEntityId();
            $sourceType = $rev->getEntityType();

            try
            {
                // If merchant is onboarded on payout service then we skip cron processing.
                // Any intermittent failures are handled at payout service end.
                if (($sourceType === E::PAYOUT) and
                    ($rev->merchant->isFeatureEnabled(Feature\Constants::PAYOUT_SERVICE_ENABLED) === true))
                {
                    $this->trace->info(
                        TraceCode::LEDGER_STATUS_CRON_SKIP_MERCHANT_ON_PAYOUT_SERVICE,
                        [
                            'payout_id'   => $rev->getPublicId(),
                            'merchant_id' => $rev->getMerchantId(),
                        ]
                    );
                    continue;
                }

                /*
                 * If merchant is not on reverse shadow, and is not present in $forcedMerchantIds array,
                 * only then skip the merchant.
                 */
                if ($rev->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === false)
                {
                    $this->trace->info(
                        TraceCode::LEDGER_STATUS_CRON_SKIP_MERCHANT_NOT_REVERSE_SHADOW,
                        [
                            'reversal_id' => $rev->getPublicId(),
                            'merchant_id' => $rev->getMerchantId(),
                        ]
                    );
                    continue;
                }

                if($skipChecks === false)
                {
                    if(in_array($rev->getPublicId(), $blacklistIds) === true)
                    {
                        $this->trace->info(
                            TraceCode::LEDGER_STATUS_CRON_SKIP_BLACKLIST_REVERSAL,
                            [
                                'reversal_id' => $rev->getPublicId(),
                                'source_id'   => $sourceId,
                                'source_type' => $sourceType,
                            ]
                        );
                        continue;
                    }
                }

                $this->trace->info(
                    TraceCode::LEDGER_STATUS_CRON_REVERSAL_INIT,
                    [
                        'reversal_id' => $rev->getPublicId(),
                        'source_id'   => $sourceId,
                        'source_type' => $sourceType,
                    ]
                );

                $ledgerRequest = null;

                if($sourceType === E::PAYOUT)
                {
                    $payout = $this->repo->payout->find($sourceId);

                    if (empty($payout->getTransactionId()) === true)
                    {
                        $this->trace->info(
                            TraceCode::LEDGER_STATUS_CRON_SKIP_REVERSAL_CREDIT_FOR_UNDEBITED_PAYOUT,
                            [
                                'reversal_id' => $rev->getPublicId(),
                                'payout_id' => $payout->getPublicId(),
                            ]
                        );

                        continue;
                    }

                    $response = null;
                    $ftsData = [];
                    $status = Payout\Status::FAILED;

                    /*
                     * In case of reversal, payout can have two states. payout_failed and payout_reversed.
                     * Currently there is no way to get the fts information from the payout entity.
                     * For payout_reversed, we need fts source data. So, if payout processed_at is set, i.e., not null,
                     * that concludes that payout has been processed. Therefore, calling ledger service with the same payout_processed
                     * event to get fts information which would be used when creating payout_reversed.
                     *
                     * In other cases, since payout is not processed, payout_failed event is sent to ledger.
                     */
                    if($payout->getProcessedAt() !== null)
                    {
                        $input = [
                            Ledger\Base::TRANSACTOR_ID    => $payout->getPublicId(),
                            Ledger\Base::TRANSACTOR_EVENT => PayoutLedger::PAYOUT_PROCESSED,
                        ];
                        $response = (new Ledger\Base)->fetchJournalByTransactor($input);
                        $ftsData = Ledger\Base::getFtsDataFromLedgerResponse($response);
                        $status = Payout\Status::REVERSED;
                    }

                    $ledgerRequest = (new PayoutLedger())->createLedgerPayloadFromEntity($payout, $status, $rev, $ftsData);
                }
                else
                {
                    $fav = $this->repo->fund_account_validation->find($sourceId);

                    if (empty($fav->getTransactionId()) === true)
                    {
                        $this->trace->info(
                            TraceCode::LEDGER_STATUS_CRON_SKIP_REVERSAL_CREDIT_FOR_UNDEBITED_FAV,
                            [
                                'reversal_id' => $rev->getPublicId(),
                                'fav_id' => $fav->getPublicId(),
                            ]
                        );

                        continue;
                    }

                    $ledgerRequest = (new FavLedger())->createLedgerPayloadFromEntity($fav, [], FundAccountValidation\Status::CREATED);
                }

                (new LedgerStatus($this->mode, $ledgerRequest, null, false))->handle();

            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::LEDGER_STATUS_CRON_REVERSAL_FAILED,
                    [
                        'reversal_id' => $rev->getPublicId(),
                        'source_id'   => $sourceId,
                        'source_type' => $sourceType,
                    ]
                );

                $this->trace->count(Payout\Metric::LEDGER_STATUS_CRON_FAILURE_COUNT,
                                    [
                                        'environment' => $this->app['env'],
                                        'entity'      => 'reversal'
                                    ]);


                continue;
            }
        }
    }

    public function createLedgerEntriesForRouteReversal(Merchant\Entity $merchant, Reversal\Entity $reversal, Refund\Entity $refund)
    {
        if((isset($reversal) === false) or (isset($refund) === false))
        {
            return;
        }

        if ($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === false)
        {
            return;
        }

        $refundMerchant = $refund->merchant;

        if ($refundMerchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === false)
        {
            return;
        }

        try
        {
            $transactionMessage = RouteReversalJournalEvents::createBulkTransactionMessageForRouteReversal($reversal, $refund);

            \Event::dispatch(new TransactionalClosureEvent(function () use ($transactionMessage) {
                // Job will be dispatched only if the transaction commits.
                LedgerEntryJob::dispatchNow($this->mode, $transactionMessage, true);
            }));

            $this->trace->info(
                TraceCode::TRANSFER_REVERSAL_LEDGER_EVENT_TRIGGERED,
                [
                    'transfer_id'           => $reversal->getTransferIdAttribute(),
                    'reversal_id'           => $reversal->getId(),
                    'refund_id'             => $refund->getId(),
                    'transactionMessage'    => $transactionMessage,
            ]);

        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::PG_LEDGER_ROUTE_ENTRY_FAILED,
                [
                    'transfer_id'           => $reversal->getTransferIdAttribute(),
                    'reversal_id'           => $reversal->getId(),
                    'refund_id'             => $refund->getId(),
                    'payment_id'            => $refund->getPaymentId(),
                ]);
        }
    }

    public function getAPIModelReversalFromPayoutService(string $id)
    {
        $this->trace->info(
            TraceCode::FETCH_PAYOUT_SERVICE_REVERSAL,
            [
                Entity::REVERSAL_ID => $id
            ]);

        $payoutServiceReversals = $this->repo->reversal->getPayoutServiceReversalById($id);

        if (count($payoutServiceReversals) === 0)
        {
            return null;
        }

        $psReversal = $payoutServiceReversals[0];

        $reversal = new Entity;

        $reversal->setAmount($psReversal->amount);
        $reversal->setBalanceId($psReversal->balance_id);
        $reversal->setChannel($psReversal->channel);
        $reversal->setCreatedAt($psReversal->created_at);
        $reversal->setCurrency($psReversal->currency);
        $reversal->setFee($psReversal->fees);
        $reversal->setId($psReversal->id);
        $reversal->setMerchantId($psReversal->merchant_id);
        $reversal->setTax($psReversal->tax);
        $reversal->setUpdatedAt($psReversal->updated_at);
        $reversal->setUtr($psReversal->utr);

        if (empty($psReversal->notes) === false)
        {
            $reversal->setAttribute(Entity::NOTES, json_decode($psReversal->notes));
        }
        else
        {
            $reversal->setNotes([]);
        }

        if (empty($psReversal->transaction_id) === false)
        {
            $txn = new Transaction\Entity;
            $txn->setId($psReversal->transaction_id);
            $txn->setEntityId($reversal->getId());
            $txn->setType('reversal');
            $reversal->transaction()->associate($txn);
            $reversal->unsetRelation('transaction');
        }

        if (empty($psReversal->payout_id) === false)
        {
            $payout = (new Payout\Core())->getAPIModelPayoutFromPayoutService($psReversal->payout_id);
            $reversal->entity()->associate($payout);

            $reversal->setIgnoreRelationsForPayoutServiceReversals();
        }

        // This is need to showcase $payout as freshly fetched entity and not like a variable on which many
        // setters are called. After doing this isDirty will give false.
        $reversal->syncOriginal();

        $reversal->setConnection($this->mode);

        $this->trace->info(
            TraceCode::FETCH_PAYOUT_SERVICE_REVERSAL_SUCCESS,
            [
                "reversal" => $reversal->toArray()
            ]);

        return $reversal;
    }
}
