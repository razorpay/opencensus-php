<?php

namespace RZP\Jobs;

use App;
use Config;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Payout\Core;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\RateLimiter\FixedWindowLimiter;

class ApprovedPayoutProcessor extends Job
{
    protected $mode;

    protected $queueConfigKey = 'approved_payout_processor';

    public $data;

    protected $payoutId;

    protected $isApproved;

    protected $attempts;

    protected $merchantId;

    const MAX_RETRIES = 2;

    /**
     * Create a new job instance.
     * @param string $mode
     * @param array $input
     * @param string $payoutId
     * @param bool $isApproved
     */
    public function __construct(
        string $mode,
        array $input,
        string $payoutId,
        bool $isApproved
    )
    {
        parent::__construct($mode);

        $this->mode = $mode;
        $this->data = $input;
        $this->payoutId = $payoutId;
        $this->isApproved = $isApproved;

        $this->extractAttemptsFromInput();
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

            $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_PROCESSOR_INITIATE, [
                'data'          => $this->data,
                'job'           => $this
            ]);

            $payout = $this->repoManager->payout->findByPublicId($this->payoutId);

            $payout->getValidator()->validatePayoutStatusForApproveOrReject();

            $this->merchantId = $payout->getMerchantId();

            $rateLimit = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::PAYOUT_ASYNC_APPROVE_PROCESSING_RATE_LIMIT]);

            $windowLengthInSeconds = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::PAYOUT_ASYNC_APPROVE_PROCESSING_WINDOW_LENGTH]);

            $rateLimiter = new FixedWindowLimiter($rateLimit, $windowLengthInSeconds, $this->queueConfigKey . "_" . $this->merchantId);

            if ($rateLimiter->checkLimit() === true)
            {
                (new Core)->processActionOnPayout($this->isApproved, $payout, $this->data);

                $endTime = floor(microtime(true) * 1000);

                $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_PROCESSOR_COMPLETE, [
                    'data'               => $this->data,
                    'merchant_id'        => $this->merchantId,
                    'payout_id'          => $this->payoutId,
                    'processing_time'    => $endTime - $startTime
                ]);

                $this->delete();
            }
            else
            {
                $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_PROCESSOR_RATE_LIMITED, [
                    'merchant_id'               => $this->merchantId,
                    'current_request_number'    => $rateLimiter->getCurrentRequestNumber(),
                    'key'                       => $rateLimiter->getKey(),
                    'payout_id'                 => $this->payoutId,
                ]);

                $this->release();
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::PAYOUT_ASYNC_APPROVE_PROCESSOR_EXCEPTION);

            $this->checkRetry($e);
        }
    }

    private function extractAttemptsFromInput()
    {
        if (array_key_exists('attempts', $this->data))
        {
            $this->attempts = $this->data['attempts'];

            unset($this->data['attempts']);
        }
        else
        {
            $this->attempts = 0;
        }
    }

    private function checkRetry(\Throwable $error = null)
    {
        if ($this->attempts < self::MAX_RETRIES)
        {
            $this->data['attempts'] = $this->attempts + 1;

            $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_PROCESSOR_RETRY, [
                'merchant_id'        => $this->merchantId,
                'retry_attempt'      => $this->attempts,
                'payout_id'          => $this->payoutId,
            ]);

            // not calling release here, since data needs to be changed for the job. Can figure out later if there is a way to use release with updated data
            ApprovedPayoutProcessor::dispatch($this->mode, $this->data, $this->payoutId, $this->isApproved);
        }
        else
        {
            $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_PROCESSOR_PUSH_TO_DLQ, [
                'retry_attempt' => $this->attempts,
                'merchant_id'   => $this->merchantId,
                'error'         => $error,
                'payout_id'     => $this->payoutId,
            ]);

            $payload = [
                'data'          => $this->data,
                'payout_id'     => $this->payoutId,
                'merchant_id'   => $this->merchantId
            ];

            $app = App::getFacadeRoot();

            $queueName = $app['config']->get('queue.approved_payout_processor_dlq.' . $this->mode);

            $app['queue']->connection('sqs')->pushRaw(json_encode($payload), $queueName);

            $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_PROCESSOR_PUSH_TO_DLQ, [
                'retry_attempt' => $this->attempts,
                'merchant_id'   => $this->merchantId,
                'error'         => $error,
                'payout_id'     => $this->payoutId,
                'queue'         => $queueName,
                'payload'       => json_encode($payload)
            ]);
        }

        $this->delete();
    }
}
