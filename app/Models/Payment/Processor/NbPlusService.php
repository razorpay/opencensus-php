<?php

namespace RZP\Models\Payment\Processor;

trait NbPlusService
{
    public function callNbPlusServiceAction($payment, $gateway, $action, $gatewayData)
    {
        $method = $payment->getMethod();

        return $this->app['nbplus.payments']->action($method, $gateway, $action, $gatewayData);
    }
}
