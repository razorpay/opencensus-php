<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use RZP\Exception;
use Symfony\Component\DomCrawler\Crawler;
use RZP\Tests\Functional\TestCase;

trait PaymentBilldeskTrait
{
    protected function runPaymentCallbackFlowBilldesk($response, &$callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $response = $this->mockCallbackFromGateway($url, $method, $content);

        $data = $this->getPaymentJsonFromCallback($response->getContent());

        $response->setContent($data);

        return $response;
    }
}
