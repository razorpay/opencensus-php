<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Cashfree;

use RZP\Models\Bank\IFSC;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class CashfreeGatewayTest extends NbPlusPaymentServiceNetbankingTest
{
    public function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'cashfree';

        $this->bank = IFSC::RATN;

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->terminal = $this->fixtures->create('terminal:cashfree_terminal');
    }
}
