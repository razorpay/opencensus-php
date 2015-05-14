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
        $this->markTestIncomplete();
        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);
    }
}
