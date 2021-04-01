<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Refund;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Upi\Base as UpiBase;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Payment\Refund\Status as RefundStatus;
use RZP\Tests\Functional\Gateway\Upi\UpiPaymentTrait;

class UpiCashfreeGatewayTest extends TestCase
{
    use UpiPaymentTrait;

    use PaymentTrait;

    use DbEntityFetchTrait;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'cashfree';

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->terminal = $this->fixtures->create('terminal:cashfree_terminal');

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
