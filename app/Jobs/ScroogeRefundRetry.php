<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class ScroogeRefundRetry extends Job
{
    const MAX_JOB_ATTEMPTS = 10;
    const JOB_RELEASE_WAIT = 300;

    //
    // Make sure that this is below 900 (seconds) because
    // SQS doesn't support delay over 15 minutes.
    //
    public $delay = 100;

    protected $trace;

    protected $data;

    public function __construct(array $data)
    {
        parent::__construct($data['mode']);

        $this->data = $data;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::REFUND_RETRY_QUEUE_SCROOGE_REQUEST,
            $this->data
        );

        try
        {
            App::getFacadeRoot()['scrooge']->initiateRefundRetry($this->data, true);

            $this->trace->info(
                TraceCode::REFUND_RETRY_QUEUE_SCROOGE_SUCCESS,
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
                TraceCode::REFUND_RETRY_SCROOGE_JOB_FAILURE_EXCEPTION,
                $this->data);

            $this->handleRefundRetryJobRelease();
        }
    }

    protected function handleRefundRetryJobRelease()
    {
        if ($this->attempts() > self::MAX_JOB_ATTEMPTS)
        {
            $this->trace->error(
                TraceCode::REFUND_RETRY_SCROOGE_QUEUE_DELETE,
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
