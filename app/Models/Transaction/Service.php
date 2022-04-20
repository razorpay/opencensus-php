<?php

namespace RZP\Models\Transaction;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Trace\Tracer;
use RZP\Models\Payout;
use RZP\Services\Mutex;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Reversal;
use RZP\Models\External;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Base\JitValidator;
use RZP\Models\Transaction;
use RZP\Models\Pricing\Fee;
use RZP\Base\RuntimeManager;
use RZP\Models\Payment\Refund;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankingAccountStatement;
use RZP\Jobs\Settlement\LedgerReconJob2;
use RZP\Models\FundAccount\Validation\Core;
use RZP\Models\Report\Types\BasicEntityReport;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Models\Payout\Processor\DownstreamProcessor\DownstreamProcessor;

class Service extends Base\Service
{
    /** @var Mutex $mutex */
    protected $mutex;

    /** @var \Illuminate\Contracts\Cache\Store $cache */
    protected $cache;

    const PG_ROUTER_TRANSACTION_FAILURE = 'pg_router_transaction_failure';

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->cache = $this->app['cache'];
    }

    public function settlementFixer()
    {
        return $this->repo->transaction(function()
        {
            return (new BugFixer)->settlementFixerInTxn();
        });
    }

    public function createFeeBreakupForTransaction($input)
    {
        return (new Transaction\DataMigration())->createFeeBreakupForTransaction($input);
    }

    /**
     * used by capital-collections service to create transactions
     *
     * @param $input
     * @return array
     */
    public function createCreditRepaymentTransaction($input)
    {
        // $span = Tracer::startSpan(['name' => 'transaction.service.createCreditRepaymentTransaction']);
        // Tracer::addAttributes($input);
        // $scope = Tracer::withSpan($span);

        $this->trace->count(\RZP\Models\CreditRepayment\Metric::CREDIT_REPAYMENT_TRANSACTION_CREATE_REQUEST);
        $this->trace->info(TraceCode::CREDIT_REPAYMENT_TRANSACTION_CREATE_REQUEST, $input);

        (new JitValidator)->rules(\RZP\Models\CreditRepayment\Validator::$createTransactionInput)
                            ->caller($this)
                            ->input($input)
                            ->validate();

        $creditRepayment = new \RZP\Models\CreditRepayment\Entity($input);

        $creditRepayment->merchant()->associate($this->repo->merchant->find($input['merchant_id']));

        // find transaction if it was created already for this credit_repayment.
        // return public response if that transaction already exists
        // else create new transaction

        try
        {
            $txn = $this->repo->transaction->fetchByEntityAndAssociateMerchant($creditRepayment);

            $this->trace->count(\RZP\Models\CreditRepayment\Metric::CREDIT_REPAYMENT_TRANSACTION_ALREADY_CREATED);
            $this->trace->debug(TraceCode::CREDIT_REPAYMENT_TRANSACTION_ALREADY_CREATED, $input);
            // $scope->close();

            return $txn->toArrayPublic();
        }
        catch (DbQueryException $e)
        {
            // Do nothing. continue with creation of new transaction
        }

        return $this->mutex->acquireAndRelease('credit_repayment_transaction_' . $input[\RZP\Models\CreditRepayment\Entity::ID],
            function() use ($creditRepayment, $input)
            {
                return $this->repo->transaction(function () use ($creditRepayment, $input)
                {
                    [$txn, $feesplit] = (new Transaction\Processor\CreditRepayment($creditRepayment))->createTransaction();

                    $this->repo->saveOrFail($txn);

                    $this->trace->count(\RZP\Models\CreditRepayment\Metric::CREDIT_REPAYMENT_TRANSACTION_CREATED);
                    $this->trace->debug(TraceCode::CREDIT_REPAYMENT_TRANSACTION_CREATED, $input);
                    // $scope->close();

                    return $txn->toArrayPublic();
                });
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
            0,
            100,
            200,
            true);
    }

    public function createCapitalTransaction($input)
    {
        $this->trace->count(\RZP\Models\CapitalTransaction\Metric::CAPITAL_TRANSACTION_CREATE_REQUEST);
        $this->trace->info(TraceCode::CAPITAL_TRANSACTION_CREATE_REQUEST, $input);

        (new JitValidator)->rules(\RZP\Models\CapitalTransaction\Validator::$createTransactionInput)
            ->caller($this)
            ->input($input)
            ->validate();

        $capitalTxn = new \RZP\Models\CapitalTransaction\Entity($input);

        $capitalTxn->merchant()->associate($this->repo->merchant->find($input['merchant_id']));

        $capitalTxn->balance()->associate($this->repo->balance->findOrFailById($input['balance_id']));

        // find transaction if it was created already for this entity.
        // return public response if that transaction already exists
        // else create new transaction

        try
        {
            $txn = $this->repo->transaction->fetchByEntityAndAssociateMerchant($capitalTxn);

            $this->trace->count(\RZP\Models\CapitalTransaction\Metric::CAPITAL_TRANSACTION_ALREADY_CREATED);
            $this->trace->debug(TraceCode::CAPITAL_TRANSACTION_ALREADY_CREATED, $input);

            return $txn->toArrayPublic();
        }
        catch (DbQueryException $e)
        {
            // Do nothing. continue with creation of new transaction
        }

        return $this->mutex->acquireAndRelease('capital_transaction_' . $input[\RZP\Models\CapitalTransaction\Entity::ID],
            function () use ($capitalTxn, $input)
            {
                return $this->repo->transaction(function () use ($capitalTxn, $input)
                {
                    [$txn, $feesplit] = (new Transaction\Processor\CapitalTransaction($capitalTxn))->createTransaction();

                    $this->repo->saveOrFail($txn);

                    $this->trace->count(\RZP\Models\CapitalTransaction\Metric::CAPITAL_TRANSACTION_CREATED);
                    $this->trace->debug(TraceCode::CAPITAL_TRANSACTION_CREATED, $input);

                    return $txn->toArrayPublic();
                });
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
            0,
            100,
            200,
            true);
    }

    public function createMultipleCapitalRepaymentTransactions($input)
    {
        // this expects 'repayment_id' as a key with array of repayment_breakups.
        // if 'repayment_id' is present in cache, then it's assumed that transactions for
        // repayment_breakups have already been saved.

        if (isset($input['repayment_id']) === false)
        {
            throw new Exception\BadRequestValidationFailureException('repayment_id not present in input', null, $input);
        }

        $repaymentId = $input['repayment_id'];

        $this->trace->count(\RZP\Models\CapitalTransaction\Metric::CAPITAL_TRANSACTION_CREATE_REQUEST);
        $this->trace->info(TraceCode::CAPITAL_TRANSACTION_CREATE_REQUEST, $input);

        return $this->mutex->acquireAndReleaseStrict('capital_transaction_' . $repaymentId, function () use ($input, $repaymentId)
            {
                if (isset($input['repayment_breakups']) === false)
                {
                    throw new Exception\BadRequestValidationFailureException('repayment_breakups not present in input', null, $input);
                }

                $repaymentBreakups = $input['repayment_breakups'];

                $repaymentCacheKey = 'capital_transaction_repayment_' . $repaymentId;

                if (empty($this->cache->get($repaymentCacheKey)) === false)
                {
                    // this repayment was already processed. return success response.
                    return [];
                }

                // check if txn with same repayment_breakup.id already exists
                // TODO: this check is not needed now. remove if everything works.
                $capitalTxn = new \RZP\Models\CapitalTransaction\Entity($repaymentBreakups[0]);
                $capitalTxn->merchant()->associate($this->repo->merchant->find($repaymentBreakups[0]['merchant_id']));
                $capitalTxn->balance()->associate($this->repo->balance->findOrFailById($repaymentBreakups[0]['balance_id']));

                // find transaction if it was created already for this entity.
                // else create new transaction
                try
                {
                    $this->repo->transaction->fetchByEntityAndAssociateMerchant($capitalTxn);

                    $this->trace->count(\RZP\Models\CapitalTransaction\Metric::CAPITAL_TRANSACTION_ALREADY_CREATED);
                    $this->trace->debug(TraceCode::CAPITAL_TRANSACTION_ALREADY_CREATED, $input);

                    return [];
                }
                catch (DbQueryException $e)
                {
                    // Do nothing. continue with creation of new transaction
                }

                return $this->repo->transaction(function () use ($repaymentBreakups, $repaymentCacheKey)
                {
                    foreach ($repaymentBreakups as $repaymentBreakup)
                    {
                        $capitalTxn = new \RZP\Models\CapitalTransaction\Entity($repaymentBreakup);
                        $capitalTxn->merchant()->associate($this->repo->merchant->find($repaymentBreakup['merchant_id']));
                        $capitalTxn->balance()->associate($this->repo->balance->findOrFailById($repaymentBreakup['balance_id']));

                        [$txn, $feesplit] = (new Transaction\Processor\CapitalTransaction($capitalTxn))->createTransaction();

                        $this->repo->saveOrFail($txn);

                        $this->trace->count(\RZP\Models\CapitalTransaction\Metric::CAPITAL_TRANSACTION_CREATED);
                        $this->trace->debug(TraceCode::CAPITAL_TRANSACTION_CREATED, $repaymentBreakup);
                    }

                    // cache response of a repayment for 24hours (in seconds)
                    $this->cache->put($repaymentCacheKey, $repaymentCacheKey, 24 * 60 * 60);

                    return [];
                });
            });
    }

    public function updateMultipleTransactions(array $input)
    {
        (new Validator())->validateInput('unsettled_txns_channel_update', $input);

        $channel    = $input['channel'];

        $merchantId = $input['merchant_id'];

        return (new Transaction\BulkUpdate)->updateMultipleTransactions($merchantId, $channel);
    }

    public function markTransactionPostpaid($input)
    {
        $this->trace->info(
            TraceCode::TRANSACTIONS_TO_POSTPAID_INPUT,
            $input);

        $transactionIds = $input['transaction_ids'];

        $successIds = [];

        $failedIds = [];

        $transactions = $this->repo->transaction->fetchMultipleTransactionsFromIds($transactionIds);
        $transactionCore = (new Transaction\Core);

        foreach ($transactions as $transaction)
        {
            try
            {
                $transactionCore->markTransactionPostpaid($transaction);
                $successIds[] = $transaction->getId();
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::TRANSACTIONS_TO_POSTPAID_FAILED,
                    ['transaction_id' => $transaction->getId()]
                );

                $failedIds[] = $transaction->getId();
            }
        }

        $response = [
            'success_ids' => $successIds,
            'failed_ids'  => $failedIds,
        ];

        $this->trace->info(
            TraceCode::TRANSACTIONS_TO_POSTPAID_RESPONSE,
            $response);

        return $response;
    }

    public function fixSettled(string $entity, array $input): array
    {
        $this->trace->info(
            TraceCode::FUND_ACCOUNT_VALIDATION_TRANSACTION_FIX,
            $input);

        return $this->app['api.mutex']->acquireAndRelease(
            'fix_settled_column_for_fav',
            function () use ($entity, $input)
            {
                $count = $input['count'] ?? 200;

                $txnIds =  $this->repo->transaction->fetchSettledTransactionsWithoutSettlementId($entity, $count);

                $this->repo->transaction->updateSettledToFalse($entity, $txnIds);

                return [
                    'count'             => $count,
                    'txns_processed'    => $txnIds,
                ];
            });
    }

    /**
     * This Method is used to put the transaction on hold passed in the input
     * @param array $input
     * @return array
     */
    public function toggleTransactionHold(array $input)
    {
        (new Validator)->validateInput('toggle_transaction_hold', $input);

        $this->trace->info(
          TraceCode::TOGGLE_TRANSACTION_HOLD,
          [
                'transaction_ids' => $input['transaction_ids'],
                'reason_for_hold' => $input['reason'],
          ]);

        return $this->toggleTransactionFlag($input['transaction_ids'], true, $input['reason']);
    }

    /**
     * This method is used to release the transactions passed in the input
     * @param array $input
     * @return array
     */
    public function toggleTransactionRelease(array $input)
    {
        (new Validator)->validateInput('toggle_transaction_release', $input);

        $this->trace->info(
            TraceCode::TOGGLE_TRANSACTION_RELEASE,
            [
                'transaction_ids' => $input['transaction_ids'],
            ]);

        return $this->toggleTransactionFlag($input['transaction_ids'], false, null);
    }

    /**
     * This methods basically used to toggle the on_hold flag of the transaction Ids
     * @param array $transactionIds
     * @param bool $toggleFlag
     * @param string $reason
     * @return array
     */
    public function toggleTransactionFlag(array $transactionIds, bool $toggleFlag, $reason = null)
    {
        $requestCount = sizeof($transactionIds);

        $failedTransactionUpdate = (new Transaction\Core)->toggleTransactionOnHold($transactionIds, $toggleFlag, $reason);

        $failedCount = sizeof($failedTransactionUpdate);

        $successCount = $requestCount - $failedCount;

        $response = [
            'total_requests'        => $requestCount,
            'successfully_updated'  => $successCount,
            'failed'                => $failedCount,
        ];

        $this->trace->info(
            TraceCode::TOGGLE_TRANSACTION_COMPLETE,
            [
                'response'                      => $response,
                'transactions_failed_to_update' => $failedTransactionUpdate,
            ]);

        return $response;
    }

    public function postInternalTransaction(array $input)
    {
        $payment = new Payment\Entity();

        if (isset($input['payment']['card']) === true)
        {
            $card = (new Card\Entity)->forceFill($input['payment']['card']);

            unset($input['payment']['card']);
        }

        $payment->forceFill($input['payment']);

        if ($payment->isCard() === true)
        {
            $payment->card()->associate($card);
        }

        $payment->setExternal(true);

        try
        {
            $txn = (new Transaction\Core)->createUpdateLedgerTransaction($payment);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::PG_ROUTER_TRANSACTION_FAILURE,
                [
                    'data' => $ex->getMessage()
                ]);
            $dimensions =  (new Payment\Metric)->getDefaultExceptionDimensions($ex);

            $this->trace->count(self::PG_ROUTER_TRANSACTION_FAILURE, $dimensions);

            throw $ex;
        }


        return $txn->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {

        $balanceType = $input['balance_type'] ?? Merchant\Balance\Type::BANKING;
        $balanceAccountType = $input['balance_account_type'] ?? Merchant\Balance\AccountType::SHARED;

        $this->trace->info(
            TraceCode::LEDGER_TRANSACTIONS_FETCH_REQUEST,
            [
                'merchant_id_count'     => count($input[Entity::MERCHANT_ID]),
                'start_time'            => $input['from'],
                'end_time'              => $input['to'],
                'limit'                 => $input['count'],
                'offset'                => $input['skip'],
                'last_processed_txn_id' => $input['last_processed_txn_id'],
                'balance_type'          => $balanceType,
                'balance_account_type'  => $balanceAccountType,
            ]);

        $startTimeMs = round(microtime(true) * 1000);

        /*
         * These merchant ids will have either DA or VA merchants. In case of VA merchants,
         * we will remove merchants which have LEDGER_REVERSE_SHADOW feature flag.
         */
        $ledgerShadowMerchantIds = $input[Entity::MERCHANT_ID];

        if ($balanceAccountType === Merchant\Balance\AccountType::SHARED)
        {
            $ledgerReverseShadowMerchantIds = $this->repo->feature->getMerchantIdsHavingFeature(Feature\Constants::LEDGER_REVERSE_SHADOW, $input[Entity::MERCHANT_ID]);
            $ledgerShadowMerchantIds = array_diff($input[Entity::MERCHANT_ID], $ledgerReverseShadowMerchantIds);
        }

        $this->trace->info(
            TraceCode::LEDGER_TRANSACTIONS_SHADOW_MERCHANTS_COUNT,
            [
                'shadow_merchant_id_count' => count($ledgerShadowMerchantIds),
            ]);

        $txn = $this->repo->transaction->fetchBankingTransactionsForLedgerRecon(
            $ledgerShadowMerchantIds,
            $input['from'],
            $input['to'],
            $input['count'],
            $input['skip'],
            $input['last_processed_txn_id'],
            $balanceType,
            $balanceAccountType
        );

        $endTimeMs = round(microtime(true) * 1000);

        // this will help us to know the query running time
        $this->trace->info(
            TraceCode::LEDGER_TRANSACTIONS_FETCH_RESPONSE,
            [
                'query_execution_time_ms' => $endTimeMs - $startTimeMs,
                'response_count'          => count($txn),
            ]);

        // This is because $txn->toArrayPublic() is unsetting the required fields from response
        $resp[Entity::ENTITY] = 'collection';
        $resp['count'] = count($txn);
        $resp['items'] = $txn->toArray();

        return $resp;
    }

    /**
     * @throws Exception\RuntimeException
     */
    public function updateEntitiesWithTransaction(array $input)
    {
        $this->trace->info(
            TraceCode::LEDGER_TRANSACTIONS_WEBHOOK_REQUEST,
            [
                'request'       => $input,
                'mode'          => $this->mode
            ]);

        $startTimeMs = round(microtime(true) * 1000);

        $journalId = null;
        try {
            $ledgerResponse = $input[Transaction\Processor\Ledger\Base::LEDGER_RESPONSE];
            $transactorId = $ledgerResponse[Transaction\Processor\Ledger\Base::TRANSACTOR_ID];

            $entityInfoArray = explode('_', $transactorId);
            $transactorEvent = $ledgerResponse[Transaction\Processor\Ledger\Base::TRANSACTOR_EVENT];
            $entityPrefix = $entityInfoArray[0];
            $entityID = $entityInfoArray[1];

            $journalId = $ledgerResponse["id"];
            $balance = Transaction\Processor\Ledger\Base::getMerchantBalanceFromLedgerResponse($ledgerResponse, Transaction\Processor\Ledger\Base::MERCHANT_DA);

            switch ($entityPrefix)
            {
                case Payout\Entity::getSign():

                    switch ($transactorEvent)
                    {
                        case Transaction\Processor\Ledger\Payout::DA_EXT_PAYOUT_PROCESSED:
                        case Transaction\Processor\Ledger\Payout::DA_EXT_FEE_PAYOUT_PROCESSED:
                            // ext to payout processed webhook
                            $this->updateTxnIdForExternalToPayoutProcessedLedgerEvent($transactorId, $journalId);
                            break;

                        default:
                            // payout processed webhook
                            $this->updateTxnIdForPayoutProcessedLedgerEvent($transactorId, $entityID, $journalId, $balance);
                    }

                    break;

                case Reversal\Entity::getSign():

                    switch ($transactorEvent) {
                        case Transaction\Processor\Ledger\Payout::DA_EXT_PAYOUT_REVERSED:
                        case Transaction\Processor\Ledger\Payout::DA_EXT_FEE_PAYOUT_REVERSED:
                            // ext to payout reversed webhook
                            $this->updateTxnIdForExternalToPayoutReversedLedgerEvent($transactorId, $journalId);
                            break;

                        default:
                            // payout reversed webhook
                            $this->updateTxnIdForPayoutReversedLedgerEvent($transactorId, $entityID, $journalId, $balance);
                    }

                    break;

                case External\Entity::getSign():
                    // external webhook
                    $this->updateTxnIdForExternalLedgerEvent($transactorId, $entityID, $journalId, $balance);

                    break;

                default:
                    $this->trace->traceException(
                        null,
                        Trace::ERROR,
                        TraceCode::LEDGER_TRANSACTIONS_WEBHOOK_INVALID_REQUEST,
                        [
                            'request' => $input,
                            'mode' => $this->mode
                        ]
                    );
                    throw new Exception\RuntimeException(
                        'Invalid entity received in request',
                        [
                            'journal_id' => $journalId,
                        ]
                    );
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::LEDGER_TRANSACTIONS_WEBHOOK_EXCEPTION,
                [
                    'request' => $input,
                    'mode' => $this->mode
                ]
            );
            throw new Exception\RuntimeException(
                'Exception while processing request:'.$ex->getTraceAsString(),
                [
                    'journal_id' => $journalId,
                ]
            );
        }

        $endTimeMs = round(microtime(true) * 1000);

        $this->trace->info(TraceCode::LEDGER_TRANSACTIONS_WEBHOOK_RESPONSE, [
            'journal_id'                => $journalId,
            'execution_time_ms'         => $endTimeMs - $startTimeMs,
        ]);

        return [
            'journal_id' => $journalId,
        ];
    }

    protected function externalToPayoutOrReversalRelinking($basEntity)
    {
        $this->trace->info(
            TraceCode::RELINKING_EXT_TO_SOURCE_ENTITY,
            [
                'bas_id'      => $basEntity->getId(),
            ]);

        $createExternalSource = false;
        $remarks = null;
        if ($basEntity->isTypeDebit() === true)
        {
            // check for payouts
            $payout = (new BankingAccountStatement\Core)->fetchExistingPayoutForAccountStatement($basEntity, $createExternalSource, $remarks);
            if ($createExternalSource === true or $payout === null)
            {
                return null;
            }

            if ($payout->hasTransaction() === true)
            {
                $this->trace->info(
                    TraceCode::EXT_TRANSACTION_ALREADY_LINKED_WITH_PAYOUT,
                    [
                        'payout_id'      => $payout->getId(),
                        'transaction_id' => $payout->getTransactionId(),
                    ]);
                return null;
            }

            return $payout;
        }

        // check for reversal
        $reversal = (new BankingAccountStatement\Core)->fetchExistingReversalIfPresent($basEntity, $createExternalSource, $remarks);
        if ($createExternalSource === true or $reversal === null)
        {
            // nothing to relink if no reversal is found
            return null;
        }

        if ($reversal->hasTransaction() === true)
        {
            $this->trace->info(
                TraceCode::EXT_TRANSACTION_ALREADY_LINKED_WITH_REVERSAL,
                [
                    'reversal_id'    => $reversal->getId(),
                    'transaction_id' => $reversal->getTransactionId(),
                ]);
            return null;
        }

        return $reversal;
    }

    protected function updateTxnIdForExternalToPayoutProcessedLedgerEvent($transactorId, $journalId)
    {
        // Ledger webhook for relink ext to payout event
        $payout = $this->repo->payout->findByPublicId($transactorId);

        if ($this->merchantHasDALedgerReverseShadowFeature($payout) === false)
        {
            $this->trace->info(TraceCode::LEDGER_WEBHOOK_FOR_MERCHANT_NOT_ON_REVERSE_SHADOW, [
                'entity_name'       => 'payout',
                'entity_id'         => $payout->getId(),
                'journal_id'        => $journalId,
            ]);
            return;
        }

        // Fetch BAS for this payout
        $bas = null;
        if (empty($payout->getUtr()) === false)
        {
            $bas = $this->repo->banking_account_statement->fetchByUtrForPayout($payout);
        }
        if ($bas === null)
        {
            $bas = $this->repo->banking_account_statement->fetchByCmsRefNumForPayout($payout);
        }

        if ($payout->hasTransaction() === true)
        {
            $this->trace->info(TraceCode::PAYOUT_ALREADY_LINKED, [
                'payout_id'                 => $payout->getId(),
                'linked_transaction_id'     => $payout->getTransactionId(),
            ]);
            return;
        }

        $externalTransaction = $this->repo->transaction(function () use ($payout, $bas, $journalId) {
            $this->trace->info(TraceCode::LEDGER_TRANSACTIONS_WEBHOOK_RELINK_EXT_TO_PAYOUT, ['payout_id' => $payout->getId()]);
            $externalTransaction = $bas->transaction;

            if ($externalTransaction === null)
            {
                throw new Exception\LogicException(
                    'bas row selected is not linked to any transaction!',
                    ErrorCode::SERVER_ERROR_TRANSACTION_WRONG_SOURCE,
                    [
                        'bas_id'            => $bas->getId(),
                        'payout_id'         => $payout->getId(),
                    ]);
            }

            $source = $externalTransaction->source;

            if ($source->getEntity() !== Constants\Entity::EXTERNAL)
            {
                throw new Exception\LogicException(
                    'payout transaction created for some other source other than external!',
                    ErrorCode::SERVER_ERROR_TRANSACTION_WRONG_SOURCE,
                    [
                        'transaction_id'    => $externalTransaction->getId(),
                        'bas_id'            => $bas->getId(),
                        'payout_id'         => $payout->getId(),
                    ]);
            }

            $basEntity = $externalTransaction->bankingAccountStatement;

            // For this ledger transactor event: A new transaction gets created on ledger side, but same transaction  (of external) was relinked to payout on API side
            // se we relink the payout and bas with new transaction id (say journal id) in RX DA<>Ledger integration
            (new Payout\Core)->updateTransactionAndSourceToPayout($payout, $externalTransaction, TraceCode::TXN_FOUND_FOR_PAYOUT_PROCESSED_IN_LEDGER_WEBHOOK);

            $payout->setTransactionId($journalId);
            $this->repo->saveOrFail($payout);
            $basEntity->setTransactionId($journalId);
            $this->repo->saveOrFail($basEntity);

            return $externalTransaction;
        });

        $externalTransaction->reload();
        $transactionForMerchantWebhook = clone $externalTransaction;
        $transactionForMerchantWebhook->setId($journalId);
        (new Transaction\Core)->dispatchEventForTransactionCreatedWithoutEmailOrSmsNotification($transactionForMerchantWebhook);
    }

    protected function updateTxnIdForPayoutProcessedLedgerEvent($transactorId, $entityID, $journalId, $balance)
    {
        // payout processed webhook
        $payout = $this->repo->payout->findByPublicId($transactorId);

        if ($this->merchantHasDALedgerReverseShadowFeature($payout) === false)
        {
            $this->trace->info(TraceCode::LEDGER_WEBHOOK_FOR_MERCHANT_NOT_ON_REVERSE_SHADOW, [
                'entity_name'       => 'payout',
                'entity_id'         => $payout->getId(),
                'journal_id'        => $journalId,
            ]);
            return;
        }

        $basEntity = $this->repo->banking_account_statement->fetchByEntityIDAndEntityType($entityID, 'payout', $payout->getChannel());

        if ($payout->hasTransaction() === true)
        {
            $this->trace->info(TraceCode::PAYOUT_ALREADY_LINKED, [
                'payout_id'             => $payout->getId(),
                'linked_transaction_id' => $payout->getTransactionId(),
            ]);
            return;
        }

        $downstreamProcessor = new DownstreamProcessor('fund_account_payout', $payout, $this->mode);
        $subProcessor = $downstreamProcessor->getSubProcessorClass();

        $this->repo->transaction(function () use ($payout, $basEntity, $journalId, $balance, $subProcessor) {
            // create transaction and update balance
            $transaction = $subProcessor->processTransactionWithIdAndLedgerBalance($payout, $journalId, intval($balance));

            // link transaction with entity
            $this->repo->saveOrFail($payout);

            // link with bas
            if ($basEntity->hasTransaction() === true)
            {
                $this->trace->info(TraceCode::BAS_ALREADY_LINKED, [
                    'bas_id'                => $basEntity->getId(),
                    'linked_transaction_id' => $basEntity->getTransactionId(),
                ]);
            }
            else
            {
                $basEntity->transaction()->associate($transaction);
                if ($basEntity->getPostedDate() !== null)
                {
                    (new Transaction\Core)->updatePostedDate($payout, $basEntity->getPostedDate());
                }
                $this->repo->saveOrFail($basEntity);
            }
        });

        (new BankingAccountStatement\Core())->fireWebhooksAfterSuccessfulMappingOfSourceEntity($payout, true, true);
    }

    protected function updateTxnIdForExternalToPayoutReversedLedgerEvent($transactorId, $journalId)
    {
        // Ledger webhook for relink ext to reversal event
        $reversal = $this->repo->reversal->findByPublicId($transactorId);

        if ($this->merchantHasDALedgerReverseShadowFeature($reversal) === false)
        {
            $this->trace->info(TraceCode::LEDGER_WEBHOOK_FOR_MERCHANT_NOT_ON_REVERSE_SHADOW, [
                'entity_name'       => 'reversal',
                'entity_id'         => $reversal->getId(),
                'journal_id'        => $journalId,
            ]);
            return;
        }

        $bas = $this->repo->banking_account_statement->fetchByUtrForReversal($reversal)->first() ??
            $this->repo->banking_account_statement->fetchByCmsRefNumForReversal($reversal)->first();

        if ($reversal->hasTransaction() === true)
        {
            $this->trace->info(TraceCode::REVERSAL_ALREADY_LINKED, [
                'reversal_id'           => $reversal->getId(),
                'linked_transaction_id' => $reversal->getTransactionId(),
            ]);
            return;
        }

        $externalTransaction = $this->repo->transaction(function () use ($reversal, $bas, $journalId) {
            $this->trace->info(TraceCode::LEDGER_TRANSACTIONS_WEBHOOK_RELINK_EXT_TO_REVERSAL, ['reversal_id' => $reversal->getId()]);
            $externalTransaction = $bas->transaction;

            if ($externalTransaction === null)
            {
                throw new Exception\LogicException(
                    'bas row selected is not linked to any transaction!',
                    ErrorCode::SERVER_ERROR_TRANSACTION_WRONG_SOURCE,
                    [
                        'bas_id'        => $bas->getId(),
                        'reversal_id'   => $reversal->getId(),
                    ]);
            }

            $source = $externalTransaction->source;
            if ($source->getEntity() !== Constants\Entity::EXTERNAL)
            {
                throw new Exception\LogicException(
                    'reversal transaction created for some other source other than external!',
                    ErrorCode::SERVER_ERROR_TRANSACTION_WRONG_SOURCE,
                    [
                        'transaction_id'        => $externalTransaction->getId(),
                        'bas_id'                => $bas->getId(),
                        'reversal_id'           => $reversal->getId(),
                    ]);
            }

            $basEntity = $externalTransaction->bankingAccountStatement;

            // For this ledger transactor event: A new transaction gets created on ledger side, but same transaction  (of external) was relinked to payout on API side
            // se we relink the payout and bas with new transaction id (say journal id) in RX DA<>Ledger integration
            (new Payout\Core)->updateTransactionAndSourceToReversal($reversal, $externalTransaction, TraceCode::TXN_FOUND_FOR_PAYOUT_REVERSED_IN_LEDGER_WEBHOOK);

            $reversal->setTransactionId($journalId);
            $this->repo->saveOrFail($reversal);
            $basEntity->setTransactionId($journalId);
            $this->repo->saveOrFail($basEntity);

            return $externalTransaction;
        });

        $externalTransaction->reload();
        $transactionForMerchantWebhook = clone $externalTransaction;
        $transactionForMerchantWebhook->setId($journalId);
        (new Transaction\Core)->dispatchEventForTransactionCreatedWithoutEmailOrSmsNotification($transactionForMerchantWebhook);
    }

    protected function updateTxnIdForPayoutReversedLedgerEvent($transactorId, $entityID, $journalId, $balance)
    {
        $reversal = $this->repo->reversal->findByPublicId($transactorId);

        if ($this->merchantHasDALedgerReverseShadowFeature($reversal) === false)
        {
            $this->trace->info(TraceCode::LEDGER_WEBHOOK_FOR_MERCHANT_NOT_ON_REVERSE_SHADOW, [
                'entity_name'       => 'reversal',
                'entity_id'         => $reversal->getId(),
                'journal_id'        => $journalId,
            ]);
            return;
        }

        $basEntity = $this->repo->banking_account_statement->fetchByEntityIDAndEntityType($entityID, 'reversal', $reversal->getChannel());

        if ($reversal->hasTransaction() === true)
        {
            $this->trace->info(TraceCode::REVERSAL_ALREADY_LINKED, [
                'reversal_id' => $reversal->getId(),
                'linked_transaction_id' => $reversal->getTransactionId(),
            ]);
            return;
        }

        $txnCore = (new Transaction\Core);

        $this->repo->transaction(function () use ($reversal, $basEntity, $journalId, $balance, $txnCore) {
            // create transaction and update balance
            $transaction = $txnCore->createFromPayoutReversalWithIdAndLedgerBalance($reversal, $journalId, $balance);
            $this->repo->saveOrFail($transaction);

            // link transaction with entity
            $this->repo->saveOrFail($reversal);

            // link with bas
            if ($basEntity->hasTransaction() === true)
            {
                $this->trace->info(TraceCode::BAS_ALREADY_LINKED, [
                    'bas_id'                => $basEntity->getId(),
                    'linked_transaction_id' => $basEntity->getTransactionId(),
                ]);
            }
            else
            {
                $basEntity->transaction()->associate($transaction);
                if ($basEntity->getPostedDate() !== null)
                {
                    (new Transaction\Core)->updatePostedDate($reversal, $basEntity->getPostedDate());
                }
                $this->repo->saveOrFail($basEntity);
            }
        });
        (new BankingAccountStatement\Core())->fireWebhooksAfterSuccessfulMappingOfSourceEntity($reversal, true, true);
    }

    protected function updateTxnIdForExternalLedgerEvent($transactorId, $entityID, $journalId, $balance)
    {
        $external = $this->repo->external->findByPublicId($transactorId);

        if ($this->merchantHasDALedgerReverseShadowFeature($external) === false)
        {
            $this->trace->info(TraceCode::LEDGER_WEBHOOK_FOR_MERCHANT_NOT_ON_REVERSE_SHADOW, [
                'entity_name'       => 'external',
                'entity_id'         => $external->getId(),
                'journal_id'        => $journalId,
            ]);
            return;
        }

        $basEntity = $this->repo->banking_account_statement->fetchByEntityIDAndEntityType($entityID, 'external', $external->getChannel());

        if ($external->hasTransaction() === true)
        {
            $this->trace->info(TraceCode::EXT_ALREADY_LINKED, [
                'external_id'               => $external->getId(),
                'linked_transaction_id'     => $external->getTransactionId(),
            ]);
            return;
        }

        $basEntity = $this->repo->transaction(function () use ($external, $basEntity, $journalId, $balance) {

            // create transaction and update balance
            list ($transaction, $feeSplit) = (new Transaction\Processor\External($external))->createTransactionWithIdAndLedgerBalance($journalId, $balance);
            $this->repo->saveOrFail($transaction);

            // If external was already linked and meanwhile FTS webhook didnt come, there is a rare possibility that this ledger webhook
            // and fts webhook happened at same time and re liniking was missed from both places
            // so bas row was linked to external, now when we hit this route again manually, we will find that external entity now already has a txn but we still
            // want to re link to source entity, so this check will appropriately skip external entity linking and just re link the source entity using externalToPayoutOrReversalRelinking

            // link transaction with entity
            $this->repo->saveOrFail($external);

            // link with bas
            if ($basEntity->hasTransaction() === true)
            {
                $this->trace->info(TraceCode::BAS_ALREADY_LINKED, [
                    'bas_id'                => $basEntity->getId(),
                    'linked_transaction_id' => $basEntity->getTransactionId(),
                ]);
            }
            else
            {
                $basEntity->transaction()->associate($transaction);
                if ($basEntity->getPostedDate() !== null)
                {
                    (new Transaction\Core)->updatePostedDate($external, $basEntity->getPostedDate());
                }
                $this->repo->saveOrFail($basEntity);
            }

            return $basEntity;
        });
        (new BankingAccountStatement\Core())->fireWebhooksAfterSuccessfulMappingOfSourceEntity($external, true, true);

        // Check if FTS webhook already arrived by the time ledger sent this webhook
        // If yes, then the FTS webhook flow wouldn't have been able to relinking from external to payout/reversal due to missing txn
        // So, now that we have webhook from ledger (and thus the transaction), we can complete what the FTS webhook flow couldn't
        // i.e we will check and do the relinking from external to payout/reversal here
        // check if payout has apt status
        $relinkedEntity = $this->externalToPayoutOrReversalRelinking($basEntity);

        if ($relinkedEntity !== null)
        {
            $this->trace->info(TraceCode::SEND_RELINKING_EVENTS_TO_LEDGER_FROM_WEBHOOK_FLOW, [
                'entity_id'     => $relinkedEntity->getPublicId(),
                'entity_name'   => $relinkedEntity->getEntityName()
            ]);

            if ($relinkedEntity->getEntityName() === Constants\Entity::PAYOUT)
            {
                // send ext to reversal event to ledger
                (new Payout\Core)->sendExtToPayoutEventToLedger($relinkedEntity, $basEntity);
            }
            else
            {
                // send ext to reversal event to ledger
                /** @var Payout\Entity $payout */
                $payout = $relinkedEntity->entity;
                (new Payout\Core)->sendExtToReversalEventToLedger($payout, $basEntity, $relinkedEntity);
            }
        }
    }

    // $sourceEntity should be payout, reversal or external
    protected function merchantHasDALedgerReverseShadowFeature($sourceEntity)
    {
        if ($sourceEntity->merchant->isFeatureEnabled(Feature\Constants::DA_LEDGER_REVERSE_SHADOW) === true)
        {
            return true;
        }
        return false;
    }

    public function dispatchIdealLedgerJob(array $input)
    {
        if (empty($input) === true)
        {
            return;
        }

        foreach ($input as $merchantId => $startTimestamp)
        {
            LedgerReconJob2::dispatch($this->mode, $merchantId, $startTimestamp);

            $this->trace->info(
                TraceCode::LEDGER_RECON_JOB_ENQUEUED,
                [
                    'merchant_id'       => $merchantId,
                    'start_timestamp'   => $startTimestamp,
                ]
            );
        }
    }

    public function prepareIdealLedger(string $merchantId, int $startTimestamp)
    {
        RuntimeManager::setMemoryLimit('1024M');

        $values = $this->repo->transaction->getDebitAndCreditValues($merchantId, $startTimestamp);

        $this->trace->info(
            TraceCode::LEDGER_RECON_DATA_FETCHED_FROM_DB,
            [
                'merchant_id'       => $merchantId,
                'start_timestamp'   => $startTimestamp,
            ]
        );

        $endIndex = count($values) - 1;

        $startingBalance = $values[0][Entity::BALANCE];
        $endingBalance = $values[$endIndex][Entity::BALANCE];

        $totalDebit = 0;
        $totalCredit = 0;

        for ($i = 1; $i <= $endIndex; $i++)
        {
            $totalDebit += $values[$i][Entity::DEBIT];
            $totalCredit += $values[$i][Entity::CREDIT];
        }

        $lhs = $totalCredit - $totalDebit;
        $rhs = $endingBalance - $startingBalance;

        if ($lhs === $rhs)
        {
            $output = 'green';
        }
        else
        {
            $output = 'red';
        }

        $this->trace->info(
            TraceCode::IDEAL_LEDGER_DATA,
            [
                'merchant_id'       => $merchantId,
                'start_timestamp'   => $startTimestamp,
                'total_debit'       => $totalDebit,
                'total_credit'      => $totalCredit,
                'lhs'               => $lhs,
                'starting_balance'  => $startingBalance,
                'ending_balance'    => $endingBalance,
                'rhs'               => $rhs,
                'output'            => $output,
            ]
        );

        return $output;
    }
}
