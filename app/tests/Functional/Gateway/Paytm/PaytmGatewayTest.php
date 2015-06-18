<?php

namespace Tests\Functional\Gateway\Paytm;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class PaytmGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/PaytmGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_paytm_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'paytm';
    }

    public function testPayment()
    {
        $this->setMockGatewayTrue();

        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('paytm', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentPaytmEntity'], $payment);
    }

    public function testPaytmWallet()
    {
        $this->fixtures->links['merchant']->enablePaytm('10000000000000');

        $this->setMockGatewayTrue();

        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'wallet';
        $payment['wallet'] = 'paytm';

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('paytm', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaytmWalletEntity'], $payment);
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

    public function testPaytmWhenNotEnabled()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'wallet';
        $payment['wallet'] = 'paytm';

        $this->startTest();
    }
}
