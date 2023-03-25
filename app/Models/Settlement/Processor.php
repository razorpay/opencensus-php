<?php

namespace RZP\Models\Settlement;

use Cache;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Base\RuntimeManager;
use RZP\Constants\Environment;
use RZP\Jobs\Settlement\Create;
use RZP\Models\Merchant\Balance;
use RZP\Models\Settlement\Bucket;
use RZP\Jobs\Transfers\TransferRecon;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant as MerchantModel;
use RZP\Models\Settlement\Merchant as SetlMerchant;

class Processor extends Base\Core
{
    use SettlementTrait;

    protected $setlTime;

    protected $input;

    protected $mutex;

    protected $debug;

    /**
     * Merchant keyed by ID for easy access later
     */
    protected $merchants = null;

    const MUTEX_RESOURCE        = 'SETTLEMENT_PROCESSING_%s_%s';

    const MUTEX_DAILY_RESOURCE  = 'SETTLEMENT_DAILY_PROCESSING_%s';

    const MUTEX_ADHOC_RESOURCE  = 'SETTLEMENT_ADHOC_PROCESSING_%s';

    const MUTEX_SETTLEMENT_STATUS_UPDATE_RESOURCE  = "mutex_settlement_status_update_%s_%s";

    const MUTEX_RETRY_RESOURCE  = 'SETTLEMENT_RETRY_%s';

    const MUTEX_LOCK_TIMEOUT    = 1800;

    const MUTEX_SETTLEMENT_CREATE_RESOURCE = 'SETTLEMENT_CREATE_%s_%s';

    const MUTEX_SETTLEMENT_CREATE_TIMEOUT  = 900;

