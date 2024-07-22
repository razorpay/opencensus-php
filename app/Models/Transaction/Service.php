<?php

namespace RZP\Models\Transaction;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Emi;
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
use RZP\Models\RewardPoint;
use RZP\Models\Payment\Refund;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankingAccountStatement;
use RZP\Jobs\Settlement\LedgerReconJob2;
use RZP\Models\FundAccount\Validation\Core;
use RZP\Models\Report\Types\BasicEntityReport;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Models\LedgerOutbox\Core as LedgerOutboxCore;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\Transaction\FeeBreakup\Repository as FeesBreakupRepo;
use RZP\Models\Payout\Processor\DownstreamProcessor\DownstreamProcessor;
use RZP\Models\Base\UniqueIdEntity;

class Service extends Base\Service
{
    /** @var Mutex $mutex */
    protected $mutex;

    /** @var \Illuminate\Contracts\Cache\Store $cache */
    protected $cache;

    const PG_ROUTER_TRANSACTION_FAILURE = 'pg_router_transaction_failure';
    const INTERNAL_TRANSACTION_CREATE_MUTEX_TTL = 60;
    const INTERNAL_TRANSACTION_CREATE_MUTEX_RETRIES = 20;
    const INTERNAL_TRANSACTION_CREATE_MUTEX_MIN_RETRY_DELAY = 1000;
    const INTERNAL_TRANSACTION_CREATE_MUTEX_MAX_RETRY_DELAY = 2000;

