<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Requests;
use Symfony\Component\DomCrawler\Crawler;

trait PaymentBladeTrait
{
    protected function runPaymentCallbackFlowMpiBlade($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);

            return $this->submitPaymentCallbackRequest($request);
        }
    }
}
