<?php

namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentBilldeskOptimizerTrait
{
    protected function runPaymentCallbackFlowBilldeskOptimizer($response, &$callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $this->otpFlow = false;

        if ($this->isOtpCallbackUrl($url) === true)
        {
            $this->callbackUrl = $url;

            $this->otpFlow = true;

            return $this->makeOtpCallback($url);
        }
    }
}
