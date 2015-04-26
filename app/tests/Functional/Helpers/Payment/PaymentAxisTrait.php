<?php

namespace Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\TestCase;

trait PaymentAxisTrait
{
    /**
     * Runs payment callback flow for atom net-banking transactions
     * @param  array $response
     */
    protected function runPaymentCallbackFlowAxis($response, &$callback = null)
    {
        $content = $response->getContent();

        $headers = array();

        $gateway = $this->app['config']->get('gateway');

        $mock = $gateway['mock_axis'];

        if ($mock)
        {
            $server = array('HTTP_REFERER' => 'http://localhost');

            $content = $this->getJsonContentFromResponse($response, $callback);
            $callback = null;

            $response = $this->makeRequestParent($content['request']);

            $statusCode = $response->getStatusCode();

            $this->assertEquals($statusCode, '302');

            $response = $this->submitPaymentCallbackRedirect($response->getTargetUrl());

            return $response;
        }
    }
}