<?php

namespace RZP\Models\Payment\Processor;

use App;
use RZP\Gateway\Base\Action;
use RZP\Trace\TraceCode;
use RZP\Exception;
use RZP\Models\Payment\Gateway;

class CaptureInferno
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

    function fire($job, $data)
    {
        $this->data = $data['data'];

        $this->job = $job;

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_REQUEST_QUEUE,
            $data
        );

        try
        {
            $this->runCaptureFlowForQueue();

            $this->trace->info(
                TraceCode::PAYMENT_CAPTURE_SUCCESS_QUEUE
            );

            $job->delete();
        }
        catch (Exception\GatewayTimeoutException $ex)
        {
            $traceCode = TraceCode::PAYMENT_CAPTURE_FAILURE_QUEUE;

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
        $terminal = $payment->terminal;

        $gateway = $payment->getGateway();

        $gatewayData['terminal'] = $terminal;
        $gatewayData['merchant'] = $payment->merchant;

        // TODO: Handle Kotak gateway capture timeout
        // $gatewayData['bank_account'] = $this->getMerchantBankAccount($terminal->merchant);

        return $this->app['gateway']->call($gateway, Action::CAPTURE, $gatewayData, $mode, $terminal);
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
                TraceCode::PAYMENT_CAPTURE_DELETE_QUEUE,
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
            $this->job->release(self::JOB_RELEASE_WAIT);
        }
    }
}