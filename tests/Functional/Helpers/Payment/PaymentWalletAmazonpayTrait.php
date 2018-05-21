<?php

namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentWalletAmazonpayTrait
{
    protected function runPaymentCallbackFlowWalletAmazonpay($response, &$callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $requestUrl = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

        $requestUrl = $this->makeFirstGatewayPaymentMockRequest($requestUrl, $method, $content);

        return $this->submitPaymentCallbackRequest(['url' => $requestUrl]);
    }
}
