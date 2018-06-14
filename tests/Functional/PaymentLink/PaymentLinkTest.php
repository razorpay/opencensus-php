<?php

namespace RZP\Tests\Functional\PaymentLink;

use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Models\PaymentLink as PaymentLinkModel;
use RZP\Jobs\PaymentLink\RefundPayment as RefundPaymentJob;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentLinkTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

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
            PaymentLinkModel\Entity::STATUS        => PaymentLinkModel\Status::INACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON => PaymentLinkModel\StatusReason::EXPIRED,
            PaymentLinkModel\Entity::EXPIRE_BY     => 1400000000,
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
            PaymentLinkModel\Entity::AMOUNT        => 10100,
            PaymentLinkModel\Entity::TIMES_PAYABLE => 10,
        ];

        $paymentLink = $this->fixtures->create('payment_link', $attributes);

        $this->makePaymentForPaymentLinkAndAssert($paymentLink);

        $this->getLastPaymentLinkEntityAndAssert($this->testData[__FUNCTION__]['payment_link']);
    }

    public function testPaymentLinkCompletePayments()
    {
        $attributes = [
            PaymentLinkModel\Entity::AMOUNT        => 10100,
            PaymentLinkModel\Entity::TIMES_PAYABLE => 2,
        ];

        $paymentLink = $this->fixtures->create('payment_link', $attributes);

        $this->makePaymentForPaymentLinkAndAssert($paymentLink);

        $this->getLastPaymentLinkEntityAndAssert($this->testData[__FUNCTION__]['payment_link_after_payment_1']);

        $this->makePaymentForPaymentLinkAndAssert($paymentLink);

        $this->getLastPaymentLinkEntityAndAssert($this->testData[__FUNCTION__]['payment_link_after_payment_2']);
    }

    public function testPaymentLinkUnavailableSlots()
    {
        $attributes = [
            PaymentLinkModel\Entity::AMOUNT        => 10100,
            PaymentLinkModel\Entity::TIMES_PAYABLE => 2,
        ];

        $paymentLink = $this->fixtures->create('payment_link', $attributes);

        $this->makePaymentForPaymentLinkAndAssert($paymentLink);

        $paymentAttributes = [
            Payment\Entity::PAYMENT_LINK_ID => $paymentLink->getId(),
        ];

        $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_PAYMENT_LINK_NOT_PAYABLE);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_PAYMENT_LINK_NOT_PAYABLE);

        $this->makePaymentForPaymentLinkAndAssert($paymentLink);
    }

    public function testPaymentLinkRefundExcessPayment()
    {
        Queue::fake();

        $paymentLinkAttributes = [
            PaymentLinkModel\Entity::AMOUNT             => 10100,
            PaymentLinkModel\Entity::TIMES_PAYABLE      => 2,
            PaymentLinkModel\Entity::TIMES_PAID         => 2,
            PaymentLinkModel\Entity::TOTAL_AMOUNT_PAID  => 20200,
            PaymentLinkModel\Entity::STATUS             => PaymentLinkModel\Status::INACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON      => PaymentLinkModel\StatusReason::COMPLETED,
        ];

        $paymentLink = $this->fixtures->create('payment_link', $paymentLinkAttributes);

        $paymentAttributes = [
            Payment\Entity::AMOUNT          => 10100,
            Payment\Entity::PAYMENT_LINK_ID => $paymentLink->getId(),
        ];

        // Following 2 captured payment attribute to above completed payment link
        $this->fixtures->times(2)->create('payment:captured', $paymentAttributes);


        //
        // Additionally, we will create a payment in past which will be attempted to be captured now(present) and
        // the flow should initiate refund. At this point we just assert that a job was pushed to make the refund onto
        // queue. We choose past 25 hrs, because following endpoint fetches so payments for auto capture.
        //
        Carbon::setTestNow(Carbon::now()->subHours(25));

        $paymentAuth = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->doAutoCapture();

        Queue::assertPushed(RefundPaymentJob::class, function($job) use ($paymentAuth)
        {
            $this->assertEquals($paymentAuth['id'], $job->getPaymentId());

            return true;
        });
    }

    // -------------------- Protected methods --------------------

    protected function createPaymentLink(string $id = self::DEFAULT_PAYMENT_LINK_ID, array $attributes = [])
    {
        $attributes[PaymentLinkModel\Entity::ID] = $id;

        $this->fixtures->create('payment_link', $attributes);
    }

    /**
     * Helper method to make payment for given payment link (with auto-capture) and do the necessary assertions
     * @param  PaymentLinkModel\Entity $paymentLink
     * @return Payment\Entity
     */
    protected function makePaymentForPaymentLinkAndAssert(PaymentLinkModel\Entity $paymentLink)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $paymentLink->getAmount();

        $payment = $this->doAuthAndGetPayment($payment, [Payment\Entity::STATUS => Payment\Status::CAPTURED]);
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($paymentLink->getAmount(), $payment->getAmount());
        $this->assertEquals($paymentLink->getId(), $payment->getPaymentLinkId());

        return $payment;
    }

    protected function getLastPaymentLinkEntityAndAssert(array $expected)
    {
        $paymentLink = $this->getDbLastEntity('payment_link');

        $this->assertArraySelectiveEquals($expected, $paymentLink->toArray());
    }
}
