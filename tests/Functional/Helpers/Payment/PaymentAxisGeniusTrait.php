<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use RZP\Tests\Functional\TestCase;

trait PaymentAxisGeniusTrait
{
    protected function runPaymentCallbackFlowAxisGenius($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $url = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);
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
