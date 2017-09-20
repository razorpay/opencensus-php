<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use RZP\Tests\Functional\TestCase;

trait PaymentNetbankingTrait
{
    protected function runPaymentCallbackFlowNetbanking($response, &$callback = null, $gateway)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $data = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);
        }
        else
        {
            ;
        }

        if ($gateway === 'netbanking_kotak' or $gateway === 'netbanking_corporation')
        {
            $response = $this->sendRequest($data);
            $this->assertEquals($response->getStatusCode(), '302');

            $data = array(
                'url' => $response->headers->get('location'),
                'method' => 'get');
        }

        if (filter_var($data, FILTER_VALIDATE_URL))
        {
            return $this->submitPaymentCallbackRedirect($data);
        }
        else
        {
            return $this->submitPaymentCallbackRequest($data);
        }
    }
}