    const NEGATIVE_BALANCE_ERROR_MESSAGE = 'Something very wrong is happening! Balance is going negative';

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->debug = true;
    }

    /**
     * Initiates settlements for merchants with
     * feature DAILY_SETTLEMENT enabled.
     * These merchants have their settlements created
     * every day, irrespective of holidays, but transfer
     * for these settlements get initiated only on
     * non-holidays at a time defined by the merchant.
     */
    public function processDailySettlements(array $input)
    {
        $this->increaseAllowedSystemLimits();

        $this->setDebugStatus($input);

        $mutexResource = sprintf(self::MUTEX_DAILY_RESOURCE, $this->mode);

        list($shouldProcess, $data) = $this->shouldProcessSettlements($input);

        if ($shouldProcess === true)
        {
            $data = $this->mutex->acquireAndRelease(
                $mutexResource,
                function ()
                {
                    return $this->createDailySettlements();
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }

        return $data;
    }

    public function processFailedSettlements(array $input)
    {
        $this->trace->info(
            TraceCode::SETTLEMENT_RETRY_REQUEST,
            $input
        );

        $this->preSettlementProcessing($input);

        list($shouldProcess, $data) = $this->shouldProcessSettlements($input);

        if ($shouldProcess === true)
        {
            $mutexResource = sprintf(self::MUTEX_RETRY_RESOURCE, $this->mode);

            $data = $this->mutex->acquireAndRelease(
                $mutexResource,
                function () use ($input)
                {
                    return $this->retryProcessFailedSettlements();
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }

        return $data;
    }

    public function process(array $input, $channel, string $balanceType = Balance\Type::PRIMARY)
    {
        $this->setDebugStatus($input);

        $this->preSettlementProcessing($input);

        $useQueue = $this->shouldUseQueue($input);

        $merchantIds = $input['merchant_ids'] ?? [];

        $params =[
            'created_at'          => $input['created_at'] ?? null,
            'settled_at'          => $input['settled_at'] ?? null,
            'initiate_at'         => $input['initiated_at'] ?? null,
            'ignore_time_limit'   => $input['ignore_time_limit'] ?? null
        ];

        list($shouldProcess, $data) = $this->shouldProcessSettlements($input, $channel);

        $startTime = microtime(true);

        if ($shouldProcess === false)
        {
            return $data;
        }

        // TODO: remove channel option form URI and from here post 100% rollout
        //  Make sure these 2 are not running parallel till then
        $mutexResource = sprintf(self::MUTEX_RESOURCE, $this->mode, $channel ?? '');

        $data = $this->mutex->acquireAndRelease(
            $mutexResource,
            function () use ($channel, $useQueue, $merchantIds, $balanceType, $params)
            {
                return $this->processSettlements($channel, $useQueue, $merchantIds, $balanceType, $params);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);

        $count = ($useQueue === true) ? 1 : $data[$channel]['count'];

        //
        // tracing metric with details like
        // - total number of merhcants involved in this process
        // - time taken to complete the process
        //
        $this->trace->count(
            Metric::SETTLEMENTS_INITIATE_EXECUTION_TIME,
            [
                Metric::CHANNEL                 => $channel,
                Metric::BALANCE_TYPE            => $balanceType,
                Metric::USING_QUEUE             => $useQueue,
                Metric::TOTAL_MERCHANTS_COUNT   => $count,
            ], get_diff_in_millisecond($startTime));

        return $data;
    }

    protected function processSettlements($channel, bool $useQueue, array $merchantIds, string $balanceType, array $params = [])
    {
        $this->trace->info(
            TraceCode::SETTLEMENT_INITIATING,
            [
                'timestamp'    => $this->setlTime,
                'time'         => time(),
                'using_queue'  => $useQueue,
                'params'       => $params,
                'merchant_ids' => $merchantIds,
            ]);

        $response = [];

        try
        {
            if ($useQueue === true)
            {
                $response = $this->createSettlementsAsync($merchantIds, $balanceType, $params);
            }
            else
            {
                $response = $this->makeResponse([$channel]);

                if (($this->mode === Mode::TEST) and (in_array($this->env, [Environment::PRODUCTION], true) === true))
                {
                    $setlResponse = $this->createSettlementsForTestMode($channel);
                }
                else
                {
                    $setlResponse = $this->createSettlements($channel, $useQueue, $merchantIds, $params);
                }

                $response[$channel]['count']    += $setlResponse['settlement_count'];
                $response[$channel]['txnCount'] += $setlResponse['txn_count'];
            }

            $this->trace->info(TraceCode::SETTLEMENT_ATTEMPT_ENTITIES_CREATED, $response);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_CREATE_FAILED,
                ['channel' => $channel]
            );

            $this->settlementFailure($channel, $e, TraceCode::SETTLEMENT_CREATE_FAILED);
        }

        return $response;
    }

    protected function retryProcessFailedSettlements(): array
    {
        $response = [];

        (new Validator)->validateInput('retry', $this->input);

        try
        {
            $setlIds = $this->input['settlement_ids'];

            Entity::verifyIdAndStripSignMultiple($setlIds);

            $response = $this->retrySettlements($setlIds);
        }
        catch (\Throwable $e)
        {
            $this->settlementFailure(null, $e, TraceCode::SETTLEMENT_RETRY_FAILED);
        }

        return $response;
    }

    protected function fetchMerchantIdsFromSettlement($settlements)
    {
        $mids = [];

        foreach ($settlements as $settlement) {
            array_push($mids, $settlement->merchant->getId());
        }

        return $mids;
    }

    protected function retrySettlements(array $setlIds)
    {
        $setlAttempts = new Base\PublicCollection;

        $settlements = $this->repo->settlement->getFailedSettlementsForRetry($setlIds);

        $mids = $this->fetchMerchantIdsFromSettlement($settlements);
        $merchantSettleToPartner = (new MerchantModel\Core)->getPartnerBankAccountIdsForSubmerchants($mids);

        $settlementsRetried = [];

        foreach ($settlements as $setl)
        {
            if($setl->getIsNewService() === 1)
            {
                $this->trace->info(
                    TraceCode::SETTLEMENTS_RETRY_SKIPPED,
                    [
                        'settlement_id' => $setl->getId(),
                        'reason'        => "settlement is created in new service",
                    ]
                );
                continue;
            }
            $channel = $setl->getChannel();

            $destinationMerchantId = $this->settlementToPartner($setl->merchant->getId());

            $isAggregateSettlement = (bool) $destinationMerchantId;

            $merchantSettler = new Merchant($setl->merchant, $channel, $this->repo,
                false,  $merchantSettleToPartner, $isAggregateSettlement);

            list($setl, $bankTransferAtpt) = $this->repo->transaction(
                function() use ($merchantSettler, $setl, $merchantSettleToPartner)
                {
                    if ($setl->hasTransaction() === false)
                    {
                        $merchantSettler->createTransaction($setl);
                    }

                    return $merchantSettler->retryFailedSettlement($setl, $merchantSettleToPartner);
                }
            );

            $setlAttempts->push($bankTransferAtpt);

            $settlementsRetried[] = $setl->getId();
        }

        $setlNotRetried = array_diff($setlIds, $settlementsRetried);

        $response['retried_settlements'] = $settlementsRetried;

        if (empty($setlNotRetried) === false)
        {
            $response['retry_skipped_count'] = count($setlNotRetried);

            $response['retry_skipped_settlements'] = $setlNotRetried;
        }

        return $response;
    }

    /**
     * @return array
     */
    protected function createDailySettlements(): array
    {
        $merchantIds      = $this->getMerchantsOnDailySettlement();

        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $settledAtCutoff  = Carbon::tomorrow(Timezone::IST)->getTimestamp();

        $params = [
            'settled_at'        => $settledAtCutoff,
            'daily_settlement'  => true,
        ];

        return $this->pushMerchantsToSettlementQueue(
            $merchantIds, Balance\Type::PRIMARY, $currentTimestamp, $params);
    }

    /**
     * This method must fetch all the entities that will be required to create
     * the desired entities in settlement process. Note, we DO NOT want to
     * eager load any relationship for any of the entities. We instead want
     * to query it separately, and key it on an appropriate value for
     * easy access later.
     *
     * @param int $settledAtCutOff
     * @param string $channel
     * @param array $inMids
     * @param array $notInMids
     * @param boolean $useLimit
     * @param array $params
     * @return mixed
     */
    protected function fetchRequiredEntities(int $settledAtCutOff, string $channel, array $inMids = [],
        array $notInMids = [], $useLimit = false, array $params = [])
    {
        $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_SETTLEMENT_FETCHING_ENTITIES);

        // fetch all the valid transactions from the slave
        $txns = $this->repo
                     ->transaction
                     ->fetchUnsettledTransactions(
                         $settledAtCutOff, $channel, $inMids, $notInMids, true, $useLimit, $params);

        $mids = $txns->pluck(Transaction\Entity::MERCHANT_ID)->toArray();

        $mids = array_unique($mids);

        $this->merchants = $this->repo
                                ->merchant
                                ->findManyWithRelations(
                                    $mids,
                                    ['primaryBalance', 'bankAccount'],
                                    [
                                        MerchantModel\Entity::ID,
                                        MerchantModel\Entity::PARENT_ID
                                    ])
                                ->keyBy(MerchantModel\Entity::ID);

        $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_SETTLEMENT_FETCHED_ENTITIES);

        return $txns;
    }

    protected function makeResponse($channels)
    {
        $response = [];

        foreach ($channels as $channel)
        {
            $response[$channel] = ['count' => 0, 'txnCount' => 0];
        }

        return $response;
    }

    /**
     * @param        $groupedTxns
     * Creates a settlement entity for every group
     *
     * @param string $channel
     * @param array  $merchantSettleToPartner
     * @param array  $params
     * @param string $balanceType
     * merchants settling to partner bank account, key will be merchantId and value will be partner bank account id
     *
     * @param string $balanceType
     *
     * @return array
     * Returns array with keys settlement_count, attempt_count, txn_count
     */
    protected function createSettlementEntities(
        $groupedTxns, string $channel, array $merchantSettleToPartner, array $params = [], string $balanceType = Balance\Type::PRIMARY): array
    {
        $settlements            = new Base\PublicCollection;
        $setlAttempts           = new Base\PublicCollection;
        $txnsSettledCount       = 0;
        $linkedAccountTxnIds    = [];

        foreach ($groupedTxns as $key => $txns)
        {
            $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_SETTLEMENT_ENTITIES_CREATE_START);

            $transactionCount = $txns->count();

            $customProperties = [
                'merchant_id'           => $key,
                'channel'               => $channel,
                'transaction_count'     => $transactionCount,
            ];

            $this->app['diag']->trackSettlementEvent(
                EventCode::SETTLEMENT_CREATION_INITIATED,
                null,
                null,
                $customProperties);

            $merchantId = $txns->first()->getMerchantId();

            $balance = $this->merchants[$merchantId]->getBalanceByTypeOrFail($balanceType);

            list($setl, $setlAttempt) = $this->createSettlementsFromTxns(
                $txns,
                $channel,
                $merchantSettleToPartner,
                $balance,
                $params);

            if ($setl !== null)
            {
                $settlements->push($setl);

                if ($setl->merchant->isLinkedAccount() === true)
                {
                    $linkedAccountTxnIds = array_merge($linkedAccountTxnIds, $txns->getIds());
                }

                if ($setlAttempt !== null)
                {
                    $setlAttempts->push($setlAttempt);

                    $txnsSettledCount += $txns->count();
                }
            }

            $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_SETTLEMENT_ENTITIES_CREATE_END);
        }

        $response = [
            'settlement_count'  => $settlements->count(),
            'attempt_count'     => $setlAttempts->count(),
            'txn_count'         => $txnsSettledCount,
            'la_txn_ids'        => $linkedAccountTxnIds,
        ];

        $this->trace->count(
            Metric::SETTLEMENTS_CREATED_TOTAL,
            [
                Metric::CHANNEL      => $channel,
                Metric::BALANCE_TYPE => $balanceType,
                Metric::MODE         => $this->mode,
            ],
            $response['settlement_count']
        );

        $this->trace->count(
            Metric::TRANSACTIONS_PICKED_FOR_SETTLEMENT_TOTAL,
            [
                Metric::CHANNEL      => $channel,
                Metric::BALANCE_TYPE => $balanceType,
                Metric::MODE         => $this->mode,
            ],
            $response['txn_count']
        );

        return $response;
    }

    protected function groupTransactionsByDay($txns): array
    {
        $groupedTxns = [];

        foreach ($txns as $txn)
        {
            $groupTimestamp = $txn->getCreatedAt();

            if ($txn->isTypePayment() === true)
            {
                $groupTimestamp = $txn->source->getCapturedAt();
            }

            $dayBeginTimestamp = Carbon::createFromTimestamp($groupTimestamp, Timezone::IST)
                                        ->hour(0)
                                        ->minute(0)
                                        ->second(0)
                                        ->getTimestamp();

            $groupedTxns[$dayBeginTimestamp] = $groupedTxns[$dayBeginTimestamp] ?? new Base\PublicCollection;

            $groupedTxns[$dayBeginTimestamp]->push($txn);
        }

        return $groupedTxns;
    }

    protected function getMerchantsToSkipForUsualSettlement(): array
    {
        // MIDs that have daily settlements feature enabled
        $dailySetlMids = $this->getMerchantsOnDailySettlement();

        //
        // MIDs that have been hardcoded to be skipped
        // Todo: Deprecate this in favour of feature based fetch
        //
        $skipMfIds = MerchantModel\Preferences::NO_SETTLEMENT_MIDS;

        // MIDs that have the block_settlements feature enabled
        $skipSetlFeatureMids = $this->repo
                                    ->feature
                                    ->findMerchantIdsHavingFeatures([Feature\Constants::BLOCK_SETTLEMENTS]);

        $skipAdhocMids = $this->getMerchantOnAdhocSettlement();

        $skipMids = array_merge($dailySetlMids, $skipMfIds, $skipSetlFeatureMids, $skipAdhocMids);

        return $skipMids;
    }

    protected function getMerchantsOnDailySettlement()
    {
        $mids = $this->repo
                     ->feature
                     ->findMerchantIdsHavingFeatures([Feature\Constants::DAILY_SETTLEMENT]);

        return $mids;
    }

    protected function createSettlementsAsync(array $merchantIds, string $balanceType, array $params = []): array
    {
        $bucketTimestamp = null;

        if (empty($merchantIds) === true)
        {
            // queue based implementation is independent of channels
            list($bucketTimestamp, $merchantIds) = (new Bucket\Core)->getMerchantIdsFromBucket($balanceType);
        }

        return $this->pushMerchantsToSettlementQueue($merchantIds, $balanceType, $bucketTimestamp, $params);
    }

    protected function createSettlements(
        $channel, bool $useQueue, array $merchantIds = [], array $params = []): array
    {
        $skipMids = $this->getMerchantsToSkipForUsualSettlement();

        $txns = $this->fetchRequiredEntities($this->setlTime, $channel, $merchantIds, $skipMids, false, $params);

        $merchantIds = $txns->pluck(Transaction\Entity::MERCHANT_ID)->toArray();

        $merchantSettleToPartner = (new MerchantModel\Core)->getPartnerBankAccountIdsForSubmerchants($merchantIds);

        $groupedTxns = $this->filterTransactionsForSettlement($txns, $merchantSettleToPartner);

        return $this->createSettlementEntities($groupedTxns, $channel, $merchantSettleToPartner, $params);
    }

    protected function createSettlementsForTestMode($channel): array
    {
        $mids = $this->repo->feature->findMerchantIdsHavingFeatures([Feature\Constants::TEST_MODE_SETTLEMENT]);

        $txns = $this->fetchRequiredEntities($this->setlTime, $channel, $mids, []);

        $merchantIds = $txns->pluck(Transaction\Entity::MERCHANT_ID)->toArray();

        $merchantSettleToPartner = (new MerchantModel\Core)->getPartnerBankAccountIdsForSubmerchants($merchantIds);

        $groupedTxns = $this->filterTransactionsForSettlement($txns, $merchantSettleToPartner);

        return $this->createSettlementEntities($groupedTxns, $channel, $merchantSettleToPartner);
    }

    protected function preSettlementProcessing(array $input)
    {
        $this->inititalizeVariables($input);

        $this->increaseAllowedSystemLimits();
    }

    protected function inititalizeVariables(array $input)
    {
        $this->setlTime = Carbon::now()->getTimestamp();

        $isTestMode = $this->isTestMode();

        if (($isTestMode === true) and
            (empty($input['testSettleTimeStamp']) === false))
        {
            $this->setlTime = $input['testSettleTimeStamp'];
        }

        $this->input = $input;
    }

    protected function shouldProcessSettlements($input, string $channel = null)
    {
        // adding this so test cases can run without below condition
        if ($this->env === Environment::TESTING)
        {
            return [true, null];
        }

        $channelWith24x7Settlement = Channel::get24x7Channels();

        if (in_array($channel, $channelWith24x7Settlement, true) === true)
        {
            return [true, null];
        }

        //
        // If the force flag is set,
        // let the settlements go
        //
        if ((isset($input['ignore_time_limit']) === true) and
            ($input['ignore_time_limit'] === '1'))
        {
            return [true, null];
        }

        $today = Carbon::today(Timezone::IST);

        if (Holidays::isWorkingDay($today) === false)
        {
            return [false, Holidays::HOLIDAY_MESSAGE];
        }

        if ($this->isInvalidSettlementTime() === true)
        {
            return [false, ['message' => 'settlements cannot be processed now']];
        }

        return [true, null];
    }

    /**
     *  NEFT can be processed between 8am and 6 pm only, while batch file can be
     *  uploaded anytime.
     *
     * @return bool returns if settlement can be processed now
     */
    protected function isInvalidSettlementTime(): bool
    {
        // Cron runs at 6.10pm.
        $sixPm = Carbon::today(Timezone::IST)->hour(18)->minute(13)->getTimestamp();

        // No settlements after five PM but allow settlements file upload anytime
        // before that, we want to do it before 8 am as well as that allows us
        // some time for fixing things before settlement window opens.
        if ($this->setlTime >= $sixPm)
        {
            return true;
        }

        return false;
    }

    /**
     * Will decide where to impose time constain on settlement process.
     * This is done based on mode and environment.
     *  - no constrains on `test` mode
     *  - no constraint on `qa` and `testing` environments
     *
     * @return bool
     */
    protected function isTestMode(): bool
    {
        if (in_array($this->env, ['testing', 'perf', 'func', 'dev'], true) === true)
        {
            return true;
        }

        return false;
    }

    /**
     * @param array  $merchantIds
     * @param string $balanceType
     * @param null   $bucketTimestamp
     * @param array $params
     *
     * @return array
     */
    protected function pushMerchantsToSettlementQueue(
        array $merchantIds,
        string $balanceType,
        $bucketTimestamp = null,
       array $params = []): array
    {
        $result = [
            'total_merchants' => count($merchantIds),
            'enqueued'        => 0,
            'enqueue_failed'  => 0,
            'time_taken'      => 0,
        ];

        $this->trace->info(
            TraceCode::MERCHANT_DISPATCH_FOR_SETTLEMENT_QUEUE_INIT,
            [
                'merchant_count' => count($merchantIds),
                'balance_type'   => $balanceType,
            ]);

        $CountKey = sprintf(Create::TOTAL_MERCHANT_COUNT, $this->mode);
        //
        // add count of total merchant IDs in cache
        // so that it can be used to initiate transfer when settlement creation is complete
        //
        Cache::increment($CountKey, count($merchantIds));

        $startTime = microtime(true);

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                //
                // passing $bucketTimestamp is not necessary
                // for now passing this, just to track the performance
                // if timestamp exist then its a automated process else its manual
                //
                Create::dispatch($this->mode, $merchantId, $bucketTimestamp, $balanceType, $params);

                $this->trace->info(
                    TraceCode::MERCHANT_DISPATCHED_FOR_SETTLEMENT,
                    [
                        'merchant_id'  => $merchantId,
                        'balance_type' => $balanceType,
                    ]);

                $this->trace->count(Metric::NUMBER_OF_MERCHANTS_IN_QUEUE_FOR_SETTLEMENT);

                $result['enqueued'] += 1;
            }
            catch(\Throwable $e)
            {
                $result['enqueue_failed'] += 1;

                //
                // in case of failures decrement the total count stored
                // this will help maintain the exact count pushed to queue
                // and also when to initiate the transfer
                //
                Cache::decrement($CountKey);

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::MERCHANT_DISPATCH_FOR_SETTLEMENT_FAILED,
                    [
                        'merchant_id'  => $merchantId,
                        'balance_type' => $balanceType,
                    ]
                );
            }
        }

        $result['time_taken'] = get_diff_in_millisecond($startTime);

        $this->trace->info(
            TraceCode::MERCHANT_DISPATCH_FOR_SETTLEMENT_QUEUE_COMPLETE,
            $result);

        return $result;
    }

    public function fetchAndProcessTransactionsForSettlement(
        MerchantModel\Entity $merchant, string $channel, string $balanceType, array $params = [])
    {
        $this->setlTime = Carbon::now(Timezone::IST)->getTimestamp();

        $forceFlag = false;

        if(isset($params['ignore_time_limit']) === true)
        {
            $forceFlag = $params['ignore_time_limit'] === '1';
        }

        list ($status, $_) = $this->isMerchantSettlementAllowed($merchant, $balanceType, $forceFlag);

        if ($status === false)
        {
            return [
                'settlement_count' => 0,
                'txn_count'        => 0,
                'attempt_count'    => 0,
            ];
        }

        // Avoiding race condition here
        $resource = sprintf(self::MUTEX_SETTLEMENT_CREATE_RESOURCE, $merchant->getId(), $this->mode);

        $result = $this->mutex->acquireAndRelease(
            $resource,
            function () use ($merchant, $channel, $balanceType, $params)
            {
                return $this->createSettlementForMerchant($merchant, $channel, $balanceType, $params);
            },
            self::MUTEX_SETTLEMENT_CREATE_TIMEOUT,
            ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);

        //
        // Marking merchant settlement as complete here (update the bucket entity)
        // at this point we have tried to settle to merchant
        // at this stage settlement might have also been skipped because of balance
        // but still we update the bucket as completed
        // reason being, if merchant balance is low the only way to get settlement is by fixing the balance
        // to fix the balance there has to be a transaction (payment/adjustment) created
        // which will add the merchant to bucket for settlement hence the process continues
        //
        (new Bucket\Core)->markMerchantSettlementAsComplete($merchant, $balanceType);

        return $result;
    }

    protected function createSettlementForMerchant(
        MerchantModel\Entity $merchant, string $channel, string $balanceType, array $params = []): array
    {
        RuntimeManager::setMemoryLimit('10240M');

        $balance = $this->repo->balance->getMerchantBalanceByType($merchant->getId(), $balanceType);

        if ((new Bucket\Core)->shouldProcessViaNewService($merchant->getId(), $balance) === true)
        {
            $this->traceMerchantSettlementSkip(
                $merchant,
                [
                    'reason' => 'settlement will process via new service',
                ]);

            return [
                'settlement_count'  => 0,
                'attempt_count'     => 0,
                'txn_count'         => 0,
            ];
        }

        if ($balance === null)
        {
            $this->traceMerchantSettlementSkip(
                $merchant,
                [
                    'reason' => 'merchant does not have balance type ' . $balanceType,
                ]);

            return [
                'settlement_count'  => 0,
                'attempt_count'     => 0,
                'txn_count'         => 0,
            ];
        }

        // fetch all the valid transactions from the slave
        $txns = $this->repo
                     ->transaction
                     ->fetchUnsettledTransactionsForProcessing($merchant->getId(), $balance, $params);

        // If there are no transactions to settle then return
        if ($txns->isEmpty() === true)
        {
            $this->traceMerchantSettlementSkip(
                $merchant,
                [
                    'reason' => 'No transactions to settle',
                ]);

            return [
                'settlement_count'  => 0,
                'attempt_count'     => 0,
                'txn_count'         => 0,
            ];
        }

        $this->merchants = $this->repo
                                ->merchant
                                ->findManyWithRelations(
                                    [$merchant->getId()],
                                    ['bankAccount'],
                                    [
                                        MerchantModel\Entity::ID,
                                        MerchantModel\Entity::PARENT_ID
                                    ])
                                ->keyBy(MerchantModel\Entity::ID);

        // refund filter is removed as this is handled while creating auth refund
        // /Models/Transaction/Processor/Refund.php:getSettledAtTimestampForRefund

        if((isset($params['daily_settlement']) === true) and ($params['daily_settlement'] === true) )
        {
            $transactionsGroup = $this->groupTransactionsByDay($txns);
        }
        else
        {
            $transactionsGroup = [
                $merchant->getId() => $txns,
            ];
        }

        $merchantSettleToPartner = (new MerchantModel\Core)->getPartnerBankAccountIdsForSubmerchants([
            $merchant->getId()
        ]);

        return $this->createSettlementEntities($transactionsGroup, $channel, $merchantSettleToPartner, $params, $balanceType);
    }

    protected function shouldUseQueue(array $input)
    {
        return (bool) isset($input['use_queue']) ?? false;
    }

    public function processAdhocSettlements(array $input)
    {
        $this->increaseAllowedSystemLimits();

        $mutexResource = sprintf(self::MUTEX_ADHOC_RESOURCE, $this->mode);

        list($shouldProcess, $data) = $this->shouldProcessSettlements($input);

        if ($shouldProcess === true)
        {
            $data = $this->mutex->acquireAndRelease(
                $mutexResource,
                function ()
                {
                    return $this->createAdhocSettlements();
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }

        return $data;
    }

    protected function createAdhocSettlements(): array
    {
        $channels = $this->getArrayedChannels();

        $response = $this->makeResponse($channels);

        try
        {
            $mids = $this->getMerchantOnAdhocSettlement();

            $merchantSettleToPartner = (new MerchantModel\Core)->getPartnerBankAccountIdsForSubmerchants($mids);
            $merchants = $this->repo->merchant->findMany(
                $mids,
                [
                    MerchantModel\Entity::ID,
                    MerchantModel\Entity::CHANNEL,
                    MerchantModel\Entity::HOLD_FUNDS
                ]);

            $this->setlTime = Carbon::now(Timezone::IST)->getTimestamp();

            foreach ($merchants as $merchant)
            {
                $channel = $merchant->getChannel();

                if ($merchant->getHoldFunds() === true)
                {
                    $this->trace->info(TraceCode::SETTLEMENT_MERCHANT_ON_HOLD, ['merchant_id' => $merchant->getId()]);

                    continue;
                }

                $mid = $merchant->getId();

                $txns = $this->fetchRequiredEntities($this->setlTime, $channel, [$mid], [], true);

                $filteredTxns = $this->processMerchantSettlement($txns, $mid);

                if (isset($filteredTxns[$mid]) === false)
                {
                    $this->trace->info(TraceCode::SETTLEMENT_MERCHANT_SKIPPED, ['merchant_id' => $mid]);

                    continue;
                }

                $setlResponse = $this->createSettlementEntities($filteredTxns, $channel, $merchantSettleToPartner);

                $response[$channel]['count']    += $setlResponse['settlement_count'];
                $response[$channel]['txnCount'] += $setlResponse['txn_count'];
            }
            $this->trace->info(
                TraceCode::ADHOC_SETTLEMENT_ENTITIES_CREATED,
                $response);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ADHOC_SETTLEMENT_CREATE_FAILED
            );

            $this->settlementFailure(null, $e, TraceCode::ADHOC_SETTLEMENT_CREATE_FAILED);
        }

        return $response;
    }

    protected function getMerchantOnAdhocSettlement()
    {
        $mids = $this->repo
                     ->feature
                     ->findMerchantIdsHavingFeatures([Feature\Constants::ADHOC_SETTLEMENT]);

        return $mids;
    }

    public function enableDebug()
    {
        $this->debug = true;
    }

    public function disableDebug()
    {
        $this->debug = false;
    }

    public function isDebugEnabled()
    {
        return $this->debug;
    }

    protected function setDebugStatus(array $input)
    {
        if (array_key_exists('debug', $input) === true)
        {
            $this->debug = (bool) $input['debug'];
        }
    }

    /**
     * This method will create the settlement entry in the API system to maintain the back word compatibility
     * with the newer settlement service
     * @param $input
     * @return mixed
     */
    public function createSettlementEntry($input)
    {
        $data =  $this->repo->settlement->findBySettlementId($input['settlement_id']);

        $settlementTrf = $this->repo->settlement_transfer->fetchBySettlementId($input['settlement_id']);

        if ($data->count() != 0)
        {
            return [
                'transaction_id'                        => $data[0]->getTransactionId(),
                'settlement_transfer_transaction_id'    => (isset($settlementTrf[0]) === true) ? $settlementTrf[0]->transaction->getId() : null,
                'duplicate'                             => true,
                'error'                                 => null,
            ];
        }

        try
        {
            $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

            $channel = $input['channel'];

            $isAggregateSettlement = $input['type'] === Feature\Constants::AGGREGATE_SETTLEMENT;

            $merchantSettler = new SetlMerchant(
                $merchant,
                $channel,
                $this->repo,
                false,
                [],
                $isAggregateSettlement,
                true
            );

            $balance = $this->repo->balance->getMerchantBalanceByType($merchant->getId(), $input['balance_type']);

            [$response, $settlementTransfer] = $merchantSettler->createSettlementFromNewService($balance, $input);

            $settlementTransferTxnId = null;

            if((isset($settlementTransfer) === true) and (isset($settlementTransfer->transaction) === true))
            {
                $settlementTransferTxnId = $settlementTransfer->transaction->getId();
            }

            return [
                'transaction_id'                        => $response->getTransactionId(),
                'settlement_transfer_transaction_id'    => $settlementTransferTxnId,
                'duplicate'                             => false,
                'error'                                 => null,
            ];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_CREATE_FAILED_FOR_SERVICE,
                [
                    'input' => $input,
                ]);

            $errorMsg      = $e->getMessage();
            $exceptionData = $e->getData();

            if ($errorMsg === self::NEGATIVE_BALANCE_ERROR_MESSAGE)
            {
                $setlAmount = -1 * $exceptionData['amount'];

                $balance = $exceptionData['balance']['balance'];

                $prevBalance = $balance + $setlAmount;

                $errorMsg .= " { balance : $prevBalance, setl_amount : $setlAmount }";
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_CREATE_FAILED_FOR_SERVICE,
                [
                   'input' => $input,
                ]);

            return [
                'transaction_id'                        => null,
                'settlement_transfer_transaction_id'    => null,
                'duplicate'                             => null,
                'error'                                 => $errorMsg,
            ];
        }
    }

    /**
     * settlementStatusUpdate used to update the status of the settlement from settlement service
     * @param array $input
     * @return array|null[]
     */
    public function settlementStatusUpdate(array $input)
    {
        try
        {
            $updateStatusAllowed = [
                Status::PROCESSED,
                Status::CREATED,
                Status::FAILED,
            ];

            $setl = $this->repo->settlement->findOrFail($input['id']);

            $currentStatus = $setl->getStatus();

            if (in_array($currentStatus, $updateStatusAllowed) === false)
            {
                return [
                    'error' => sprintf("current settlement status %s can not be updated", $currentStatus)
                ];
            }

            // Avoiding race condition here
            $resource = sprintf(self::MUTEX_SETTLEMENT_STATUS_UPDATE_RESOURCE, $setl->getId(), $this->mode);

            $setl = $this->mutex->acquireAndRelease(
                $resource,
                function () use ($setl, $input)
                {
                    // Setting the utr and failure reason as null in case empty string
                    $utr = empty($input['utr']) === true ? null : $input['utr'];
                    $failureReason = empty($input['failure_reason']) === true ? null : $input['failure_reason'];

                    $setl->setUtr($utr);
                    $setl->setStatus($input['status']);
                    $setl->setRemarks($input['remarks']);
                    $setl->setFailureReason($failureReason);

                    $this->repo->saveOrFail($setl);

                    return $setl;
                },
                30,
                ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_SETTLEMENT_UPDATE_IN_PROGRESS);

            $failedNotification = false;

            $merchant_id = $setl->getMerchantId();

            if (isset($input['trigger_failed_notification']) === true)
            {
                $failedNotification = $input['trigger_failed_notification'];
            }

            (new Core)->triggerSettlementWebhook($setl, $input['redacted_ba'], $failedNotification, $failedNotification);

            // we started updating failed state also in old service for all the settlements from new settlements
            // service so triggering this webhook only for the processed state
            if ((in_array($merchant_id, MerchantModel\Preferences::TRANSFER_SETTLED_WEBHOOK_MIDS) === true)
                and ($setl->getStatus() === Status::PROCESSED))
            {
                $input['settlement_id'] = $setl->getId();

                TransferRecon::dispatch($input, $this->mode);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_STATUS_UPDATE_FAILED_FOR_SERVICE,
                [
                    'input' => $input,
                ]);

            return [
                'error' => $e->getMessage(),
            ];
        }

        return [
            'error' => null,
        ];
    }
}
