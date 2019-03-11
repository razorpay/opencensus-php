<?php

namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentPaysecureTrait
{
    protected function runPaymentCallbackFlowPaysecure($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);
        }
        else
        {
            assertTrue (false, 'Mock is not enabled');
        }

        return $this->submitPaymentCallbackRequest($request);
    }
}
