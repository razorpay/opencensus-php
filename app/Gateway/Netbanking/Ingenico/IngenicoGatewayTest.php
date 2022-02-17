<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Ingenico;

use RZP\Models\Bank\IFSC;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class IngenicoGatewayTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'ingenico';

        $this->bank = IFSC::KKBK;

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->terminal = $this->fixtures->create('terminal:ingenico_terminal');
    }
}
