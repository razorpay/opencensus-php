<?php

namespace RZP\Jobs;

use Config;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\RateLimiter\FixedWindowLimiter;

class ApprovedPayoutDistribution extends Job
{
    protected $mode;

    protected $queueConfigKey = 'approved_payout_distribute';

    public $data;

    public $messageGroup;

    public $rawJob;

    const DEFAULT_VISIBILITY_TIMEOUT_VALUE = 30;

    /**
     * Create a new job instance.
     * @param string $mode
     * @param array $payload
     * @param string $messageGroup
     */
    public function __construct(
        string $mode,
        array $payload,
        string $messageGroup
    )
    {
        parent::__construct($mode);
        $this->mode = $mode;
        $this->data = $payload;
        $this->messageGroup = $messageGroup;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try
        {
            $startTime = floor(microtime(true) * 1000);

            parent::handle();

            $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_DISTRIBUTION_INITIATE, [
                'data'          => $this->data,
                'message_group' => $this->messageGroup,
                'job'           => $this,
            ]);

            // 15 TPS -> 30 requests/2 seconds -> because visibility timeout cannot be set in milliseconds
            $rateLimit = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::PAYOUT_ASYNC_APPROVE_DISTRIBUTION_RATE_LIMIT]);

            $windowLengthInSeconds = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::PAYOUT_ASYNC_APPROVE_DISTRIBUTION_WINDOW_LENGTH]);

            $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_RATE_LIMIT_CHECK, [
                'data'          => $this->data,
                'message_group' => $this->messageGroup,
            ]);

            $rateLimiter = new FixedWindowLimiter($rateLimit, $windowLengthInSeconds, $this->queueConfigKey . "_" . $this->messageGroup);

            if ($rateLimiter->checkLimit() === true)
            {
                $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_PROCESSOR_QUEUE_PUSH, [
                    'message_group'             => $this->messageGroup,
                    'current_request_number'    => $rateLimiter->getCurrentRequestNumber(),
                ]);

                // dispatch to processor queue
                ApprovedPayoutProcessor::dispatch($this->mode, $this->data['input'], $this->data['payout_id'], $this->data['is_approved']);

                $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_PROCESSOR_QUEUE_PUSH_SUCCESS, [
                    'message_group'              => $this->messageGroup,
                    'current_request_number'     => $rateLimiter->getCurrentRequestNumber()
                ]);

                $this->delete();
            }
            else
            {
                $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_DISTRIBUTION_RATE_LIMITED, [
                    'message_group'             => $this->messageGroup,
                    'current_request_number'    => $rateLimiter->getCurrentRequestNumber(),
                    'key'                       => $rateLimiter->getKey()
                ]);

                $delay = ($rateLimiter->getRemainingTimeForWindowResetInMs() < 1000) ? 1 : 2;

                $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_DISTRIBUTION_JOB_RELEASE, [
                    'key'                        => $rateLimiter->getKey(),
                    'message_group'              => $this->messageGroup,
                    'computed_delay'             => $delay,
                    'time_to_reset_in_ms'        => $rateLimiter->getRemainingTimeForWindowResetInMs()
                ]);

                $this->release($delay);
            }

            $endTime = floor(microtime(true) * 1000);

            $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_DISTRIBUTION_COMPLETE, [
                'data'          => $this->data,
                'message_group' => $this->messageGroup,
                'processing_time'    => $endTime - $startTime
            ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::PAYOUT_ASYNC_APPROVE_DISTRIBUTION_EXCEPTION);

            $this->release();
        }
    }
}
