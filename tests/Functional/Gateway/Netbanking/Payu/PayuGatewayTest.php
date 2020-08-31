<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Payu;

use RZP\Models\Bank\IFSC;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceTest;

class PayuGatewayTest extends NbPlusPaymentServiceTest
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = 'payu';

        $this->bank = IFSC::MSNU;

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->terminal = $this->fixtures->create('terminal:payu_terminal');
    }
}
