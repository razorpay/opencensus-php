<?php

namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentWalletAmazonpayTrait
{
    protected function runPaymentCallbackFlowWalletAmazonpay($response, &$callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $requestUrl = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

        // Make sure the amazon callback is on direct auth
        $this->ba->publicCallbackAuth();

        $requestUrl = $this->makeFirstGatewayPaymentMockRequest($requestUrl, $method, $content);

        $request = [
            'url'       => $requestUrl,
            'method'    => 'get'
        ];

        return $this->submitPaymentCallbackRequest($request);
    }
}
