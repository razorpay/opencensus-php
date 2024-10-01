<?php

namespace RZP\Jobs\Transfers;

use RZP\Jobs\Job;
use RZP\Models\Payment\Entity;
use RZP\Trace\TraceCode;


class PaymentUpdate extends Job
{
    const RETRY_INTERVAL    = 300;
    const MAX_RETRY_ATTEMPT = 3;

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
            app('route')->saveApiPayment($this->payment->getId(), $params);
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

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        // check if retry is needed
    }

}

