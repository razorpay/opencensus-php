<?php

namespace Tests\Functional\Gateway\Sbiepaypay;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class SbiepayGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/SbiepayGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sbiepay_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'sbiepay';

        $this->setMockGatewayTrue();
    }

    public function testPayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'SBIN';
        $payment = $this->doAuthAndCapturePayment($payment);
        $payment = $this->getLastEntity('payment', true);
        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('sbiepay', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentSbiepayEntity'], $payment);
    }

//    public function testPaymentFailed()
//    {
//        $this->markTestIncomplete();
//
//        $this->failPaymentOnBankPage = true;
//    }
//
    public function testPaymentVerify()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'SBIN';
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->verifyPayment($payment['id']);
    }

    public function testPaymentRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'SBIN';
        $payment = $this->doAuthAndCapturePayment($payment);
sd($payment);
        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('sbiepay', true);

        $this->assertTestResponse($refund);
    }

}
