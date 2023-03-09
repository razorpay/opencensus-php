<?php

namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentCashfreeTrait
{
    protected function runPaymentCallbackFlowCashfree($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        $this->otpFlow = false;

        if ($this->isOtpCallbackUrl($url) === true)
        {
            $this->callbackUrl = $url;

            $this->otpFlow = true;

            return $this->makeOtpCallback($url);
        }

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);

            return $this->submitPaymentCallbackRequest($request);
        }
    }
}
