<?php

namespace Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\TestCase;

trait PaymentBilldeskTrait
{
    protected function runPaymentCallbackFlowBilldesk($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $request = compact('url', 'method', 'content');
            $response = $this->makeRequestParent($request);

            list($url, $method, $content) = $this->getFormDataFromResponse(
                                    $response->getContent(), 'https://localhost');
        }
        else
        {
            ;
        }

        return $this->submitPaymentCallbackData($url, $method, $content);
    }
}