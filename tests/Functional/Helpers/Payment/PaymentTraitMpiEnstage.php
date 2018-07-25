<?php

namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentTraitMpiEnstage
{
    protected function runPaymentCallbackFlowMpiEnstage($response, &$callback = null)
    {
        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        $this->callbackUrl = $url;
        $this->otpFlow = true;

        return $this->makeOtpCallback($url);
    }
}
