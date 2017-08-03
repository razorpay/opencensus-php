<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Sbibuddy;

use RZP\Gateway\Wallet\Sbibuddy\RequestFields;
use RZP\Gateway\Wallet\Sbibuddy\ResponseFields;
use RZP\Gateway\Wallet\Sbibuddy\StatusCode;
use RZP\Http\Route;
use RZP\Models\Payment\Refund\Status as RefundStatus;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class SbibuddyGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/SbibuddyGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sbibuddy_terminal');

        $this->gateway = 'wallet_sbibuddy';

        $this->fixtures->merchant->enableWallet('10000000000000', 'sbibuddy');
    }

    public function testPayment()
    {

        $payment = $this->getDefaultWalletPaymentArray('sbibuddy');

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    protected function runPaymentCallbackFlowWalletSbibuddy($response, & $callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $requestUrl = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

            // It's a redirect url. Jiomoney use callback flow for payment authorization.
            $request = ['url' => $requestUrl];

            return $this->submitPaymentCallbackRequest($request);
        }
    }
}
