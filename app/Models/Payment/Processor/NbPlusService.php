<?php

namespace RZP\Models\Payment\Processor;

trait NbPlusService
{
    public function callNbPlusServiceAction($payment, $gateway, $action, $gatewayData)
    {
        return $this->app['nbplus.payments']->action($gateway, $action, $gatewayData);
    }
}
