<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Jobs\SettlementJob;
use RZP\Models\BankAccount;
use RZP\Constants\Environment;
use RZP\Models\Merchant as MerchantModel;

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

    const MUTEX_RETRY_RESOURCE  = 'SETTLEMENT_RETRY_%s';

    const MUTEX_LOCK_TIMEOUT    = 1800;

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

    public function process(array $input, $channel)
    {
        $this->setDebugStatus($input);

        $this->preSettlementProcessing($input);

        $useQueue = $this->shouldUseQueue($input);

        $merchantIds = $input['merchant_ids'] ?? [];

        list($shouldProcess, $data) = $this->shouldProcessSettlements($input, $channel);

        $startTime = microtime(true);

        if ($shouldProcess === true)
        {
            $mutexResource = sprintf(self::MUTEX_RESOURCE, $this->mode, $channel);

            $data = $this->mutex->acquireAndRelease(
                $mutexResource,
                function () use ($channel, $useQueue, $merchantIds)
                {
                    return $this->processSettlements($channel, $useQueue, $merchantIds);
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }

        //
        // tracing metric with details like
        // - total number of merhcants involved in this process
        // - time taken to complete the process
        //
        $this->trace->count(
            Metric::SETTLEMENTS_INITIATE_RUNTIME,
            [
                Metric::CHANNEL                 => $channel,
                Metric::TIME_TAKEN_IN_MILLI     => get_diff_in_millisecond($startTime),
                Metric::TOTAL_MERCHANTS_COUNT   => $data[$channel]['count'],
            ]);

        return $data;
    }

    protected function processSettlements($channel, bool $useQueue, array $merchantIds)
    {
        $response = [];

        try
        {
            $channels = $this->getArrayedChannels($channel);

            $response = $this->makeResponse($channels);

            foreach ($channels as $channel)
            {
                $this->traceSetlInitiating($channel);

                if (($this->mode === Mode::TEST) and (in_array($this->env, [Environment::PRODUCTION], true) === true))
                {
                    $setlResponse = $this->createSettlementsForTestMode($channel);
                }
                else
                {
                    $setlResponse = $this->createSettlements($channel, $useQueue, $merchantIds);
                }

                if ($useQueue === true)
                {
                    $response = $setlResponse;
                }
                else
                {
                    $response[$channel]['count']    += $setlResponse['settlement_count'];
                    $response[$channel]['txnCount'] += $setlResponse['txn_count'];
                }
            }

            $this->trace->info(
                TraceCode::SETTLEMENT_ATTEMPT_ENTITIES_CREATED,
                $response);
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
            $channel = $setl->getChannel();

            $merchantSettler = new Merchant($setl->merchant, $channel, $this->repo);

            list($setl, $bankTransferAtpt) = $this->repo->transaction(
                function() use ($merchantSettler, $setl, $merchantSettleToPartner)
            {
                if ($setl->hasTransaction() === false)
                {
                    $merchantSettler->createTransaction($setl);
                }

                return $merchantSettler->retryFailedSettlement($setl, $merchantSettleToPartner);
            });

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
     * Return array is keyed by channel
     * Sample [
     *          'channel1' => [
     *              'count' => 'some integer',
     *              'txnCount' => 'some integer'
     *          ],
     *         'channel2' => [
     *              'count' => 'some integer',
     *              'txnCount' => 'some integer'
     *          ]
     *      ]
     */
    protected function createDailySettlements(): array
    {
        $channels = $this->getArrayedChannels();

        $response = $this->makeResponse($channels);

        try
        {
            $mids = $this->getMerchantsOnDailySettlement();
            $merchantSettleToPartner = (new MerchantModel\Core)->getPartnerBankAccountIdsForSubmerchants($mids);

            $merchants = $this->repo->merchant->findMany(
                            $mids,
                            [
                                MerchantModel\Entity::ID,
                                MerchantModel\Entity::CHANNEL,
                                MerchantModel\Entity::HOLD_FUNDS
                            ]);

            $this->setlTime = Carbon::now(Timezone::IST)->getTimestamp();

            $settledAtCutoff = Carbon::tomorrow(Timezone::IST)->getTimestamp();

            foreach ($merchants as $merchant)
            {
                $channel = $merchant->getChannel();

                if ($merchant->getHoldFunds() === true)
                {
                    continue;
                }

                $mid = $merchant->getId();

                // Get all transactions due settlement till yesterday end of day
                $txns = $this->fetchRequiredEntities($settledAtCutoff, $channel, [$mid]);

                $filteredTxns = $this->filterTransactionsForSettlement($txns);

                if (isset($filteredTxns[$mid]) === false)
                {
                    continue;
                }

                $groupedTxns = $this->groupTransactionsByDay($filteredTxns[$mid]);

                $setlResponse = $this->createSettlementEntities($groupedTxns, $channel, $merchantSettleToPartner);

                $response[$channel]['count']    += $setlResponse['settlement_count'];
                $response[$channel]['txnCount'] += $setlResponse['txn_count'];
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::DAILY_SETTLEMENT_CREATE_FAILED
            );

            $this->settlementFailure($channel, $e, TraceCode::DAILY_SETTLEMENT_CREATE_FAILED);
        }

        return $response;
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
     * @return mixed
     */
    protected function fetchRequiredEntities(
        int $settledAtCutOff, string $channel, array $inMids = [], array $notInMids = [], $useLimit = false)
    {
        $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_SETTLEMENT_FETCHING_ENTITIES);

        $txns = $this->repo->transaction->fetchUnsettledTransactions(
                    $settledAtCutOff, $channel, $inMids, $notInMids, true, $useLimit);

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
     * @param $groupedTxns
     * Creates a settlement entity for every group
     *
     * @param string $channel
     * @param array $merchantSettleToPartner
     * merchants settling to partner bank account, key will be merchantId and value will be partner bank account id
     *
     * @return array
     * Returns array with keys settlement_count, attempt_count, txn_count
     */
    protected function createSettlementEntities($groupedTxns, string $channel, array $merchantSettleToPartner): array
    {
        $settlements        = new Base\PublicCollection;
        $setlAttempts       = new Base\PublicCollection;
        $txnsSettledCount   = 0;

        foreach ($groupedTxns as $merchantId => $txns)
        {
            $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_SETTLEMENT_ENTITIES_CREATE_START);

            list($setl, $setlAttempt) = $this->createSettlementsFromTxns($txns, $channel, $merchantSettleToPartner);

            if ($setl !== null)
            {
                $settlements->push($setl);

                if ($setlAttempt !== null)
                {
                    $setlAttempts->push($setlAttempt);

                    $txnsSettledCount += $txns->count();
                }

                $this->updateSettlementIdInTransfer($txns);
            }

            $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_SETTLEMENT_ENTITIES_CREATE_END);
        }

        $response = [
            'settlement_count'  => $settlements->count(),
            'attempt_count'     => $setlAttempts->count(),
            'txn_count'         => $txnsSettledCount,
        ];

        $this->trace->count(
            Metric::SETTLEMENTS_CREATED_TOTAL,
            [
                Metric::CHANNEL => $channel,
                Metric::MODE    => $this->mode,
            ],
            $response['settlement_count']
        );

        $this->trace->count(
            Metric::TRANSACTIONS_PICKED_FOR_SETTLEMENT_TOTAL,
            [
                Metric::CHANNEL => $channel,
                Metric::MODE    => $this->mode,
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

    protected function createSettlements($channel, bool $useQueue, array $merchantIds = []): array
    {
        $skipMids = $this->getMerchantsToSkipForUsualSettlement();

        if ($useQueue === true)
        {
            // TODO: refactor this. add merchant filter to ensure only required merchants are been pushed to the queue
            $activatedMerchants = $this->repo
                                       ->transaction
                                       ->fetchUnsettledTransactions($this->setlTime, $channel, $merchantIds, $skipMids, false);

            $activatedMerchants = array_keys($activatedMerchants->getStringAttributesByKey(Transaction\Entity::MERCHANT_ID));

            return $this->pushMerchantsToSettlementQueue($activatedMerchants, $channel);
        }

        $txns = $this->fetchRequiredEntities($this->setlTime, $channel, [], $skipMids);

        $groupedTxns = $this->filterTransactionsForSettlement($txns);

        $merchantIds = array_keys($groupedTxns);
        $merchantSettleToPartner = (new MerchantModel\Core)->getPartnerBankAccountIdsForSubmerchants($merchantIds);

        return $this->createSettlementEntities($groupedTxns, $channel, $merchantSettleToPartner);
    }

    protected function createSettlementsForTestMode($channel): array
    {
        $mids = $this->repo->feature->findMerchantIdsHavingFeatures([Feature\Constants::TEST_MODE_SETTLEMENT]);

        $txns = $this->fetchRequiredEntities($this->setlTime, $channel, $mids, []);

        $groupedTxns = $this->filterTransactionsForSettlement($txns);

        $merchantIds = array_keys($groupedTxns);
        $merchantSettleToPartner = (new MerchantModel\Core)->getPartnerBankAccountIdsForSubmerchants($merchantIds);

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
     * @param string $channel
     * @return array
     */
    protected function pushMerchantsToSettlementQueue(array $merchantIds, string $channel): array
    {
        $totalCount[$channel]['merchant_count']    = 0;

        $this->trace->info(
            TraceCode::MERCHANT_DISPATCH_FOR_SETTLEMENT_QUEUE_INIT,
            [
                'channel'           => $channel,
                'merchant_count'    => count($merchantIds)
            ]);

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                SettlementJob::dispatch($this->mode, $channel, $merchantId);

                $this->trace->info(
                    TraceCode::MERCHANT_DISPATCHED_FOR_SETTLEMENT,
                    [
                        'channel'     => $channel,
                        'merchant_id' => $merchantId
                    ]);

                // todo: check the usage
                $this->trace->gauge(
                    Metric::NUMBER_OF_MERCHANTS_IN_QUEUE_FOR_SETTLEMENT,
                    1,
                    [
                        'channel' => $channel,
                    ]);

                $totalCount[$channel]['merchant_count'] += 1;
            }
            catch(\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::MERCHANT_DISPATCH_FOR_SETTLEMENT_FAILED,
                    [
                        'channel'     => $channel,
                        'merchant_id' => $merchantId
                    ]
                );
            }
        }

        $this->trace->info(
            TraceCode::MERCHANT_DISPATCH_FOR_SETTLEMENT_QUEUE_COMPLETE,
            $totalCount);

        return $totalCount;
    }

    public function fetchAndProcessTransactionsForSettlement(string $channel, string $merchantId)
    {
        $this->setlTime = Carbon::now(Timezone::IST)->getTimestamp();

        // get merchat details for further filtering
        $this->merchant = $this->repo->merchant->find($merchantId);

        if ($this->isMerchantSettlementAllowed($this->merchant) === false)
        {
            // dont proceed with the settlement
        }

        $txns = $this->repo->transaction->fetchUnsettledTransactionsForProcessing($merchantId, $channel);

        $this->merchants = $this->repo
                                ->merchant
                                ->findManyWithRelations(
                                    [$merchantId],
                                    ['primaryBalance', 'bankAccount'],
                                    [
                                        MerchantModel\Entity::ID,
                                        MerchantModel\Entity::PARENT_ID
                                    ])
                                ->keyBy(MerchantModel\Entity::ID);

        $groupedTxns = $this->filterTransactionsForSettlement($txns);

        $merchantSettleToPartner = (new MerchantModel\Core)->getPartnerBankAccountIdsForSubmerchants([$merchantId]);
        return $this->createSettlementEntities($groupedTxns, $channel, $merchantSettleToPartner);
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
}
