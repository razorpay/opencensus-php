<?php

namespace RZP\Tests\Functional\PaymentLink;

use Illuminate\Support\Facades\Queue;

use RZP\Models\Payment;
use RZP\Models\PaymentLink;
use RZP\Jobs\PaymentLinkRefund;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentLinkTest extends TestCase
{
    use PaymentTrait;

    const DEFAULT_PAYMENT_LINK_ID = '100000000000pl';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/PaymentLinkTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreatePaymentLink()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithBadExpireBy()
    {
        $this->startTest();
    }

    public function testFetchPaymentLink()
    {
        $this->createPaymentLink();

        $this->startTest();
    }

    public function testFetchPaymentLinks()
    {
        $this->createPaymentLink();

        $this->startTest();
    }

    public function testUpdatePaymentLink()
    {
        $this->createPaymentLink();

        $this->startTest();
    }

    public function testUpdatePaymentLinkWithBadExpireBy()
    {
        $this->createPaymentLink();

        $this->startTest();
    }

    public function testPaymentLinkSendNotification()
    {
        $this->createPaymentLink();

        $this->startTest();
    }

    public function testInactivePaymentLinkSendNotification()
    {
        $attributes = [
            PaymentLink\Entity::STATUS        => PaymentLink\Status::INACTIVE,
            PaymentLink\Entity::STATUS_REASON => PaymentLink\StatusReason::EXPIRED,
            PaymentLink\Entity::EXPIRE_BY     => 1400000000,
        ];

        $this->createPaymentLink(self::DEFAULT_PAYMENT_LINK_ID, $attributes);

        $this->startTest();
    }

    public function testExpirePaymentLinks()
    {
        $this->fixtures->times(2)->create('payment_link', [
            'expire_by' => '1400000000',
        ]);

        $this->fixtures->create('payment_link');

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testPaymentLinkMakePayment()
    {
        $attributes = [
            'amount'            => 10100,
            'times_payable'     => 10,
        ];

        $paymentLink = $this->fixtures->create('payment_link', $attributes);

        $this->makePaymentForPaymentLinkAndAssert($paymentLink->toArray());

        $paymentLink = $this->getLastEntity('payment_link', true);

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__]['state'], $paymentLink);
    }

    public function testPaymentLinkCompletePayments()
    {
        $attributes = [
            'amount'            => 10100,
            'times_payable'     => 2,
        ];

        $paymentLink = $this->fixtures->create('payment_link', $attributes);

        $this->makePaymentForPaymentLinkAndAssert($paymentLink->toArray());

        $this->makePaymentForPaymentLinkAndAssert($paymentLink->toArray());

        $paymentLink = $this->getLastEntity('payment_link', true);

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__]['state'], $paymentLink);
    }

    public function testPaymentLinkUnavailableSlots()
    {
        $attributes = [
            'amount'            => 10100,
            'times_payable'     => 2,
        ];

        $paymentLink = $this->fixtures->create('payment_link', $attributes);

        $this->makePaymentForPaymentLinkAndAssert($paymentLink->toArray());

        $paymentAttributes = [
            'payment_link_id' => $paymentLink['id'],
        ];

        $this->fixtures->create('payment:authorized', $paymentAttributes);

        $code = null;
        $message = null;

        try
        {
            $this->makePaymentForPaymentLinkAndAssert($paymentLink->toArray());
        }
        catch (\Throwable $ex)
        {
            $message = $ex->getMessage();
            $code = $ex->getCode();
        }

        $this->assertEquals('BAD_REQUEST_PAYMENT_LINK_NOT_PAYABLE', $code);

        $this->assertEquals('Payment cannot be made on this payment link', $message);
    }

    public function testPaymentLinkRefundExcessPayment()
    {
        Queue::fake();

        $paymentLinkAttributes = [
            'amount'            => 10100,
            'times_payable'     => 2,
            'times_paid'        => 2,
            'total_amount_paid' => 20200,
            'status'            => 'inactive',
            'status_reason'     => 'completed',
        ];

        $paymentLink = $this->fixtures->create('payment_link', $paymentLinkAttributes);

        $paymentAttributes = [
            'amount'            => 10100,
            'payment_link_id' => $paymentLink['id'],
        ];

        $this->fixtures->times(2)->create('payment:captured', $paymentAttributes);

        //
        // This is not a mandatory condition, but we added this to
        // recreate the condition for which the test is required
        //
        $paymentAttributes['created_at'] = time() - ((24+1) * 60 * 60);;

        $paymentAuth = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->doAutoCapture();

        Queue::assertPushed(PaymentLinkRefund::class, function($job) use ($paymentAuth)
        {
            $this->assertEquals($paymentAuth['id'], $job->getPaymentId());

            return true;
        });
    }

    // -------------------- Protected methods --------------------

    protected function createPaymentLink(string $id = self::DEFAULT_PAYMENT_LINK_ID, array $attributes = [])
    {
        $attributes[PaymentLink\Entity::ID] = $id;

        $this->fixtures->create('payment_link', $attributes);
    }

    /**
     * Helper method to make  payment for given payment link
     * (with auto-capture) and do the necessary assertions.
     *
     * @param array $paymentLink
     *
     * @return array
     */
    protected function makePaymentForPaymentLinkAndAssert(array $paymentLink)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::PAYMENT_LINK_ID] = PaymentLink\Entity::getSignedId($paymentLink['id']);

        $payment['amount']   = $paymentLink['amount'];

        $payment = $this->doAuthAndGetPayment($payment, ['status' => 'captured']);

        $this->assertEquals($paymentLink['amount'], $payment['amount']);

        $this->assertEquals($paymentLink['id'], $payment['payment_link_id']);

        $this->assertEquals('captured', $payment['status']);

        return $payment;
    }
}
