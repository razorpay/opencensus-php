<?php

namespace RZP\Models\Adjustment;

use RZP\Exception;
use RZP\Models\Base;

use RZP\Models\Dispute;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Jobs\LedgerStatus;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Jobs\Transactions;
use RZP\Models\Transaction;
use RZP\Models\Payout\Metric;
use RZP\Models\Merchant\Balance;
use Exception as DefaultException;
use RZP\Constants as DefaultConstants;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayTimeoutException;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Ledger\AdjustmentJournalEvents;
use RZP\Models\Merchant\Invoice as MerchantInvoice;
use RZP\Models\Settlement\Channel as BankingChannel;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Transaction\Processor\Ledger\Adjustment as LedgerAdjustment;
use RZP\Models\Ledger\MerchantReserveBalanceJournalEvents;
use Neves\Events\TransactionalClosureEvent;
use RZP\Jobs\Ledger\CreateLedgerJournal as LedgerEntryJob;
use RZP\Models\Ledger\ReverseShadow\Adjustments\Core as ReverseShadowAdjustmentsCore;

class Core extends Base\Core
{
    // input param for adjustment creation on capital collection balances.
    const BALANCE_ID = 'balance_id';

    public function createAdjustment(array $input, Merchant\Entity $merchant, $payment=null): Entity
    {
        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_REQUEST,
            [
                'input' => $input,
                'merchant' => $merchant->getId()
            ]);

        // Create input for adjustment
        $adjInput = $input;

        // Create input for Merchant Invoice
        $merchantInvoiceInput = $input;

        // Checking validations on input array
        (new Validator)->validateAdjustmentCreateInput($input, $merchant);

        $amount = $input[Entity::AMOUNT] ?? 0;

        $tax =  $input[MerchantInvoice\Entity::TAX] ?? 0;

        $fees = $input[Entity::FEES] ?? 0;

        $balanceType = $input[Entity::TYPE] ?? Balance\Type::PRIMARY;

        $adjInput[Entity::AMOUNT] = $amount + $tax + $fees;

        unset($adjInput[Entity::FEES]);

        unset($adjInput[MerchantInvoice\Entity::TAX]);

        unset($adjInput[Entity::TYPE]);

        $sendReserveBalanceMail = false;

        if (empty($input[self::BALANCE_ID]) === false)
        {
            // used for capital collections transactions.
            // capital-collections uses balance_id for reference, not balance type
            // as there can be multiple balances of same type on same merchant id:
            // like multiple principal balances if merchant has multiple loc withdrawals.

            /** @var Balance\Entity $balance */
            $balance = $this->repo->balance->findByIdAndMerchant($input[self::BALANCE_ID], $merchant);

            // check balance is of type: principal, charge, interest
            if (in_array($balance->getType(), Balance\Type::$capitalBalances, true) === false)
            {
                throw new Exception\BadRequestValidationFailureException('invalid capital collections balance: '.
                    $balance->getType(), self::BALANCE_ID, $balance->toArrayPublic());
            }

            unset($adjInput[self::BALANCE_ID]);
        }
        else if (($balanceType === Balance\Type::RESERVE_BANKING) or
            ($balanceType === Balance\Type::RESERVE_PRIMARY))
        {
            [$balance, $sendReserveBalanceMail] = (new Balance\Core)->createOrFetchReserveBalance($merchant,
                                                                        $balanceType, $this->mode);

        }
        else
        {
            $balance = $merchant->getBalanceByTypeOrFail($balanceType);
        }

        $adj = (new Adjustment\Entity)->build($adjInput);

        $adj->balance()->associate($balance);

        $adj->setStatus(Status::CREATED);

        $this->app['workflow']
             ->setEntityAndId($adj->getEntity(), $merchant->getId())
             ->handle((new \stdClass), $adj);

        /** @var Entity|null $adjustment */
        $adjustment = null;

