<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Sbibuddy;

use RZP\Gateway\Wallet\Sbibuddy\StatusCode;
use RZP\Gateway\Wallet\Sbibuddy\RequestFields;
use RZP\Gateway\Wallet\Sbibuddy\ResponseFields;
use RZP\Models\Payment\Refund\Status as RefundStatus;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

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

    public function testRefundPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('sbibuddy');

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($capturePayment['id']);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund);
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('sbibuddy');

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    //----------------------Helper methods-----------------------

    protected function runPaymentCallbackFlowWalletSbibuddy($response, & $callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $mock = $this->isGatewayMocked();

        if ($mock)
        {
            $requestUrl = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

            $request = ['url' => $requestUrl];

            return $this->submitPaymentCallbackRequest($request);
        }
    }
}
