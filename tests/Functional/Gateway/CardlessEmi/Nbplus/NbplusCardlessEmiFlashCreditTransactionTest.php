<?php

namespace RZP\Tests\Functional\Gateway\CardlessEmi\Nbplus;

use RZP\Constants\Entity;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Payment\Entity as Payment;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Payment\NbplusPaymentServiceCardlessEmiTest;

class NbplusCardlessEmiFlashCreditTransactionTest extends NbplusPaymentServiceCardlessEmiTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = 'flashcredit';

        $this->payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        $this->terminal = $this->fixtures->create('terminal:sharedCardlessEmiFlashCreditTerminal');

    }

    public function testFlashCreditTransaction()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);
    }

}
