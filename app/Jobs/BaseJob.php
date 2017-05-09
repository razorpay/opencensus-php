<?php

namespace RZP\Jobs;

use App;

use RZP\Jobs\Job;
use RZP\Trace\Trace;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class BaseJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    const MAX_ALLOWED_ATTEMPTS = 5;
    const RELEASE_WAIT_SECS    = 60;

    const JOB_DELETED          = 'job_deleted';
    const JOB_RELEASED         = 'job_released';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }

    /**
     *  Initializes instance variables: core, trace etc.
     *
     * @return null
     */
    protected function init()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];
    }

    /**
     * When an exception occurs, the job gets deleted if it has
     * exceeded the maximum attempts. Otherwise it is released back
     * into the queue after the set release wait time
     *
     * @param Exception $e
     * (not typecasting, as it can be a throwable, or exception of any other kinds)
     * @param string    $traceCode
     * @param array     $payload
     */
    protected function handleException($e, string $traceCode, array $payload = [])
    {
        $jobAction = self::JOB_DELETED;

        if ($this->attempts() > self::MAX_ALLOWED_ATTEMPTS)
        {
            $this->delete();
        }
        else
        {
            $this->release(self::RELEASE_WAIT_SECS);

            $jobAction = self::JOB_RELEASED;
        }

        $payload = array_merge(['job_action' => $jobAction], $payload);

        $this->trace->traceException($e, Trace::ERROR, $traceCode, $payload);
    }
}
