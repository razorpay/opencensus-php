<?php

namespace Tests\Functional\Gateway\Wallet\Payumoney;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class PayumoneyGatewayTest extends TestCase
{
    use PaymentTrait;

    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/PayumoneyGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_payumoney_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'wallet_payumoney';

        $this->fixtures->merchant->enableWallet('10000000000000', 'payumoney');

        $this->setMockGatewayTrue();
    }

    public function testPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $authPayment = $this->doAuthPayment($payment);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($wallet, 'testPaymentWalletEntity');
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertEquals($this->payment['payment']['verified'], 1);
    }

    public function testRefundPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $authPayment = $this->doAuthPayment($payment);

        $capturePayment = $this->capturePayment($authPayment['razorpay_payment_id'], $payment['amount']);

        $this->refundPayment($capturePayment['id']);

        $refund = $this->getLastEntity('wallet', true);

        $this->assertTestResponse($refund);
    }

    public function testRefundPayment2()
    {
        $payment = $this->getDefaultWalletPaymentArray('payumoney');

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($capturePayment['id']);

        $refund = $this->getLastEntity('wallet', true);
    }

    protected function runPaymentCallbackFlowWalletPayumoney($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $content['otp'] = '123456';
            $content['type'] = 'otp';

            $request = array(
                'url'       => $url,
                'method'    => $method,
                'content'   => $content
            );

            return $this->makeRequest($request);
        }

        return null;
    }

}
