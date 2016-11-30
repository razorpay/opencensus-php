<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class NetbankingIciciGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingIciciGatewayTestData.php';

        parent::setUp();

        // removed disable hdfc

        $this->gateway = 'netbanking_icici';

        $this->setMockGatewayTrue();

        $this->fixtures->on('test')->create('terminal:shared_netbanking_icici_terminal');
    }

    public function testPayment()
    {
        $terminal = $this->fixtures->create('terminal:netbanking_icici_terminal');

        $paymentAction = 'AuthAndCapture';
        $payment = $this->doNetbankingIciciPayment($paymentAction);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertEquals(
            strtoupper($payment['payment_id']), $payment['caps_payment_id']);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment);

        // Asserts that bank payment id exists in response and is an int
        $this->assertArrayHasKey('bank_payment_id', $payment);
        $this->assertTrue(filter_var($payment['bank_payment_id'], FILTER_VALIDATE_INT) !== false);
    }

    public function testPaymentVerify()
    {
        $paymentAction = 'Auth';
        $payment = $this->doNetbankingIciciPayment($paymentAction);

        $content = $this->verifyPayment($payment['razorpay_payment_id']);

        assert($content['payment']['verified'] === 1);
    }

    protected function doNetbankingIciciPayment($paymentAction)
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'ICIC';

        // Switch case
        $switch = array(
            'Auth'           => $this->doAuthPayment($payment),
            'AuthAndCapture' => $this->doAuthAndCapturePayment($payment)
        );

        $payment = $switch[$paymentAction];

        return $payment;
    }

}
