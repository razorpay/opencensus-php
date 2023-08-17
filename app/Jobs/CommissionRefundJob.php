<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Partner\Metric;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Partner\Commission\RefundCommission;

class CommissionRefundJob extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected string $refundId;

    protected string $paymentId;

    protected int    $refundAmount;

    public function __construct(string $mode, string $refundId, string $paymentId, int $refundAmount)
    {
        parent::__construct($mode);

        $this->refundId     = $refundId;
        $this->paymentId    = $paymentId;
        $this->refundAmount = $refundAmount;
    }

    public function handle()
    {
        $startTime = millitime();
        parent::handle();

        $this->trace->info(
            TraceCode::COMMISSION_REFUND_CREATE_REQUEST,
            [
                'mode' => $this->mode,
                'refund_id' => $this->refundId,
            ]
        );

        try
        {
            (new RefundCommission($this->refundId, $this->paymentId, $this->refundAmount))->createReversalCommissionForRefund();

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_REFUND_CREATE_FAILED,
                [
                    'mode'        => $this->mode,
                    'refund_id'   => $this->refundId,
                ]
            );

            $this->checkRetry($e);
        }

        $timeTaken = millitime() - $startTime;

        $this->trace->histogram(Metric::COMMISSION_REFUND_CREATE_JOB_PROCESSING_IN_MS, $timeTaken);
    }

    protected function checkRetry(\Throwable $e): void
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::COMMISSION_REFUND_QUEUE_DELETE, [
                'refund_id'           => $this->refundId,
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
