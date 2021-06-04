<?php

namespace RZP\Tests\Functional\Gateway\File;

use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingNsdlGatewayTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingNsdlGatewayTestData.php';

        parent::setUp();

        $this->bank = 'NSPB';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_nsdl_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray('NSPB');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $paymentEntity = $this->getDbLastPayment();

        $this->assertEquals(3, $paymentEntity['cps_route']);

        $this->assertEquals('captured', $paymentEntity['status']);

        $this->assertEquals('netbanking_nsdl', $paymentEntity['gateway']);

        $this->assertEquals(50000, $paymentEntity['base_amount']);
    }
}
