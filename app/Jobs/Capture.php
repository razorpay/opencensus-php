<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use RZP\Exception;
use RZP\Models\Payment;

class Capture
{
    const MAX_JOB_ATTEMPTS = 10;
    const JOB_RELEASE_WAIT = 300;

    protected $trace;

    protected $data;

    protected $job;

    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }

    public function fire($job, $data)
    {
        $this->data = $data['data'];

        $this->job = $job;

        $this->trace->info(
            TraceCode::PAYMENT_QUEUE_CAPTURE_REQUEST,
            $data
        );

        try
        {
            $this->runCaptureFlowForQueue();

            $this->trace->info(
                TraceCode::PAYMENT_QUEUE_CAPTURE_SUCCESS
            );

            $job->delete();
        }
        catch (Exception\GatewayTimeoutException $ex)
        {
            $traceCode = TraceCode::PAYMENT_QUEUE_CAPTURE_FAILURE;

            $this->handleCaptureException($traceCode, $ex);
        }
        catch (\Exception $ex)
        {
            $traceCode = TraceCode::PAYMENT_CAPTURE_FAILURE_EXCEPTION;

            $this->handleCaptureException($traceCode, $ex);
        }
    }

    protected function runCaptureFlowForQueue()
    {
        $mode = $this->data['mode'];

        \Database\DefaultConnection::set($mode);

        $this->app['basicauth']->setMode($mode);

        $payment = $this->app['repo']->payment->findOrFail($this->data['payment']['id']);

        $merchant = $payment->merchant;

        $paymentProcessor = new Payment\Processor\Processor($merchant);

        $paymentProcessor->callGatewayFunctionCaptureViaQueue($this->data, $payment);
    }

    protected function handleCaptureException($traceCode, $ex)
    {
        $this->data['job_attempts'] = $this->job->attempts();

        $this->trace->error(
            $traceCode,
            $this->data
        );

        $this->trace->traceException($ex);

        $this->handleCaptureJobRelease();
    }

    protected function handleCaptureJobRelease()
    {
        if ($this->job->attempts() > self::MAX_JOB_ATTEMPTS)
        {
            $this->trace->error(
                TraceCode::PAYMENT_QUEUE_CAPTURE_DELETE,
                [
                    'data'         => $this->data,
                    'job_attempts' => $this->job->attempts(),
                    'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                ]
            );

            $this->job->delete();
        }
        else
        {
            // When queue_driver is sync, there's no release and
            // hence it's as good as deleting the job.
            $this->job->release(self::JOB_RELEASE_WAIT);
        }
    }
}