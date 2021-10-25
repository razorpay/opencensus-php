<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class ScroogeRefundUpdate extends Job
{
    const MAX_JOB_ATTEMPTS = 5;
    const JOB_RELEASE_WAIT = 300;

    protected $trace;

    protected $data;

    public function __construct(array $data)
    {
        parent::__construct($data['mode']);
        parent::setPassportTokenForJobs();

        $this->data = $data;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::REFUND_UPDATE_QUEUE_SCROOGE_REQUEST,
            $this->data
        );

        try
        {
            App::getFacadeRoot()['scrooge']->bulkUpdateRefundStatus($this->data, true);

            $this->trace->info(
                TraceCode::REFUND_UPDATE_QUEUE_SCROOGE_SUCCESS,
                $this->data
            );

            $this->delete();
        }
        catch (\Throwable $ex)
        {
            $this->data['job_attempts'] = $this->attempts();

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::REFUND_UPDATE_SCROOGE_JOB_FAILURE_EXCEPTION,
                $this->data);

            $this->handleRefundJobRelease();
        }
    }

    protected function handleRefundJobRelease()
    {
        if ($this->attempts() > self::MAX_JOB_ATTEMPTS)
        {
            $this->trace->error(
                TraceCode::REFUND_UPDATE_SCROOGE_QUEUE_DELETE,
                [
                    'data'         => $this->data,
                    'job_attempts' => $this->attempts(),
                    'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                ]
            );

            $this->delete();
        }
        else
        {
            //
            // When queue_driver is sync, there's no release
            // and hence it's as good as deleting the job.
            //
            $this->release(self::JOB_RELEASE_WAIT);
        }
    }
}
