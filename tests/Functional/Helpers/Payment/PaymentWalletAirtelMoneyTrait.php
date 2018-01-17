<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use RZP\Tests\Functional\TestCase;

trait PaymentWalletAirtelMoneyTrait
{
    protected function runPaymentCallbackFlowWalletAirtelmoney($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $requestUrl = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

            // It's a redirect url. Airtelmoney use callback flow for payment authorization.
            $request = [
                'url' => $requestUrl,
            ];

            return $this->submitPaymentCallbackRequest($request);
        }
    }
}
