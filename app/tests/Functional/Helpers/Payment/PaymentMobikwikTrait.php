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

        if ($mock) {

            if (isset($this->type) and $this->type === 'otp')
            {
                $content['otp'] = '123456';

                if (isset($this->step))
                {
                    switch ($this->step)
                    {
                        case 'RETRY':
                            $content['otp'] = '121212';
                            break;
                    }
                }

                $content['type'] = 'otp';

                $request = array(
                    'url'       => $url,
                    'method'    => $method,
                    'content'   => $content
                );

                return $this->makeRequest($request);
            }

            $url = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);

            return $this->submitPaymentCallbackRedirect($url);
        } else {
            $options = ['follow_redirects' => false];
            list($url, $method, $values) = $this->makeRequestAndGetFormData($url, $method, [], $values, $options);
            list($url, $method, $values) = $this->makeRequestAndGetFormData($url, $method, [], $values, $options);
            list($url, $method, $values) = $this->makeRequestAndGetFormData($url, $method, [], $values, $options);

            return $this->submitPaymentCallbackData($url, $method, $values);
        }
    }
}