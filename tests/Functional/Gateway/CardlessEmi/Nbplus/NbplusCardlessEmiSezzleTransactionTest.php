<?php

namespace RZP\Tests\Functional\Gateway\CardlessEmi\Nbplus;

use RZP\Constants\Entity;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Payment\NbplusPaymentsServiceCardlessEmiSezzleTest;

class NbplusCardlessEmiSezzleTransactionTest extends NbplusPaymentsServiceCardlessEmiSezzleTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = 'sezzle';

        $this->payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

    }
    public function testTransactionSuccess()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);

        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

    }

}
