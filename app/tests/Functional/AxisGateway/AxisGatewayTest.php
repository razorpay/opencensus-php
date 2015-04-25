<?php

namespace Tests\Functional\AxisGateway;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class AxisGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/AxisGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'axis';
    }

    public function testPayment()
    {
        $payment = $this->doAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);
    }
}
