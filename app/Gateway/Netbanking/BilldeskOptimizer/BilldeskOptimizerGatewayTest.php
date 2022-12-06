<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\BilldeskOptimizer;

use RZP\Models\Bank\IFSC;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class BilldeskOptimizerGatewayTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'billdesk_optimizer';

        $this->bank = IFSC::KKBK;

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->terminal = $this->fixtures->create('terminal:billdesk_optimizer_terminal');
    }
}
