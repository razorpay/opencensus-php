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
        $content = $response->getContent();

        $headers = array();

        $gateway = $this->app['config']->get('gateway');

        $mock = $gateway['mock_kotak'];

        if ($callback)
        {
            $content = $this->getJsonContentFromResponse($response, $callback);
            $callback = null;

            $request = $content['request'];
            $url = $content['request']['url'];
        }
        else
        {
            $url = $response->getTargetUrl();
        }

        if ($mock)
        {
            $request = array(
               'url' => $url,
               'method' => 'GET');

            $response = $this->makeRequestParent($request);

            $statusCode = $response->getStatusCode();
            $this->assertEquals($statusCode, '302');

            $url = $response->getTargetUrl();
        }
        else
        {
            list($url, $method, $values, $response) = $this->makeRequestAndGetFormData($url, 'GET');
        }

        return $this->submitPaymentCallbackRedirect($url);
    }
}