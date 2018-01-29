<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Csb;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingCsbGatewayTest extends TestCase
{
    private $payment;

    private $sharedTerminal;

    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();

        $this->payment = $this->getDefaultNetbankingPaymentArray(IFSC::CSBK);

        $this->gateway = Payment\Gateway::NETBANKING_CSB;

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_netbanking_csb_terminal');
    }

    public function testPayment()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        sd($payment);
    }
}
