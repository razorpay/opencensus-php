<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Partner\Commission;

class CommissionCapture extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $commissionIds;

    public function __construct(string $mode, $commissionId)
    {
        parent::__construct($mode);

        $this->commissionIds = array_wrap($commissionId);
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $commissions = $this->repoManager->commission->findManyByPublicIds($this->commissionIds);

            $core = new Commission\Core;

            foreach ($commissions as $commission)
            {
                try
                {
                    $core->capture($commission);

                    $this->delete();
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::COMMISSION_TRANSACTION_CREATE_FAILED,
                        [
                            'mode' => $this->mode,
                            'id'   => $commission->getId(),
                        ]
                    );
                }
            }
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

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
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
