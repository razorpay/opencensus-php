<?php

namespace RZP\Jobs;

use App;
use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;

class PartnershipServiceAsync extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 3;

    protected $queueConfigKey = 'commission';

    protected $path;
    protected $parameters;
    protected $app;

    public function __construct(array $parameters, string $path)
    {
        parent::__construct();

        $this->parameters = $parameters;
        $this->path  = $path;
    }

    public function handle()
    {
        parent::handle();
        $this->app = App::getFacadeRoot();

        try
        {
            $traceInfo = ['parameters' => $this->parameters, 'path'=> $this->path];
            $this->trace->info(
                TraceCode::PARTNERSHIP_SERVICE_ASYNC_JOB_REQUEST,
                $traceInfo
            );
            $this->app->partnerships->sendRequest($this->parameters,$this->path,Requests::POST);

            $this->delete();
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PARTNERSHIP_SERVICE_ASYNC_JOB_FAILED,
                [
                    'parameters'      => $this->parameters,
                    'path'            => $this->path,
                    'message'         => $e->getMessage(),
                ]
            );
            $this->checkRetry($e);
        }
    }

    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::PARTNERSHIP_SERVICE_ASYNC_JOB_DELETE, [
                'mode'         => $this->mode,
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
