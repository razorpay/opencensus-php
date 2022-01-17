<?php

namespace RZP\Tests\Functional\PaymentLink;

use Carbon\Carbon;

use Illuminate\Http\UploadedFile;
use RZP\Constants\Mode;
use RZP\Models\Feature\Constants;
use RZP\Models\Item;
use RZP\Models\Order;
use RZP\Models\Currency\Currency;
use RZP\Models\PaymentLink\Entity;
use RZP\Services\Elfin\Impl\Gimli;
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Services\Mock;
use RZP\Services\Elfin;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Models\Settings;
use RZP\Error\ErrorCode;
use RZP\Models\LineItem;
use RZP\Models\PaymentLink;
use RZP\Constants\Timezone;
use RZP\Services\UfhService;
use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Traits\PaymentLinkTestTrait;
use RZP\Models\PaymentLink as PaymentLinkModel;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\RazorXClient;

class PaymentLinkTest extends TestCase
{
    use PaymentTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use PaymentLinkTestTrait;

    const TEST_PL_ID    = '100000000000pl';
    const TEST_PL_ID_2  = '100000000001pl';
    const TEST_PPI_ID   = '10000000000ppi';
    const TEST_PPI_ID_2 = '10000000001ppi';
    const TEST_ORDER_ID = '10000000000ord';
    const TEST_PLAN_ID  = '1000000000plan';

