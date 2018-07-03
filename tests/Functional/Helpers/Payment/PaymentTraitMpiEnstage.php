<?php

namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentTraitMpiEnstage
{
    protected function runPaymentCallbackFlowMpiEnstage($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();
        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);
        if ($mock)
        {
            $this->callbackUrl = $url;
            return $this->makeOtpCallback($url);
        }
    }
}
