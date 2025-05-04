<?php

namespace RZP\Jobs\Transfers;

use App;
use RZP\Constants\Metric;
use RZP\Jobs\Job;
use RZP\Models\Transfer\Payment\Entity;
use RZP\Trace\TraceCode;


class TransferPaymentDecrementer extends Job
{
    const RETRY_INTERVAL    = 30;
    const MAX_RETRY_ATTEMPT = 15;

    protected $input;

    protected $transferPayment;

    protected $decrementAmount;

    public function __construct(Entity $transferPayment, $decrementAmount)
    {
        parent::__construct();

        $this->transferPayment = $transferPayment;

        $this->decrementAmount = $decrementAmount;
    }

    public function handle()
    {
        parent::handle();

        if ($this->transferPayment->isExternal() === false)
        {
            return;
        }

        $params = ['amount' => $this->decrementAmount];

        $this->trace->info(
            TraceCode::DECREMENT_TRANSFER_PAYMENT_VIA_ROUTE_SERVICE,
            [
                'transfer_payment_id' => $this->transferPayment->getId(),
                'payment_id'          => $this->transferPayment->getPaymentId(),
                'params'              => $params,
            ]
        );

        try
        {
            $startTime = millitime();

            app('route')->decrementTransferPayment($this->transferPayment->getPaymentId(), $params);

            $this->traceSuccessMetrics('transferPaymentUpdateJob', millitime()-$startTime);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::DECREMENT_TRANSFER_PAYMENT_VIA_ROUTE_SERVICE_FAILURE,
                [
                    'id'         => $this->transferPayment->getId(),
                    'payment_id' => $this->transferPayment->getPaymentId(),
                ]);

            $this->traceFailureMetrics('transferPaymentDecrementerJob', millitime()-$startTime);

            $this->checkRetry();
        }
    }

    protected function traceFailureMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_TRANSFER_PAYMENT_REPO_SAVE_FAILURE, [
            'caller'      => $functionName,
        ]);

        $trace->histogram(Metric::EXTERNAL_TRANSFER_PAYMENT_REPO_SAVE_FAILURE_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

    protected function traceSuccessMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_TRANSFER_PAYMENT_REPO_SAVE_SUCCESS, [
            'caller'      => $functionName,
        ]);

        $trace->histogram(Metric::EXTERNAL_TRANSFER_PAYMENT_REPO_SAVE_SUCCESS_TIME_TAKEN,
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

