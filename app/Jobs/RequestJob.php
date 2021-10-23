<?php

namespace RZP\Jobs;

use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;

class RequestJob extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 5;
    const RELEASE_WAIT_SECS    = 60;

    const JOB_DELETED          = 'job_deleted';
    const JOB_RELEASED         = 'job_released';

    // Constants for request
    const STATUS_CODE          = 'status_code';
    const BODY                 = 'body';

    protected $trace;

    protected $request;

    protected $response;

    protected $traceCodeRequest = TraceCode::REQUESTS_JOB_REQUEST;

    protected $traceCodeResponse = TraceCode::REQUESTS_JOB_RESPONSE;

    protected $traceCodeError = TraceCode::REQUESTS_JOB_ERROR;

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
            $this->traceCodeRequest,
            [
                'request' => [
                    'url'     => $this->request['url'],
                    'content' => $this->request['content'],
                    'options' => $this->request['options'],
                ]
            ]);
    }

    protected function handleRequest()
    {
        $this->traceRequest();

        $timeStarted = microtime(true);

        $method = $this->request['method'];

        $this->response = Requests::$method(
            $this->request['url'],
            $this->request['headers'],
            $this->request['content'],
            $this->request['options']);

        $timeTaken = microtime(true) - $timeStarted;

        $this->trace->info(
            $this->traceCodeResponse,
            [
                'time_taken' => $timeTaken,
                'attempts'   => $this->attempts(),
                'response'   => $this->response->body
            ]);

        return [
            self::STATUS_CODE => $this->response->status_code,
            self::BODY        => json_decode($this->response->body, true),
        ];
    }

    /**
     * When an exception occurs, the job gets deleted if it has
     * exceeded the maximum attempts. Otherwise it is released back
     * into the queue after the set release wait time
     *
     * @param \Throwable $e
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
            $this->traceCodeError,
            ['job_action' => $jobAction]);
    }
}
