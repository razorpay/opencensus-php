<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use RZP\Tests\Functional\TestCase;

trait PaymentSharpTrait
{
    protected function runPaymentCallbackFlowSharp($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($this->isOtpCallbackUrl($url))
        {
            return $this->makeOtpCallback($url);
        }

        $request = compact('url', 'method', 'content');

        $response = $this->makeRequestParent($request);

        $request = $this->getFormRequestFromResponse(
                                $response->getContent(), 'https://localhost');

        $request['content']['success'] = 'S';

        if ($this->failPaymentOnBankPage)
        {
            $request['content']['success'] = 'F';
        }

        if ($this->gatewayDown === true)
        {
            $request['content']['success'] = 'gateway_down';
        }

        $response = $this->makeRequestParent($request);

        $this->assertEquals(302, $response->getStatusCode());

        $url = $response->getTargetUrl();
        $method = 'get';
        $content = [];

        $this->recorder = new PaymentStateRecorder;

        $this->recorder->callbackUrl = $url;

        return $this->submitPaymentCallbackData($url, $method, $content);
    }
}
