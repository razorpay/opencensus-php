<?php

namespace RZP\Models\FundTransfer\Attempt;

use Carbon\Carbon;
use Monolog\Logger;
use App\Trace\Trace;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Jobs\FundTransfer;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Constants\Environment;
use RZP\Models\Settlement\Channel;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Settlement\SlackNotification;
use RZP\Jobs\AttemptsRecon as AttemptsReconJob;
use RZP\Models\FundTransfer\Mode as TransferMode;
use RZP\Jobs\AttemptStatusCheck as AttemptStatusCheckJob;

class Initiator extends Base\Core
{
    const MUTEX_RESOURCE                    = 'FUND_TRANSFER_PROCESSING_%s_%s_%s';
    const DEFAULT_LIMIT_FOR_MUTEX_TIMEOUT   = 500;
    const REQUEST_TIMEOUT                   = 30;

    const FTA_PURPOSE                       = 'settlement';

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

        $mutexResource = sprintf(self::MUTEX_RESOURCE, $this->mode, $channel, self::FTA_PURPOSE);

        $purpose ='';

        if ($channel === Channel::YESBANK)
        {
            if ((isset($input[Entity::PURPOSE]) === true) and (Purpose::isValid($input[Entity::PURPOSE])))
            {
                $mutexResource = sprintf(self::MUTEX_RESOURCE, $this->mode, $channel, $input[Entity::PURPOSE]);

                $purpose = $input[Entity::PURPOSE];
            }
            else
            {
                return [
                    'channel'   => $channel,
                    'count'     => 0,
                    'message'   => 'Invalid purpose for fund transfer'
                ];
            }
        }

        $limit = $this->getLimitForChannel($channel) ?? self::DEFAULT_LIMIT_FOR_MUTEX_TIMEOUT;

        $mutexTimeout = $limit * self::REQUEST_TIMEOUT;

        return $this->mutex->acquireAndRelease(
            $mutexResource,
            function() use ($input, $channel, $purpose)
            {
                RuntimeManager::setMemoryLimit('1024M');

                return $this->processBankTransfers($input, $channel, $purpose);
            },
            $mutexTimeout,
            ErrorCode::BAD_REQUEST_FUND_TRANSFER_ANOTHER_OPERATION_IN_PROGRESS);
    }

    /**
     * @param array $input
     * @param string $channel
     * @param string $purpose
     * @return array
     */
    protected function processBankTransfers(array $input, string $channel, string $purpose): array
    {
        $this->trace->info(TraceCode::FTA_PROCESS_BEGIN);

        return $this->repo->transaction(function() use ($input, $channel, $purpose)
        {
            (new Validator)->validateInput('initiate_fund_transfer', $input);

            $purpose = $input[Entity::PURPOSE];

            $sourceType = $input[Entity::SOURCE_TYPE] ?? null;

            $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

            $limit = $this->getLimitForChannel($channel);

            $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_FTA_FETCHING_ENTITIES);

            $attempts = $this->repo
                             ->fund_transfer_attempt
                             ->getCreatedAttemptsBeforeTimestamp(
                                $timestamp,
                                $purpose,
                                $sourceType,
                                $channel,
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

            $data[$channel] = 0;

            if (($channel === Channel::YESBANK) and ($purpose === Purpose::SETTLEMENT))
            {
                $attemptIds = $attempts->pluck(Entity::ID);

                return $this->dispatchTransfersForSettlement($channel, $attemptIds, $data);
            }
            else
            {
                $data[$channel] = $this->processFundTransferAttempts($purpose, $channel, $attempts);
            }
            return $data;
        });
    }

    public function processFundTransferAttempts(
        string $purpose, string $channel, Base\PublicCollection $attempts): array
    {
        $count = $attempts->count();

        $data = ['channel' => $channel, 'count' => $count];

        $slackData = $data;

        if ($count === 0)
        {
            $data['message'] = 'No Attempts to process';

            return $data;
        }

        list($response, $attemptedFTAs) = (new Lock($channel))->acquireLockAndProcessAttempts(
            $attempts,
            function(PublicCollection $collection) use ($purpose, $channel)
            {
                $class = "RZP\\Models\\FundTransfer\\" . ucfirst($channel) . "\\NodalAccount";

                return [
                    (new $class($purpose))->initiateTransfer($collection),
                    $collection
                ];
            });

        $allowedChannels = Channel::getApiBasedChannels();

        if (in_array($channel, $allowedChannels, true) === true)
        {
            $this->dispatchForReconAndStatusCheck($attemptedFTAs);
        }

        $data += $response;

        $this->trace->info(TraceCode::SETTLEMENT_INITIATED, $data);

        (new SlackNotification)->send('setl_initiate', $slackData);

        return $data;
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
        if ($attempt->getSourceType() !== Type::PAYOUT)
        {
            return;
        }

        try
        {
            AttemptsReconJob::dispatch($this->mode, $attempt->getId());

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
    protected function getLimitForChannel(string $channel)
    {
        switch ($channel)
        {
            case Channel::AXIS:
                return 400;

            case Channel::YESBANK:
                return 100;

            case Channel::ICICI:
                return null;

            case Channel::KOTAK:
                return null;

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

        if (Holidays::isWorkingDay(Carbon::today(Timezone::IST)) === false)
        {
            $this->trace->info(TraceCode::FUND_TRANSFER_ATTEMPT_INITIATE_SKIPPED, [
                'channel'   => $channel,
                'message'   => 'Holiday today!',
            ]);

            return false;
        }

        $startTime = Carbon::today(Timezone::IST)->hour(8)->getTimestamp();

        $endTime = Carbon::today(Timezone::IST)->hour(18)->minute(15)->getTimestamp();

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $this->trace->info(
            TraceCode::FTA_INITIATE_TIMES,
            [
                'banking_start_time'    => $startTime,
                'banking_ending_time'   => $endTime,
                'current_time'          => $currentTime
            ]);

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
     */
    public function initFundTransferOnChannel(Entity $fta, $channel)
    {
        $data = [
            'fta_id'  => $fta->getId(),
            'source'  => $fta->getSourceId(),
            'channel' => $channel,
        ];

        $this->trace->info(TraceCode::FTA_MERCHANT_FUND_TRANSFER_INIT, $data);

        $attempts = (new PublicCollection)->push($fta);

        $response = $this->processFundTransferAttempts($fta->getPurpose(), $channel, $attempts);

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

        if ($this->isValidTime($channel) === false)
        {
            return [false, 'Invalid time to initiate transfer'];
        }

        return [true, null];
    }

    protected function dispatchTransfersForSettlement(string $channel, array $attemptIds, array $data)
    {
        foreach ($attemptIds as $id)
        {
            try
            {
                $this->trace->info(TraceCode::FTA_MERCHANT_FUND_TRANSFER_INIT,  $data);

                FundTransfer::dispatch($this->mode, $id);

                $data[$channel]++;

                $this->trace->info(TraceCode::FTA_MERCHANT_FUND_TRANSFER_COMPLETE,  $data);
            }
            catch (\Exception $exception)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::CRITICAL,
                    TraceCode::FTA_TRANSFER_DISPATCH_FAILED
                );
            }
        }
        return $data;
    }
}
