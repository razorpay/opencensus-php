<?php

namespace RZP\Tests\Functional\Payment;

class StaticCallbackNbplusGatewayTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath =  'Functional/Gateway/Mozart/NetbankingKvbGatewayTestData.php';

        parent::setUp();

        $this->bank = 'KVBL';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_kvb_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    protected function runPaymentCallbackFlowForGateway($response, $gateway, &$callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $response = $this->mockCallbackFromGateway($url, $method, $content);

        $data = array(
            'url' => $response->headers->get('location'),
            'method' => 'get');

        $response = $this->sendRequest($data);

        $data = $this->getPaymentJsonFromCallback($response->getContent());

        $response->setContent($data);

        return $response;
    }
}
