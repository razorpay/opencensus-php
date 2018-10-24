<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Vijaya;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingVijayaGatewayTest extends TestCase
{
    use PaymentTrait;

    protected $bank = null;

    protected $payment = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingVijayaGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_vijaya';

        $this->bank = 'VIJB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_vijaya_terminal');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentNetbankingEntity');
    }
}
