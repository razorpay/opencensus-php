<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Requests;
use Symfony\Component\DomCrawler\Crawler;

trait PaymentFirstDataTrait
{
    protected function runPaymentCallbackFlowFirstData($response, &$callback = null)
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