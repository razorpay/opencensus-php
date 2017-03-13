<?php

namespace RZP\Jobs;

use RZP\Jobs\Job;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App;
use Requests;
use RZP\Trace\Trace;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class RequestJob extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    const MAX_ALLOWED_ATTEMPTS = 5;
    const RELEASE_WAIT_SECS    = 60;

    const JOB_DELETED          = 'job_deleted';
    const JOB_RELEASED         = 'job_released';

    protected $trace;
    protected $request;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $request)
    {
        $this->request = $request;

        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];
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
            $this->handleRequest($this->request);

            $this->delete();
        }
        catch(\Requests_Exception $e)
        {
            $this->handleException($e);
        }
    }

    private function handleRequest($request)
    {
        $this->trace->info(TraceCode::REQUESTS_JOB_REQUEST, ['request' => $request]);

        $timeStarted = microtime(true);

        $method = $request['method'];

        $response = Requests::$method(
            $request['url'],
            $request['headers'],
            $request['content'],
            $request['options']);

        $timeTaken = microtime(true) - $timeStarted;

        $this->trace->info(
            TraceCode::REQUESTS_JOB_RESPONSE,
            ['time_taken' => $timeTaken,
             'response'   => $response->body]);
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

        if ($this->attempts() > self::MAX_ALLOWED_ATTEMPTS)
        {
            $this->deleted();
        }
        else
        {
            $this->release(self::RELEASE_WAIT_SECS);

            $jobAction = self::JOB_RELEASED;
        }

        $this->trace->traceException(
            $e,
            Trace::ERROR,
            TraceCode::REQUESTS_JOB_ERROR,
            ['job_action' => $jobAction]);
    }
}
