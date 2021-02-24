<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Gateway\Upi;
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

    protected function verifyMozart($input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->verify($input);
    }

    /**
     * Note : This is supposed to be sendVerifyRequestMozart
     *
     */
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

    protected function refundRequest(array $input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        $mozart->refund($input);
    }

    protected function getPaymentIdFromServerCallbackRequest(array $response, $gateway)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->getPaymentIdFromServerCallback($response, $gateway);
    }

    /**
     * Returns mozart gateway
     * TODO: Eventually remove this function when dependency of mozart gateway is
     * removed
     * @return \RZP\Gateway\Mozart\Gateway
     */
    protected function getMozartGatewayWithModeSet()
    {
        /**
         * @var $gateway \RZP\Gateway\Mozart\Gateway
         */
        $gateway = $this->app['gateway']->gateway('mozart');

        $gateway->setMode($this->getMode());

        return $gateway;
    }
}
