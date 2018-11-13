<?php

namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentPaysecureTrait
{
    protected function runPaymentCallbackFlowNpciPaysecure($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);
            sd($request);
        }
        else
        {
            assert (false, 'Mock is not enabled');
        }

        return $this->submitPaymentCallbackRedirect($request);
    }
}
