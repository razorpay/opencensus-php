<?php

namespace Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\TestCase;

trait PaymentSharpTrait
{
    protected function runPaymentCallbackFlowSharp($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $request = compact('url', 'method', 'content');
        $response = $this->makeRequestParent($request);

        $request = $this->getFormRequestFromResponse(
                                $response->getContent(), 'https://localhost');

        $request['content']['success'] = 'S';

        if ($this->failPaymentOnBankPage)
        {
            $request['content']['success'] = 'F';
        }

        $response = $this->makeRequestParent($request);

        $this->assertEquals(302, $response->getStatusCode());

        $url = $response->getTargetUrl();
        $method = 'get';
        $content = [];

        return $this->submitPaymentCallbackData($url, $method, $content);
    }
}