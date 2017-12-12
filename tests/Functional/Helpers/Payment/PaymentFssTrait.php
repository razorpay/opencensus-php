<?php

namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentFssTrait
{
    protected function runPaymentCallbackFlowFss($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $url = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);
        }

        return $this->submitPaymentCallbackRedirect($url);
    }

}