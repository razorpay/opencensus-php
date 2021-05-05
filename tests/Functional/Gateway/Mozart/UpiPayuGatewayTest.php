<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Gateway\Upi\UpiPaymentTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiPayuGatewayTest extends TestCase
{
    use UpiPaymentTrait;

    use PaymentTrait;

    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'payu';

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->terminal = $this->fixtures->create('terminal:payu_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();
    }
    public function testUpiIntentPaymentCreateSuccess()
    {
        $this->markTestSkipped('Skipping Not implemented right now will remove once implement');
    }

    public function testUpiCollectPaymentSuccess()
    {
        $this->markTestSkipped('Skipping Not implemented right now will remove once implement');
    }

    public function testUpiPaymentCallbackFailed()
    {
        $this->markTestSkipped('Skipping Not implemented right now will remove once implement');
    }

    public function testCallbackAmountMismatch()
    {
        $this->markTestSkipped('Skipping Not implemented right now will remove once implement');
    }

    public function testUpiVerifyPayment()
    {
        $this->markTestSkipped('Skipping Not implemented right now will remove once implement');
    }

    public function testUpiLateAuthPayment()
    {
        $this->markTestSkipped('Skipping Not implemented right now will remove once implement');
    }

    public function testVerifyPaymentAmountMismatch()
    {
        $this->markTestSkipped('Skipping Not implemented right now will remove once implement');
    }
}
