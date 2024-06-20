<?php

namespace RZP\Tests\Functional\Gateway\CardlessEmi\Nbplus;

use RZP\Constants\Entity;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Payment\Entity as Payment;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Payment\NbplusPaymentServiceCardlessEmiTest;

class NbplusCardlessEmiInstantEmiTransactionTest extends NbplusPaymentServiceCardlessEmiTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = 'instant_emi';

        $this->payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        $this->terminal = $this->fixtures->create('terminal:sharedCardlessEmiInstant_EmiTerminal');

    }

    public function testInstantEmiTransaction()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);
    }

}
