<?php

namespace Tests\Functional\Gateway\Kotak;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class KotakGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/KotakGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_kotak_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'kotak';
    }

    public function testPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6070020000000000';
        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);
    }

    public function testTransactionDeclinedPayment()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment = $this->runTestForAuthPayment();
    }

    public function testFailPayment()
    {
        $this->markTestIncomplete();
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4111111111111111';

        $content = $this->doAuthPayment($payment);

        sd($content);
    }
}
