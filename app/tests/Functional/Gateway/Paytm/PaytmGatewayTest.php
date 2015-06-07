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
        $this->config['gateway.mock_paytm'] = true;

        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('paytm', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentPaytmEntity'], $payment);
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
}
