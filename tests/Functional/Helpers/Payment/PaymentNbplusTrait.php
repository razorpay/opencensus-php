<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Mockery;

use RZP\Models\Terminal\Entity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

trait PaymentNbplusTrait
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    /**
     * @var Entity
     */
    protected $terminal;

    protected $provider;

    protected $bank;
    /**
     * @var Mockery\Mock
     */
    protected $nbPlusService;
    /**
     * @var array
     */
    protected $payment;

    protected function runPaymentCallbackFlowForGateway($response, $gateway, &$callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $response = $this->mockCallbackFromGateway($url, $method, $content);

        $data = $this->getPaymentJsonFromCallback($response->getContent());

        $response->setContent($data);

        return $response;
    }

    protected function mockCallbackFromGateway($url, $method = 'get', $content = array())
    {
        $request = array(
            'url' => $url,
            'method' => strtoupper($method),
            'content' => $content);

        $response = $this->makeRequestParent($request);

        return $response;
    }

    // TODO
    /*public function testAuthorizeViaCpsCheckoutAuthorizePayment()
    {
    }

    public function testCaptureViaNbPlusService()
    {
    }

    public function testAuthorizeViaCpsS2SAuthorize()
    {
    }*/

    protected function mockServerContentFunction($closure)
    {
        $this->nbPlusService->shouldReceive('content')->andReturnUsing($closure);
    }

    protected function mockServerRequestFunction($closure)
    {
        $this->nbPlusService->shouldReceive('request')->andReturnUsing($closure);
    }
}
