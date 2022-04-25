<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Gateway\Upi\UpiPaymentTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiPinelabsGatewayTest extends TestCase
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

        $this->gateway = 'pinelabs';

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->terminal = $this->fixtures->create('terminal:pinelabs_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();
    }

    public function testUpiIntentPaymentCreateSuccess()
    {
        $this->markTestSkipped('Gateway does not support UPI Intent');
    }

    public function testUpiPaymentCallbackFailed()
    {
        $this->markTestSkipped('Gateway does not support callback');
    }

    public function testCallbackAmountMismatch()
    {
        $this->markTestSkipped('Gateway does not support callback');
    }

    public function testUpiCollectPaymentSuccess()
    {
        $this->markTestSkipped('Gateway does not support callback flow.');
    }

    // Callback not supported on gateway
    // Gateway will authorize from verify action, triggered by cron
    public function testUpiVerifyPayment()
    {
        $this->testUpiCollectUpiPaymentCreateFail('collect_request_pending_v2');

        $payment = $this->getDbLastPayment();

        $this->assertSame(Status::FAILED, $payment->getStatus());

        $this->assertSame('GATEWAY_ERROR_TRANSACTION_PENDING', $payment->getInternalErrorCode());

        $time = Carbon::now(Timezone::IST)->addMinutes(4);

        Carbon::setTestNow($time);

        $response = $this->getPaymentStatus($payment->getPublicId());

        $status = $response['status'];

        $this->assertEquals('created', $status);

        $this->verifyAllPayments();

        $payment->reload();

        $this->assertSame(Status::AUTHORIZED, $payment->getStatus());

        $this->assertFalse($payment->isLateAuthorized());

        $this->assertTrue($payment->getGatewayCaptured());

        $response = $this->getPaymentStatus($payment->getPublicId());

        $this->assertEquals($payment->getPublicId(), $response['razorpay_payment_id']);
    }
}
