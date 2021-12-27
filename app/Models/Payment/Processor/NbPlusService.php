<?php

namespace RZP\Models\Payment\Processor;

use RZP\Diag\EventCode;

trait NbPlusService
{
    public function callNbPlusServiceAction($payment, $gateway, $action, $gatewayData)
    {
        $method = $payment->getMethod();

        $properties = [
            'action' => $action,
        ];

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_NBPLUS_CALL_INITIATED, $payment, null, [], $properties);

        try
        {
            $returnData =  $this->app['nbplus.payments']->action($method, $gateway, $action, $gatewayData);

        }
        catch (\Exception $e)
        {
            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_NBPLUS_CALL_PROCESSED, $payment, $e, [], $properties);

            throw $e;
        }

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_NBPLUS_CALL_PROCESSED, $payment, null, [], $properties);

        return $returnData;
    }
}