        if (isset($input[Entity::AMOUNT]) === true)
        {
            // Creating adjustment only, since no invoice record is reqd
            $adjustment = $this->transaction([$this, 'createAdjInTransaction'], $adj, $merchant);

            $this->trace->info(TraceCode::MERCHANT_BALANCE_UPDATE_SUCCESSFULL,
                [
                    'adjustment transaction'  => $adjustment,
                    'balance_type'             => $balanceType,
                    'merchant_id'              => $merchant->getMerchantId()
                ]
            );
        }
        else
        {
            // Creating merchant invoice entries too along with adj
            // because of adjustment entries (fees and tax)
            $merchantInvoiceInput[MerchantInvoice\Entity::TAX] = $tax;

            $merchantInvoiceInput[MerchantInvoice\Entity::AMOUNT] = $fees;

            unset($merchantInvoiceInput['fees']);

            $adjustment = $this->repo->transaction(
                function () use ($adj, $merchant, $merchantInvoiceInput)
                {
                    // 1. Create adjustment
                    // 2. Create Invoice entity for adjustment
                    $adjustment = $this->createAdjInTransaction($adj, $merchant);

                    (new Merchant\Invoice\Core)->createAdjustmentInvoiceEntity($adj, $merchantInvoiceInput);

                    return $adjustment;
                }
            );
        }

        if ($sendReserveBalanceMail === true)
        {
            (new Balance\NegativeReserveBalanceMailers())->sendReserveBalanceActivatedMail($merchant, $balance);
        }

        if ($adjustment->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === false)
        {
            $this->processLedgerAdjustment($adjustment);
        } else {
            // reverse shadow enabled case
            if ($adj->isBalanceTypeBanking() === true)
            {
                $this->processLedgerForReverseShadow($adjustment);
            }
        }

        if ($balanceType === Balance\Type::RESERVE_PRIMARY)
        {
            $this->createLedgerEntriesForMerchantReserveBalanceLoading($adj, $merchant, $payment);
        }

