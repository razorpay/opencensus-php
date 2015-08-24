<?php

namespace Tests\Functional\Gateway\Mobikwik;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class MobikwikGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/MobikwikGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_mobikwik_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'mobikwik';
    }

//    public function testPayment()
//    {
//        $this->setMockGatewayTrue();
//
//        $payment = $this->getDefaultPaymentArray();
//        $payment = $this->doAuthAndCapturePayment($payment);
//
//        $payment = $this->getLastEntity('payment', true);
//
//        $this->assertTestResponse($payment);
//
//        $payment = $this->getLastEntity('mobikwik', true);
//
//        $this->assertArraySelectiveEquals(
//            $this->testData['testPaymentMobikwikEntity'], $payment);
//    }

    public function testMobikwikWallet()
    {
        $this->fixtures->links['merchant']->enableMobikwik('10000000000000');

        $this->setMockGatewayTrue();

        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'wallet';
        $payment['wallet'] = 'mobikwik';

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('mobikwik', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testMobikwikWalletEntity'], $payment);
    }

    public function testFailedPayment()
    {
        $this->markTestIncomplete();
    }

    public function testPayment3dsecureFailed()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment = $this->runTestForAuthPayment();
    }

    public function testVerifyPayment()
    {
        $this->markTestIncomplete();

        $this->setMockGatewayTrue();

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $id = $payment['id'];

        $payment = $this->verifyPayment($id);

        $this->assertEquals($payment['verified'], true);
    }

    public function testRefundPayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('mobikwik', true);

        $this->assertTestResponse($refund);
    }

    public function testMobikwikWhenNotEnabled()
    {
        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'wallet';
        $payment['wallet'] = 'mobikwik';

        $testData['request']['content'] = $payment;

        $content = $this->startTest($testData);
    }
}
