<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;

class Processor extends Base\Core
{
    use SettlementTrait;

    protected $setlTime;

    protected $input;

    protected $mutex;

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
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_INITIATE_FAILED,
                ['channel' => $channel]
            );

            $this->settlementFailure($channel, $e, TraceCode::SETTLEMENT_INITIATE_FAILED);
        }

        return $response;
    }

    protected function retryProcessFailedSettlements(): array
    {
        $response = [];

        try
        {
            (new Validator)->validateInput('retry', $this->input);

            $setlIds = $this->input['settlement_ids'];

            Entity::verifyIdAndStripSignMultiple($setlIds);

            $response = $this->retrySettlements($setlIds);
        }
        catch (\Exception $e)
        {
            $this->settlementFailure(null, $e, TraceCode::SETTLEMENT_RETRY_FAILED);
        }

        return $response;
    }

    protected function retrySettlements(array $setlIds)
    {
        $setlAttempts = new Base\PublicCollection;

        $totalTxns = 0;

        $settlements = $this->repo->settlement->getFailedSettlementsForRetry($setlIds);

        $settlementsRetried = [];

        foreach ($settlements as $setl)
        {
            $setlTxns = $setl->setlTransactions;

            $setlTxnsCount = $setlTxns->count();

            $channel = $setl->getChannel();

            $merchantSettler = new Merchant($setl->merchant, $channel, $this->repo);

            list($setl, $bankTransferAtpt) = $this->repo->transaction(
                function() use ($merchantSettler, $setl, $setlTxns, $setlTxnsCount)
            {
                if ($setl->hasTransaction() === false)
                {
                    $merchantSettler->createTransaction($setl);
                }

                return $merchantSettler->retryFailedSettlement($setl);
            });

            $setlAttempts->push($bankTransferAtpt);

            $totalTxns += $setlTxnsCount;

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
     *
     * @throws SettlementFailureException
     */
    protected function createDailySettlements(): array
    {
        $channels = $this->getArrayedChannels();

        $response = $this->makeResponse($channels);

        try
        {
            $mids = $this->getMerchantsOnDailySettlement();

            $merchants = $this->repo->merchant->findMany($mids);

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
                $txns = $this->repo->transaction->fetchUnsettledTransactions(
                            $settledAtCutoff, $channel, [$mid]);

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
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::DAILY_SETTLEMENT_INITIATE_FAILED
            );

            $this->settlementFailure($channel, $e, TraceCode::DAILY_SETTLEMENT_INITIATE_FAILED);
        }

        return $response;
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

    protected function getMerchantsOnDailySettlement()
    {
        $features = [Feature\Constants::DAILY_SETTLEMENT];

        $featureEntities = $this->repo->feature->findMerchantsHavingFeatures($features);

        $mids = $featureEntities->pluck(Feature\Entity::ENTITY_ID)->toArray();

        return $mids;
    }

    protected function createSettlements($channel): array
    {
        $skipMids = $this->getMerchantsOnDailySettlement();

        $txns = $this->repo->transaction->fetchUnsettledTransactions($this->setlTime, $channel, [], $skipMids);

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

        if (($this->mode === Mode::TEST) and
            (empty($input['testSettleTimeStamp']) === false))
        {
            $this->setlTime = $input['testSettleTimeStamp'];
        }

        $this->input = $input;
    }

    protected function shouldProcessSettlements($input)
    {
        if (($this->mode === Mode::TEST) and
            ($this->env === 'testing'))
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
        if (($this->setlTime >= $sixPm) and ($this->env !== 'testing'))
        {
            return true;
        }

        return false;
    }
}
