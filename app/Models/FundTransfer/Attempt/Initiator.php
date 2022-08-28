<?php

namespace RZP\Models\FundTransfer\Attempt;

use Carbon\Carbon;
use Monolog\Logger;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Jobs\FundTransfer;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Constants\Environment;
use RZP\Models\Admin\ConfigKey;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Base\PublicCollection;
use RZP\Services\FTS\Base as FtsService;
use RZP\Models\Settlement\SlackNotification;
use RZP\Jobs\AttemptsRecon as AttemptsReconJob;
use RZP\Models\FundTransfer\Mode as TransferMode;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Constants\SettlementChannelMedium as Medium;
use RZP\Jobs\AttemptStatusCheck as AttemptStatusCheckJob;

class Initiator extends Base\Core
{
    const FTA_PURPOSE                       = 'settlement';

    const MUTEX_RESOURCE                    = 'FUND_TRANSFER_PROCESSING_%s_%s_%s_%s';

    const FILE_BASED_MUTEX_TIMEOUT          = 60;

    const REQUEST_TIMEOUT                   = 30;

    const MUTEX_FTS_RESOURCE                = 'MUTEX_FTS_RESOURCE_%s_%s_%s';

    const FTS_REQUEST_TIMEOUT               = 300;

    const DEFAULT_LIMIT_FOR_MUTEX_TIMEOUT   = 500;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Initiate bank transfers for all merchants EXCEPT
     * those with special settlement schedule requirements.
     * Check code below.
     *
     * @param  array  $input
     * @param  string $channel
     * @return array
     */
    public function initiateFundTransfers(array $input, string $channel): array
    {
        list($shouldProcessBankTransfers, $message) = $this->shouldProcessBankTransfers($input, $channel);

        if ($shouldProcessBankTransfers === false)
        {
            return [
                'channel'   => $channel,
                'count'     => 0,
                'message'   => $message
            ];
        }

        $mutexResource = sprintf(self::MUTEX_RESOURCE, $this->mode, $channel, $input[Entity::PURPOSE], $input[Entity::SOURCE_TYPE]);

        // Default timeout to be used for file based channels.
        $mutexTimeout = self::FILE_BASED_MUTEX_TIMEOUT;

        $apiChannels = Channel::getApiBasedChannels();

        if (in_array($channel, $apiChannels, true) === true)
        {
            $limit = $this->getLimitForChannel($channel) ?? self::DEFAULT_LIMIT_FOR_MUTEX_TIMEOUT;

            $mutexTimeout = $limit * self::REQUEST_TIMEOUT;
        }

        return $this->mutex->acquireAndRelease(
            $mutexResource,
            function() use ($input, $channel)
            {
                RuntimeManager::setMemoryLimit('1024M');

                return $this->processBankTransfers($input, $channel);
            },
            $mutexTimeout,
            ErrorCode::BAD_REQUEST_FUND_TRANSFER_ANOTHER_OPERATION_IN_PROGRESS);
    }

