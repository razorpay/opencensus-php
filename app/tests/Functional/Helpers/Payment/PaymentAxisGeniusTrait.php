<?php

namespace Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\TestCase;

trait PaymentAxisGeniusTrait
{
    use PaymentAxisGeniusTrait;

    protected function runPaymentCallbackFlowAxisGenius($response, &$callback = null)
    {
        $content = $response->getContent();

        $gateway = $this->app['config']->get('gateway');

        $mock = $gateway['mock_axis_genius'];

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
            $url = $this->runAxisGeniusGatewayAutomation($url, $method, $values);
        }

        return $this->submitPaymentCallbackRedirect($url);
    }

    protected function runAxisGeniusGatewayAutomation($url, $method, $values)
    {
        $options = ['follow_redirects' => false];

        // Hit Genius url
        list($url, $method, $values, $response) = $this->makeRequestAndGetFormData($url, $method, $options, $values);

        // Collect genius cookies
        $cookiesArray = $this->collectCookiesInArray($response);
        $cookies = $this->mapCookiesArrayToString($cookiesArray);

        $url = 'https://migs.mastercard.com.au/vpcpay';
        $method = 'POST';

        // Run Migs automation
        $url = $this->runAxisMigsGatewayAutomation($url, $method, $values);

        // Hit genius again, with previously collected cookies
        $headers = array('Cookie' => $cookies);
        $response = Requests::get($url, $headers, $options);
        $url = $response->headers['location'];

        return $url;
    }
}