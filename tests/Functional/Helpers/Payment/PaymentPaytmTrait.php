<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use RZP\Tests\Functional\TestCase;

trait PaymentPaytmTrait
{
    protected function runPaymentCallbackFlowPaytm($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        $this->otpFlow = false;

        if ($this->isOtpCallbackUrl($url) === true)
        {
            $this->callbackUrl = $url;

            $this->otpFlow = true;

            return $this->makeOtpCallback($url);
        }

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
