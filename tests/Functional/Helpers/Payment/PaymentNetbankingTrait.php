<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Config;
use Requests;
use RZP\Models\Payment\Gateway;
use Symfony\Component\DomCrawler\Crawler;
use RZP\Tests\Functional\TestCase;

trait PaymentNetbankingTrait
{
    protected function runPaymentCallbackFlowNetbanking($response, &$callback = null, $gateway)
    {
        if (Gateway::gatewaysAlwaysRoutedThroughNbplusService($gateway, null, 'netbanking') === true)
        {
            return $this->runPaymentCallbackFlowForNbplusGateway($response, $gateway, $callback);
        }

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

        if ($gateway === 'netbanking_kotak' or
            $gateway === 'netbanking_corporation' or
            $gateway === 'netbanking_canara' or
            $gateway === 'netbanking_yesb' or
            $gateway === 'netbanking_kvb')
        {
            // Make sure bank's callback are on public auth
            $this->ba->publicCallbackAuth();

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

    protected function runPaymentCallbackFlowForNbplusGateway($response, $gateway, &$callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $response = $this->mockCallbackFromGateway($url, $method, $content);

        $data = $this->getPaymentJsonFromCallback($response->getContent());

        $response->setContent($data);

        return $response;
    }
}
