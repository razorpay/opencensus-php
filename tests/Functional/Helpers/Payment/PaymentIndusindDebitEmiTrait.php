<?php

namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentIndusindDebitEmiTrait
{
    protected function runPaymentCallbackFlowIndusindDebitEmi($response, &$callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $response = ['url' => $url];

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
    }
}
