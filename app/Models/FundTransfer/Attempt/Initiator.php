<?php

namespace RZP\Models\FundTransfer\Attempt;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Models\Settlement\Channel;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Settlement\SlackNotification;

class Initiator extends Base\Core
{
    const MUTEX_RESOURCE        = 'FUND_TRANSFER_PROCESSING_%s';
    const MUTEX_LOCK_TIMEOUT    = 900;

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
        $isValidTime = $this->isValidTime($channel);

        if ($isValidTime === false)
        {
            return [
                'channel'   => $channel,
                'count'     => 0,
                'message'   => 'Invalid time to initiate transfer'
            ];
        }

        $mutexResource = sprintf(self::MUTEX_RESOURCE, $channel);

        return $this->mutex->acquireAndRelease(
            $mutexResource,
            function() use ($input, $channel)
            {
                RuntimeManager::setMemoryLimit('1024M');

                return $this->processBankTransfers($input, $channel);
            },
            self::MUTEX_LOCK_TIMEOUT,
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

            $sourceType = $input[Entity::SOURCE_TYPE] ?? null;

            $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

            $limit = $this->getLimitForChannel($channel);

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

            $data[$channel] = $this->processFundTransferAttempts($purpose, $channel, $attempts);

            return $data;
        });
    }

    protected function processFundTransferAttempts(
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

        $class = "RZP\\Models\\FundTransfer\\" . ucfirst($channel) . "\\NodalAccount";

        $response = (new $class($purpose))->initiateTransfer($attempts);

        $data += $response;

        $this->trace->info(TraceCode::SETTLEMENT_INITIATED, $data);

        (new SlackNotification)->send('setl_initiate', $slackData);

        return $data;
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
                return 500;

            case Channel::YESBANK:
                return 100;

            case Channel::ICICI:
                return null;

            case Channel::KOTAK:
                return null;

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
}
