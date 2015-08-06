<?php

namespace Tests\Functional\Gateway\Kotak;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class KotakGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->markTestSkipped();

        $this->testDataFilePath = __DIR__.'/KotakGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_kotak_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'kotak';
    }

    public function testPayment()
    {
        $this->config['gateway.mock_kotak'] = true;

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6070020000000000';
        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);
    }

    public function testPaymentTransactionCannotBeProcessed()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment = $this->runTestForAuthPayment();
    }

    public function testTransactionDeclinedPayment()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment = $this->runTestForAuthPayment();
    }
}
