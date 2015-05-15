<?php

namespace Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\TestCase;

trait PaymentPaytmTrait
{
    protected function runPaymentCallbackFlowPaytm($response, &$callback = null)
    {
        $content = $response->getContent();

        $headers = array();

        $gateway = $this->app['config']->get('gateway');

        $mock = $gateway['mock_paytm'];

        if ($callback)
        {
            $content = $this->getJsonContentFromResponse($response, $callback);
            $callback = null;

            $request = $content['request'];
            list($url, $method, $values) = [$request['url'], $request['method'], $request['content']];
        }
        else
        {
           list($url, $method, $values) = $this->getFormDataFromResponse($response->getContent(), 'https://localhost');
        }

        if ($mock)
        {
            $request = array(
               'url' => $url,
               'method' => $method,
               'content' => $values);

            $response = $this->makeRequestParent($request);

            $statusCode = $response->getStatusCode();
            $this->assertEquals($statusCode, '302');

            $url = $response->getTargetUrl();
        }
        else
        {
            $options = ['follow_redirects' => false];
            list($url, $method, $values) = $this->makeRequestAndGetFormData($url, $method, [], $values, $options);
            list($url, $method, $values) = $this->makeRequestAndGetFormData($url, $method, [], $values, $options);
            list($url, $method, $values) = $this->makeRequestAndGetFormData($url, $method, [], $values, $options);

            return $this->submitPaymentCallbackData($url, $method, $values);
        }

        return $this->submitPaymentCallbackRedirect($url);
    }
}