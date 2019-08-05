<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaypalGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/PaypalGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->fixtures->merchant->enableInternational();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_paypal_terminal');

        $this->payment = $this->getDefaultWalletPaymentArray('paypal');
        $this->payment['currency'] = "USD";

        $this->fixtures->merchant->enableWallet('10000000000000', 'paypal');

        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 0]);

    }

    public function testPayment()
    {
        $payment = $this->payment;

        $this->doAuthAndCapturePayment($payment);

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
                $content['data']['paymentId'] = 'ABCD1234567890'; //some random payment_id
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

    protected function runPaymentCallbackFlowWalletPaypal($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method) = $this->getDataForGatewayRequest($response, $callback);
        $this->response = $response;

        if ($mock)
        {
            return $this->submitPaymentCallbackData($url,$method ,null);
        }

        return null;
    }
}
