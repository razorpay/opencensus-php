<?php

namespace RZP\Jobs;

use RZP\Jobs\Job;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Requests;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

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
        parent::__construct();

        $this->request = $request;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        try
        {
            $this->handleRequest();

            $this->delete();
        }
        catch(\Throwable $e)
        {
            $this->handleException($e);
        }
    }

    protected function traceRequest()
    {
        $this->trace->info(
            TraceCode::REQUESTS_JOB_REQUEST,
            [
                'request' => [
                    'url'     => $this->request['url'],
                    'content' => $this->request['content'],
                    'options' => $this->request['options'],
                ]
            ]);
    }

    private function handleRequest()
    {
        $this->traceRequest();

        $timeStarted = microtime(true);

        $method = $this->request['method'];

        $response = Requests::$method(
            $this->request['url'],
            $this->request['headers'],
            $this->request['content'],
            $this->request['options']);

        $timeTaken = microtime(true) - $timeStarted;

        $this->trace->info(
            TraceCode::REQUESTS_JOB_RESPONSE,
            [
                'time_taken' => $timeTaken,
                'attempts'   => $this->attempts(),
                'response'   => $response->body
            ]);
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
            TraceCode::REQUESTS_JOB_ERROR,
            ['job_action' => $jobAction]);
    }
}
