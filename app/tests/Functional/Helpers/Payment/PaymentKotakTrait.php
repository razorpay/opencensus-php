<?php

namespace Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\TestCase;

trait PaymentKotakTrait
{
    protected function runPaymentCallbackFlowKotak($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, ) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $url = $this->makeFirstGatewayPaymentMockRequest($url);
        }
        else
        {
            $options = ['follow_redirects' => false, 'verify' => false];
            $response = Requests::get($url, [], $options);

            $this->assertEquals($response->status_code, 302);
            $url = $response->headers['location'];

            $response = Requests::get($url, [], $options);
            $this->assertEquals($response->status_code, 302);
            $url = $response->headers['location'];

            $response = Requests::get($url, [], $options);
            $this->assertEquals($response->status_code, 302);
            $url = $response->headers['location'];
        }

        return $this->submitPaymentCallbackRedirect($url);
    }
}