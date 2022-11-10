<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Airtel;

use Razorpay\IFSC\Bank;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingAirtelGatewayTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingAirtelGatewayTestData.php';

        parent::setUp();

        $this->bank = Bank::AIRP;

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_airtel_terminal');
    }

    public function testPayment()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getDbLastPayment();

        $this->assertTestResponse($payment->toArray());

        $this->assertTrue($payment->getCpsRoute() === 3);
    }
}
