<?php

namespace Tests\Functional\Gateway\Netbanking\Kotak;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class NetbankingKotakGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingKotakGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'netbanking_kotak';

        $this->setMockGatewayTrue();

        $this->fixtures->on('test')->create('terminal:shared_netbanking_kotak_terminal');
    }

    public function testPayment()
    {
        $terminal = $this->fixtures->create('terminal:netbanking_kotak_terminal');

        $payment = $this->doNetbankingKotakAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment);

        $this->assertArrayHasKey('bank_payment_id', $payment);
        $this->assertTrue(filter_var($payment['bank_payment_id'], FILTER_VALIDATE_INT) !== false);
    }

//    public function testPaymentOnSharedTerminal()
//    {
//        $payment = $this->doNetbankingKotakAuthAndCapturePayment();
//
//        $payment = $this->getLastEntity('payment', true);
//
//        $this->assertTestResponse($payment);
//
//        $payment = $this->getLastEntity('netbanking', true);
//
//        $this->assertArraySelectiveEquals(
//            $this->testData['testPaymentNetbankingEntity'], $payment);
//
//        $this->assertArrayHasKey('bank_payment_id', $payment);
//        $this->assertTrue(filter_var($payment['bank_payment_id'], FILTER_VALIDATE_INT) !== false);
//    }
//
//    public function testPaymentVerify()
//    {
//        $payment = $this->doNetbankingKotakAuthAndCapturePayment();
//
//        $this->verifyPayment($payment['id']);
//    }

    protected function doNetbankingKotakAuthAndCapturePayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'KKBK';
        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }
}
