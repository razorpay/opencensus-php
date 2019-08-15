<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


class PaypalGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/PaypalGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_paypal_terminal');

        $this->fixtures->merchant->enableInternational();

        $this->setMockGatewayTrue();

        $this->fixtures->merchant->enableWallet('10000000000000', 'paypal');

        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 0]);

        $this->payment = $this->getDefaultWalletPaymentArray('paypal');
        $this->payment['currency'] = "USD";

    }

    public function testPayment()
    {
        $payment = $this->payment;

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');
        $this->assertEquals('100000Razorpay', $payment['terminal_id']);

        $mozartEntity = $this->getLastEntity('mozart', true);

        $this->assertTestResponse($mozartEntity, 'testPaymentMozartEntity');
    }

    public function testVerifyPayment()
    {
        $payment = $this->payment;

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testPaymentIdMismatch()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'pay_verify')
            {
                $content['data']['PayId'] = 'Hacked'; //some random payment_id
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals('failed', $paymentEntity['status']);
    }

    public function testAuthFailed()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'pay_verify')
            {
                $content['success'] = false;
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function ()
        {
            $this->doPaypalAuthAndCapturePayment();
        });
    }

    protected function runPaymentCallbackFlowWalletPaypal($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response,$callback);

        $data = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

        return $this->submitPaymentCallbackRequest($data);
    }

    protected function doPaypalAuthAndCapturePayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('paypal');
        $payment['currency'] = "USD";
        $this->doAuthAndCapturePayment($payment);
    }
}
