<?php

namespace RZP\Jobs;

use App;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Capture extends Job
{
    const MAX_JOB_ATTEMPTS = 10;
    const JOB_RELEASE_WAIT = 300;

    // Make sure that this is below 900 (seconds) because SQS doesn't support
    // delay over 15 minutes.
    public $delay = 100;

    /**
     * {@inheritDoc}
     */
    protected $queueConfigKey = 'capture';

    protected $trace;

    protected $data;


    public function __construct(array $data)
    {
        parent::__construct($data['mode']);

        $this->data = $data;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::PAYMENT_QUEUE_CAPTURE_REQUEST,
            $this->data
        );

        try
        {
            $this->runCaptureFlowForQueue();

            $this->trace->info(
                TraceCode::PAYMENT_QUEUE_CAPTURE_SUCCESS,
                $this->data
            );

            $this->delete();
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
        $basicAuth = App::getFacadeRoot()['basicauth'];

        $basicAuth->setMode($this->mode);

        $payment = $this->repoManager->payment->findOrFail($this->data['payment']['id']);

        $merchant = $payment->merchant;

        $paymentProcessor = new Payment\Processor\Processor($merchant);

        $paymentProcessor->callGatewayFunctionCaptureViaQueue($this->data, $payment);
    }

    protected function handleCaptureException($traceCode, $ex)
    {
        $this->data['job_attempts'] = $this->attempts();

        $this->trace->error(
            $traceCode,
            $this->data
        );

        $this->trace->traceException($ex);

        $this->handleCaptureJobRelease();
    }

    protected function handleCaptureJobRelease()
    {
        if ($this->attempts() > self::MAX_JOB_ATTEMPTS)
        {
            $this->trace->error(
                TraceCode::PAYMENT_QUEUE_CAPTURE_DELETE,
                [
                    'data'         => $this->data,
                    'job_attempts' => $this->attempts(),
                    'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                ]
            );

            $this->delete();
        }
        else
        {
            // When queue_driver is sync, there's no release and
            // hence it's as good as deleting the job.
            $this->release(self::JOB_RELEASE_WAIT);
        }
    }
}
