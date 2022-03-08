<?php

namespace RZP\Models\Reversal;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Ledger\RefundJournalEvents;
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
use Razorpay\Trace\Logger;
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
use RZP\Exception\GatewayTimeoutException;
use Neves\Events\TransactionalClosureEvent;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\BankingAccountStatement\Channel;
use RZP\Models\Adjustment\Core as AdjustmentCore;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Jobs\Ledger\CreateLedgerJournal as LedgerEntryJob;
use RZP\Models\FundAccount\Validation as FundAccountValidation;
use RZP\Models\Transaction\Processor\Ledger\Payout as PayoutLedger;
use RZP\Models\Transaction\Processor\Ledger\FundAccountValidation as FavLedger;

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

                // Return reversal entity
                return $result[0] ?? null;
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

                // dispatch to queue for transactions creation.
                try
                {
                    Transactions::dispatch($this->mode, $reversal->getId(), E::REVERSAL, $response);
                }
                catch (\Throwable $ex)
                {
                    // Todo: check how to handle this failure
                    $this->trace->info(
                        TraceCode::LEDGER_TRANSACTIONS_QUEUE_JOB_PUSH_FAILED,
                        [
                            'reversal_id'    => $reversal->getId(),
                            'entity_name'    => \RZP\Constants\Entity::REVERSAL,
                            'ledgerResponse' => $response,
                        ]);
                }
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
     * @throws \Throwable
     */
    public function pushFavReversalToLedgerTxnQueue($reversal, $ledgerResponse)
    {
        try
        {
            Transactions::dispatch($this->mode, $reversal->getId(), E::REVERSAL, $ledgerResponse);
        }
        catch (\Throwable $ex)
        {
            // trace and ignore exception
            $payload = [
                'reversal_id'    => $reversal->getId(),
                'entity_name'    => \RZP\Constants\Entity::REVERSAL,
                'ledgerResponse' => $ledgerResponse,
            ];
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::LEDGER_TRANSACTIONS_QUEUE_JOB_PUSH_FAILED, $payload);
        }
    }

    /**
     * Create a full reversal for a refund
     *
     * @param Refund\Entity $refund
     * @param bool $feeOnlyReversal
     *
     * @return Entity
     */
    public function reverseForRefund(Payment\Refund\Entity $refund, bool $feeOnlyReversal): Entity
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

        $reversal = $this->repo->transaction(function() use ($reversal, $txnCore, $feeOnlyReversal)
        {
            list($txn, $feesSplit) = $txnCore->createFromRefundReversal($reversal);

            $this->repo->saveOrFail($txn);

            $this->repo->saveOrFail($reversal);

            $txnCore->saveFeeDetails($txn, $feesSplit);

            if($reversal->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === true)
            {
                // Create ledger entry for transaction here
                \Event::dispatch(new TransactionalClosureEvent(function () use ($txn, $reversal, $feeOnlyReversal)
                {
                    $transactionMessage = RefundJournalEvents::createTransactionMessageForRefundReversal($reversal, $txn);

                    $transactionMessage[LedgerConstants::ADDITIONAL_PARAMS] = (object)RefundJournalEvents::fetchLedgerRulesForReversal($reversal, $txn, $reversal->entity, $feeOnlyReversal);

                    LedgerEntryJob::dispatch($this->mode, $transactionMessage, $reversal->merchant)->onConnection('sync');
                }));
            }

            return $reversal;
        });

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

        return $reversal;
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
            $payment = $transfer->source->payments()->where('status', 'captured')->first();
        }
        else
        {
            $payment = $transfer->source;
        }

        $merchant = $payment->merchant;

        $refund = (new Payment\Processor\Processor($merchant))->refund($payment, $input);

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
                        $payout   = $this->repo->payout->findOrFail($payoutId);

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

    public function createTransactionInLedgerReverseShadowFlow(string $entityId, array $ledgerResponse)
    {
        $reversal = $this->repo->reversal->find($entityId);

        if ($reversal->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === false)
        {
            throw new Exception\LogicException('Merchant does not have the ledger reverse shadow feature flag enabled'
                , ErrorCode::BAD_REQUEST_MERCHANT_NOT_ON_LEDGER_REVERSE_SHADOW,
                                               ['merchant_id' => $reversal->getMerchantId()]);
        }

        $txn = $this->app['api.mutex']->acquireAndRelease(
            'rvrsl_'.$entityId,
            function () use ($reversal, $ledgerResponse)
            {
                $reversal->reload();

                return $this->repo->transaction(function() use ($reversal, $ledgerResponse)
                {
                    $txnId      = $ledgerResponse[Entity::ID];
                    $newBalance = Transaction\Processor\Ledger\Base::getMerchantBalanceFromLedgerResponse($ledgerResponse);

                    list($txn, $feeSplit) = (new Transaction\Processor\Reversal($reversal))->createTransactionForLedger($txnId, $newBalance);

                    $reversal->transaction()->associate($txn);

                    $this->repo->saveOrFail($reversal);

                    if ($feeSplit !== null)
                    {
                        $this->repo->saveOrFail($txn);

                        (new Transaction\Core)->saveFeeDetails($txn, $feeSplit);

                        // TODO: This dispatch has to be moved to some other location once ledger becomes primary
                        // As we will stop the dual write to the transactions table
                        // If fee split is null, it means that duplicate txn was found
                        // so no dispatch necessary again.
                        if ($reversal->getEntityType() === E::PAYOUT)
                        {
                            (new Transaction\Core)->dispatchEventForTransactionCreated($reversal->transaction);
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

    public function createReversalViaLedgerCronJob(array $blacklistIds, array $forcedMerchantIds, int $limit)
    {

        for ($i = 0; $i < 3; $i++)
        {
            // Fetch all reversals created in the last 24 hours.
            // Doing this 3 times in for loop to fetch reversals created in last 72 hours.
            // This is done so as to not put extra load on the database while querying.
            $reversals = $this->repo->reversal->fetchReversalAndTxnIdNullBetweenTimestamp($i, $limit);

            foreach ($reversals as $rev)
            {
                $sourceId = $rev->getEntityId();
                $sourceType = $rev->getEntityType();

                try
                {
                    /*
                     * If merchant is not on reverse shadow, and is not present in $forcedMerchantIds array,
                     * only then skip the merchant.
                     */
                    if (($rev->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === false)
                        && (in_array($rev->getMerchantId(), $forcedMerchantIds) === false))
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
                        $ledgerRequest = (new FavLedger())->createLedgerPayloadFromEntity($fav);
                    }

                    (new LedgerStatus($this->mode, $ledgerRequest, null, false))->handle();

                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Logger::ERROR,
                        TraceCode::LEDGER_STATUS_CRON_REVERSAL_FAILED,
                        [
                            'reversal_id' => $rev->getPublicId(),
                            'source_id'   => $sourceId,
                            'source_type' => $sourceType,
                        ]
                    );

                    continue;
                }
            }
        }
    }
}
