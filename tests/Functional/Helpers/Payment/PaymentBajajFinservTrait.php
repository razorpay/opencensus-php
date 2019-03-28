<?php

namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentBajajFinservTrait
{
    protected function runPaymentCallbackFlowBajajFinserv($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $response = ['url' => $url];

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
    }

    protected function makeResponseJson($body)
    {
        $response = \Response::make($body);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
