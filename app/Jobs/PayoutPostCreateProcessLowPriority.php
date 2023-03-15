<?php

namespace RZP\Jobs;

use RZP\Trace\Tracer;
use RZP\Models\Payout;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\HyperTrace;
use RZP\Models\Payout\Entity;
use RZP\Exception\LogicException;
use RZP\Jobs\Extended\PendingDispatch;
use RZP\Models\Settlement\SlackNotification;

class PayoutPostCreateProcessLowPriority extends Job
{
    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 10;

    const PAYOUT_CREATE_MUTEX_LOCK_TIMEOUT = 60;

    const RETRY_DELAY_FOR_ENTITY_CREATION = 60;

    protected $payoutId;

    protected $queueFlag;

    protected $metadata = [];

    protected $merchantId = null;

    protected $payoutRequest = [];

    protected $mode;

    /**
     * @param string      $mode
     * @param string      $payoutId
     * @param bool        $queueFlag
     * @param array       $metadata
     * @param string|null $merchantId
     * @param array       $payoutRequest
     */
    public function __construct(string $mode,
                                string $payoutId,
                                bool $queueFlag,
                                array $metadata = [],
                                string $merchantId = null,
                                array $payoutRequest = [])
    {
        parent::__construct($mode);

        $this->payoutId = $payoutId;

        $this->queueFlag = $queueFlag;

        $this->metadata = $metadata;

        $this->merchantId = $merchantId;

        $this->payoutRequest = $payoutRequest;
    }

    public function handle()
    {
        parent::handle();

        $traceData = [
            'payout_id'      => $this->payoutId,
            'queue_flag'     => $this->queueFlag,
            'meta_data'      => $this->metadata,
            'merchant_id'    => $this->merchantId,
            'payout_request' => $this->payoutRequest
        ];

        $this->trace->info(
            TraceCode::PAYOUT_CREATE_SUBMITTED_INITIATE_REQUEST_LOW_PRIORITY,
                     $traceData);

        $startTime = microtime(true);

        $processPayout = true;

        try
        {
            if (empty($this->metadata) === false)
            {
                $this->mutex->acquireAndRelease(
                    $this->payoutId,
                    function()
                    {
                        $payout = (new Payout\Repository)->find($this->payoutId);

                        if ($payout === null)
                        {
                            // possible failures of this try block:
                            // 1. POD goes down
                            // 2. DB connection issues
                            (new Payout\Service)->fundAccountCompositePayoutForHighTpsMerchants($this->payoutRequest,
                                                                                                $this->merchantId,
                                                                                                $this->metadata);
                        }
                    },
                    self::PAYOUT_CREATE_MUTEX_LOCK_TIMEOUT,
                    ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::PAYOUT_CREATE_LOW_PRIORITY_FAILED,
                $traceData);

            $this->checkRetry(true, self::RETRY_DELAY_FOR_ENTITY_CREATION);

            $processPayout = false;
        }

        if ($processPayout === true)
        {
            try
            {
                // Failures:
                // 1. pod down
                // 2. db connection issues
                // 3. Db transaction 1 failures, other than low balance
                (new Payout\Core)->processPayoutPostCreateLowPriority($this->payoutId, $this->queueFlag);

                $this->delete();
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::PAYOUT_CREATE_SUBMITTED_PROCESS_FAILED_LOW_PRIORITY,
                    $traceData);

                $this->checkRetry();
            }
        }

        $this->trace->info(
            TraceCode::PAYOUT_CREATE_SUBMITTED_RESPONSE_LOW_PRIORITY,
            [
                'worker_start_taken' => $startTime,
                'worker_end_time'    => microtime(true)
            ]);

    }

    protected function checkRetry($deleteAndFireFailureWebhook = false, $retryDelay = self::MAX_RETRY_DELAY)
    {
        $data = [
                'payout_id'      => $this->payoutId,
            ];

        if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
        {
            // release with higher delay to handle replica lag.
            $this->release($retryDelay);

            $this->trace->info(TraceCode::PAYOUT_CREATE_SUBMITTED_PROCESS_JOB_RELEASED_LOW_PRIORITY, $data);

            $this->pushErrorMetricsToVajra(false);

            $this->pushErrorMetrics(false);
        }
        else
        {
            $this->delete();

            if ($deleteAndFireFailureWebhook === true)
            {
                (new Payout\Core)->fireWebhookForPayoutCreationFailure($this->metadata, $this->payoutRequest, $this->merchantId);
            }

            $this->trace->error(TraceCode::PAYOUT_CREATE_SUBMITTED_PROCESS_JOB_DELETED_LOW_PRIORITY, $data);

            $this->pushErrorMetricsToVajra(true);

            $this->pushErrorMetrics(true);

            $operation = 'Post payout create process fetch job failed';

            (new SlackNotification)->send($operation, $data, null, 1, 'x-payouts-core-alerts');
        }
    }

    protected function pushErrorMetricsToVajra($isDeleted)
    {
        $this->trace->count(Payout\Metric::PAYOUT_CREATE_SUBMITTED_PROCESS_JOB_ERROR_TOTAL,
                            [
                                Payout\Metric::IS_JOB_DELETED => $isDeleted
                            ]);
    }

    protected function pushErrorMetrics($isDeleted)
    {
        Tracer::startSpanWithAttributes( HyperTrace::PAYOUT_CREATE_SUBMITTED_PROCESS_JOB_ERROR_TOTAL,
            [
                'is_job_deleted' => $isDeleted
            ]);
    }
}
