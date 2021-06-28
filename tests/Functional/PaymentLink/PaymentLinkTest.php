<?php

namespace RZP\Tests\Functional\PaymentLink;

use Carbon\Carbon;

use RZP\Models\Item;
use RZP\Models\Order;
use RZP\Models\PaymentLink\Entity;
use RZP\Services\Elfin;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Models\Settings;
use RZP\Error\ErrorCode;
use RZP\Models\LineItem;
use RZP\Models\PaymentLink;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicEntity;
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
    const TEST_PL_ID_2  = '100000000001pl';
    const TEST_PPI_ID   = '10000000000ppi';
    const TEST_PPI_ID_2 = '10000000001ppi';
    const TEST_ORDER_ID = '10000000000ord';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/PaymentLinkTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testPaymentLinkMakePaymentWhenPageIsInactive()
    {
        $this->createPaymentLinkAndOrderForThat(['id' => self::TEST_PL_ID], ['id' => self::TEST_ORDER_ID]);

        $this->fixtures->edit('payment_link',self::TEST_PL_ID,['status' => 'inactive']);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testPaymentLinkMakePaymentWithDifferentOrder()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->fixtures->create('order', [
            'id'     => self::TEST_ORDER_ID,
            'amount' => 10000,
        ]);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testPaymentLinkMakePaymentWithoutOrder()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWhenPageIsInactive()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MANDATORY => true,
                    PaymentLink\PaymentPageItem\Entity::STOCK => 5,
                    PaymentLink\PaymentPageItem\Entity::QUANTITY_SOLD => 5,
                ],
            ],
            PaymentLink\Entity::STATUS => PaymentLink\Status::INACTIVE,
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWhenQuantitySoldOut()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MANDATORY => true,
                    PaymentLink\PaymentPageItem\Entity::STOCK => 5,
                    PaymentLink\PaymentPageItem\Entity::QUANTITY_SOLD => 5,
                ],
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 10000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MANDATORY => false,
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithDuplicateItem()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 1000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MIN_PURCHASE => 3
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithRequiredItem()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MANDATORY => true,
                ],
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 10000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MANDATORY => false,
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithoutRequiredItem()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MANDATORY => true,
                ],
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 10000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MANDATORY => false,
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithPurchaseLesserThanMinPurchase()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 1000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MIN_PURCHASE => 3
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithPurchaseGreaterThanMaxPurchase()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 1000,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MAX_PURCHASE => 3
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithFixedAmount()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 1000,
                    ],
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithAmountGreaterThanMaxAmount()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => null,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MAX_AMOUNT => 500
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testCreateOrderForPaymentLinkWithAmountLessThanMinAmount()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => null,
                    ],
                    PaymentLink\PaymentPageItem\Entity::MIN_AMOUNT => 5000
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testUpdatePaymentLinkRemoveAllItem()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
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
        ]);

        $this->startTest();
    }

    public function testUpdatePaymentLinkAddingItem()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
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
        ]);

        $this->startTest();
    }

    public function testUpdatePaymentLinkDeletingItem()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, [
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
        ]);

        $response = $this->startTest();

        $this->assertEquals(1, sizeof($response[PaymentLink\Entity::PAYMENT_PAGE_ITEMS] ?? []));
    }

    public function testCreatePaymentLinkWithoutItem()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithMoreThanLimitedItem()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithDifferentCurrency()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $this->startTest();
    }

    public function testCreatePaymentLinkByPassingAmountWhenAmountPassed()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithMinPurchaseGreaterThanMaxPurchase()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithMinAmountGreaterThanMaxAmount()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithSinglePaymentPageItem()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithMultiplePaymentPageItem()
    {
        $this->startTest();

        $entity = $this->getDbLastEntity("payment_link");

        $entityArray = $entity->toArray();

        self::assertEquals($entityArray['view_type'], 'page');
    }

    public function testCreatePaymentButtonWithMultipleItems()
    {
        $this->startTest();

        $entity = $this->getDbLastEntity("payment_link");

        $entityArray = $entity->toArray();

        self::assertEquals($entityArray['view_type'], 'button');
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
        $this->createPaymentLinkWithMultipleItem();

        $this->startTest();
    }

    public function testFetchPaymentLinks()
    {
        $this->testCreatePaymentLinkWithMultiplePaymentPageItem();
        $this->startTest();
    }

    public function testFetchPaymentButtons()
    {
        $this->testCreatePaymentButtonWithMultipleItems();
        $this->startTest();
    }

    public function testFetchButtonNotInPagesList()
    {
        $this->testCreatePaymentLinkWithMultiplePaymentPageItem();
        $this->startTest();
    }

    public function testFetchPageNotInButtonList()
    {
        $this->testCreatePaymentButtonWithMultipleItems();
        $this->startTest();
    }

    public function testFetchButtonPreferences()
    {
        $this->testCreatePaymentButtonWithMultipleItems();

        $entity = $this->getDbLastEntity("payment_link");

        $id = $entity->getId();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_buttons/pl_{$id}/button_preferences");

        $response->assertStatus(200);

        $content = json_decode($response->getContent(), true);

        $this->assertArrayKeysExist($content, ['preferences', 'is_test_mode']);

        $this->assertEquals($content['preferences']['payment_button_text'], 'Please pay');

        $this->assertEquals($content['preferences']['payment_button_theme'], 'rzp-dark-standard');

        $this->assertArrayHasKey("merchant_brand_color", $content['preferences']);
    }

    public function testFetchGetButtonHostedView()
    {
        $this->testCreatePaymentButtonWithMultipleItems();

        $entity = $this->getDbLastEntity("payment_link");

        $id = $entity->getId();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_buttons/pl_{$id}/view");

        $response->assertStatus(200);

        $this->assertStringContainsString($id, $response->getContent());
    }

    public function testOptionalFeatureInHostedPage()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_pages/pl_".self::TEST_PL_ID."/view");

        $response->assertStatus(200);

        $this->assertStringContainsString('contact_optional', $response->getContent());
    }

    public function testFetchPostButtonHostedView()
    {
        $this->testCreatePaymentButtonWithMultipleItems();

        $entity = $this->getDbLastEntity("payment_link");

        $id = $entity->getId();

        $this->ba->publicAuth();

        $response = $this->call('POST', "/v1/payment_buttons/pl_{$id}/view");

        $response->assertStatus(200);

        $this->assertStringContainsString($id, $response->getContent());

        $this->assertStringContainsString("udf_schema", $response->getContent());

        $this->assertStringContainsString("payment_button_text", $response->getContent());
    }

    public function testFetchPublicButtonDetails()
    {
        $this->testCreatePaymentButtonWithMultipleItems();

        $entity = $this->getDbLastEntity("payment_link");

        $id = $entity->getId();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_buttons/pl_{$id}/button_details");

        $response->assertStatus(200);

        $content = json_decode($response->getContent(), true);

        $this->assertArrayKeysExist($content, ['data', 'udf_schema']);

        $this->assertArrayKeysExist($content['data'], ['base_url', 'payment_link', 'merchant', 'key_id', 'is_test_mode', 'environment', 'org', 'view_preferences']);

        $this->assertEquals($content['data']['payment_link']['id'], $entity->getPublicId());
    }

    public function testUpdatePaymentLinkWithBadExpireBy()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->startTest();
    }

    public function testPaymentLinkSendNotification()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->startTest();
    }

    public function testInactivePaymentLinkSendNotification()
    {
        $attributes = [
            PaymentLinkModel\Entity::STATUS        => PaymentLinkModel\Status::INACTIVE,
            PaymentLinkModel\Entity::STATUS_REASON => PaymentLinkModel\StatusReason::EXPIRED,
            PaymentLinkModel\Entity::EXPIRE_BY     => 1400000000,
        ];

        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, $attributes);

        $this->startTest();
    }

    public function testExpirePaymentLinks()
    {
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, ['expire_by' => '1400000000']);
        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID_2, ['expire_by' => '1400000000']);

        $this->fixtures->create('payment_link');

        $this->ba->cronAuth();

        $this->startTest();

        $expiredPlCount = $this->getDbEntities('payment_link')
                               ->where(PaymentLinkModel\Entity::STATUS, PaymentLinkModel\Status::INACTIVE)
                               ->where(PaymentLinkModel\Entity::STATUS_REASON, PaymentLinkModel\StatusReason::EXPIRED)
                               ->count();

        $this->assertEquals(2, $expiredPlCount);
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
        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        // Enable customer fee_bearer model
        $this->fixtures->merchant->enableConvenienceFeeModel();

        $data = $this->createPaymentLinkAndOrderForThat();

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::AMOUNT] = $order->getAmount();

        $fees = $this->createAndGetFeesForPayment($payment);
        $fee  = $fees['input']['fee'];

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order->getAmount() + $fee;
        $payment[Payment\Entity::FEE]             = $fee;
        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();

        $this->doAuthAndGetPayment($payment, [
            Payment\Entity::STATUS => Payment\Status::CAPTURED,
            Payment\Entity::ORDER_ID => $order->getPublicId(),
        ]);
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($order->getAmount() + $fee, $payment->getAmount());
        $this->assertEquals($paymentLink->getId(), $payment->getPaymentLinkId());

        // total_amount_paid must be equal to amount and not amount+fee
        $this->getLastPaymentLinkEntityAndAssert($this->testData[__FUNCTION__]['payment_link']);
    }

    public function testPaymentLinkMakePaymentWithUdfInvalid()
    {
        $attributes = [
            PaymentLinkModel\Entity::UDF_JSONSCHEMA_ID => '10000pludftest',
        ];

        $data = $this->createPaymentLinkAndOrderForThat($attributes);

        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage('The customer_id field is invalid. The property customer_id is required');

        $this->makePaymentForPaymentLinkAndAssert($data['payment_link'], $data['payment_link_order']['order']);
    }

    public function testPaymentLinkMakePaymentWithUdf()
    {
        $attributes = [
            PaymentLinkModel\Entity::UDF_JSONSCHEMA_ID => '10000pludftest',
        ];

        $data = $this->createPaymentLinkAndOrderForThat($attributes);

        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $data['payment_link']->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $data['payment_link_order']['order']->getAmount();
        $payment[Payment\Entity::ORDER_ID]        = $data['payment_link_order']['order']->getPublicId();
        $payment[Payment\Entity::NOTES]           = [
            'customer_id'   => '1000001',
            'customer_name' => 'Random Name',
        ];

        $this->doAuthAndGetPayment($payment, [
            Payment\Entity::STATUS => Payment\Status::CAPTURED,
            Payment\Entity::ORDER_ID => $data['payment_link_order']['order']->getPublicId(),
        ]);
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($data['payment_link_order']['order']->getAmount(), $payment->getAmount());
        $this->assertEquals($data['payment_link']->getId(), $payment->getPaymentLinkId());
    }

    public function testDeactivatePaymentLink()
    {
        $this->createPaymentLinkWithMultipleItem();

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

    public function testCreateOrderForPaymentLink()
    {
        $this->createPaymentLink(self::TEST_PL_ID);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->startTest();

    }

    public function testCreateOrderForPaymentLinkAndVerifyProductTypePage()
    {
        $this->createPaymentLink(self::TEST_PL_ID);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->startTest();

        $order = $this->getDbLastEntity("order");

        $this->assertEquals($order->getProductType(), 'payment_page');

        $this->assertEquals($order->getProductId(), self::TEST_PL_ID);
    }

    public function testCreateOrderForPaymentLinkAndVerifyProductTypeButton()
    {
        $this->createPaymentLink(self::TEST_PL_ID,  ['view_type' => 'button']);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->startTest();

        $order = $this->getDbLastEntity("order");

        $this->assertEquals($order->getProductType(), 'payment_button');

        $this->assertEquals($order->getProductId(), self::TEST_PL_ID);
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

        $this->createPaymentLinkWithMultipleItem(self::TEST_PL_ID, $attributes);

        $expireBy = Carbon::now(Timezone::IST)->addSeconds(120)->getTimestamp();

        $this->testData[__FUNCTION__]['request']['content']['expire_by'] = $expireBy;

        $this->startTest();
    }

    public function testGetPaymentLinkView()
    {
        $this->createPaymentLinkWithMultipleItem();

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

    public function testPaymentLinkPaymentRefundAfterNoStock()
    {
        $data = $this->createPaymentLinkAndOrderForThat(
            ['id' => self::TEST_PL_ID,
                PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                    [
                        PaymentLink\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                        PaymentLink\PaymentPageItem\Entity::ITEM => [
                            Item\Entity::AMOUNT => 5000,
                        ],
                        PaymentLink\PaymentPageItem\Entity::MANDATORY => true,
                        PaymentLink\PaymentPageItem\Entity::STOCK => 5,
                        PaymentLink\PaymentPageItem\Entity::QUANTITY_SOLD => 0,
                    ],
                ]
            ],
            [
                'id' => self::TEST_ORDER_ID,
                Order\Entity::PAYMENT_CAPTURE => false,
            ]
        );

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $payment = $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order, Payment\Status::AUTHORIZED);

        $this->fixtures->edit(
            'payment_page_item',
            self::TEST_PPI_ID,
            [
                'quantity_sold' => 5,
            ]
        );

        $this->fixtures->edit(
            'payment_link',
            self::TEST_PL_ID,
            [
                'status' => PaymentLink\Status::INACTIVE,
                'status_reason' => PaymentLink\StatusReason::COMPLETED,
            ]
        );

        $this->fixtures->edit(
            'payment',
            $payment['id'],
            [
                'auto_captured' => true,
            ]
        );

        $this->capturePayment($payment['id'], $payment['amount'], 'INR', 0, Payment\Status::REFUNDED);
    }

    public function testSetMerchantDetails()
    {
        $this->startTest();
    }

    public function testFetchMerchantDetails()
    {
        $settings = [
            PaymentLink\Entity::TEXT_80G_12A    => 'text',
            PaymentLink\Entity::IMAGE_URL_80G   => 'https://url',
        ];
        $merchant = $this->getDbEntityById('merchant', 10000000000000);
        Settings\Accessor::for($merchant, Settings\Module::PAYMENT_LINK)
            ->upsert($settings)
            ->save();
        $this->startTest();
    }

    public function testSetReceiptDetails()
    {
        $settings = [
            PaymentLink\Entity::UDF_SCHEMA => '[
            {"name":"email","required":true,"title":"Email","type":"string","pattern":"email","settings":{"position":1}},
            {"name":"phone","title":"Phone","required":true,"type":"number","pattern":"phone","minLength":"8","options":{},"settings":{"position":2}}]'
        ];

        $paymentLink = $this->createPaymentLink(self::TEST_PL_ID);

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();

        $this->startTest();
    }

    public function testSetReceiptDetailsEmpty()
    {
        $settings = [
            PaymentLink\Entity::UDF_SCHEMA => '[
            {"name":"email","required":true,"title":"Email","type":"string","pattern":"email","settings":{"position":1}},
            {"name":"phone","title":"Phone","required":true,"type":"number","pattern":"phone","minLength":"8","options":{},"settings":{"position":2}}]',
            PaymentLink\Entity::RECEIPT_ENABLE          => true,
            PaymentLink\Entity::SELECTED_INPUT_FIELD    => 'email',
            PaymentLink\Entity::CUSTOM_SERIAL_NUMBER    => true,
        ];

        $paymentLink = $this->createPaymentLink(self::TEST_PL_ID);

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();

        $this->startTest();
    }

    public function testCreateOrderLineItemsEmptyArray()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->startTest();

    }

    public function testCreateOrderLineItemsNotArray()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->startTest();
    }

    public function testMakePaymentReceiptEnabledCustomSerialNotEnabled()
    {
        $settings = [
            PaymentLink\Entity::UDF_SCHEMA => '[
            {"name":"email","required":true,"title":"Email","type":"string","pattern":"email","settings":{"position":1}},
            {"name":"phone","title":"Phone","required":true,"type":"number","pattern":"phone","minLength":"8","options":{},"settings":{"position":2}}]',
            PaymentLink\Entity::RECEIPT_ENABLE          => true,
            PaymentLink\Entity::SELECTED_INPUT_FIELD    => 'email',
            PaymentLink\Entity::PAYMENT_SUCCESS_MESSAGE => 'success',
        ];

        $data = $this->createPaymentLinkAndOrderForThat();

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();

        $paymentNotes = [
            'email' => 'abc@abc.com',
            'phone' =>  '1234567890'
        ];

        $payment = $this->makePaymentForPaymentLinkWithOrderAndAssert(
            $paymentLink,
            $order,
            Payment\Status::CAPTURED ,
            $paymentNotes);

        $invoice = $order->invoice;

        $this->assertNotNull($invoice);

        $this->assertEquals($invoice->getStatus(), Invoice\Status::PAID);

        $this->assertEquals($invoice->getAttribute(Invoice\Entity::COMMENT), 'success');

        $this->assertEquals($invoice->getReceipt(), $payment['id']);

        $this->assertLineItems($invoice->lineItems->toArray(), $order->lineItems->toArray());

    }

    public function testMakePaymentReceiptEnabledCustomSerialEnabled()
    {
        $settings = [
            PaymentLink\Entity::UDF_SCHEMA => '[
            {"name":"email","required":true,"title":"Email","type":"string","pattern":"email","settings":{"position":1}},
            {"name":"phone","title":"Phone","required":true,"type":"number","pattern":"phone","minLength":"8","options":{},"settings":{"position":2}}]',
            PaymentLink\Entity::RECEIPT_ENABLE          => true,
            PaymentLink\Entity::SELECTED_INPUT_FIELD    => 'email',
            PaymentLink\Entity::PAYMENT_SUCCESS_MESSAGE => 'success',
            PaymentLink\Entity::CUSTOM_SERIAL_NUMBER    => true,
        ];

        $data = $this->createPaymentLinkAndOrderForThat();

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();

        $paymentNotes = [
            'email' => 'abc@abc.com',
            'phone' =>  '1234567890'
        ];

        $payment = $this->makePaymentForPaymentLinkWithOrderAndAssert(
            $paymentLink,
            $order,
            Payment\Status::CAPTURED ,
            $paymentNotes);

        $invoice = $order->invoice;

        $this->assertNotNull($invoice);

        $this->assertEquals($invoice->getStatus(), Invoice\Status::PAID);

        $this->assertEquals($invoice->getAttribute(Invoice\Entity::COMMENT), 'success');

        $this->assertNull($invoice->getEmailStatus());

        $this->assertNull($invoice->getReceipt());

        $this->assertLineItems($invoice->lineItems->toArray(), $order->lineItems->toArray());

    }

    public function testMakePaymentReceiptDisabled()
    {
        $settings = [
            PaymentLink\Entity::UDF_SCHEMA => '[
            {"name":"email","required":true,"title":"Email","type":"string","pattern":"email","settings":{"position":1}},
            {"name":"phone","title":"Phone","required":true,"type":"number","pattern":"phone","minLength":"8","options":{},"settings":{"position":2}}]',
            PaymentLink\Entity::RECEIPT_ENABLE          => false,
            PaymentLink\Entity::SELECTED_INPUT_FIELD    => 'email',
            PaymentLink\Entity::PAYMENT_SUCCESS_MESSAGE => 'success'
        ];

        $data = $this->createPaymentLinkAndOrderForThat();

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();

        $paymentNotes = [
            'email' => 'abc@abc.com',
            'phone' =>  '1234567890'
        ];

        $this->makePaymentForPaymentLinkWithOrderAndAssert(
            $paymentLink,
            $order,
            Payment\Status::CAPTURED ,
            $paymentNotes);

        $invoice = $order->invoice;

        $this->assertNull($invoice);
    }

    public function testCreateOrderForPaymentLinkAndFetchProductType()
    {
        $this->testCreateOrderForPaymentLink();

        $order = $this->getDbLastEntity('order');

        $this->ba->proxyAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v1/orders/order_'.$order->getId().'/product_details';

        $response = $this->startTest();
    }

    public function testFetchButtonPreferencesForSuspendedMerchant()
    {
        $this->fixtures->edit('merchant', '10000000000000', [ 'suspended_at' => '123456789' ]);

        $this->ba->directAuth();

        $this->createPaymentLink(self::TEST_PL_ID,  ['view_type' => 'button']);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->startTest();
    }

    public function testFetchButtonDetailsForSuspendedMerchant()
    {
        $this->fixtures->edit('merchant', '10000000000000', [ 'suspended_at' => '123456789' ]);

        $this->ba->directAuth();

        $this->createPaymentLink(self::TEST_PL_ID,  ['view_type' => 'button']);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->startTest();
    }

    public function testPaymentPageHostedViewForSuspendedMerchant()
    {
        $this->fixtures->edit('merchant', '10000000000000', [ 'suspended_at' => '123456789' ]);

        $this->createPaymentLink(self::TEST_PL_ID,  ['view_type' => 'page']);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->ba->publicAuth();

        $request = [
            'method'  => 'GET',
            'url'     => '/v1/payment_pages/pl_100000000000pl/view',
        ];

        $this->makeRequestAndCatchException(function() use ($request)
        {
            $this->makeRequestAndGetContent($request);
        }, BadRequestValidationFailureException::class);


    }

    public function testFetchPaymentPageInvoiceReceiptDetails()
    {
        $this->testMakePaymentReceiptEnabledCustomSerialNotEnabled();

        $payment = $this->getDbLastEntity('payment');

        $invoice = $this->getDbLastEntity('invoice');

        $this->ba->proxyAuth();

        $request = [
            'method'  => 'GET',
            'url'     => '/v1/payment_pages/'.$payment->getPublicId().'/receipt',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['invoice_id'], $invoice->getPublicId());

        $this->assertEquals($content['receipt'], $payment->getPublicId());

        $this->assertArrayHasKey('receipt_download_url', $content);
    }

    public function testSaveCustomSerialNumberReceiptAndFetch()
    {
        $this->testMakePaymentReceiptEnabledCustomSerialEnabled();

        $payment = $this->getDbLastEntity('payment');

        $invoice = $this->getDbLastEntity('invoice');

        $this->assertNull($invoice->getReceipt());

        $this->ba->proxyAuth();

        $request = [
            'method' => 'GET',
            'url' => '/v1/payment_pages/' . $payment->getPublicId() . '/receipt',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNull($content['receipt']);

        $request = [
            'method' => 'POST',
            'url' => '/v1/payment_pages/' . $payment->getPublicId() . '/save_receipt',
            'content' => [
                'receipt' => 'thisisatestreceiptvalue'
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $request = [
            'method' => 'GET',
            'url' => '/v1/payment_pages/' . $payment->getPublicId() . '/receipt',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['invoice_id'], $invoice->getPublicId());

        $this->assertEquals($content['receipt'], 'thisisatestreceiptvalue');

        $this->assertArrayHasKey('receipt_download_url', $content);
    }

    public function testPaymentPageDetails()
    {
        $this->testPaymentLinkMakePaymentWithOrder();

        $this->ba->privateAuth();

        $this->startTest();
    }

    /**
     * @group support_contact_validation
     */
    public function testCreatePaymentLinkWithAlphabetSupportNumber()
    {
        $this->startTest();
    }

    /**
     * @group support_contact_validation
     */
    public function testCreatePaymentLinkWithSupportNumberLessDigits()
    {
        $this->startTest();
    }

    /**
     * @group support_contact_validation
     */
    public function testCreatePaymentLinkWithSupportNumberLargeDigits()
    {
        $this->startTest();
    }

    // -------------------- Protected methods --------------------

    protected function createPaymentLink(string $id = self::TEST_PL_ID, array $attributes = []): PaymentLinkModel\Entity
    {
        $attributes[PaymentLinkModel\Entity::ID]      = $id;
        $attributes[PaymentLinkModel\Entity::USER_ID] = User::MERCHANT_USER_ID;

        return $this->fixtures->create('payment_link', $attributes);
    }

    protected function createPaymentLinkWithMultipleItem(string $id = self::TEST_PL_ID, array $attributes = []): PaymentLinkModel\Entity
    {
        $defaultPaymentLinkAttribute = [
            PaymentLink\Entity::ID     => self::TEST_PL_ID,
            PaymentLink\Entity::AMOUNT => null,
            PaymentLink\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => PublicEntity::generateUniqueId(),
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ]
                ],
                [
                    PaymentLink\PaymentPageItem\Entity::ID   => PublicEntity::generateUniqueId(),
                    PaymentLink\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 10000,
                    ]
                ]
            ]
        ];

        $paymentLinkAttribute = array_merge($defaultPaymentLinkAttribute, $attributes);

        $paymentPageItemsAttribute = array_pull($paymentLinkAttribute, PaymentLink\Entity::PAYMENT_PAGE_ITEMS, []);

        $paymentLink = $this->createPaymentLink($id, $paymentLinkAttribute);

        $this->createPaymentPageItems(
            $paymentPageItemsAttribute[PaymentLink\Entity::ID] ?? self::TEST_PL_ID,
            $paymentPageItemsAttribute);

        return $paymentLink;
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

        $orderAttribute = array_merge(
            [
                'amount' => $totalAmount,
                Order\Entity::PAYMENT_CAPTURE => true,
            ],
            $orderAttribute
        );

        $order = $this->fixtures->create('order', $orderAttribute);

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
    protected function makePaymentForPaymentLinkAndAssert(PaymentLinkModel\Entity $paymentLink, Order\Entity $order)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();
        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order->getAmount();

        $payment = $this->doAuthAndGetPayment($payment, [
            Payment\Entity::STATUS => Payment\Status::CAPTURED,
            Payment\Entity::ORDER_ID => $order->getPublicId(),
            Payment\Entity::AMOUNT => $order->getAmount(),
        ]);
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($paymentLink->getAmount(), $payment->getAmount());
        $this->assertEquals($paymentLink->getId(), $payment->getPaymentLinkId());

        return $payment;
    }

    protected function makePaymentForPaymentLinkWithOrderAndAssert(
        PaymentLinkModel\Entity $paymentLink,
        Order\Entity $order,
        $status = Payment\Status::CAPTURED,
        array $paymentNotes = []
    )
    {
        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order->getAmount();
        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();
        $payment[Payment\Entity::NOTES]           = $paymentNotes;

        $payment = $this->doAuthAndGetPayment($payment, [
            Payment\Entity::STATUS   => $status,
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
        $response = $this->call('GET', "/v1/payment_pages/pl_{$id}/view");

        $response->assertStatus($code);

        // If there is an message expected, assert that it exists in the response content
        if (empty($message) === false)
        {
            $this->assertContains($message, $response->getContent());
        }
    }

    protected function assertLineItems(array $lineItemsInvoice, array $lineItemsPP)
    {
        $this->assertEquals(count($lineItemsInvoice), count($lineItemsPP));

        for($i = 0; $i < count($lineItemsPP); $i++)
        {
            $itemInvoice = $lineItemsInvoice[$i];
            $itemPP = $lineItemsPP[$i];
            $this->assertEquals($itemInvoice[LineItem\Entity::NAME], $itemPP[LineItem\Entity::NAME]);
            $this->assertEquals($itemInvoice[LineItem\Entity::DESCRIPTION], $itemPP[LineItem\Entity::DESCRIPTION]);
            $this->assertEquals($itemInvoice[LineItem\Entity::AMOUNT], $itemPP[LineItem\Entity::AMOUNT]);
            $this->assertEquals($itemInvoice[LineItem\Entity::CURRENCY], $itemPP[LineItem\Entity::CURRENCY]);
            $this->assertEquals($itemInvoice[LineItem\Entity::QUANTITY], $itemPP[LineItem\Entity::QUANTITY]);
        }
    }
}
