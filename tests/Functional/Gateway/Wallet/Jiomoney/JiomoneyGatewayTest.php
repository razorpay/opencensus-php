<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Jiomoney;

use RZP\Http\Route;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class JiomoneyGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/JiomoneyGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_jiomoney_terminal');

        $this->gateway = 'wallet_jiomoney';

        $this->fixtures->merchant->enableWallet('10000000000000', 'jiomoney');
    }

    public function testPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('jiomoney');

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    protected function runPaymentCallbackFlowWalletJiomoney($response, & $callback = null)
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
