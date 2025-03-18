<?php

namespace RZP\Jobs\Transfers;

use App;
use RZP\Constants\Metric;
use RZP\Jobs\Job;
use RZP\Models\Payment\Entity;
use RZP\Trace\TraceCode;


class PaymentUpdate extends Job
{
    const RETRY_INTERVAL    = 30;
    const MAX_RETRY_ATTEMPT = 15;

    protected $input;

    protected $payment;


    public function __construct(Entity $payment)
    {
        parent::__construct();

        $this->payment = $payment;
    }

    public function handle()
    {
        parent::handle();

        if ($this->payment->isExternal() === false)
        {
            return;
        }

        $params = $this->repoManager->payment_method_transfer->getUpdatableLinkedAccountPaymentFields($this->payment);

        $this->trace->info(
            TraceCode::SAVE_PAYMENT_VIA_ROUTE_SERVICE,
            [
                'payment_id' => $this->payment->getId(),
                'params'     => $params,
            ]
        );

        try
        {
            $startTime = millitime();

            app('route')->saveApiPayment($this->payment->getId(), $params);

            $this->traceSuccessMetrics('paymentUpdateJob', millitime()-$startTime);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::SAVE_PAYMENT_VIA_ROUTE_SERVICE_FAILURE,
                [
                    'id' => $this->payment->getId()
                ]);

            $this->traceFailureMetrics('paymentUpdateJob', millitime()-$startTime);

            $this->checkRetry();
        }
    }

    protected function traceFailureMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_LA_PAYMENT_REPO_SAVE_FAILURE, [
            'caller'      => $functionName,
        ]);

        $trace->histogram(Metric::EXTERNAL_LA_PAYMENT_REPO_SAVE_FAILURE_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

    protected function traceSuccessMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_LA_PAYMENT_REPO_SAVE_SUCCESS, [
            'caller'      => $functionName,
        ]);

        $trace->histogram(Metric::EXTERNAL_LA_PAYMENT_REPO_SAVE_SUCCESS_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}

