<?php

namespace RZP\Gateway\Enach\Base;

use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Base\Action;

class Gateway extends \RZP\Gateway\Base\Gateway
{
    protected function createGatewayPaymentEntity($attributes)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($this->input['payment']['id']);

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->fill($attributes);

        $gatewayPayment->saveOrFail();

        return $gatewayPayment;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Entity;
    }

    protected function getRepository()
    {
        $gateway = 'enach';

        return $this->app['repo']->$gateway;
    }

    protected function traceGatewayPaymentRequest(
        array $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST,
        array $extraData = [])
    {
        $this->trace->info(
            $traceCode,
            [
                'request'    => $request,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
                'extra_data' => $extraData
            ]);
    }
}