        return $adjustment;
    }
    private function createLedgerEntriesForMerchantReserveBalanceLoading(Adjustment\Entity $adj, Merchant\Entity $merchant, $payment)
    {
        try
        {
            if($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === false)
            {
                return;
            }

            $transactionMessage= MerchantReserveBalanceJournalEvents::createBulkTransactionMessageForMerchantReserveBalanceLoading($adj, $payment);

            \Event::dispatch(new TransactionalClosureEvent(function () use ($transactionMessage)
            {
                LedgerEntryJob::dispatchNow($this->mode, $transactionMessage, true);
            }));

            $this->trace->info(
                TraceCode::MERCHANT_RESERVE_BALANCE_LOADING_EVENT,
                [
                    'merchant' => $merchant->getId(),
                    'transactionMessage' => $transactionMessage,
                    'payment' => $payment,
                ]);

        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PG_LEDGER_ENTRY_FAILED,
                ['adjustment_id'             => $adj->getId()]);
        }
    }

    public function createLedgerEntriesForManualAdjustment(Adjustment\Entity $adj, Merchant\Entity $merchant, string $publicId)
    {
        if($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === false)
        {
            return;
        }

        try
        {
            $transactionMessage= AdjustmentJournalEvents::createTransactionMessageForManualAdjustment($adj, $publicId);

            \Event::dispatch(new TransactionalClosureEvent(function () use ($transactionMessage)
            {
                LedgerEntryJob::dispatchNow($this->mode, $transactionMessage, false);
            }));

            $this->trace->info(
                TraceCode::ADJUSTMENT_JOURNAL_EVENT,
                [
                    'merchant' => $merchant->getId(),
                    'transactionMessage' => $transactionMessage,
                ]);

        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PG_LEDGER_ENTRY_FAILED,
                ['adjustment_id'             => $adj->getId()]);
        }
    }

    public function createAdjustmentForSource(array $input, Base\PublicEntity $source): Entity
    {
        $traceCode = Constants::getAdjustmentCreateRequestTraceCode($source->getEntityName());

        $this->trace->info(
            $traceCode,
            [
                'input'       => $input,
                'merchant_id' => $source->getMerchantId()
            ]);

        (new Validator)->validateMerchantBalance($source->merchant, $source, $input);

        $adjustment = $this->createAdjustment($input, $source->merchant);

        $adjustment->entity()->associate($source);

        if (($adjustment->isBalanceTypePrimary() === true) and
            ($adjustment->getEntityType() !== DefaultConstants\Entity::DISPUTE))
        {
            if ($source->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
            {
                (new ReverseShadowAdjustmentsCore())->createLedgerEntryForManualAdjustmentReverseShadow($adjustment, $source->getPublicId());

                $adjustment->setStatus(Status::PROCESSED);
            }
            else
            {
                $this->createLedgerEntriesForManualAdjustment($adjustment, $source->merchant, $source->getPublicId());
            }
        }

        $this->repo->saveOrFail($adjustment);

        return $adjustment;
    }

    public function splitAdjustments(array $adjustment): array
    {
        $this->trace->info(
            TraceCode::ADJUSTMENT_SPLIT_REQUEST,
            [
                'input' => $adjustment
            ]);

        (new Validator)->validateInput('split_adjustment', $adjustment);

        $adj = $this->repo->adjustment->findOrFail($adjustment[Entity::ID]);

        $count = 1;

        list($valid, $data) = $this->verifyAmountsToSplit($adj, $adjustment[Dispute\Entity::PAYMENT_ID]);

        if ($valid === false)
        {
            throw new Exception\BadRequestValidationFailureException('Amounts do not seem to add up');
        }
        try
        {
            $this->repo->transaction(function () use ($data, $count, $adj)
            {
                $originalAmount = $adj->getAmount();

                $txn = $this->repo->transaction->findOrFail($adj->getTransactionId());

                $setlDetails = $this->repo->settlement_details->fetch([
                    Settlement\Details\Entity::SETTLEMENT_ID => $txn->getSettlementId(),
                    Settlement\Details\Entity::COMPONENT     => $txn->getType()
                ])->first();

                $setlDetails->update([Settlement\Details\Entity::COUNT => count($data) + $setlDetails->getCount() - 1]);

                $balance = 0;

                $adjId = $adj->getId();

                $txnId = $txn->getId();

                foreach ($data as $id => $amount)
                {
                    if ($count === 1)
                    {
                        $adj->update([Entity::AMOUNT => 0 - $amount]);

                        $this->updateTransactionAmountAndBalance($txn, $amount, $originalAmount);

                        $balance = $txn->getBalance();

                        $count++;
                    }
                    else
                    {
                        $newAdjId = $this->getNextId($adjId);

                        $newAdj = $this->insertSplitAdjustment($newAdjId, $amount, $adjId);

                        $adjId = $newAdjId;

                        $newTxnId = $this->getNextId($txnId);

                        $newTxn = $this->insertSplitTransaction($newTxnId, $newAdj, $amount, $balance, $txn);

                        $txnId = $newTxnId;

                        $balance -= $amount;

                        $newAdj->transaction()->associate($newTxn);

                        $this->repo->adjustment->saveOrFail($newAdj);
                    }
                }
            });
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ADJUSTMENT_SPLIT_ERROR,
                [
                    'id'          => $adjustment[Entity::ID],
                    'payment_ids' => $adjustment[Dispute\Entity::PAYMENT_ID]
                ]);
        }

        return ['success' => true];
    }

    protected function verifyAmountsToSplit(Entity $adjustment, string $paymentIds): array
    {
        $paymentIds = explode(',', $paymentIds);

        $data = [];

        $amount = 0;

        $adjId = $adjustment->getId();

        if (($adjustment->getAmount() >= 0) === true)
        {
            $this->trace->info(
                TraceCode::ADJUSTMENT_SPLIT_ERROR,
                [
                    'id'     => $adjId,
                    'reason' => 'Positive amount not expected'
                ]);

            return [false, []];
        }

        foreach ($paymentIds as $paymentId)
        {
            $paymentId = trim($paymentId);

            $payment = $this->repo->payment->findByPublicId($paymentId);

            $amount += $payment[Payment\Entity::AMOUNT];

            $data[$paymentId] = $payment[Payment\Entity::AMOUNT];
        }

        if ($amount !== (int) abs($adjustment->getAmount()))
        {
            $this->trace->debug(
                TraceCode::ADJUSTMENT_SPLIT_ERROR,
                [
                    'id' => $adjId,
                ]);

            return [false,[]];
        }

        return [true, $data];
    }

    public function createAdjInTransaction(Entity $adj, $merchant, $txnId = null): Entity
    {
        $this->repo->assertTransactionActive();

        // set channel if not set already from input
        if ($adj->getChannel() === null)
        {
            if ($adj->isBalanceTypeBanking() === true)
            {
                // TODO : Remove second condition later
                $channel = $adj->balance->getChannel() ?? BankingChannel::YESBANK;

                $adj->setChannel($channel);
            }
            else if ($adj->isBalanceTypeCommission() === true)
            {
                // commission adjustments to be made from yes_bank channel
                $adj->setChannel(BankingChannel::YESBANK);
            }
            else
            {
                $adj->setChannel($merchant->getChannel());
            }
        }

        $adj->merchant()->associate($merchant);

        $this->repo->saveOrFail($adj);

        // if (not RX case (OR) RX but no reverse shadow case) (AND) no PG Ledger reverse shadow case
        if (($adj->isBalanceTypeBanking() === false) ||
                ($adj->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === false))
        {
            if($adj->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
            {
                return $adj;
            }

            $txn = (new Transaction\Core)->createFromAdjustment($adj, $txnId);
            $this->repo->saveOrFail($txn);
            $adj->setStatus(Status::PROCESSED);
            $this->repo->saveOrFail($adj);
            (new Transaction\Core)->dispatchEventForTransactionCreated($txn);
        }

        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_SUCCESS,
            $adj->toArrayPublic());

        return $adj;
    }

    /**
     * @param Transaction\Entity $txn
     * @param int                $amount
     * @param int                $originalAmount
     *
     * DO NOT USE THIS FUNCTION CASUALLY, THIS IS
     * WRITTEN FOR THE ABOVE SPLIT MIGRATION BUT SHOULD
     * NOT BE NEEDED IN GENERAL.
     */
    protected function updateTransactionAmountAndBalance(
        Transaction\Entity $txn,
        int $amount,
        int $originalAmount)
    {
        $newBalance = $txn->getBalance() + abs($originalAmount) - $amount;

        $txn->update([
            Transaction\Entity::AMOUNT  => abs($amount),
            Transaction\Entity::DEBIT   => abs($amount),
            Transaction\Entity::BALANCE => $newBalance
        ]);

        $this->repo->saveOrFail($txn);
    }

    protected function insertSplitAdjustment(string $newAdjId, int $amount, string $adjId): Entity
    {
        $adj = $this->repo->adjustment->findOrFail($adjId);

        $newAdj = $adj->replicate();

        $newAdj->setAmount(0 - $amount);

        $newAdj->setCreatedAt($adj->getCreatedAt());

        $newAdj->setUpdatedAt($adj->getUpdatedAt());

        $newAdj->setId($newAdjId);

        $newAdj->saveOrFail();

        return $newAdj;
    }

    protected function insertSplitTransaction(
        string $newTxnId,
        Entity $newAdj,
        int $amount,
        int $balance,
        Transaction\Entity $txn): Transaction\Entity
    {
        $newTxn = $txn->replicate();

        $newTxn->setCreatedAt($txn->getCreatedAt());

        $newTxn->setUpdatedAt($txn->getUpdatedAt());

        $newTxn->setAmount(abs($amount));

        $newTxn->setDebit(abs($amount));

        $newTxn->setBalance($balance - $amount);

        $newTxn->setId($newTxnId);

        $newTxn->source()->associate($newAdj);

        $newTxn->saveOrFail();

        return $newTxn;
    }

    /**
     * @param  string $oldId
     *
     * @return string $newId
     *
     * This function does not cover a lot of corner cases
     * as the current data does not need them. Those checks
     * should be added if ever needed.
     */
    protected function getNextId(string $oldId)
    {
        $last = substr($oldId, -1, 1);

        $newId = substr_replace($oldId, ++$last, -1, 1);

        return $newId;
    }

    protected function processLedgerAdjustment(Entity $adjustment)
    {
        // In case env variable ledger.enabled is false, return.
        // We shall also skip the ledger creation
        if (($this->app['config']->get('applications.ledger.enabled') === false) or
            ($adjustment->balance->isTypeBanking() === false))
        {
            return;
        }

        // If the mode is live but the merchant does not have the ledger journal write feature, we return.
        if (($this->isLiveMode()) and
            ($adjustment->merchant->isFeatureEnabled(Feature\Constants::LEDGER_JOURNAL_WRITES) === false))
        {
            return;
        }

        $event = self::getLedgerEventBasedOnAdjustment($adjustment);

        (new Ledger\Adjustment)->pushTransactionToLedger($adjustment, $event);
    }

    protected function getLedgerEventBasedOnAdjustment(Entity $adjustment)
    {
        if ($adjustment->getAmount() >= 0)
        {
            return Ledger\Adjustment::POSITIVE_ADJUSTMENT_PROCESSED;
        }
        else
        {
            return Ledger\Adjustment::NEGATIVE_ADJUSTMENT_PROCESSED;
        }
    }

    public function createAdjustmentForSubBankingBalance(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_REQUEST_FOR_SUB_BALANCES,
            [
                'input'    => $input,
                'merchant' => $merchant->getId()
            ]);

        // Create input for adjustment
        $adjInput = $input;

        // Checking validations on input array
        (new Validator)->validateInput(Validator::SUB_BANKING_BALANCE_ADJUSTMENT_CREATE, $input);

        /** @var Balance\Entity $balance */
        $balance = $this->repo->balance->findByIdAndMerchant($input[self::BALANCE_ID], $merchant);

        $balanceType = $balance->getType();

        // check balance is of type: principal, charge, interest
        if ($balanceType !== Balance\Type::BANKING)
        {
            throw new Exception\BadRequestValidationFailureException('invalid balance type: '.
                                                                     $balance->getType(), self::BALANCE_ID, $balance->toArrayPublic());
        }

        unset($adjInput[self::BALANCE_ID]);

        $adj = (new Adjustment\Entity)->build($adjInput);

        $adj->balance()->associate($balance);

        $adj->setStatus(Status::CREATED);

        /** @var Entity|null $adjustment */
        $adjustment = null;

        if (isset($input[Entity::AMOUNT]) === true)
        {
            // Creating adjustment only, since no invoice record is reqd
            $adjustment = $this->createAdjInTransactionWithoutNotification($adj, $merchant);

            $this->trace->info(TraceCode::MERCHANT_BALANCE_UPDATE_SUCCESSFULL,
                               [
                                   'adjustment transaction'  => $adjustment,
                                   'balance_type'             => $balanceType,
                                   'merchant_id'              => $merchant->getMerchantId()
                               ]
            );
        }

        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_RESPONSE_SUB_BALANCE,
            [
                'input'      => $input,
                'merchant'   => $merchant->getId(),
                'adjustment' => $adjustment->toArrayPublic(),
            ]);

        return $adjustment;
    }

    public function subBalanceAdjustment(array $input, Merchant\Entity $merchant)
    {
        try
        {
            $sourceAdInput = $input;

            $sourceAdInput[Entity::BALANCE_ID] = $sourceAdInput[Entity::SOURCE_BALANCE_ID];

            $sourceAdInput[Entity::AMOUNT] = -1 * $sourceAdInput[Entity::AMOUNT];

            unset($sourceAdInput[Entity::SOURCE_BALANCE_ID]);
            unset($sourceAdInput[Entity::DESTINATION_BALANCE_ID]);

            $destinationAdjustmentInput = $input;

            $destinationAdjustmentInput[Entity::BALANCE_ID] = $destinationAdjustmentInput[Entity::DESTINATION_BALANCE_ID];

            unset($destinationAdjustmentInput[Entity::SOURCE_BALANCE_ID]);
            unset($destinationAdjustmentInput[Entity::DESTINATION_BALANCE_ID]);

            [$sourceAdjustment, $destinationAdjustment] = $this->repo->transaction(function() use ($sourceAdInput, $destinationAdjustmentInput, $merchant) {

                $sourceAdjustment = $this->createAdjustmentForSubBankingBalance($sourceAdInput, $merchant);

                $this->trace->info(
                    TraceCode::ADJUSTMENT_CREATED_FOR_SOURCE_BALANCE_ID,
                    [
                        'source_adjustment_input' => $sourceAdInput,
                        'merchant'                => $merchant->getId(),
                        'source_adjustment'       => $sourceAdjustment->toArrayPublic(),
                    ]);

                $destinationAdjustment = $this->createAdjustmentForSubBankingBalance($destinationAdjustmentInput, $merchant);

                return [$sourceAdjustment, $destinationAdjustment];
            });
        }
        catch (\Throwable $exception)
        {
            throw $exception;
        }

        $this->trace->info(
            TraceCode::ADJUSTMENT_BETWEEN_BALANCE_CREATE_RESPONSE,
            [
                'input'                  => $input,
                'merchant'               => $merchant->getId(),
                'source_adjustment'      => $sourceAdjustment->toArrayPublic(),
                'destination_adjustment' => $destinationAdjustment->toArrayPublic()
            ]);

        return [
            'source_adjustment'      => $sourceAdjustment->toArrayPublic(),
            'destination_adjustment' => $destinationAdjustment->toArrayPublic()
        ];
    }

    protected function createAdjInTransactionWithoutNotification(Entity $adj, $merchant): Entity
    {
        $this->repo->assertTransactionActive();

        // set channel if not set already from input
        if ($adj->getChannel() === null)
        {
            if ($adj->isBalanceTypeBanking() === true)
            {
                // TODO : Remove second condition later
                $channel = $adj->balance->getChannel() ?? BankingChannel::YESBANK;

                $adj->setChannel($channel);
            }
            else
            {
                $adj->setChannel($merchant->getChannel());
            }
        }

        $adj->merchant()->associate($merchant);

        $adj->setStatus(Status::PROCESSED);

        $this->repo->saveOrFail($adj);

        $txn = (new Transaction\Core)->createFromAdjustment($adj);

        $this->repo->saveOrFail($txn);

        $this->repo->saveOrFail($adj);

        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_SUCCESS,
            $adj->toArrayPublic());

        return $adj;
    }

    /**
     * @param Entity $adj
     *
     *
     * This function proceses txn in reverse shadow mode
     * We call ledger in sync and use ledger response to
     * create txn in api db in async
     * @throws Exception\BadRequestException
     * @throws BadRequestValidationFailureException
     */
    protected function processLedgerForReverseShadow(Entity $adj)
    {
        // Fetching terminal to get the terminal_id which will be the identifier to uniquely
        // identify accounts in case of fund loading.
        $ledgerResponse = [];
        $event = self::getLedgerEventBasedOnAdjustment($adj);
        $ledgerPayload = (new LedgerAdjustment)->createPayloadForJournalEntry($adj, $event);
        try {
            $ledgerResponse = (new LedgerAdjustment)->createJournalEntry($ledgerPayload);
            $adj->setStatus(Status::PROCESSED);
            $this->repo->saveOrFail($adj);
        }
        catch (BadRequestException $ex)
        {
            // insufficient balance error from ledger
            $traceCode = TraceCode::LEDGER_JOURNAL_CREATE_FAILED_REVERSE_SHADOW;
            if ($adj->getAmount() >= 0)
            {
                $traceCode = TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_ERROR_IN_CREDIT_FLOW;
            }
            $alertPayload = [
                'adjustment_id'         => $adj->getId(),
                'ledger_payload'        => $ledgerPayload,
            ];

            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                $traceCode,
                $alertPayload
            );

            // mark negative adj as failed in case of insufficient errors
            if ($adj->getAmount() < 0)
            {
                $adj->setStatus(Status::FAILED);
                $this->repo->saveOrFail($adj);
            }
        }
        catch (Exception\IntegrationException $ex)
        {
            $traceCode = TraceCode::LEDGER_JOURNAL_CREATE_FAILED_REVERSE_SHADOW;

            $alertPayload = [
                'adjustment_id'         => $adj->getId(),
                'ledger_payload'        => $ledgerPayload,
            ];

            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                $traceCode,
                $alertPayload
            );

            $adj->setStatus(Status::FAILED);
            $this->repo->saveOrFail($adj);
        }
        catch (\Throwable $ex)
        {
            // trace and ignore exception as it will be retries in async
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_FAILURE,
                [
                    'adjustment_id'         => $adj->getId(),
                    'ledger_payload'        => $ledgerPayload,
                ]
            );
        }
    }

    /**
     * @throws \Throwable
     */
    public function processAdjustmentAfterLedgerStatusCheck($adjustment, $ledgerResponse)
    {
        $this->trace->info(
            TraceCode::PROCESS_ADJUSTMENT_AFTER_LEDGER_STATUS_SUCCESS,
            [
                'adjustment_id'     => $adjustment->getId(),
                'entity_name'       => DefaultConstants\Entity::ADJUSTMENT,
            ]);

        $adjustment->setStatus(Status::PROCESSED);
        $this->repo->saveOrFail($adjustment);
    }

    public function failAdjustmentAfterLedgerStatusCheck($adjustment)
    {
        $this->trace->info(
            TraceCode::FAIL_ADJUSTMENT_AFTER_LEDGER_STATUS_SUCCESS,
            [
                'adjustment_id' => $adjustment->getId(),
                'entity_name'   => DefaultConstants\Entity::ADJUSTMENT,
            ]);

        $adjustment->setStatus(Status::FAILED);
        $this->repo->saveOrFail($adjustment);
    }

    public function createAdjustmentViaLedgerCronJob(array $blacklistIds, array $whitelistIds, int $limit)
    {
        if(empty($whitelistIds) === false)
        {
            $adjustments = $this->repo->adjustment->fetchCreatedAdjustmentWhereTxnIdNullAndIdsIn($whitelistIds);

            return $this->processAdjustmentViaLedgerCronJob($blacklistIds, $adjustments, true);
        }

        for ($i = 0; $i < 3; $i++)
        {
            // Fetch all adjustments created in the last 24 hours.
            // Doing this 3 times in for loop to fetch adjustments created in last 72 hours.
            // This is done so as to not put extra load on the database while querying.
            $adjustments = $this->repo->adjustment->fetchCreatedAdjustmentAndTxnIdNullBetweenTimestamp($i, $limit);

            $this->processAdjustmentViaLedgerCronJob($blacklistIds, $adjustments);
        }
    }

    private function processAdjustmentViaLedgerCronJob(array $blacklistIds, $adjustments, bool $skipChecks = false)
    {
        foreach ($adjustments as $adj)
        {
            try
            {
                /*
                 * If merchant is not on reverse shadow, and is not present in $forcedMerchantIds array,
                 * only then skip the merchant.
                 */
                if ($adj->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === false)
                {
                    $this->trace->info(
                        TraceCode::LEDGER_STATUS_CRON_SKIP_MERCHANT_NOT_REVERSE_SHADOW,
                        [
                            'adjustment_id' => $adj->getPublicId(),
                            'merchant_id'   => $adj->getMerchantId(),
                        ]
                    );
                    continue;
                }

                if($skipChecks === false)
                {
                    if(in_array($adj->getPublicId(), $blacklistIds) === true)
                    {
                        $this->trace->info(
                            TraceCode::LEDGER_STATUS_CRON_SKIP_BLACKLIST_ADJUSTMENT,
                            [
                                'adjustment_id' => $adj->getPublicId(),
                            ]
                        );
                        continue;
                    }
                }

                $this->trace->info(
                    TraceCode::LEDGER_STATUS_CRON_ADJUSTMENT_INIT,
                    [
                        'adjustment_id' => $adj->getPublicId(),
                    ]
                );

                $event = self::getLedgerEventBasedOnAdjustment($adj);
                $ledgerRequest = (new LedgerAdjustment())->createPayloadForJournalEntry($adj, $event);

                (new LedgerStatus($this->mode, $ledgerRequest, null, false))->handle();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::LEDGER_STATUS_CRON_ADJUSTMENT_FAILED,
                    [
                        'adjustment_id' => $adj->getPublicId(),
                    ]
                );

                $this->trace->count(Metric::LEDGER_STATUS_CRON_FAILURE_COUNT,
                                    [
                                        'environment'   => $this->app['env'],
                                        'entity'        => 'adjustment'
                                    ]);

                continue;
            }
        }
    }

    public function createTransactionInLedgerReverseShadowFlow(string $entityId, array $ledgerResponse)
    {
        $adjustment = $this->repo->adjustment->find($entityId);
        if ($adjustment->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === false)
        {
            throw new Exception\LogicException('Merchant does not have the ledger reverse shadow feature flag enabled'
                , ErrorCode::BAD_REQUEST_MERCHANT_NOT_ON_LEDGER_REVERSE_SHADOW,
                ['merchant_id' => $adjustment->getMerchantId()]);
        }

        $mutex = $this->app['api.mutex'];
        list($entityId, $txnId) = $mutex->acquireAndRelease('adj_' . $entityId,
            function () use ($adjustment, $ledgerResponse)
            {
                $adjustment->reload();
                $journalId = $ledgerResponse["id"];
                $balance = Transaction\Processor\Ledger\Base::getMerchantBalanceFromLedgerResponse($ledgerResponse);

                $tempAdjustment = $adjustment;
                list($adjustment, $txn) = $this->repo->transaction(function() use ($tempAdjustment, $journalId, $balance)
                {
                    $adjustment = clone $tempAdjustment;

                    list ($txn, $feeSplit) = (new Transaction\Processor\Adjustment($adjustment))->createTransactionWithIdAndLedgerBalance($journalId, intval($balance));
                    $this->repo->saveOrFail($txn);

                    // need to update txn id in adj table
                    $this->repo->saveOrFail($adjustment);

                    return [$adjustment, $txn];
                });

                // dispatch event for txn created
                (new Transaction\Core)->dispatchEventForTransactionCreated($txn);
                return [
                    $adjustment->getPublicId(),
                    $txn->getPublicId(),
                ];
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
        return [
            'entity_id' => $entityId,
            'txn_id'    => $txnId
        ];
    }
}
