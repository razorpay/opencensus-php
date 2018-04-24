<?php

namespace RZP\Jobs;

use RZP\Exception;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Dashboard extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 5;
    const RELEASE_WAIT_SECS    = 60;

    const JOB_DELETED          = 'job_deleted';
    const JOB_RELEASED         = 'job_released';

    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        parent::__construct();

        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        if (isset($this->data['type']) === false)
        {
            $this->trace->error(
                TraceCode::DASHBOARD_INTEGRATION_ERROR,
                [
                    'data' => $this->data,
                ]);

            $this->delete();
        }

        try
        {
            $className  = '\RZP\Dashboard\\' . ucfirst($this->data['type']);

            //will be payment or refund
            $entity = new $className();

            $entity->postRequest($this, $this->data);

            $this->delete();
        }
        catch(\Throwable $e)
        {
            $this->handleException($e);
        }
    }

    /**
     * When an exception occurs, the job gets deleted if it has
     * exceeded the maximum attempts. Otherwise it is released back
     * into the queue after the set release wait time
     *
     * @param Throwable $e
     */
    protected function handleException(\Throwable $e)
    {
        $jobAction = self::JOB_DELETED;

        if ($this->attempts() >= self::MAX_ALLOWED_ATTEMPTS)
        {
            $this->delete();
        }
        else
        {
            $this->release(self::RELEASE_WAIT_SECS);

            $jobAction = self::JOB_RELEASED;
        }

        $this->trace->traceException(
            $e,
            Trace::ERROR,
            TraceCode::DASHBOARD_JOB_ERROR,
            ['job_action' => $jobAction]);
    }
}
