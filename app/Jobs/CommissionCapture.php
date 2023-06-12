<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Partner\Metric;
use RZP\Models\Partner\Commission;

class CommissionCapture extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $commissionIds;

    protected $retry;

    public function __construct(string $mode, $commissionId, int $retry = 0 )
    {
        parent::__construct($mode);

        $this->commissionIds = array_wrap($commissionId);

        $this->retry = $retry;
    }

    public function handle()
    {
        $startTime = millitime();

        parent::handle();

        $this->trace->info(
            TraceCode::COMMISSION_TRANSACTION_CAPTURE_REQUEST,
            [
                'mode' => $this->mode,
                'id' => $this->commissionIds,
            ]
        );

        $failedCommissionIds = [];

        try
        {
            $commissions = $this->repoManager->commission->findMultipleByPublicIds($this->commissionIds);

            $this->trace->info(
                TraceCode::COMMISSION_TRANSACTION_CAPTURE_FETCH,
                [
                    'id' => $commissions->getIds(),
                ]
            );

            $core = new Commission\Core;

            foreach ($commissions as $commission)
            {
                try
                {
                    $core->capture($commission);
                }
                catch (\Throwable $e)
                {
                    $this->countJobException($e);
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::COMMISSION_TRANSACTION_CREATE_FAILED,
                        [
                            'mode' => $this->mode,
                            'id'   => $commission->getId(),
                        ]
                    );
                    $failedCommissionIds[] = $commission->getId();
                }
            }

            if (count($failedCommissionIds) > 0 )
            {
                $this->countJobException($e);
                if($this->retry < self::MAX_RETRY_ATTEMPT)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::COMMISSION_TRANSACTION_JOB_ERROR,
                        [
                            'mode'       => $this->mode,
                            'failed_ids' => $failedCommissionIds
                        ]
                    );
                    CommissionCapture::dispatch($this->mode, $failedCommissionIds, $this->retry + 1);
                }
                else
                {
                    $this->trace->error(TraceCode::COMMISSION_TRANSACTION_QUEUE_DELETE, [
                        'id'           => $failedCommissionIds,
                        'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                    ]);
                }
            }

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_TRANSACTION_JOB_ERROR,
                [
                    'mode' => $this->mode,
                ]
            );

            $this->checkRetry($e);
        }

        $timeTaken = millitime() - $startTime;

        $this->trace->histogram(Metric::COMMISSION_CAPTURE_JOB_PROCESSING_IN_MS, $timeTaken);
    }

    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::COMMISSION_TRANSACTION_QUEUE_DELETE, [
                'id'           => $this->commissionIds,
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
