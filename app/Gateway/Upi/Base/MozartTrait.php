<?php

namespace RZP\Gateway\Upi\Base;


use RZP\Gateway\Base\Verify;

trait MozartTrait
{
    protected function authorizeRequest(array $input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->authorize($input);
    }

    protected function callbackRequest(array $input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->callback($input);
    }

    protected function verifyRequest(Verify $input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->sendVerifyRequest($input);
    }

//preProcessServerCallbackForUpiSbi as we dont get encrypted callback
    protected function preProcessServerCallbackRequest(array $input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();
        $gateway = $this->gateway;

        return $mozart->preProcessServerCallbackForUpiSbi($input, $gateway);
    }

    protected function getMozartGatewayWithModeSet()
    {
        $gateway = $this->app['gateway']->gateway('mozart');

        $gateway->setMode($this->getMode());

        return $gateway;
    }
}
