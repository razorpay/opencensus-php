<?php

namespace Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\TestCase;

trait PaymentMobikwikTrait
{
    protected function runPaymentCallbackFlowMobikwik($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $url = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);

            return $this->submitPaymentCallbackRedirect($url);
        }
        else
        {
            $options = ['follow_redirects' => false];
            list($url, $method, $values) = $this->makeRequestAndGetFormData($url, $method, [], $values, $options);
            list($url, $method, $values) = $this->makeRequestAndGetFormData($url, $method, [], $values, $options);
            list($url, $method, $values) = $this->makeRequestAndGetFormData($url, $method, [], $values, $options);

            return $this->submitPaymentCallbackData($url, $method, $values);
        }
    }
}