    const TRANSACTION_ON_HOLD_TOGGLE = 'transaction_on_hold_toggle';

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->cache = $this->app['cache'];
    }

    public function createFeeBreakupForTransaction($input)
    {
        return (new Transaction\DataMigration())->createFeeBreakupForTransaction($input);
    }

    public function getTransactionMutexresource($payment)
    {
        return $payment->getId()."_transaction";
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
            $this->trace->traceException(
                $e,
                Trace::INFO,
                TraceCode::CREDIT_REPAYMENT_TRANSACTION_DB_EXCEPTION,
                ['input' => $input, 'info' => "creating new transaction"]
            );
        }

        return $this->mutex->acquireAndRelease('credit_repayment_transaction_' . $input[\RZP\Models\CreditRepayment\Entity::ID],
            function() use ($creditRepayment, $input)
            {
                return $this->repo->transaction(function () use ($creditRepayment, $input)
                {
                    $txnId = $input["api_id"];

                    [$txn, $feesplit] = (new Transaction\Processor\CreditRepayment($creditRepayment))->createTransaction($txnId);

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
            $this->trace->traceException(
                $e,
                Trace::INFO,
                TraceCode::CAPITAL_TRANSACTION_DB_EXCEPTION,
                ['input' => $input, 'info' => "creating new transaction"]
            );
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
                    $this->trace->traceException(
                        $e,
                        Trace::INFO,
                        TraceCode::CAPITAL_TRANSACTION_DB_EXCEPTION,
                        ['input' => $input, 'info' => "creating new transaction"]
                    );
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

        $experimentVariable = UniqueIdEntity::generateUniqueId();

        $isExpEnabled = $this->checkIfTransactionOnholdToggleExpEnabled($experimentVariable);

        if ($isExpEnabled === false)
        {
            $failedTransactionUpdate = (new Transaction\Core)->toggleTransactionOnHold($transactionIds, $toggleFlag, $reason);
        }
        else
        {
            $failedTransactionUpdate = (new Transaction\Core)->toggleTransactionOnHoldOnNss($transactionIds, $toggleFlag, $reason);
        }

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

        $txnId = null;

        if (isset($input['transaction_id']) === true)
        {
            $txnId = $input['transaction_id'];
        }

        if (isset($input['payment']['card']) === true)
        {
            $card = (new Card\Entity)->forceFill($input['payment']['card']);

            unset($input['payment']['card']);
        }

        if (isset($input['payment']['reward']) === true)
        {
            $rewardPoint = (new RewardPoint\Entity)->forceFill($input['payment']['reward']);

            $rewardPoint->setExternal(true);

            unset($input['payment']['reward']);

            $payment->reward()->associate($rewardPoint);
        }

        if (isset($input['payment']['source_channel']) === true)
        {
            $payment->setSourceChannel($input['payment']['source_channel']);
        }


        if ((isset($input['payment']['emi_plan']) === true) and
        (isset($input['payment']['source_channel']) === true))
        {
            $emiPlan = (new Emi\Entity)->forceFill($input['payment']['emi_plan']);

            unset($input['payment']['emi_plan']);
        }

        $payment->forceFill($input['payment']);

        if ($payment->isFeeBearerCustomer() === false)
        {
            $payment->setFee(0);
        }

        if ($payment->isCard() === true)
        {
            $payment->card()->associate($card);

            if ((isset($input['payment']['source_channel']) === true) and
                ($input['payment']['source_channel'] === 'in_person'))
            {
                $payment->enablePCPService();
            }
        }


        if ($payment->isEmi() === true)
        {
            $payment->card()->associate($card);
            $payment->emiPlan()->associate($emiPlan);

            if ((isset($input['payment']['source_channel']) === true) and
                ($input['payment']['source_channel'] === 'in_person'))
            {
                $payment->enablePCPService();
            }
        }

        $payment->setExternal(true);

        $resource = $this->getTransactionMutexresource($payment);

        return $this->mutex->acquireAndRelease(
            $resource,
            function () use ($payment, $txnId)
            {
                try
                {
                    $txn = (new Transaction\Core)->createUpdateLedgerTransaction($payment, $txnId);
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
                    $dimensions = (new Payment\Metric)->getDefaultExceptionDimensions($ex);

                    $this->trace->count(self::PG_ROUTER_TRANSACTION_FAILURE, $dimensions);

                    throw $ex;
                }

                return $txn->toArrayPublic();
            },
            self::INTERNAL_TRANSACTION_CREATE_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            self::INTERNAL_TRANSACTION_CREATE_MUTEX_RETRIES,
            self::INTERNAL_TRANSACTION_CREATE_MUTEX_MIN_RETRY_DELAY,
            self::INTERNAL_TRANSACTION_CREATE_MUTEX_MAX_RETRY_DELAY
        );
    }

    public function postInternalTransactionCron(array $input)
    {
        $failureIds = [];
        $successIds = [];

        if (empty($input['cps_routes']) === false and
            empty($input['start_time_offset']) === false and
            empty($input['end_time_offset']) === false and
            isset($input['create_api_transaction']) === true and
            $input['create_api_transaction'] === true)
        {
            $payments = $this->repo->payment->fetchCapturedRearchPaymentsTxnNull($input['cps_routes'], $input['start_time_offset'], $input['end_time_offset']);

            foreach ($payments as $payment)
            {
                try
                {
                    $rearchPayment = $this->repo->payment->findByPublicId($payment->getId());

                    $rearchPayment->setExternal(true);

                    $merchant = $this->repo->merchant->findByPublicId($payment->getMerchantId());

                    $payment->merchant()->associate($merchant);

                    if ($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
                    {
                        array_push($failureIds, [$payment->getId() => "Transaction creation blocked for non reverse shadow mode"]);

                        continue;
                    }

                    $ledgerOutboxCore = new LedgerOutboxCore();

                    $txnId = $ledgerOutboxCore->getAPITxnIDForReverseShadowPayments($payment);

                    if ($txnId === null)
                    {
                        array_push($failureIds, [$payment->getId() => "No journal exists"]);

                        continue;
                    }

                    $txn = (new Transaction\Core)->createUpdateLedgerTransaction($rearchPayment, $txnId);

                    array_push($successIds, $payment->getId());
                }
                catch (\Exception $e)
                {
                    array_push($failureIds, [$rearchPayment->getId() => $e->getMessage()]);
                }
            }

            return ["failures" => $failureIds,
                "success" => $successIds];
        }

        if (isset($input['payments_arr']) === true)
        {
            $paymentsArrString = $input['payments_arr'];

            $paymentsArr = explode(',', $paymentsArrString);

            for ($i = 0; $i < count($paymentsArr); $i++)
            {
                try
                {
                    $currentPaymentId = $paymentsArr[$i];

                    $payment = $this->repo->payment->findByPublicId($currentPaymentId);

                    $payment->setExternal(true);

                    if($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
                    {
                       array_push($failureIds, [ $currentPaymentId => "Transaction creation blocked for reverse shadow mode" ]);

                       continue;
                    }

                    $txn = (new Transaction\Core)->createUpdateLedgerTransaction($payment);

                    array_push($successIds, $currentPaymentId);
                }
                catch(\Exception $e)
                {
                    array_push($failureIds, [$currentPaymentId => $e->getMessage()]);
                }

            }
            return ["failures" => $failureIds,
                "success" => $successIds];
        }

        if (isset($input['payments_balance_arr']) === true and
            isset($input['only_balance_update']) === true and
            $input['only_balance_update'] === true)
        {
            $paymentsArrString = $input['payments_balance_arr'];

            $paymentsArr = explode(',', $paymentsArrString);

            for ($i = 0; $i < count($paymentsArr); $i++)
            {
                try
                {
                    $currentPaymentId = $paymentsArr[$i];

                    $payment = $this->repo->payment->findByPublicId($currentPaymentId);

                    if($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
                    {
                       array_push($failureIds, [ $currentPaymentId => "Transaction creation blocked for reverse shadow mode" ]);

                       continue;
                    }

                    $paymentProcessor = new Payment\Processor\Processor($payment->merchant);

                    $paymentProcessor->handleAsyncUpdateBalanceIfApplicable($payment, $payment->transaction);

                    array_push($successIds, $currentPaymentId);
                }
                catch(\Exception $e)
                {
                    array_push($failureIds, [$currentPaymentId => $e->getMessage()]);
                }

            }
            return ["failures" => $failureIds,
                "success" => $successIds];
        }

        $payments = null;

        if (isset($input['merchant_id']) === true)
        {
            $payments = $this->repo->payment->fetchCapturedRearchPaymentsTxnNullForMerchant($input['merchant_id']);
        }
        elseif(empty($input['cps_routes']) === false and empty($input['start_time_offset']) === false and empty($input['end_time_offset']) === false)
        {
            $payments = $this->repo->payment->fetchCapturedRearchPaymentsTxnNull($input['cps_routes'], $input['start_time_offset'], $input['end_time_offset']);
        }


        foreach ($payments as $payment)
        {
            try
            {
                $rearchPayment = $this->repo->payment->findByPublicId($payment->getId());

                $rearchPayment->setExternal(true);

                $merchant =  $this->repo->merchant->findByPublicId($payment->getMerchantId());

                $payment->merchant()->associate($merchant);

                if($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
                {
                    array_push($failureIds, [ $payment->getId() => "Transaction creation blocked for reverse shadow mode" ]);

                    continue;
                }

                $txn = (new Transaction\Core)->createUpdateLedgerTransaction($rearchPayment);

                array_push($successIds, $payment->getId());
            }
            catch(\Exception $e)
            {
                array_push($failureIds, [$rearchPayment->getId() => $e->getMessage()]);
            }
        }

        return ["failures" => $failureIds,
            "success" => $successIds];


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

    public function createFeesBreakupPartition() : array
    {
        try
        {
            (new FeesBreakupRepo())->managePartitions();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::TABLE_PARTITION_ERROR);

            return ['success' => false];
        }

        return ['success' => true];
    }

    /***
     * This function is used to compare api transaction and transaction entity created from ledger response
     * @param array $apiTxnArray api db transaction array
     * @param array $ledgerTxnArray scrooge db transaction array
     * @param array $extraTrace additional trace info to log
     * @return void
     */
    public function compareTransactionsAndLogDifference($apiTxnArray, $ledgerTxnArray, array $extraTrace = [])
    {
        // Compare ledger and api response
        $this->trace->info(TraceCode::LEDGER_AND_API_TXN_COMPARE, [
            'route_name'  => $this->app['api.route']->getCurrentRouteName(),
            'extra_trace' => $extraTrace,
        ]);

        try
        {
            $diff = $this->differenceKeysOfTransactions($apiTxnArray[0], $ledgerTxnArray[0]);

            if (empty($diff) === false)
            {
                $this->trace->info(TraceCode::LEDGER_AND_API_TXN_INCONSISTENCY, [
                    'diff'        => $diff,
                    'route_name'  => $this->app['api.route']->getCurrentRouteName(),
                    'extra_trace' => $extraTrace,
                ]);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::COMPARE_API_LEDGER_TXN_FETCH_ERROR,
                [
                    'api'           => $apiTxnArray,
                    'ledger'        => $ledgerTxnArray,
                    'extra_trace'   => $extraTrace,
                ]);
        }
    }

    public function differenceKeysOfTransactions($apiTxnArray, $ledgerTxnArray) : array
    {
        $responseDiff = [];

        $fieldsToCompare = [TransactionEntity::ID,
            TransactionEntity::ENTITY_ID,
            TransactionEntity::MERCHANT_ID,
            TransactionEntity::AMOUNT,
            TransactionEntity::CURRENCY,
            TransactionEntity::CREDIT,
            TransactionEntity::DEBIT,
            TransactionEntity::BALANCE,
            TransactionEntity::CREATED_AT,
            TransactionEntity::TYPE,
            TransactionEntity::FEE,
            TransactionEntity::TAX,
            TransactionEntity::CHANNEL,
            TransactionEntity::CREDITS,
            TransactionEntity::CREDIT_TYPE,
            TransactionEntity::BALANCE_ID,
            TransactionEntity::UPDATED_AT,
            TransactionEntity::POSTED_AT];

        foreach ($apiTxnArray as $key => $value)
        {
            if (in_array($key, $fieldsToCompare) === false)
            {
                continue;
            }

            if ($ledgerTxnArray[$key] != $value)
            {
                $responseDiff[$key]["ledger"]   = $ledgerTxnArray[$key];
                $responseDiff[$key]["api"]      = $value;
            }
        }

        return $responseDiff;
    }

    public function checkIfTransactionOnholdToggleExpEnabled($txnId): bool
    {
        $variant = \App::getFacadeRoot()->razorx->getTreatment(
            $txnId,
            self::TRANSACTION_ON_HOLD_TOGGLE,
            $this->mode ?? Mode::LIVE
        );

        $isExperimentEnabled = ($variant === 'on');

        $this->trace->info(TraceCode::TRANSACTION_ON_HOLD_TOGGLE_EXP_CHECK,
            [
                'merchant'               => $txnId,
                'isExperimentEnabled'    => $isExperimentEnabled
            ]);

        return $isExperimentEnabled;
    }
}
