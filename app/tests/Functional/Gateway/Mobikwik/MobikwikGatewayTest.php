<?php

namespace Tests\Functional\Gateway\Mobikwik;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class MobikwikGatewayTest extends TestCase
{
    use PaymentTrait;

    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/MobikwikGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_mobikwik_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'mobikwik';

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultPaymentArray();
        $this->payment['wallet'] = 'mobikwik';
        $this->payment['method'] = 'wallet';
    }

    public function testPayment()
    {

        $this->payment = $this->doAuthAndCapturePayment($this->payment);
        $this->payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($this->payment);

        $this->payment = $this->getLastEntity('mobikwik', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testMobikwikWalletEntity'], $this->payment);
    }

//    public function testFailedPayment()
//    {
//        $this->markTestIncomplete();
//    }

    public function testVerifyPayment()
    {

        $this->payment = $this->doAuthAndCapturePayment($this->payment);
        $id = $this->payment['id'];
        $this->payment = $this->verifyPayment($id);
        $this->assertEquals($this->payment['payment']['verified'], true);
    }

    public function testRefundPayment()
    {
        $this->payment = $this->doAuthAndCapturePayment($this->payment);

        $this->refundPayment($this->payment['id']);
        $refund = $this->getLastEntity('mobikwik', true);
        $this->assertTestResponse($refund);
    }

}
