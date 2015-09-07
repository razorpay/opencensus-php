<?php

namespace Tests\Functional\Gateway\Netbanking\Hdfc;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class NetbankingHdfcGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingHdfcGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'netbanking_hdfc';

        $this->setMockGatewayTrue();

        $this->fixtures->on('test')->create('terminal:shared_netbanking_hdfc_terminal');
    }

    public function testPayment()
    {
        $terminal = $this->fixtures->create('terminal:netbanking_hdfc_terminal');

        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment);

        $this->assertArrayHasKey('bank_payment_id', $payment);
        $this->assertTrue(filter_var($payment['bank_payment_id'], FILTER_VALIDATE_INT) !== false);
    }

    public function testPaymentOnSharedTerminal()
    {
        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment);

        $this->assertArrayHasKey('bank_payment_id', $payment);
        $this->assertTrue(filter_var($payment['bank_payment_id'], FILTER_VALIDATE_INT) !== false);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doNetbankingHdfcAuthAndCapturePayment();

        $this->verifyPayment($payment['id']);
    }

    protected function doNetbankingHdfcAuthAndCapturePayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'HDFC';
        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }
}