    protected function enableRazorXTreatmentForKeylessHeader()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->expects($this->any())->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === RazorxTreatment::KEYLESS_HEADER_PP)
                    {
                        return 'on';
                    }
                    return 'off';
                }));
    }

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/PaymentLinkTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->enableRazorXTreatmentForKeylessHeader();
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

        $this->assertArrayKeysExist($content['data'], ['base_url', 'payment_link', 'merchant', 'key_id', 'is_test_mode', 'environment', 'org', 'view_preferences', 'keyless_header']);

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

    public function testDeactivatedPaymentPageHostedView()
    {
        $support = $this->fixtures->create('merchant_email', ['type' => 'support']);

        $support = $support->toArrayPublic();

        $this->createPaymentLink(self::TEST_PL_ID,  ['view_type' => 'page']);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->fixtures->edit('payment_link', self::TEST_PL_ID, [ 'status' => 'inactive' , 'status_reason' => 'deactivated']);

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_pages/pl_".self::TEST_PL_ID."/view");

        $this->assertStringContainsString($support['email'], $response->getContent());

        $this->assertStringContainsString($support['phone'], $response->getContent());

        $this->assertStringContainsString('This page has been deactivated', $response->getContent());
    }

    public function testPaymentPageHostedViewForSuspendedMerchant()
    {
        $this->fixtures->edit('merchant', '10000000000000', [ 'suspended_at' => '123456789' ]);

        $support = $this->fixtures->create('merchant_email', ['type' => 'support']);

        $support = $support->toArrayPublic();

        $this->createPaymentLink(self::TEST_PL_ID,  ['view_type' => 'page']);

        $this->createPaymentPageItem(self::TEST_PPI_ID, self::TEST_PL_ID, []);

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_pages/pl_".self::TEST_PL_ID."/view");

        $this->assertStringContainsString($support['email'], $response->getContent());

        $this->assertStringContainsString($support['phone'], $response->getContent());

        $this->assertStringContainsString('This account is suspended', $response->getContent());
    }

    public function testPaymentPageHostedViewForSuspendedMerchantForCustomBrandingOrg()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => $org->getId()]);

        $this->fixtures->edit('org', $org->getId(), ['checkout_logo_url' => 'https://www.google.com']);

        $this->fixtures->edit('merchant', '10000000000000', [ 'suspended_at' => '123456789' ]);

        $support = $this->fixtures->create('merchant_email', ['type' => 'support']);

        $support = $support->toArrayPublic();

        $this->createPaymentLinkWithMultipleItem();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_pages/pl_" . self::TEST_PL_ID . "/view");

        $this->assertStringContainsString('https:\/\/cdn.razorpay.com\/logo.svg', $response->getContent());

        $this->fixtures->create('feature', [
            'entity_id' => $org->getId(),
            'entity_type' => 'org',
            'name' => 'org_custom_branding',
        ]);

        $response = $this->call('GET', "/v1/payment_pages/pl_" . self::TEST_PL_ID . "/view");

        $this->assertStringContainsString('https:\/\/www.google.com', $response->getContent());
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

    public function testBrandColorInPaymentPageHosted()
    {
        $this->createPaymentLinkWithMultipleItem();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_pages/pl_".self::TEST_PL_ID."/view");

        $response->assertStatus(200);

        $this->assertStringContainsString('rgb(35,113,236)', $response->getContent());
    }

    public function testBrandColourInPaymentPageHostedForCustomColor()
    {
        $this->fixtures->merchant->edit('10000000000000', ['brand_color' => '000000']);

        $this->createPaymentLinkWithMultipleItem();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_pages/pl_".self::TEST_PL_ID."/view");

        $response->assertStatus(200);

        $this->assertStringContainsString('rgb(0,0,0)', $response->getContent());
    }

    public function testBrandColorInPaymentPageHostedForCustomBrandingOrg()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->fixtures->merchant->edit('10000000000000', ['org_id' => $org->getId()]);

        $this->fixtures->edit('org', $org->getId(), ['merchant_styles' => ["checkout_theme_color" => "#97144D"]]);

        $this->createPaymentLinkWithMultipleItem();

        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/payment_pages/pl_" . self::TEST_PL_ID . "/view");

        $response->assertStatus(200);

        $this->assertStringContainsString('rgb(35,113,236)', $response->getContent());

        $this->fixtures->create('feature', [
            'entity_id' => $org->getId(),
            'entity_type' => 'org',
            'name' => 'org_custom_branding',
        ]);

        $response = $this->call('GET', "/v1/payment_pages/pl_" . self::TEST_PL_ID . "/view");

        $response->assertStatus(200);

        $this->assertStringContainsString('rgb(151,20,77)', $response->getContent());
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

    public function testZapierPaymentPagePaidWebhook()
    {
        $data = $this->createPaymentLinkAndOrderForThat(['view_type' => 'page']);

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $this->expectWebhookEventWithContents('zapier.payment_page.paid.v1', 'testPaymentPagePaidWebhookEventData');

        $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order);
    }

    public function testNoZapierPaymentButtonPaidWebhook()
    {
        $data = $this->createPaymentLinkAndOrderForThat(['view_type' => 'button']);

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $this->dontExpectWebhookEvent('zapier.payment_page.paid.v1');

        $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order);
    }

    public function testZapierWebhookNotPresentInEventsList()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertContains("invoice.paid", $response);

        $this->assertNotContains("zapier.payment_page.paid.v1", $response);

        $this->assertNotContains("shiprocket.payment_page.paid.v1", $response);
    }

    public function testNoShiprocketPaymentPagePaidWebhookDefault()
    {
        $data = $this->createPaymentLinkAndOrderForThat(['view_type' => 'page']);

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $this->dontExpectWebhookEvent('shiprocket.payment_page.paid.v1');

        $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order);
    }

    public function testShiprocketPaymentPagePaidWebhookEnabled()
    {
        $data = $this->createPaymentLinkAndOrderForThat(['view_type' => 'page']);

        $paymentLink = $data['payment_link'];

        $settings = [
            'partner_webhook_settings' => [
                'partner_shiprocket' => "1",
            ]
        ];

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();

        $order = $data['payment_link_order']['order'];

        $this->expectWebhookEventWithContents('shiprocket.payment_page.paid.v1', 'testShiprocketPaymentPagePaidWebhookEventData');

        $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order);
    }

    public function testFetchPaymentsForPaymentPage()
    {
        $data = $this->createPaymentLinkAndOrderForThat(['view_type' => 'page']);

        $paymentLink = $data['payment_link'];

        $order = $data['payment_link_order']['order'];

        $this->makePaymentForPaymentLinkWithOrderAndAssert($paymentLink, $order);

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testSendPaymentPageReceipt()
    {
        $this->testMakePaymentReceiptEnabledCustomSerialEnabled();

        $this->ba->proxyAuth();

        $payment = $this->getDbLastEntity('payment');

        $url = '/payment_pages/'.$payment->getPublicId().'/send_receipt';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $payment = $this->getDbLastEntity('payment');

        $order = $payment->order;

        $invoice = $order->invoice;

        $this->assertEquals('thisisareceipt', $invoice->getReceipt());
    }

    public function testUploadPaymentPageImages()
    {
        $this->ba->proxyAuth();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();
    }

    public function testPaymentPageItemUpdate()
    {
        $this->createPaymentLinkAndOrderForThat();

        $paymentPageItem = $this->getDbEntityById('payment_page_item', 'ppi_10000000000ppi');

        $item = $paymentPageItem->item;

        $this->assertNull($paymentPageItem->getStock());

        $this->assertEquals(5000, $item->getAmount());

        $this->ba->proxyAuth();

        $this->startTest();

        $paymentPageItem = $this->getDbEntityById('payment_page_item', 'ppi_10000000000ppi');

        $item = $paymentPageItem->item;

        $this->assertEquals(2, $paymentPageItem->getStock());

        $this->assertEquals(7500, $item->getAmount());
    }

    /**
     * @group pp_line_item_amount
     */
    public function testCreatePaymentPageOrderWithFloatAmountShouldThrowValidationError()
    {
        $this->createPaymentLink();
        $this->createPaymentPageItem();
        $this->startTest();
    }

    /**
     * @group pp_line_item_amount
     */
    public function testCreatePaymentPageOrderWithOutOfScopeIntegerAmountShouldThrowValidationError()
    {
        $this->createPaymentLink();
        $this->createPaymentPageItem();
        $this->startTest();
    }

    /**
     * @group pp_line_item_amount
     */
    public function testCreatePaymentPageOrderWithOutAmountShouldThrowValidationError()
    {
        $this->createPaymentLink();
        $this->createPaymentPageItem();
        $this->startTest();
    }

    public function testSettingsInPaymentPageItemsInPaymentButton()
    {
        $this->createPaymentLink(self::TEST_PL_ID, ['view_type' => 'button']);

        $item = $this->createPaymentPageItem();
        $settings = [
            PaymentLink\PaymentPageItem\Entity::POSITION => '0'
        ];

        $item->getSettingsAccessor()->upsert($settings)->save();
        $this->startTest();
    }

    public function testSettingsInPaymentPageItemsInSubscriptionButton()
    {
        $this->createPaymentLink(self::TEST_PL_ID, ['view_type' => 'subscription_button']);

        $item = $this->createPaymentPageItem();

        $settings = [
            PaymentLink\PaymentPageItem\Entity::POSITION => '0'
        ];

        $item->getSettingsAccessor()->upsert($settings)->save();

        $this->startTest();
    }

    /**
     * @group nocode_pp_order_notes
     */
    public function testOrderCreateShowStoreNotes()
    {
        $this->createPaymentLink(self::TEST_PL_ID, ['view_type' => 'page']);

        $ppis = [
            [
                PaymentLinkModel\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                    Item\Entity::AMOUNT => 5000,
                ]
            ]
        ];

        $this->createPaymentPageItems(self::TEST_PL_ID, $ppis);

        $this->startTest();
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testCreatePaymentPageWithDonationGoalTrackerShouldBeReturnedInDetailsApi()
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID, ['view_type' => 'page']);
        $settings = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::GOAL_AMOUNT             => "10000",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "0"
                ]
            ]
        ];

        $pl->getSettingsAccessor()->upsert($settings)->save();
        $this->startTest();
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testCreatePaymentPageWithDonationGoalTrackerAmountBasedSuccessfully()
    {
        $data = $this->startTest();
        $this->ba->proxyAuth();
        $pl = $this->getDbEntityById('payment_link', $data['id']);
        $resDataSubSet = [
            Entity::GOAL_TRACKER        => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::GOAL_AMOUNT             => "10000",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "1",
                    Entity::COLLECTED_AMOUNT        => "0",
                    Entity::SUPPORTER_COUNT         => "0"
                ]
            ]
        ];
        $this->assertDonationGoalTracker($pl, $resDataSubSet);
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testCreatePaymentPageWithDonationGoalTrackerSupporterBasedSuccessfully()
    {
        $data = $this->startTest();
        $this->ba->proxyAuth();
        $pl = $this->getDbEntityById('payment_link', $data['id']);
        $resDataSubSet = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::AVALIABLE_UNITS         => "10000",
                    Entity::DISPLAY_AVAILABLE_UNITS => "1",
                    Entity::DISPLAY_SOLD_UNITS      => "1",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "1",
                    Entity::SOLD_UNITS              => "0",
                    Entity::SUPPORTER_COUNT         => "0"
                ]
            ]
        ];

        $this->assertDonationGoalTracker($pl, $resDataSubSet);
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testDonationGoalTrackerAmountBasedOnMakePaymentShouldIncrementKeys()
    {
        [$pl, $_, $payment] = $this->createDonationGoalTrackerWithSinglePayment(
            ['view_type' => 'page']
        );
        $pl = $this->getDbEntityById('payment_link', $pl->getId());
        $resDataSubSet = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::GOAL_AMOUNT             => "10000",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "1",
                    Entity::SOLD_UNITS              => "2",
                    Entity::SUPPORTER_COUNT         => "1",
                    Entity::COLLECTED_AMOUNT        => "15000",
                ]
            ]
        ];

        $this->assertDonationGoalTracker($pl, $resDataSubSet);


        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SOLD_UNITS]         = "0";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SUPPORTER_COUNT]    = "0";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::COLLECTED_AMOUNT]   = "0";

        $this->assertDonationGoalTrackerRefundFlow($pl, $resDataSubSet, [
            'pay_id'    => $payment['id'],
            'amount'    => 15000
        ]);
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testDonationGoalTrackerAmountBasedOnMultipleOrderMakePaymentShouldIncrementKeys()
    {
        [$pl, $order, $_] = $this->createDonationGoalTrackerWithSinglePayment(
            ['view_type' => 'page']
        );

        // total 6 payments
        for ($i = 0; $i<5; $i++)
        {
            $orderRes   = $this->startTest();
            $orderId    = $order->stripDefaultSign($orderRes['order']['id']);
            $payment    = $this->makePaymentForPaymentLinkWithOrderAndAssert($pl, $this->getDbEntityById('order', $orderId));
        }

        $pl = $this->getDbEntityById('payment_link', $pl->getId());
        $resDataSubSet = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_AMOUNT_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::GOAL_AMOUNT             => "10000",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "1",
                    Entity::SOLD_UNITS              => "17",
                    Entity::SUPPORTER_COUNT         => "6",
                    Entity::COLLECTED_AMOUNT        => stringify(15000 + (5000 * 5) + (10000 * 5 * 2)),
                ]
            ]
        ];

        $this->assertDonationGoalTracker($pl, $resDataSubSet);

        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SOLD_UNITS]         = "14";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SUPPORTER_COUNT]    = "5";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::COLLECTED_AMOUNT]   = stringify(15000 + (5000 * 4) + (10000 * 4 * 2));

        $this->assertDonationGoalTrackerRefundFlow($pl, $resDataSubSet, [
            'pay_id'    => $payment['id'],
            'amount'    => 25000
        ]);
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testDonationGoalTrackerSupporterBasedOnMakePaymentShouldIncrementKeys()
    {
        [$pl, $_, $payment] = $this->createDonationGoalTrackerWithSinglePayment(
            ['view_type' => 'page'],
            [
                Entity::AVALIABLE_UNITS         => "10000",
                Entity::DISPLAY_AVAILABLE_UNITS => "1",
                Entity::DISPLAY_SOLD_UNITS      => "1",
                Entity::DISPLAY_DAYS_LEFT       => "0",
                Entity::DISPLAY_SUPPORTER_COUNT => "1"
            ],
            PaymentLink\DonationGoalTrackerType::DONATION_SUPPORTER_BASED
        );
        $pl = $this->getDbEntityById('payment_link', $pl->getId());
        $resDataSubSet = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "1",
                    Entity::SOLD_UNITS              => "2",
                    Entity::SUPPORTER_COUNT         => "1",
                    Entity::COLLECTED_AMOUNT        => "15000",
                ]
            ]
        ];

        $this->assertDonationGoalTracker($pl, $resDataSubSet);

        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SOLD_UNITS]         = "0";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SUPPORTER_COUNT]    = "0";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::COLLECTED_AMOUNT]   = "0";

        $this->assertDonationGoalTrackerRefundFlow($pl, $resDataSubSet, [
            'pay_id'    => $payment['id'],
            'amount'    => 15000
        ]);
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testDonationGoalTrackerSupporterBasedOnMultipleOrderMakePaymentShouldIncrementKeys()
    {
        [$pl, $order, $_] = $this->createDonationGoalTrackerWithSinglePayment(
            ['view_type' => 'page'],
            [
                Entity::AVALIABLE_UNITS         => "10000",
                Entity::DISPLAY_AVAILABLE_UNITS => "1",
                Entity::DISPLAY_SOLD_UNITS      => "1",
                Entity::DISPLAY_DAYS_LEFT       => "0",
                Entity::DISPLAY_SUPPORTER_COUNT => "1"
            ],
            PaymentLink\DonationGoalTrackerType::DONATION_SUPPORTER_BASED
        );

        // total 6 payments
        for ($i = 0; $i<5; $i++)
        {
            $orderRes   = $this->startTest();
            $orderId    = $order->stripDefaultSign($orderRes['order']['id']);
            $payment    = $this->makePaymentForPaymentLinkWithOrderAndAssert($pl, $this->getDbEntityById('order', $orderId));
        }

        $pl = $this->getDbEntityById('payment_link', $pl->getId());
        $resDataSubSet = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "1",
                    Entity::SOLD_UNITS              => "27",
                    Entity::SUPPORTER_COUNT         => "6",
                    Entity::COLLECTED_AMOUNT        => stringify(15000 + (5000 * 5 * 3) + (10000 * 5 * 2)),
                ]
            ]
        ];

        $this->assertDonationGoalTracker($pl, $resDataSubSet);

        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SOLD_UNITS]         = "22";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::SUPPORTER_COUNT]    = "5";
        $resDataSubSet[Entity::GOAL_TRACKER][Entity::META_DATA][Entity::COLLECTED_AMOUNT]   = stringify(15000 + (5000 * 4 * 3) + (10000 * 4 * 2));

        $this->assertDonationGoalTrackerRefundFlow($pl, $resDataSubSet, [
            'pay_id'    => $payment['id'],
            'amount'    => 35000
        ]);
    }

    /**
     * @group nocode_pp_subscription
     */
    public function testCreateSubscriptionButton()
    {
        $subscriptionModule = $this->app['module']->subscription;

        $mockSubscription   = \Mockery::mock($subscriptionModule)->makePartial();

        $mockSubscription->shouldReceive('fetchPlan')->andReturn([
            PaymentLink\PaymentPageItem\Entity::ITEM    => [
                Item\Entity::NAME           => "amount",
                Item\Entity::AMOUNT         => 100000,
                Item\Entity::CURRENCY       => Currency::INR,
                Item\Entity::DESCRIPTION    => "SAMPLE DESCRIPTION"
            ],
            "interval"  => 23,
            "period"    => 23,
        ]);

        $this->app['module']->subscription = $mockSubscription;

        $this->startTest();
    }

    /**
     * @group nocode_pp_subscription
     */
    public function testCreateSubscription()
    {
        $this->ba->directAuth();

        $this->createPaymentLink(self::TEST_PL_ID, [
            'view_type' => 'subscription_button'
        ]);

        $this->createSubscriptionPaymentPageItem();

        $subscriptionModule = $this->app['module']->subscription;

        $mockSubscription   = \Mockery::mock($subscriptionModule)->makePartial();

        $mockSubscription->shouldReceive('createSubscription')->andReturn(["id" => 'plan_' . self::TEST_PLAN_ID]);

        $this->app['module']->subscription = $mockSubscription;

        $this->startTest();
    }

    /**
     * @group nocode_pp_subscription
     */
    public function testCreateSubscriptionWithNoPlanIdShouldThrowException()
    {
        $this->ba->directAuth();

        $this->createPaymentLink(self::TEST_PL_ID, [
            'view_type' => 'subscription_button'
        ]);

        $this->createPaymentPageItem();

        $this->startTest();
    }

    public function testPaymentHandleCreation()
    {
        $this->activateMerchantToTriggerPaymentHandleCreation();

        $this->ba->proxyAuthLive();

        $ph = $this->getDbLastEntity('payment_link', MODE::LIVE);

        $this->assertNotNull($ph);

        $this->assertEquals('Test Label 123', $ph[Entity::TITLE]);

        $this->assertEquals('@testlabel123', $ph->getSlugFromShortUrl());
    }

    public function testPaymentHandleCreationWithDashInBillingLabel()
    {
        // billing label with -
        $billingLabel = 'Test Label-123';

        $this->activateMerchantToTriggerPaymentHandleCreation($billingLabel);

        $this->ba->proxyAuthLive();

        $ph = $this->getDbLastEntity('payment_link', MODE::LIVE);

        $this->assertNotNull($ph);

        $this->assertEquals('Test Label-123', $ph[Entity::TITLE]);

        $this->assertEquals('@testlabel-123', $ph->getSlugFromShortUrl());
    }

    public function testPaymentHandleUpdate()
    {
        $this->testPaymentHandleCreation();

        $pl = $this->getDbLastEntity('payment_link', 'live');

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $gimli = $this->createMock(Gimli::class);

        $gimli->method('expandAndGetMetadata')->willReturn(null);

        $elfin = $this->createMock(ElfinService::class);

        $elfin->method('driver')->willReturn($gimli);

        $elfin->method('shorten')->willReturn(
            "https://rzp.io/i/@newHandle"
        );

        $this->app->instance('elfin', $elfin);

        $this->app->instance('mode', 'live');

        $newPaymentHandle = "@newHandle";

        $handleUrl = $this->app['config']->get('app.payment_handle_hosted_base_url')
            . "/". $newPaymentHandle;

        $request = [
            'method' => 'PATCH',
            'url' => '/v1/payment_handle/' . $pl->getPublicId(),
            'content' => [
                'slug' => $newPaymentHandle
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey(Entity::URL, $content);

        $this->assertArrayHasKey(Entity::SLUG, $content);

        $this->assertEquals($content[Entity::SLUG], $newPaymentHandle);

        $this->assertEquals($content[Entity::URL], $handleUrl);
    }

    public function testPaymentHandleFetch()
    {
        $this->testPaymentHandleCreation();

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testPaymentHandleFetchWhenHandleDoesNotExists()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPaymentHandleDeactivatedView()
    {
        $this->activateMerchantToTriggerPaymentHandleCreation('ANC Corp');

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->app->instance('mode', 'live');

        // activating merchant to make live request
        $this->fixtures->merchant->activate('10000000000000');

        // getting pl id for handle
        $paymentHandle = $this->getDbLastEntity('payment_link', 'live');

        $this->fixtures->on('live')->edit('payment_link', $paymentHandle[Entity::ID], [ 'status' => 'inactive' , 'status_reason' => 'deactivated']);

        // calling view get on deactivated payment handle
        $view = $this->call('GET', "/v1/payment_pages/pl_" . $paymentHandle[Entity::ID] . "/view");;

        $view->assertStatus(200);

        $this->assertStringContainsString('payment-handle/error.js', $view->getContent());
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testUpdatePageWithGoalTrackerInactiveAndEndDatePastShouldNotThrowValidationError()
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID, ['view_type' => 'page']);
        $this->createPaymentPageItem();
        $settings = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                Entity::GOAL_IS_ACTIVE  => "0",
                Entity::META_DATA       => [
                    Entity::DISPLAY_AVAILABLE_UNITS => "0",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "0",
                    Entity::DISPLAY_SOLD_UNITS      => "0",
                    Entity::AVALIABLE_UNITS         => "55",
                    Entity::GOAL_END_TIMESTAMP      => (string) (new Carbon())->subDays(1)->getTimestamp()
                ]
            ]
        ];

        $pl->getSettingsAccessor()->upsert($settings)->save();
        $this->startTest();
    }

    /**
     * @group pp_donation_goal_tracker
     */
    public function testUpdatePageWithGoalTrackerActiveEndDatePastShouldThrowValidationError()
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID, ['view_type' => 'page']);
        $this->createPaymentPageItem();
        $settings = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => PaymentLink\DonationGoalTrackerType::DONATION_SUPPORTER_BASED,
                Entity::GOAL_IS_ACTIVE  => "1",
                Entity::META_DATA       => [
                    Entity::DISPLAY_AVAILABLE_UNITS => "0",
                    Entity::DISPLAY_DAYS_LEFT       => "0",
                    Entity::DISPLAY_SUPPORTER_COUNT => "0",
                    Entity::DISPLAY_SOLD_UNITS      => "0",
                    Entity::AVALIABLE_UNITS         => "55",
                    Entity::GOAL_END_TIMESTAMP      => (string) (new Carbon())->subDays(1)->getTimestamp()
                ]
            ]
        ];

         $pl->getSettingsAccessor()->upsert($settings)->save();

        $this->startTest();
    }

    // -------------------- Protected methods --------------------

    protected function activateMerchantToTriggerPaymentHandleCreation(string $billingLabel = 'Test Label 123')
    {
        $this->mockGimliPaymentHandle($billingLabel);

        $this->ba->proxyAuthLive();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => '10000000000000',
            'promoter_pan'      => 'EBPPK8222K',
            'promoter_pan_name' => 'User 1',
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000000');

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => $billingLabel]);

        // Activation request for instant activation
        $content = [
            'activation_form_milestone'   => 'L1',
            'company_cin'                 => 'U65999KA2018PTC114468',
            'business_category'           => 'ecommerce',
            'business_subcategory'        => 'fashion_and_lifestyle',
            'promoter_pan'                => 'ABCPE0000Z',
            'business_name'               => 'business_name',
            'business_dba'                => $billingLabel,
            'business_type'               => 1,
            'business_model'              => '1245',
            'business_website'            => 'https://example.com',
            'business_operation_address'  => 'My Addres is somewhere',
            'business_operation_state'    => 'KA',
            'business_operation_city'     => 'Bengaluru',
            'business_operation_pin'      => '560095',
            'business_registered_address' => 'Registered Address',
            'business_registered_state'   => 'DL',
            'business_registered_city'    => 'Delhi',
            'business_registered_pin'     => '560050',
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => $content
        ];

        $activationResponse = $this->makeRequestAndGetRawContent($request);

        $activationResponse->assertStatus(200);
    }

    protected function assertDonationGoalTrackerRefundFlow(Entity $paymentLink, array $goalTrackerSubset, array $refundInput): void
    {
        $this->refundPayment(
            $refundInput['pay_id'],
            $refundInput['amount'],
            [],
            [],
            ($refundInput['reverse_all'] ?? "1") === "1"
        );
        $pl = $this->getDbEntityById('payment_link', $paymentLink->getId());

        $this->assertDonationGoalTracker($pl, $goalTrackerSubset);
    }

    protected function createDonationGoalTrackerWithSinglePayment(
        array $paymentLinkAttribute = [],
        array $metadata = [],
        string $trackerType = PaymentLink\DonationGoalTrackerType::DONATION_AMOUNT_BASED,
        string $goalIsActive = "1",
        array $orderAttribute = []
    ): array
    {
        $data = $this->createPaymentLinkAndOrderForThat($paymentLinkAttribute, $orderAttribute);
        if (empty($metadata))
        {
            $metadata =  [
                Entity::GOAL_AMOUNT             => "10000",
                Entity::DISPLAY_DAYS_LEFT       => "0",
                Entity::DISPLAY_SUPPORTER_COUNT => "1"
            ];
        }
        $settings = [
            Entity::GOAL_TRACKER    => [
                Entity::TRACKER_TYPE    => $trackerType,
                Entity::GOAL_IS_ACTIVE  => $goalIsActive,
                Entity::META_DATA       => $metadata,
            ]
        ];

        $pl     = $data['payment_link'];
        $order  = $data['payment_link_order']['order'];

        $pl->getSettingsAccessor()->upsert($settings)->save();

        $payment = $this->makePaymentForPaymentLinkWithOrderAndAssert($pl, $order);

        return [$pl, $order, $payment];
    }

    protected function assertDonationGoalTracker(Entity $paymentLink, array $goalTrackerSubArray)
    {
        $serializer     = new PaymentLink\ViewSerializer($paymentLink);
        $settings       = $serializer->serializeSettingsWithDefaults();
        $forHostedPage  = $serializer->serializeForHosted();

        $resHostedPagePayloadSubSet = [
            "payment_link"  => [
                Entity::SETTINGS => $goalTrackerSubArray
            ]
        ];
        $this->assertArraySubset($goalTrackerSubArray, $settings);
        $this->assertArraySubset($resHostedPagePayloadSubSet, $forHostedPage);
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

    protected function createAndPutImageFileInRequest(string $callee, $files = [])
    {
        if (empty($files) === true)
        {
            $uploadedFile = $this->createUploadedFile(__DIR__ . '/Helpers/' . 'number2.png', 'number2.png', 'image/png');

            $this->testData[$callee]['request']['content']['images'][] = $uploadedFile;
        }
    }

    protected function createUploadedFile(string $url, $fileName = 'test.jpeg', $mime = 'image/jpeg'): UploadedFile
    {
        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            filesize($url),
            null,
            true
        );
    }

    protected function createHandleFromBillingLabel(string $billingLabel)
    {
        $handle = preg_replace('/[^a-zA-Z0-9-]+/', '', $billingLabel);

        $handle = '@' . strtolower(str_replace(' ', '', $billingLabel));

        return $handle;
    }


    protected function mockGimliPaymentHandle(string $billingLabel = 'Test Label 123')
    {
        $handle = $this->createHandleFromBillingLabel($billingLabel);

        $gimli = $this->createMock(Gimli::class);

        $gimli->method('expandAndGetMetadata')->willReturn(null);

        $elfin = $this->createMock(ElfinService::class);

        $elfin->method('driver')->willReturn($gimli);

        $elfin->method('shorten')->willReturn(
            "https://rzp.io/i/" . $handle
        );

        $this->app->instance('elfin', $elfin);
    }
}
