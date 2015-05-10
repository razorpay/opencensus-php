<?php

namespace Tests\Functional\Gateway\AxisMigs;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class KotakGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/AxisGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'axis_migs';
    }

    public function testPayment()
    {
        $this->markTestIncomplete();
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '6069880000000000';
        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);
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
