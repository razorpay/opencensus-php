<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\BankAccount;
use RZP\Models\Merchant as MerchantModel;

class Processor extends Base\Core
{
    use SettlementTrait;

    protected $setlTime;

    protected $input;

    protected $mutex;

    /**
     * Merchant keyed by ID for easy access later
     */
    protected $merchants = null;

    const MUTEX_RESOURCE        = 'SETTLEMENT_PROCESSING_%s';

    const MUTEX_RETRY_RESOURCE  = 'SETTLEMENT_RETRY_%s';

    const MUTEX_LOCK_TIMEOUT    = 900;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
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

        $mutexResource = sprintf(self::MUTEX_RESOURCE, $this->mode);

        $data = $this->mutex->acquireAndRelease(
            $mutexResource,
            function ()
            {
                return $this->createDailySettlements();
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);

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
        $this->preSettlementProcessing($input);

        list($shouldProcess, $data) = $this->shouldProcessSettlements($input);

        if ($shouldProcess === true)
        {
            $mutexResource = sprintf(self::MUTEX_RESOURCE, $this->mode);

            $data = $this->mutex->acquireAndRelease(
                $mutexResource,
                function () use ($channel)
                {
                    return $this->processSettlements($channel);
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }

        return $data;
    }

    protected function processSettlements($channel)
    {
        $response = [];

        try
        {
            $channels = $this->getArrayedChannels($channel);

            $response = $this->makeResponse($channels);

            foreach ($channels as $channel)
            {
                $this->traceSetlInitiating($channel);

                $setlResponse = $this->createSettlements($channel);

                $response[$channel]['count']    += $setlResponse['settlement_count'];
                $response[$channel]['txnCount'] += $setlResponse['txn_count'];
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

    protected function retrySettlements(array $setlIds)
    {
        $setlAttempts = new Base\PublicCollection;

        $settlements = $this->repo->settlement->getFailedSettlementsForRetry($setlIds);

        $settlementsRetried = [];

        foreach ($settlements as $setl)
        {
            $channel = $setl->getChannel();

            $merchantSettler = new Merchant($setl->merchant, $channel, $this->repo);

            list($setl, $bankTransferAtpt) = $this->repo->transaction(
                function() use ($merchantSettler, $setl)
            {
                if ($setl->hasTransaction() === false)
                {
                    $merchantSettler->createTransaction($setl);
                }

                return $merchantSettler->retryFailedSettlement($setl);
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

                $setlResponse = $this->createSettlementEntities($groupedTxns, $channel);

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
     * @return mixed
     */
    protected function fetchRequiredEntities(
        int $settledAtCutOff, string $channel, array $inMids = [], array $notInMids = [])
    {
        $txns = $this->repo->transaction->fetchUnsettledTransactions(
                    $settledAtCutOff, $channel, $inMids, $notInMids);

        $mids = $txns->pluck(Transaction\Entity::MERCHANT_ID)->toArray();

        $this->merchants = $this->repo
                                ->merchant
                                ->findManyWithRelations(
                                    $mids,
                                    ['balance', 'bankAccount'],
                                    [
                                        MerchantModel\Entity::ID,
                                        MerchantModel\Entity::PARENT_ID
                                    ])
                                ->keyBy(MerchantModel\Entity::ID);

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
     * @return array
     * Returns array with keys settlement_count, attempt_count, txn_count
     */
    protected function createSettlementEntities($groupedTxns, string $channel): array
    {
        $settlements        = new Base\PublicCollection;
        $setlAttempts       = new Base\PublicCollection;
        $txnsSettledCount   = 0;

        foreach ($groupedTxns as $key => $txns)
        {
            list($setl, $setlAttempt) = $this->createSettlementsFromTxns($txns, $channel);

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
        }

        return [
            'settlement_count'  => $settlements->count(),
            'attempt_count'     => $setlAttempts->count(),
            'txn_count'         => $txnsSettledCount,
        ];
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
        $dailySetlMids = $this->getMerchantsOnDailySettlement();

        $skipMfIds = MerchantModel\Preferences::NO_SETTLEMENT_MIDS;

        $skipMids = array_merge($dailySetlMids, $skipMfIds);

        return $skipMids;
    }

    protected function getMerchantsOnDailySettlement()
    {
        $features = [Feature\Constants::DAILY_SETTLEMENT];

        $featureEntities = $this->repo->feature->findMerchantsHavingFeatures($features);

        $mids = $featureEntities->pluck(Feature\Entity::ENTITY_ID)->toArray();

        return $mids;
    }

    protected function createSettlements($channel): array
    {
        $skipMids = $this->getMerchantsToSkipForUsualSettlement();

        $txns = $this->fetchRequiredEntities($this->setlTime, $channel, [], $skipMids);

        $groupedTxns = $this->filterTransactionsForSettlement($txns);

        return $this->createSettlementEntities($groupedTxns, $channel);
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

    protected function shouldProcessSettlements($input)
    {
        $isTestMode = $this->isTestMode();

        if ($isTestMode === true)
        {
            return [true, null];
        }

        $today = Carbon::today(Timezone::IST);

        if (Holidays::isWorkingDay($today) === false)
        {
            return [false, Holidays::HOLIDAY_MESSAGE];
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
        if (($this->mode === Mode::TEST) or
            (in_array($this->env, ['testing', 'perf', 'func'], true) === true))
        {
            return true;
        }

        return false;
    }
}
