<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use RZP\Tests\Functional\TestCase;

trait PaymentMobikwikTrait
{
    protected function runPaymentCallbackFlowMobikwik($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            if ($this->isOtpCallbackUrl($url))
            {
                $this->callbackUrl = $url;

                return $this->makeOtpCallback($url);
            }

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