    /**
     * @param array $input
     * @param string $channel
     * @return array
     */
    protected function processBankTransfers(array $input, string $channel): array
    {
        $this->trace->info(TraceCode::FTA_PROCESS_BEGIN);

        return $this->repo->transaction(function() use ($input, $channel)
        {
            (new Validator)->validateInput('initiate_fund_transfer', $input);

            $purpose = $input[Entity::PURPOSE];

            $sourceType = $input[Entity::SOURCE_TYPE];

            $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

            $limit = $this->getLimitForChannel($channel);

            $unsupportedModeList = $this->getUnsupportedModesForTime($timestamp);

            $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_FTA_FETCHING_ENTITIES);

            $attempts = $this->repo
                             ->fund_transfer_attempt
                             ->getCreatedAttemptsBeforeTimestamp(
                                $timestamp,
                                $purpose,
                                $sourceType,
                                $channel,
                                $unsupportedModeList,
                                $limit,
                                ['source']);

            $this->trace->info(
                TraceCode::FTA_FETCHED,
                [
                    'limit'         => $limit,
                    'timestamp'     => $timestamp,
                    'source_type'   => $sourceType,
                    'count'         => $attempts->count()
                ]);

            $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_FTA_ENTITIES_FETCHED);

            $data[$channel] = [];

            $forceFlag = false;

            if ((isset($input[Constants::IGNORE_TIME_LIMIT]) === true) and
                ($input[Constants::IGNORE_TIME_LIMIT] === '1'))
            {
                $forceFlag = true;
            }

            // Since yesbank fund transfer with purpose settlement need Beneficiary
            // Registration and verification, they will go via queue.
            if (($channel === Channel::YESBANK) and ($purpose == Type::SETTLEMENT))
            {
                $response = $this->dispatchTransfers($channel, $attempts, $forceFlag);
            }
            else
            {
                $response = $this->processFundTransferAttempts($channel, $attempts);
            }

            $data[$channel]  = $response;

            return $data;
        });
    }

    public function processFundTransferAttempts(
        string $channel, Base\PublicCollection $attempts, bool $forceFlag = false): array
    {
        $count = $attempts->count();

        $data = ['channel' => $channel, 'count' => $count];

        $slackData = $data;

        if ($count === 0)
        {
            $data['message'] = 'No Attempts to process';

            return $data;
        }

        $purpose = $attempts->first()->getPurpose();

        $medium = (in_array($channel, Channel::getApiBasedChannels(), true) === true) ?
            Medium::API : Medium::FILE;

        $customProperties = [
            'channel'                       => $channel,
            'fund_transfer_attempt_count'   => $count,
            'fund_transfer_attempt_purpose' => $purpose,
            'fund_transfer_attempt_medium'  => $medium,
        ];

        $this->raiseSettlementEvent(
            EventCode::BATCH_FUND_TRANSFER_CREATION_INITIATED,
            null,
            null,
            $customProperties);

        try
        {
            $allowedChannels = Channel::getApiBasedChannels();

            $class = "RZP\\Models\\FundTransfer\\" . ucfirst($channel) . "\\NodalAccount";

            $attemptInitiator = new $class($purpose);

            if (in_array($channel, $allowedChannels, true) === true)
            {
                list($response, $attemptedFTAs) = (new Lock($channel))->acquireLockAndProcessAttempts(
                    $attempts,
                    function(PublicCollection $collection) use ($purpose, $channel, $forceFlag, $attemptInitiator)
                    {
                        return [
                            $attemptInitiator->initiateTransfer($collection, $forceFlag),
                            $collection
                        ];
                    });

                $this->dispatchForReconAndStatusCheck($attemptedFTAs);
            }
            else
            {
                // File based channels to use a timeout of 30 sec
                $response = $attemptInitiator->initiateTransfer($attempts, $forceFlag);

                $attemptedFTAs = $attempts;
            }

            $data += $response;

            $this->trace->info(TraceCode::SETTLEMENT_INITIATED, $data);

            //reducing slack alerts for API based channels
            if (in_array($channel, $allowedChannels, true) === false)
            {
                (new SlackNotification)->send('setl_initiate', $slackData);
            }

            if($attemptedFTAs->isEmpty() === false)
            {
                $this->raiseBatchFtaCreatedEvent($channel, $attemptedFTAs, $purpose, $medium);
            }
        }
        catch (\Throwable $exception)
        {
            $customProperties = [
                'channel'                       => $channel,
                'fund_transfer_attempt_count'   => $count,
                'purpose'                       => $purpose,
                'medium'                        => $medium,
            ];

            $this->raiseSettlementEvent(
                EventCode::BATCH_FUND_TRANSFER_CREATION_FAILED,
                null,
                $exception,
                $customProperties);

            $this->trace->traceException(
                $exception,
                Trace::CRITICAL,
                TraceCode::FUND_TRANSFER_ATTEMPT_INITIATE_FAILED,
                [
                    'channel'                       => $channel,
                    'fund_transfer_attempt_count'   => $count,
                    'fund_transfer_attempt_purpose' => $purpose,
                    'fund_transfer_attempt_medium'  => $medium,
                ]
               );

            throw  $exception;
        }

        return $data;
    }

    protected function raiseBatchFtaCreatedEvent($channel, $attemptedFTAs, $purpose, $medium)
    {
        $startTime = microtime(true);

        $batchFundTransfer = $attemptedFTAs->first()->batchFundTransfer;

        $batchFTaId = null;

        $batchAmount = null;

        $transactionCount = null;

        $ftaCountInBatch = null;

        if(empty($batchFundTransfer) === false)
        {
            $batchFTaId = $batchFundTransfer->getId();

            $batchAmount = $batchFundTransfer->getAmount();

            $transactionCount = $batchFundTransfer->getTransactionCount();

            $ftaCountInBatch = $batchFundTransfer->getTotalCount();
        }

        $customProperties = [
            'channel'                               => $channel,
            'fund_transfer_attempt_count'           => $ftaCountInBatch,
            'purpose'                               => $purpose,
            'fund_transfer_attempt_medium'          => $medium,
            'batch_fund_transfer_id'                => $batchFTaId,
            'batch_fund_transfer_attempt_amount'    => $batchAmount,
            'transaction_count'                     => $transactionCount,
        ];

        $attemptedFTAs->each(function ($attemptedFTA) use ($customProperties)
        {
            $ftaId = $attemptedFTA->getId();

            $customProperties['fund_transfer_attempt_id'] = $ftaId;

            $this->raiseSettlementEvent(
                EventCode::BATCH_FUND_TRANSFER_CREATION_SUCCESS,
                null,
                null,
                $customProperties
            );

            return true;
        });

        $this->trace->info(TraceCode::FTA_BATCH_SUCCESS_EVENT_TIME_TAKEN,
            [
                'batch_fund_transfer_id'        => $batchFTaId,
                'fund_transfer_attempt_count'   => $ftaCountInBatch,
                'time_taken'                    => get_diff_in_millisecond($startTime),
            ]);
    }

    protected function dispatchFtaForStatusCheckProcess(Entity $attempt)
    {
        try
        {
            // Default delay for status dispatch.
            $delay = Constants::DEFAULT_STATUS_CHECK_DISPATCH_TIME;

            if ($attempt->getMode() === TransferMode::IMPS)
            {
                // For IMPS we receive the status in 10 sec. (Observed for YESBANK)
                $delay = Constants::IMPS_STATUS_CHECK_DISPATCH_TIME;
            }
            //
            // Dispatching in 180 sec as all the operation are happening in queue
            // and bank generally update the status in 2 min
            // TODO: observe the response time from bank and update the wait time accordingly
            //
            AttemptStatusCheckJob::dispatch($this->mode, $attempt->getId())->delay($delay);

            $this->trace->info(
                TraceCode::FTA_STATUS_CHECK_JOB_DISPATCHED,
                [
                    'mode'   => $this->mode,
                    'fta_id' => $attempt->getId(),
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FTA_STATUS_CHECK_DISPATCH_FAILED,
                [
                    'mode'   => $this->mode,
                    'fta_id' => $attempt->getId(),
                ]);
        }
    }

    protected function dispatchFtaForReconProcess(Entity $attempt)
    {
        // TODO: Allow for all, after testing payouts.
        if (in_array($attempt->getSourceType(), [Type::PAYOUT, Type::REFUND], true) === false)
        {
            return;
        }

        try
        {
            AttemptsReconJob::dispatch($this->mode, $attempt->getId())->delay(300);

            $this->trace->info(
                TraceCode::FTA_RECON_JOB_DISPATCHED,
                [
                    'mode'   => $this->mode,
                    'fta_id' => $attempt->getId(),
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FTA_RECONCILE_DISPATCH_FAILED,
                [
                    'mode'   => $this->mode,
                    'fta_id' => $attempt->getId(),
                ]);
        }
    }

    protected function getTransactionsCount(Entity $attempt): int
    {
        $sourceType = $attempt->getSourceType();

        switch ($sourceType)
        {
            case Type::SETTLEMENT:

                $source = $attempt->source;

                return $source->setlTransactions->count();

            default:
                return 1;
        }
    }

    /**
     * Returns the maximum number of attempts that can
     * be processed by a channel in one request.
     * If null is returned, it means there is no
     * such limit for that channel.
     *
     * @param string $channel
     * @return int|null
     */
    public function getLimitForChannel(string $channel): int
    {
        switch ($channel)
        {
            case Channel::AXIS:
                return 400;

            case Channel::YESBANK:
                return 100;

            case Channel::ICICI:
                return 400;

            case Channel::KOTAK:
                return 0;

            case Channel::AXIS2:
                return 400;

            default:
                return 100;
        }
    }

    /**
     *
     * @param string $channel
     * @return bool Returns if transfers can be initiated now
     * Returns if transfers can be initiated now
     */
    protected function isValidTime(string $channel): bool
    {
        if (in_array($this->env, ['testing', 'perf', 'func'], true) === true)
        {
            return true;
        }

        if (in_array($channel, Channel::get24x7Channels(), true) === true)
        {
            $this->trace->info(TraceCode::FTA_INITIATE_247);

            return true;
        }

        // Checks for holidays.
        if (Holidays::isWorkingDay(Carbon::today(Timezone::IST)) === false)
        {
            $this->trace->info(TraceCode::FUND_TRANSFER_ATTEMPT_INITIATE_SKIPPED, [
                'channel'   => $channel,
                'message'   => 'Holiday today!',
            ]);

            return false;
        }

        // Banking hours start time.
        $startTime = Carbon::today(Timezone::IST)->hour(8)->getTimestamp();

        // Banking hours end time.
        $endTime = Carbon::today(Timezone::IST)->hour(18)->minute(15)->getTimestamp();

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $this->trace->info(
            TraceCode::FTA_INITIATE_TIMES,
            [
                'banking_start_time'    => $startTime,
                'banking_ending_time'   => $endTime,
                'current_time'          => $currentTime
            ]);

        // Checks for non Banking hours on working days.
        if (($currentTime < $startTime) or
            ($currentTime > $endTime))
        {
            $this->trace->info(TraceCode::FUND_TRANSFER_ATTEMPT_INITIATE_SKIPPED, [
                'channel'   => $channel,
                'message'   => 'Outside banking hours',
            ]);

            return false;
        }

        return true;
    }

    public function traceMemoryUsage(string $traceCode)
    {
        $memoryAllocated = get_human_readable_size(memory_get_usage(true));
        $memoryUsed = get_human_readable_size(memory_get_usage());
        $memoryPeakUsage = get_human_readable_size(memory_get_peak_usage());
        $memoryPeakUsageAllocated = get_human_readable_size(memory_get_peak_usage(true));

        $this->trace->info(
            $traceCode,
            [
                'memory_allocated'               => $memoryAllocated,
                'memory_used'                    => $memoryUsed,
                'memory_peak_usage'              => $memoryPeakUsage,
                'memory_peak_usage_allocated'    => $memoryPeakUsageAllocated,
            ]);
    }

    /**
     * This will be called for individual fta processing
     *
     * @param Entity $fta
     * @param        $channel
     * @param        $forceFlag
     */
    public function initFundTransferOnChannel(Entity $fta, $channel, bool $forceFlag = false)
    {
        $data = [
            'fta_id'  => $fta->getId(),
            'source'  => $fta->getSourceId(),
            'channel' => $channel,
        ];

        $this->trace->info(TraceCode::FTA_MERCHANT_FUND_TRANSFER_INIT, $data);

        $attempts = (new PublicCollection)->push($fta);

        $response = $this->processFundTransferAttempts($channel, $attempts, $forceFlag);

        $this->trace->info(TraceCode::FTA_MERCHANT_FUND_TRANSFER_COMPLETE,  $data + $response);
    }

    protected function dispatchForReconAndStatusCheck($attemptedFTAs)
    {
        // Dispatching after lock is released as this should also work in sync mode
        // This dispatch is will happen only on locked attempts in above step
        foreach ($attemptedFTAs as $attempt)
        {
            // For bank accounts, we anyway don't get the status in initiate. So no use
            // of dispatching it as part of initiate request. In VPA, we get the status.
            if ($attempt->hasVpa() === true)
            {
                $this->dispatchFtaForReconProcess($attempt);
            }
            else
            {
                $this->dispatchFtaForStatusCheckProcess($attempt);
            }
        }

        return;
    }

    /**
     * Restricts transfer in test mode or after invalid time
     *
     * @param string $channel
     * @return array
     */
    protected function shouldProcessBankTransfers(array $input, string $channel = null): array
    {
        if (isset($input[Entity::PURPOSE]) === true and $input[Entity::PURPOSE] === Purpose::PENNY_TESTING)
        {
            return [true, null];
        }

        if (($this->env === Environment::PRODUCTION) and ($this->mode === Mode::TEST))
        {
            return [false, 'Invalid mode to initiate transfer'];
        }
        //
        // If the force flag is set,
        // let the fund transfer go
        //
        if ((isset($input['ignore_time_limit']) === true) and
            ($input['ignore_time_limit'] === '1'))
        {
            return [true, null];
        }

        if ($this->isValidTime($channel) === false)
        {
            return [false, 'Invalid time to initiate transfer'];
        }

        return [true, null];
    }

    /**
     * Takes a list of attempt ids and dispatches to the queue
     * @param string $channel
     * @param PublicCollection $attempts
     * @param forceFlag
     * @return array
     */
    protected function dispatchTransfers(string $channel, Base\PublicCollection $attempts, bool $forceFlag = false): array
    {
        $attemptIds = $attempts->pluck(Entity::ID);

        $info = [
            'channel' => $channel,
            'count' => $attempts->count()
        ];

        $successCount = 0;

        $failureCount = 0;

        foreach ($attemptIds as $id)
        {
            try
            {
                $this->trace->info(TraceCode::FTA_MERCHANT_FUND_TRANSFER_INIT,
                    [
                        'fta_id'  => $id,
                        'data'    => $info
                    ]);

                FundTransfer::dispatch($this->mode, $id, $forceFlag);

                $successCount ++;

                $this->trace->info(TraceCode::FTA_MERCHANT_FUND_TRANSFER_DISPATCHED,
                    [
                        'fta_id'  => $id,
                        'data'    => $info
                    ]);
            }
            catch (\Exception $exception)
            {
                $failureCount++;

                $this->trace->traceException(
                    $exception,
                    Trace::CRITICAL,
                    TraceCode::FTA_TRANSFER_DISPATCH_FAILED
                );
            }
        }

        $info += [
            "success" => $successCount,
            "failed"  => $failureCount,
        ];

        return $info;
    }

    public function processFundTransfersUsingFts(array $input, string $channel)
    {
        $this->trace->info(
            TraceCode::FTS_TRANSFER_ACTION_INIT,
            [
                'input'   => $input,
                'channel' => $channel
            ]);

        (new Validator)->validateInput('fts_fund_transfer', $input);

        if (in_array($channel, Channel::getFtsSupportedChannels(), true) === false)
        {
            throw new LogicException($channel . ' channel is not supported for transfers via fts');
        }

        $action = $input['action'];

        $mutexResource = sprintf(self::MUTEX_FTS_RESOURCE, $this->mode, $channel, $action);

        $response = $this->mutex->acquireAndRelease(
            $mutexResource,
            function() use ($input, $channel)
            {
                return $this->processBankTransfersThroughFts($input, $channel);
            },
            self::FTS_REQUEST_TIMEOUT,
            ErrorCode::BAD_REQUEST_FUND_TRANSFER_FTS_ANOTHER_OPERATION_IN_PROGRESS);


        $this->trace->info(
            TraceCode::FTS_TRANSFER_ACTION_COMPLETE,
            [
                'response' => $response
            ]);

        return $response;
    }

    /**
     * @param array $input
     * @param string $channel
     * @return array
     * @throws LogicException
     */
    protected function processBankTransfersThroughFts(array $input, string $channel)
    {
        $action = $input['action'];

        $status = $this->getAttemptStatusByAction($action);

        $attempts = $this->fetchAttemptsForProcessing($input, $channel, $status);

        $response = $this->processAttemptsByAction($attempts, $action);

        return $response;
    }

    /**
     * @param Entity $fta
     * @return bool
     */
    public function sendFTSFundTransferRequest(Entity $fta, string $otp = null): bool
    {
        try
        {
            if ($this->isTestMode() === true)
            {
                return true;
            }

            FtsFundTransfer::dispatch($this->mode, $fta->getId(), $otp);

            $this->trace->info(
                TraceCode::FTS_FUND_TRANSFER_JOB_DISPATCHED,
                [
                    'fta_id'      => $fta->getId(),
                    'source_type' => $fta->getSourceType(),
                ]);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTS_FUND_TRANSFER_DISPATCH_FAILED,
                [
                    'fta_id'      => $fta->getId(),
                    'source_type' => $fta->getSourceType(),
                ]);

            return false;
        }

        return true;
    }

    /**
     * @param array $input
     * @param string $channel
     * @param string $status
     * @return mixed|null
     * @throws LogicException
     */
    protected function fetchAttemptsForProcessing(array $input, string $channel, string $status)
    {
        if (isset($input['size']) === true)
        {
            return $this->repo
                        ->fund_transfer_attempt
                        ->getFtsAttempts($channel, $status, $input['size']);
        }

        if (isset($input[Entity::ID]) === true)
        {
            $input[Entity::ID] = Entity::verifyIdAndStripSign($input[Entity::ID]);

            return $this->repo
                        ->fund_transfer_attempt
                        ->getFtsAttempts($channel, $status, null, $input[Entity::ID]);
        }

        if ((isset($input['from']) === true)||(isset($input['to']) === true)||(isset($input['limit']) === true))
        {
            if ($input['from'] > $input['to'])
            {
                throw new LogicException('Invalid timestamp specified');

            }

            return $this->repo
                        ->fund_transfer_attempt
                        ->getFtsAttempts(
                            $channel,
                            $status,
                            null,
                            null,
                            $input['from'],
                            $input['to'],
                            $input['limit']);
        }

        return null;
    }

    /**
     * @param $attempts
     * @param string $action
     * @return array
     */
    protected function processAttemptsByAction($attempts, string $action): array
    {
        $count = $attempts->count();

        $response = [ 'success' => 0, 'failure' => 0, 'total' => $count ];

        if ($action === FtsService::TRANSFER_RETRY)
        {
            return $this->retryFtsTransfers($attempts, $response);
        }

        return $response;
    }

    /**
     * Decides the status in which an
     * attempt shall be taken for processing.
     *
     * @param string $action
     * @return string
     * @throws LogicException
     */
    protected function getAttemptStatusByAction(string $action)
    {
        if ($action === FtsService::TRANSFER_RETRY)
        {
            return Status::CREATED;
        }

        throw new LogicException('Invalid action to derive attempt status');
    }

    /**
     * @param PublicCollection $attempts
     * @param array $response
     * @return array
     */
    protected function retryFtsTransfers(PublicCollection $attempts, array $response)
    {
        $this->trace->info(
            TraceCode::FTS_TRANSFER_RETRY_ACTION_INIT,
            [
                'attempts' => $attempts->count(),
            ]);

        foreach ($attempts as $attempt)
        {
            $result = $this->sendFTSFundTransferRequest($attempt);

            if ($result === true)
            {
                $response['success']++;
            }
            else
            {
                $response['failure']++;
            }
        }

        $this->trace->info(
            TraceCode::FTS_TRANSFER_RETRY_ACTION_COMPLETE,
            [
                'response' => $response,
            ]);

        return $response;
    }

    protected function raiseSettlementEvent(array $eventDetails,
                                            Settlement\Entity $settlement = null,
                                            \Throwable $exception = null,
                                            array $customProperties = [])
    {

        $this->app['diag']->trackSettlementEvent(
            $eventDetails,
            $settlement,
            $exception,
            $customProperties);
    }

    // This will return the mode which are unsupported due to being outside of timing window.
    // The time uses minimum Start Timing of all banks supported for razorpayX payouts and
    // maximum ending timing. Since Nodal account class for each channel has its own timing
    // so transfer initiation will get blocked there if the timings are different for that channel.
    public function getUnsupportedModesForTime(int $currentTime)
    {
        $modeList = [];

        if ($this->isOutsideNeftTimings($currentTime) === true)
        {
            $modeList[] = TransferMode::NEFT;
        }

        if ($this->isOutsideRtgsTimings($currentTime) === true)
        {
            $modeList[] = TransferMode::RTGS;
        }

        return $modeList;
    }

    public function isOutsideNeftTimings(int $currentTime)
    {
        $bankingStartTime = Carbon::today(Timezone::IST)->hour(Constants::NEFT_START_HOUR)->getTimestamp();

        $bankingEndTime = Carbon::today(Timezone::IST)->hour(Constants::NEFT_END_HOUR)
                                                          ->minute(Constants::NEFT_END_MINUTE)
                                                          ->getTimestamp();

        if (($currentTime < $bankingStartTime) or
            ($currentTime > $bankingEndTime))
        {
            return true;
        }

        return false;
    }

    public function isOutsideRtgsTimings(int $currentTime)
    {
        $bankingStartTimeRtgs = Carbon::today(Timezone::IST)->hour(Constants::RTGS_REVISED_START_HOUR)->getTimestamp();

        $bankingEndTimeRtgs = Carbon::today(Timezone::IST)->hour(Constants::RTGS_REVISED_END_HOUR)
                                                              ->minute(Constants::RTGS_REVISED_END_MINUTE)
                                                              ->getTimestamp();

        if (($currentTime < $bankingStartTimeRtgs) or
            ($currentTime > $bankingEndTimeRtgs))
        {
            return true;
        }

        return false;
    }
}
