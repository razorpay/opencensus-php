<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Zaakpay;

use RZP\Models\Bank\IFSC;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class ZaakpayGatewayTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'zaakpay';

        $this->bank = IFSC::KKBK;

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->terminal = $this->fixtures->create('terminal:zaakpay_terminal');
    }
}
