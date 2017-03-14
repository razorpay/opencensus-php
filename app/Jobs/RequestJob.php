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
    protected $url;
    protected $method;
    protected $contentType;
    protected $token;
    protected $secret;
    protected $content;
    protected $request;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $url,
                                string $method,
                                string $contentType,
                                string $token,
                                string $secret,
                                string $content)
    {
        $this->url = $url;
        $this->method = $method;
        $this->contentType = $contentType;
        $this->token = $token;
        $this->secret = $secret;
        $this->content = $content;
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
            $this->handleException($e);
        }
    }

    protected function init()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $headers['Content-Type'] = $this->contentType;

        $options['auth'] = [$this->token, $this->secret];

        $this->request = [
            'url'     => $this->url,
            'method'  => $this->method,
            'headers' => $headers,
            'options' => $options,
            'content' => $this->content
        ];
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
            ['time_taken' => $timeTaken,
             'attempts'   => $this->attempts(),
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
