<?php

namespace RZP\Jobs;

use App;
use Requests;

use RZP\Trace\Trace;
use RZP\Jobs\BaseJob;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class RequestJob extends BaseJob
{
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
            $this->init();

            $this->handleRequest();

            $this->delete();
        }
        catch(\Requests_Exception $e)
        {
            $this->handleException($e, TraceCode::REQUESTS_JOB_ERROR);
        }
    }

    private function handleRequest()
    {
        $this->trace->info(TraceCode::REQUESTS_JOB_REQUEST, ['request' => $this->request]);

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
}
