<?php

namespace Tests\Functional\Gateway\AxisGenius;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class GeniusGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/GeniusGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_axis_genius_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'axis_genius';
    }

    public function testPayment()
    {
        $payment = $this->doAuthAndCapturePayment();

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
