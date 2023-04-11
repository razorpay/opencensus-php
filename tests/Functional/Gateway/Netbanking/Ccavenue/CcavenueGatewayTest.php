<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Ccavenue;

use RZP\Models\Bank\IFSC;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class CcavenueGatewayTest extends NbPlusPaymentServiceNetbankingTest
{
    public function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'ccavenue';

        $this->bank = IFSC::FDRL;

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->terminal = $this->fixtures->create('terminal:ccavenue_terminal');
    }
}
