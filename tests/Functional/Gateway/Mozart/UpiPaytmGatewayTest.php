<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Models\Terminal\Type;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Gateway\Upi\UpiPaymentTrait;

class UpiPaytmGatewayTest extends TestCase
{
    use PaymentTrait;

    use DbEntityFetchTrait;

    use UpiPaymentTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'paytm';

        $this->setMockGatewayTrue();

        $this->createTestTerminal();

    }

    public function testUpiIntentPaymentCreateSuccess()
    {
        $this->markTestSkipped('Skipping Not implemented right now will remove once implement');
    }

    public function testCallbackAmountMismatch()
    {
        $this->markTestSkipped('Skipping Not implemented right now will remove once implement');
    }

    protected function createTestTerminal()
    {
        $this->terminal = $this->fixtures->create('terminal:upi_paytm_terminal', [
            'type'=>    [
                Type::NON_RECURRING => '1',
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '0'
            ]
        ]);
        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();
    }
}
