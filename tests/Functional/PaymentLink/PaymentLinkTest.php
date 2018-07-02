<?php

namespace RZP\Tests\Functional\PaymentLink;

use Carbon\Carbon;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\PaymentLink;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Models\PaymentLink as PaymentLinkModel;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentLinkTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    const TEST_PL_ID = '100000000000pl';

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

    public function testCreatePaymentLinkWithCurrencyAndNoAmount()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithoutAmountOrCurrency()
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
        $this->createPaymentLink(self::TEST_PL_ID, ['amount' => 2000]);

        $this->startTest();
    }

    public function testUpdatePaymentLinkInvalidAmountCurrency()
    {
        $this->createPaymentLink(self::TEST_PL_ID);

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

        $this->createPaymentLink(self::TEST_PL_ID, $attributes);

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

    public function testPaymentLinkMakePaymentWithUserDefinedAmount()
    {
        $attributes = [
            PaymentLinkModel\Entity::AMOUNT        => null,
            PaymentLinkModel\Entity::CURRENCY      => null,
            PaymentLinkModel\Entity::TIMES_PAYABLE => 10,
        ];

        $paymentLink = $this->fixtures->create('payment_link', $attributes);

        $payment = $this->getDefaultPaymentArray();
        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = 45000;

        $this->doAuthAndGetPayment($payment, [Payment\Entity::STATUS => Payment\Status::CAPTURED]);
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals(45000, $payment->getAmount());
        $this->assertEquals($paymentLink->getId(), $payment->getPaymentLinkId());

        $this->getLastPaymentLinkEntityAndAssert($this->testData[__FUNCTION__]['payment_link']);
    }


    public function testPaymentLinkMakePaymentWithUdfInvalid()
    {
        $attributes = [
            PaymentLinkModel\Entity::ID            => '10000pludftest',
            PaymentLinkModel\Entity::AMOUNT        => 10100,
            PaymentLinkModel\Entity::TIMES_PAYABLE => 10,
        ];
        $paymentLink = $this->fixtures->create('payment_link', $attributes);
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage('The customer_id field is invalid. The property customer_id is required');
        $this->makePaymentForPaymentLinkAndAssert($paymentLink);
    }

    public function testPaymentLinkMakePaymentWithUdf()
    {
        $attributes = [
            PaymentLinkModel\Entity::ID            => '10000pludftest',
            PaymentLinkModel\Entity::AMOUNT        => 10100,
            PaymentLinkModel\Entity::TIMES_PAYABLE => 10,
        ];
        $paymentLink = $this->fixtures->create('payment_link', $attributes);
        $payment = $this->getDefaultPaymentArray();
        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $paymentLink->getAmount();
        $payment[Payment\Entity::NOTES]           = [
            'customer_id'   => '1000001',
            'customer_name' => 'Random Name',
        ];
        $this->doAuthAndGetPayment($payment, [Payment\Entity::STATUS => Payment\Status::CAPTURED]);
        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals($paymentLink->getAmount(), $payment->getAmount());
        $this->assertEquals($paymentLink->getId(), $payment->getPaymentLinkId());
    }

    public function testPaymentLinkMakePaymentWithInvalidAmount()
    {
        $paymentLink = $this->createPaymentLink();

        $payment = $this->getDefaultPaymentArray();
        $payment[Payment\Entity::AMOUNT] = 5000; // Different amount value
        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_PAYMENT_LINK_PAYMENT_AMOUNT_MISMATCH);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_PAYMENT_LINK_PAYMENT_AMOUNT_MISMATCH);

        $this->doAuthAndGetPayment($payment);
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

        // TODO: Fix this test, simulate late authorized payments instead!

        //
        // Additionally, we will create a payment in past which will be attempted to be captured now(present) and
        // the flow should initiate refund. At this point we just assert that a job was pushed to make the refund onto
        // queue. We choose past 25 hrs, because following endpoint fetches so payments for auto capture.
        //
        Carbon::setTestNow(Carbon::now()->subHours(25));

        $paymentAuth = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->doAutoCapture();
    }

    public function testDeactivatePaymentLink()
    {
        $this->createPaymentLink();

        $this->startTest();
    }

    public function testDeactivateAlreadyDeactivatedPaymentLink()
    {
        $attributes = [
            PaymentLinkModel\Entity::STATUS        => PaymentLinkModel\Status::INACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON => PaymentLinkModel\StatusReason::DEACTIVATED,
        ];

        $this->createPaymentLink(self::TEST_PL_ID, $attributes);

        $this->startTest();
    }

    public function testActivatePaymentLink()
    {
        $attributes = [
            PaymentLinkModel\Entity::STATUS        => PaymentLinkModel\Status::INACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON => PaymentLinkModel\StatusReason::DEACTIVATED,
        ];

        $this->createPaymentLink(self::TEST_PL_ID, $attributes);

        $this->startTest();
    }

    public function testActivateLinkAlreadyActivated()
    {
        $attributes = [
            PaymentLinkModel\Entity::STATUS        => PaymentLinkModel\Status::ACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON => null,
        ];

        $this->createPaymentLink(self::TEST_PL_ID, $attributes);

        $this->startTest();
    }

    public function testActivateWithTimesPayableLessThanTimesPaid()
    {
        $attributes = [
            PaymentLinkModel\Entity::STATUS            => PaymentLinkModel\Status::INACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON     => PaymentLinkModel\StatusReason::COMPLETED,
            PaymentLinkModel\Entity::TIMES_PAID        => 2,
            PaymentLinkModel\Entity::TIMES_PAYABLE     => 2,
            PaymentLinkModel\Entity::AMOUNT            => 100,
            PaymentLinkModel\Entity::TOTAL_AMOUNT_PAID => 200,
        ];

        $this->createPaymentLink(self::TEST_PL_ID, $attributes);

        $this->startTest();
    }

    public function testMinExpiryTimeForActivation()
    {
        $attributes = [
            PaymentLinkModel\Entity::STATUS        => PaymentLinkModel\Status::INACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON => PaymentLinkModel\StatusReason::EXPIRED,
        ];

        $this->createPaymentLink(self::TEST_PL_ID, $attributes);

        $expireBy = Carbon::now(Timezone::IST)->addSeconds(120)->getTimestamp();

        $this->testData[__FUNCTION__]['request']['content']['expire_by'] = $expireBy;

        $this->startTest();
    }

    public function testEditPaymentLinkToCompleteAndExcessPaymentRefunded()
    {
        $attributes = [
            PaymentLinkModel\Entity::TIMES_PAYABLE => 2,
        ];

        $paymentLink = $this->createPaymentLink(self::TEST_PL_ID, $attributes);

        $this->makePaymentForPaymentLinkAndAssert($paymentLink);

        Carbon::setTestNow(Carbon::now()->subHours(25));

        $paymentAttributes = [
            Payment\Entity::PAYMENT_LINK_ID => $paymentLink->getId(),
        ];

        // TODO: Fix this test, simulate late authorized payments instead!

        $paymentAuth = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->ba->proxyAuth();

        $this->startTest();

        $this->doAutoCapture();
    }

    public function testGetPaymentLinkView()
    {
        $this->createPaymentLink();

        $this->callViewUrlAndMakeAssertions();
    }

    public function testGetInactivePaymentLinkView()
    {
        $attributes = [
            PaymentLinkModel\Entity::STATUS        => PaymentLinkModel\Status::INACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON => PaymentLinkModel\StatusReason::DEACTIVATED,
        ];

        $this->createPaymentLink(self::TEST_PL_ID, $attributes);

        // TODO: Have this & assert error message once view has been implemented
        // $this->callViewUrlAndMakeAssertions();
    }

    // -------------------- Protected methods --------------------

    protected function createPaymentLink(string $id = self::TEST_PL_ID, array $attributes = []): PaymentLinkModel\Entity
    {
        $attributes[PaymentLinkModel\Entity::ID]      = $id;
        $attributes[PaymentLinkModel\Entity::USER_ID] = User::MERCHANT_USER_ID;

        return $this->fixtures->create('payment_link', $attributes);
    }

    /**
     * Helper method to make payment for given payment link (with auto-capture) and do the necessary assertions
     *
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

    protected function callViewUrlAndMakeAssertions(string $id = self::TEST_PL_ID, int $code = 200, string $error = null)
    {
        $response = $this->call('GET', "/v1/payment_links/pl_{$id}/view");

        $response->assertStatus($code);

        // If there is an error message expected assert that
        if (empty($error) === false)
        {
            $this->assertContains($error, $response->getContent());
        }
    }
}
