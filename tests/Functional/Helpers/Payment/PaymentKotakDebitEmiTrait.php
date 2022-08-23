<?php

namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentKotakDebitEmiTrait
{
    protected function runPaymentCallbackFlowKotakDebitEmi($response, &$callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $response = ['url' => $url];

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
    }
}
