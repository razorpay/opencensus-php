<?php

namespace RZP\Tests\Functional\PaymentLink;

use Carbon\Carbon;

use RZP\Models\Item;
use RZP\Models\Order;
use RZP\Services\Elfin;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\LineItem;
use RZP\Models\PaymentLink;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Models\PaymentLink as PaymentLinkModel;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;

class PaymentLinkTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    const TEST_PL_ID    = '100000000000pl';
    const TEST_PPI_ID   = '10000000000ppi';
    const TEST_PPI_ID_2 = '10000000001ppi';

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

    public function testCreatePaymentLinkWithPaymentPageItem()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithMultiplePaymentPageItem()
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

    public function testCreatePaymentLinkWithMinAmountIntCurrency()
    {
        $this->startTest();
    }

    /**
     * Asserts fail attempt to create payment link with amount greater than max payment amount allowed for merchant
     */
    public function testCreatePaymentLinkWithTooLargeAmount()
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

        $expiredPlCount = $this->getDbEntities('payment_link')
                               ->where(PaymentLinkModel\Entity::STATUS, PaymentLinkModel\Status::INACTIVE)
                               ->where(PaymentLinkModel\Entity::STATUS_REASON, PaymentLinkModel\StatusReason::EXPIRED)
                               ->count();

        $this->assertEquals(2, $expiredPlCount);
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

    public function testPaymentLinkMakePaymentWithOrder()
    {
        $data = $this->createPaymentLinkAndOrderForThat();

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $payment = $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order);

        $paymentPageItem1 = $this->getDbEntityById('payment_page_item', self::TEST_PPI_ID);

        $this->assertEquals(1, $paymentPageItem1->getQuantitySold());

        $this->assertEquals(5000, $paymentPageItem1->getTotalAmountPaid());

        $paymentPageItem2 = $this->getDbEntityById('payment_page_item', self::TEST_PPI_ID_2);

        $this->assertEquals(1, $paymentPageItem2->getQuantitySold());

        $this->assertEquals(10000, $paymentPageItem2->getTotalAmountPaid());
    }

    public function testPaymentLinkMakePaymentCustomerFeeBearer()
    {
        $attributes = [
            PaymentLinkModel\Entity::AMOUNT        => 12000,
            PaymentLinkModel\Entity::TIMES_PAYABLE => 10,
        ];

        $paymentLink = $this->fixtures->create('payment_link', $attributes);

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        // Enable customer fee_bearer model
        $this->fixtures->merchant->enableConvenienceFeeModel();

        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::AMOUNT] = $paymentLink->getAmount();

        $fees = $this->createAndGetFeesForPayment($payment);
        $fee  = $fees['input']['fee'];

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $paymentLink->getAmount() + $fee;
        $payment[Payment\Entity::FEE]             = $fee;

        $this->doAuthAndGetPayment($payment, [Payment\Entity::STATUS => Payment\Status::CAPTURED]);
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($paymentLink->getAmount() + $fee, $payment->getAmount());
        $this->assertEquals($paymentLink->getId(), $payment->getPaymentLinkId());

        // total_amount_paid must be equal to amount and not amount+fee
        $this->getLastPaymentLinkEntityAndAssert($this->testData[__FUNCTION__]['payment_link']);
    }

    public function testPaymentLinkMakePaymentWithUserDefinedAmount()
    {
        $attributes = [
            PaymentLinkModel\Entity::AMOUNT        => null,
            PaymentLinkModel\Entity::CURRENCY      => 'INR',
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
            PaymentLinkModel\Entity::AMOUNT            => 10100,
            PaymentLinkModel\Entity::TIMES_PAYABLE     => 10,
            PaymentLinkModel\Entity::UDF_JSONSCHEMA_ID => '10000pludftest',
        ];

        $paymentLink = $this->fixtures->create('payment_link', $attributes);

        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage('The customer_id field is invalid. The property customer_id is required');

        $this->makePaymentForPaymentLinkAndAssert($paymentLink);
    }

    public function testPaymentLinkMakePaymentWithUdf()
    {
        $attributes = [
            PaymentLinkModel\Entity::AMOUNT            => 10100,
            PaymentLinkModel\Entity::TIMES_PAYABLE     => 10,
            PaymentLinkModel\Entity::UDF_JSONSCHEMA_ID => '10000pludftest',
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

        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        $this->expectExceptionMessage('Payment amount provided does not match amount expected for the payment link.');

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

    public function testCreateOrderForPaymentLink()
    {
        $this->createPaymentLink(self::TEST_PL_ID);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithMultipleItem()
    {
        $this->createPaymentLink(self::TEST_PL_ID);

        $this->createPaymentPageItems(self::TEST_PL_ID, [['id' => self::TEST_PPI_ID], ['id' => self::TEST_PPI_ID_2]]);

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

    public function testGetSlugExistsApi()
    {
        $gimli = $this->getMockBuilder(Elfin\Impl\Gimli::class)
                      ->setConstructorArgs([$this->app['config']->get('applications.elfin.gimli')])
                      ->setMethods(['expand'])
                      ->getMock();

        $gimli->expects($this->once())
              ->method('expand')
              ->willReturn(null);

        $elfin = $this->getMockBuilder(Elfin\Mock\Service::class)
                      ->setConstructorArgs([$this->app['config'], $this->app['trace']])
                      ->setMethods(['driver'])
                      ->getMock();

        $elfin->expects($this->once())
              ->method('driver')
              ->with('gimli')
              ->willReturn($gimli);

        $this->app->instance('elfin', $elfin);

        $this->startTest();
    }

    // -------------------- Protected methods --------------------

    protected function createPaymentLink(string $id = self::TEST_PL_ID, array $attributes = []): PaymentLinkModel\Entity
    {
        $attributes[PaymentLinkModel\Entity::ID]      = $id;
        $attributes[PaymentLinkModel\Entity::USER_ID] = User::MERCHANT_USER_ID;

        return $this->fixtures->create('payment_link', $attributes);
    }

    protected function createPaymentPageItem(string $id = self::TEST_PPI_ID, string $paymentLinkId = self::TEST_PL_ID, array $attributes = []): PaymentLinkModel\PaymentPageItem\Entity
    {
        $attributes[PaymentLink\PaymentPageItem\Entity::ID]              = $id;
        $attributes[PaymentLink\PaymentPageItem\Entity::PAYMENT_LINK_ID] = $paymentLinkId;

        $defaultItem = [
            Item\Entity::ID     => $id,
            Item\Entity::TYPE   => Item\Type::PAYMENT_PAGE,
            Item\Entity::NAME   => 'amount',
            Item\Entity::AMOUNT => null
        ];

        $defaultItem = array_merge($defaultItem, array_pull($attributes, PaymentLink\PaymentPageItem\Entity::ITEM, []));

        $item = $this->fixtures->create('item', $defaultItem);

        $attributes[PaymentLink\PaymentPageItem\Entity::ITEM_ID] = $item->getId();

        return $this->fixtures->create('payment_page_item', $attributes);
    }

    protected function createPaymentPageItems(string $paymentLinkId = self::TEST_PL_ID, array $paymentPageItems = [])
    {
        $data = [];

        foreach ($paymentPageItems as $paymentPageItem)
        {
            $data[] = $this->createPaymentPageItem(
                $paymentPageItem['id'] ?? UniqueIdEntity::generateUniqueId(),
                $paymentLinkId,
                $paymentPageItem);
        }

        return $data;
    }

    protected function createPaymentLinkAndOrderForThat(array $paymentLinkAttribute = [], array $orderAttribute = [])
    {
        $defaultPaymentLinkAttribute = [
            PaymentLink\Entity::ID     => self::TEST_PL_ID,
            PaymentLink\Entity::AMOUNT => null,
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ]
                ],
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 10000,
                    ]
                ]
            ]
        ];

        $paymentLinkAttribute = array_merge($defaultPaymentLinkAttribute, $paymentLinkAttribute);

        $paymentPageItemsAttribute = array_pull($paymentLinkAttribute, PaymentLink\Entity::PAYMENT_PAGE_ITEMS, []);

        $paymentLink = $this->createPaymentLink($paymentPageItemsAttribute[PaymentLink\Entity::ID] ?? self::TEST_PL_ID, $paymentLinkAttribute);

        $paymentPageItems = $this->createPaymentPageItems(
            $paymentPageItemsAttribute[PaymentLink\Entity::ID] ?? self::TEST_PL_ID,
            $paymentPageItemsAttribute);

        $data = [];

        $data['payment_link'] = $paymentLink;

        $data['payment_link_order'] = $this->createOrderForPaymentLink($paymentPageItems, $orderAttribute);

        return $data;
    }

    protected function createOrderForPaymentLink($paymentPageItems, array $orderAttribute = [])
    {
        $data = [];

        $totalAmount = 0;

        foreach ($paymentPageItems as $paymentPageItem)
        {
            $item = $paymentPageItem[PaymentLink\PaymentPageItem\Entity::ITEM];

            $amount = empty($item->getAmount()) === true ? 10000 : $item->getAmount();

            $totalAmount += 1 * $amount;
        }

        $order = $this->fixtures->create('order', ['amount' => $totalAmount]);

        $data['order'] = $order;

        foreach ($paymentPageItems as $paymentPageItem)
        {
            $item = $paymentPageItem[PaymentLink\PaymentPageItem\Entity::ITEM];

            $itemForLineItemAttributes = [
                Item\Entity::ID     => UniqueIdEntity::generateUniqueId(),
                Item\Entity::AMOUNT => empty($item->getAmount()) === true ? 10000 : $item->getAmount()
            ];

            $itemForLineItem = $this->fixtures->create('item', $itemForLineItemAttributes);

            $lineItem = $this->fixtures->create('line_item', [
                LineItem\Entity::ID          => $paymentPageItem->getId(),
                LineItem\Entity::ITEM_ID     => $itemForLineItem->getId(),
                LineItem\Entity::REF_TYPE    => 'payment_page_item',
                LineItem\Entity::REF_ID      => $paymentPageItem->getId(),
                LineItem\Entity::ENTITY_ID   => $order->getId(),
                LineItem\Entity::ENTITY_TYPE => 'order',
                LineItem\Entity::AMOUNT      => $itemForLineItem->getAmount(),
            ]);

            $data['line_items'][] = $lineItem->toArrayPublic();
        }

        return $data;
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

    protected function makePaymentForPaymentLinkWithOrderAndAssert(PaymentLinkModel\Entity $paymentLink, Order\Entity $order)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order->getAmount();
        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();

        $payment = $this->doAuthAndGetPayment($payment, [
            Payment\Entity::STATUS   => Payment\Status::CAPTURED,
            Payment\Entity::ORDER_ID => $order->getPublicId(),
        ]);

        $this->assertEquals($order->getAmount(), $payment['amount']);
        $this->assertEquals($order->getPublicId(), $payment['order_id']);

        return $payment;
    }

    protected function getLastPaymentLinkEntityAndAssert(array $expected)
    {
        $paymentLink = $this->getDbLastEntity('payment_link');

        $this->assertArraySelectiveEquals($expected, $paymentLink->toArray());
    }

    protected function callViewUrlAndMakeAssertions(
        string $id = self::TEST_PL_ID,
        int $code = 200,
        string $message = null)
    {
        $response = $this->call('GET', "/v1/payment_links/pl_{$id}/view");

        $response->assertStatus($code);

        // If there is an message expected, assert that it exists in the response content
        if (empty($message) === false)
        {
            $this->assertContains($message, $response->getContent());
        }
    }
}
