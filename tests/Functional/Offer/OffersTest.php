<?php

namespace RZP\Tests\Functional\Offer;

use Carbon\Carbon;
use Mockery;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as E;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\Collection;
use RZP\Models\Base\DbMigrationMetricsObserver;
use RZP\Models\Merchant\Account;
use RZP\Models\Offer\Core;
use RZP\Services\OffersEngine as OffersEngine;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Admin\Service as AdminService;
use Illuminate\Support\Facades\Config;
use RZP\Services\DbRequestsBeforeMigrationMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Razorpay\Trace\Facades\Trace;
class OffersTest extends TestCase
{
    use RazorxTrait;
    use MocksSplitz;
    use RefreshDatabase;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected $offersEngineMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/OffersTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->setUpOffersEngineMock();

        // This is set to 1 Jan 2018
        // Because in test cases offers start date is set
        // to Feb 2018 and it should always be in future
        Carbon::setTestNow("1-1-2018 00:00:00");

        //Creating observer in set up for cases where request is sampled out.
        $entityClass = E::getEntityClass( Entity::OFFER);
        $entityClass::observe(DbMigrationMetricsObserver::class);
    }

    protected function setUpOffersEngineMock()
    {
        $this->offersEngineMock = Mockery::mock('RZP\Services\OffersEngine', [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->app['offers_engine'] = $this->offersEngineMock;
    }


    private function mockSplitzExperiment($output)
    {
        $this->splitzMock = \Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->byDefault()
            ->andReturn($output);
    }

    public function testCreateCardOffer()
    {
        $this->offersEngineMock->shouldReceive('createOffer')->times(0)->andReturn( [
            'offer' => [
                'metadata' => [
                    'offer_id' => 'offer_10000000000000',
                    'name' => 'Test Offer',
                    'display_name' => 'Test Offer',
                    'description' => 'Some more details',
                    'terms' => [
                        'tnc' => 'Some more details',
                    ],
                    'advertiser_id' => 'rzp.merchant.10000000000000', // Replace with the actual advertiser ID
                    'state' => 'STATE_CREATED',
                    'offer_on' => 'BENEFICIARY_TYPE_SELF',
                    'currency' => 'INR',
                    'schedules' => [
                        'starts_at' => 1514764800,
                        'ends_at' => 1546300800,
                    ],
                ],
                'spec' => [
                    'allowed_channels' => [
                        'CHANNEL_RZP_CHECKOUT',
                    ],
                    'funding' => [
                        'type' => 'BENEFICIARY_TYPE_SELF',
                        'split' => [
                            [
                                'type' => 'VALUE_OPTION_PERCENTAGE',
                                'bearer' => 'USER_TYPE_PUBLISHER',
                                'value' => 100,
                            ],
                        ],
                    ],
                    'benefits_types' => [
                        'BENEFIT_TYPE_DISCOUNT',
                    ],
                    'rule_groups' => [
                        'CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER' => [
                            'rules' => [
                                [
                                    'when_expression' => 'true',
                                    'then' => [
                                        [
                                            'discount' => [
                                                'percent_discount' => 1000,
                                                'applicable_on' => 'Order.total_amount',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'CHANNEL_RZP_CHECKOUT.STAGE_AVAIL' => [
                            'rules' => [
                                [
                                        'when_expression' => 'true && PaymentInstrument.Method == \"card\" && PaymentInstrument.CardType == \"credit\" && PaymentInstrument.CardNetwork == \"VISA\" && PaymentInstrument.Issuer == \"HDFC\"',
                                    'then' => [
                                        [
                                            'discount' => [
                                                    [
                                                        'percent_discount' => 1000,
                                                        'applicable_on' => 'Order.total_amount',
                                                    ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'publish' => [
                'continue_txn_on_failure' => 0,
                'offer_type' => 'OFFER_TYPE_STAGE_REGULAR',
                'channel_name' => 'CHANNEL_RZP_CHECKOUT',
            ],
        ]);

        $this->startTest();
    }
    public function testCreateMultiplePaymentMethodOffer()
    {
        $this->markTestSkipped("marking this as skipped due to concurrency issues with config keys.");

        (new AdminService)->setConfigKeys(
        [
            ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => true,
            ConfigKey::OFFERS_ENGINE_SERVICE_ENABLED => true,
        ]);

        $this->offersEngineMock->shouldReceive('createOffer')->times(1)->andReturn( [
                'offer' => [
                    "public_offer" => [
                        "entity" => "Offer",
                        "id" => "offer_10000000000000",
                        "name" => "Second Rule Offer Others",
                        "display_name" => "Title sample",
                        "description" => "Description",
                        "status" => "CREATED",
                        "currency" => "INR",
                        "schedules" => [
                            [
                                "starts_at" => "1739944804",
                                "ends_at" => "1801896804"
                            ]
                        ],
                        "terms" => [
                            "tnc" => "testing the terms and conditions",
                            "url" => "test.com"
                        ],
                        "applicable_channels" => [
                            "RZP_CHECKOUT"
                        ],
                        "funding" => [
                            "split" => [
                                [
                                    "bearer" => "APP",
                                    "type" => "PERCENTAGE",
                                    "value" => "100"
                                ]
                            ]
                        ],
                        "benefits_types" => [
                            "DISCOUNT"
                        ],
                        "rules" => [
                            [
                                "includes" => [
                                    "orders" => [
                                        [
                                            "min_amount" => "5000"
                                        ]
                                    ],
                                    "payment_instruments" => [
                                        [
                                            "method" => "card"
                                        ],
                                        [
                                            "method" => "netbanking"
                                        ]
                                    ]
                                ],
                                "benefits" => [
                                    [
                                        "instant_discount" => [
                                            [
                                                "limit_type" => "UPTO",
                                                "unit" => "PERCENTAGE",
                                                "value" => "1000"
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'metadata' => [
                        'offer_id' => 'offer_10000000000000',
                        'name' => 'Test Offer',
                        'display_name' => 'Test Offer',
                        'description' => 'Some more details',
                        'terms' => [
                            'tnc' => 'Some more details',
                        ],
                        'advertiser_id' => 'rzp.merchant.10000000000000', // Replace with the actual advertiser ID
                        'state' => 'STATE_CREATED',
                        'offer_on' => 'BENEFICIARY_TYPE_SELF',
                        'currency' => 'INR',
                        'schedules' => [
                            'starts_at' => 1514764800,
                            'ends_at' => 1546300800,
                        ],
                    ],
                    'spec' => [
                        'allowed_channels' => [
                            'CHANNEL_RZP_CHECKOUT',
                        ],
                        'funding' => [
                            'type' => 'BENEFICIARY_TYPE_SELF',
                            'split' => [
                                [
                                    'type' => 'VALUE_OPTION_PERCENTAGE',
                                    'bearer' => 'USER_TYPE_PUBLISHER',
                                    'value' => 100,
                                ],
                            ],
                        ],
                        'benefits_types' => [
                            'BENEFIT_TYPE_DISCOUNT',
                        ],
                        'rule_groups' => [
                            'CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER' => [
                                'rules' => [
                                    [
                                        'when_expression' => 'true',
                                        'then' => [
                                            [
                                                'discount' => [
                                                    'percent_discount' => 1000,
                                                    'applicable_on' => 'Order.total_amount',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'CHANNEL_RZP_CHECKOUT.STAGE_AVAIL' => [
                                'rules' => [
                                    [
                                        'when_expression' => 'true && PaymentInstrument.Method == \"card\" && PaymentInstrument.CardType == \"credit\" && PaymentInstrument.CardNetwork == \"VISA\" && PaymentInstrument.Issuer == \"HDFC\"',
                                        'then' => [
                                            [
                                                'discount' => [
                                                    [
                                                        'percent_discount' => 1000,
                                                        'applicable_on' => 'Order.total_amount',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'publish' => [
                    'continue_txn_on_failure' => 0,
                    'offer_type' => 'OFFER_TYPE_STAGE_REGULAR',
                    'channel_name' => 'CHANNEL_RZP_CHECKOUT',
                ],
            ]);

        $this->startTest();

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => false,
                ConfigKey::OFFERS_ENGINE_SERVICE_ENABLED => false,
        ]);
    }

    public function testCreateMultiplePaymentMethodOfferWithValidationFailure()
    {
        $this->startTest();
    }

    public function testCreateOfferWithApiReadsConflictingOffers()
    {
        $this->markTestSkipped();
        $this->fixtures->create('offer', [
            'merchant_id'         => '10000000000000',
            'name'                => 'Test Offer',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => 'VISA',
            'issuer'              => 'HDFC',
            'percent_rate'        => 1000,
            'starts_at'           => 1546300799,
            'ends_at'             => 1554764800,
            'active'              => 1,
            'display_text'        => 'Some more details',
            'terms'               => 'Some more details',
            'block'               => 1,
            'type'                => 'instant'
        ]);

        $this->offersEngineMock->shouldReceive('createOffer')->times(0);

        $this->startTest();
    }

    public function testCreateOfferWithoutApiReadsConflictingOffers()
    {
        $this->markTestSkipped("marking this as skipped due to concurrency issues with config keys.");
        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => true,
            ]);

        $this->fixtures->create('offer', [
            'merchant_id'         => '10000000000000',
            'name'                => 'Test Offer',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => 'VISA',
            'issuer'              => 'HDFC',
            'percent_rate'        => 1000,
            'starts_at'           => 1546300799,
            'ends_at'             => 1554764800,
            'active'              => 1,
            'display_text'        => 'Some more details',
            'terms'               => 'Some more details',
            'block'               => 1,
            'type'                => 'instant'
        ]);

        $this->offersEngineMock->shouldReceive('createOffer')->times(1)->andReturn($this->getOffersEngineMockResponse());

        $this->startTest();

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => false,
            ]);
    }

    public function testCreateOfferWithApiReadsValidateMerchantMethod()
    {

        $this->fixtures->merchant->disableCard();

        $this->offersEngineMock->shouldReceive('createOffer')->times(0);

        $this->startTest();
    }

    public function testCreateOfferWithoutApiReadsValidateMerchantMethod()
    {
        $this->markTestSkipped("marking this as skipped due to concurrency issues with config keys.");
        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => true,
            ]);

        $this->fixtures->merchant->disableCard();

        $this->offersEngineMock->shouldReceive('createOffer')->times(1)->andReturn($this->getOffersEngineMockResponse());

        $this->startTest();

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => false,
            ]);
    }

    public function testCreateOfferWithApiReadsValidateMerchantCategory()
    {

        $this->fixtures->merchant->setCategory('6211');

        $this->offersEngineMock->shouldReceive('createOffer')->times(0);

        $this->startTest();
    }

    public function testCreateOfferWithoutApiReadsValidateMerchantCategory()
    {
        $this->markTestSkipped("marking this as skipped due to concurrency issues with config keys.");
        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => true,
            ]);

        $this->fixtures->merchant->setCategory('6211');

        $this->offersEngineMock->shouldReceive('createOffer')->times(1)->andReturn($this->getOffersEngineMockResponse());

        $this->startTest();

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => false,
            ]);
    }

    public function testCreateOfferWithApiReadsValidateOfferFeatureBlock()
    {

        $this->fixtures->merchant->addFeatures(['block_offer_creation']);

        $this->offersEngineMock->shouldReceive('createOffer')->times(0);

        $this->startTest();
    }

    public function testCreateOfferWithoutApiReadsValidateOfferFeatureBlock()
    {
        $this->markTestSkipped("marking this as skipped due to concurrency issues with config keys.")
        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => true,
            ]);

        $this->fixtures->merchant->addFeatures(['block_offer_creation']);

        $this->offersEngineMock->shouldReceive('createOffer')->times(1)->andReturn($this->getOffersEngineMockResponse());

        $this->startTest();

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => false,
            ]);
    }

    public function testCreateOfferWithoutApiReadsTenureDiscountMap()
    {
        $this->markTestSkipped("marking this as skipped due to concurrency issues with config keys.");
        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => true,
            ]);

        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $offersEngineMock = Mockery::mock('RZP\Services\OffersEngine', [$this->app])
                                   ->makePartial()
                                   ->shouldAllowMockingProtectedMethods();

        $offersEngineMock->shouldReceive('sendRequest')->andReturnUsing(
            function(string $endpoint,
                     string $method,
                     array  $data,
            ) {
                self::assertEquals('v1/offers', $endpoint);
                self::assertEquals("POST", $method);
                self::assertNotEmpty($data);

                return
                    [
                        "offer"   => [
                            "metadata" => [
                                "name"          => "Test Offer",
                                "display_name"  => "Test Offer",
                                "description"   => "HDFC Debit Card Emi Subvention offers",
                                "terms"         => [
                                    "tnc" => "Some more details"
                                ],
                                "advertiser_id" => "rzp.merchant.10000000000000",
                                "offer_id"      => "offer_PYZ4O1JXyCOrCb",
                                "created_by_id" => "merchantuser01@razorpay.com",
                                "state"         => "STATE_CREATED",
                                "offer_on"      => "BENEFICIARY_TYPE_SELF",
                                "currency"      => "INR",
                                "schedules"     => [
                                    "starts_at" => 1514764800,
                                    "ends_at"   => 1546300800
                                ]
                            ],
                            "spec"     => [
                                "allowed_channels" => [
                                    "CHANNEL_RZP_CHECKOUT"
                                ],
                                "funding"          => [
                                    "type"  => "BENEFICIARY_TYPE_SELF",
                                    "split" => [
                                        [
                                            "type"   => "VALUE_OPTION_PERCENTAGE",
                                            "bearer" => "USER_TYPE_PUBLISHER",
                                            "value"  => 100
                                        ]
                                    ]
                                ],
                                "benefits_types"   => [
                                    "BENEFIT_TYPE_NO_COST_EMI"
                                ],
                                "usage_limits"     => [
                                    [
                                        "maximum_value" => 2,
                                        "on"            => "LIMIT_ON_CARD_NUMBER",
                                        "limit_type"    => "LIMIT_TYPE_COUNT"
                                    ]
                                ],
                                "rule_groups"      => [
                                    "CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER" => [
                                        "rules" => [
                                            [
                                                "when_expression" => "Order.TotalAmount >= 500000",
                                                "then"            => [
                                                    [
                                                        "no_cost_emi" => [
                                                            [
                                                                "discount" => [
                                                                    "percent_discount" => null,
                                                                    "applicable_on"    => "Order.total_amount",
                                                                ],
                                                                "tenure"   => 6,
                                                                "issuer"   => "HDFC"
                                                            ]
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    "CHANNEL_RZP_CHECKOUT.STAGE_AVAIL"    => [
                                        "rules" => [
                                            [
                                                "when_expression" => "Order.TotalAmount >= 500000 && PaymentInstrument.Method == \"emi\" && PaymentInstrument.CardType == \"debit\" && PaymentInstrument.CardCobrandingPartner == \"NA\" && PaymentInstrument.Issuer == \"HDFC\" && PaymentInstrument.EmiTenure == 6",
                                                "then"            => [
                                                    [
                                                        "no_cost_emi" => [
                                                            [
                                                                "discount" => [
                                                                    "percent_discount" => null,
                                                                    "applicable_on"    => "Order.total_amount"
                                                                ],
                                                                "tenure"   => 6,
                                                                "issuer"   => "HDFC"
                                                            ]
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "publish" => [
                            "continue_txn_on_failure" => false,
                            "offer_type"              => "OFFER_TYPE_STAGE_HIDDEN",
                            "auto_apply"              => false,
                            "channel_name"            => "CHANNEL_RZP_CHECKOUT"
                        ],
                    ];
            }
        )->times(1);

        $this->app['offers_engine'] = $offersEngineMock;

        $response                   = $this->startTest();

        $this->assertTrue(is_array($response));

        $this->assertNotEmpty($response);

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => true,
            ]);
    }
    public function testCreateOfferWithNullMethod()
    {
        $this->offersEngineMock->shouldReceive('createOffer')->times(0)->andReturn( [
            'offer' => [
                'metadata' => [
                    'offer_id' => 'offer_10000000000000',
                    'name' => 'Test Offer',
                    'display_name' => 'Test Offer',
                    'description' => 'Some more details',
                    'terms' => [
                        'tnc' => 'Some more details',
                    ],
                    'advertiser_id' => 'rzp.merchant.10000000000000', // Replace with the actual advertiser ID
                    'created_by' => 'rzp_merchant',
                    'state' => 'STATE_CREATED',
                    'offer_on' => 'BENEFICIARY_TYPE_SELF',
                    'currency' => 'INR',
                    'schedules' => [
                        'starts_at' => 1514764800,
                        'ends_at' => 1546300800,
                    ],
                ],
                'spec' => [
                    'allowed_channels' => [
                        'CHANNEL_RZP_CHECKOUT',
                    ],
                    'funding' => [
                        'type' => 'BENEFICIARY_TYPE_SELF',
                        'split' => [
                            [
                                'type' => 'VALUE_OPTION_PERCENTAGE',
                                'bearer' => 'USER_TYPE_PUBLISHER',
                                'value' => 100,
                            ],
                        ],
                    ],
                    'benefits_types' => [
                        'BENEFIT_TYPE_DISCOUNT',
                    ],
                    'rule_groups' => [
                        'CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER' => [
                            'rules' => [
                                [
                                    'when_expression' => 'true',
                                    'then' => [
                                        [
                                            'discount' => [
                                                [
                                                    'percent_discount' => 1000,
                                                    'applicable_on' => 'Order.total_amount',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'CHANNEL_RZP_CHECKOUT.STAGE_AVAIL' => [
                            'rules' => [
                                [
                                    'when_expression' => 'true && PaymentInstrument.CardNetwork == \"VISA\" && PaymentInstrument.Issuer == \"HDFC\"',
                                    'then' => [
                                        [
                                            'discount' => [
                                                [
                                                    'percent_discount' => 1000,
                                                    'applicable_on' => 'Order.total_amount',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'publish' => [
                'continue_txn_on_failure' => 0,
                'offer_type' => 'OFFER_TYPE_STAGE_REGULAR',
                'channel_name' => 'CHANNEL_RZP_CHECKOUT',
            ],
        ]);

        $this->startTest();
    }

    public function testCreateOfferWithNullMethodAndInvalidIssuer()
    {
        $this->startTest();
    }

    public function testCreateCardOfferWithIin()
    {
        $this->offersEngineMock->shouldReceive('createOffer')->times(0)->andReturn( [
            'offer' => [
                'metadata' => [
                    'offer_id' => 'offer_10000000000000',
                    'name' => 'Test Offer',
                    'display_name' => 'Test Offer',
                    'description' => 'Some more details',
                    'terms' => [
                        'tnc' => 'Some more details',
                    ],
                    'advertiser_id' => 'rzp.merchant.10000000000000', // Replace with the actual advertiser ID
                    'created_by' => 'rzp_merchant',
                    'state' => 'STATE_CREATED',
                    'offer_on' => 'BENEFICIARY_TYPE_SELF',
                    'currency' => 'INR',
                    'schedules' => [
                        'starts_at' => 1514764800,
                        'ends_at' => 1546300800,
                    ],
                ],
                'spec' => [
                    'allowed_channels' => [
                        'CHANNEL_RZP_CHECKOUT',
                    ],
                    'funding' => [
                        'type' => 'BENEFICIARY_TYPE_SELF',
                        'split' => [
                            [
                                'type' => 'VALUE_OPTION_PERCENTAGE',
                                'bearer' => 'USER_TYPE_PUBLISHER',
                                'value' => 100,
                            ],
                        ],
                    ],
                    'benefits_types' => [
                        'BENEFIT_TYPE_DISCOUNT',
                    ],
                    'rule_groups' => [
                        'CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER' => [
                            'rules' => [
                                [
                                    'when_expression' => 'true',
                                    'then' => [
                                        [
                                            'discount' => [
                                                [
                                                    'percent_discount' => 1000,
                                                    'applicable_on' => 'Order.total_amount',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'CHANNEL_RZP_CHECKOUT.STAGE_AVAIL' => [
                            'rules' => [
                                [
                                    'when_expression' => 'true && PaymentInstrument.Method == \"card\" && PaymentInstrument.Iin in [\"411111\"]',
                                    'then' => [
                                        [
                                            'discount' => [
                                                [
                                                    'percent_discount' => 1000,
                                                    'applicable_on' => 'Order.total_amount',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'publish' => [
                'continue_txn_on_failure' => 0,
                'offer_type' => 'OFFER_TYPE_STAGE_REGULAR',
                'channel_name' => 'CHANNEL_RZP_CHECKOUT',
            ],
        ]);

        $this->startTest();
    }

    public function testCreateDcCardOfferWithIin()
    {
        $this->offersEngineMock->shouldReceive('createOffer')->times(0)->andReturn( [
            'offer' => [
                'metadata' => [
                    'offer_id' => 'offer_10000000000000',
                    'name' => 'Test Offer',
                    'display_name' => 'Test Offer',
                    'description' => 'Some more details',
                    'terms' => [
                        'tnc' => 'Some more details',
                    ],
                    'advertiser_id' => 'rzp.merchant.10000000000000', // Replace with the actual advertiser ID
                    'created_by' => 'rzp_merchant',
                    'state' => 'STATE_CREATED',
                    'offer_on' => 'BENEFICIARY_TYPE_SELF',
                    'currency' => 'INR',
                    'schedules' => [
                        'starts_at' => 1514764800,
                        'ends_at' => 1546300800,
                    ],
                ],
                'spec' => [
                    'allowed_channels' => [
                        'CHANNEL_RZP_CHECKOUT',
                    ],
                    'funding' => [
                        'type' => 'BENEFICIARY_TYPE_SELF',
                        'split' => [
                            [
                                'type' => 'VALUE_OPTION_PERCENTAGE',
                                'bearer' => 'USER_TYPE_PUBLISHER',
                                'value' => 100,
                            ],
                        ],
                    ],
                    'benefits_types' => [
                        'BENEFIT_TYPE_DISCOUNT',
                    ],
                    'rule_groups' => [
                        'CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER' => [
                            'rules' => [
                                [
                                    'when_expression' => 'true',
                                    'then' => [
                                        [
                                            'discount' => [
                                                [
                                                    'percent_discount' => 1000,
                                                    'applicable_on' => 'Order.total_amount',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'CHANNEL_RZP_CHECKOUT.STAGE_AVAIL' => [
                            'rules' => [
                                [
                                    'when_expression' => 'true && PaymentInstrument.Method == \"card\" && PaymentInstrument.Issuer == \"HDFC\" && PaymentInstrument.CardType == \"debit\" && PaymentInstrument.Iin in [\"411111\"]',
                                    'then' => [
                                        [
                                            'discount' => [
                                                [
                                                    'percent_discount' => 1000,
                                                    'applicable_on' => 'Order.total_amount',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'publish' => [
                'continue_txn_on_failure' => 0,
                'offer_type' => 'OFFER_TYPE_STAGE_REGULAR',
                'channel_name' => 'CHANNEL_RZP_CHECKOUT',
            ],
        ]);

        $this->startTest();
    }

    public function testCreateHDFCDebitCardNoCostEMIOffer(): void
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $this->offersEngineMock->shouldReceive('createOffer')->times(0)->andReturn( [
            'offer' => [
                'metadata' => [
                    'offer_id' => 'offer_10000000000000',
                    'name' => 'Test Offer',
                    'display_name' => 'HDFC Debit Card Emi Subvention offers',
                    'description' => 'HDFC Debit Card Emi Subvention offers',
                    'terms' => [
                        'tnc' => 'Some more details',
                    ],
                    'advertiser_id' => 'rzp.merchant.10000000000000', // Replace with the actual advertiser ID
                    'created_by' => 'rzp_merchant',
                    'state' => 'STATE_CREATED',
                    'offer_on' => 'BENEFICIARY_TYPE_SELF',
                    'currency' => 'INR',
                    'schedules' => [
                        'starts_at' => 1514764800,
                        'ends_at' => 1546300800,
                    ],
                ],
                'spec' => [
                    'allowed_channels' => [
                        'CHANNEL_RZP_CHECKOUT',
                    ],
                    'funding' => [
                        'type' => 'BENEFICIARY_TYPE_SELF',
                        'split' => [
                            [
                                'type' => 'VALUE_OPTION_PERCENTAGE',
                                'bearer' => 'USER_TYPE_PUBLISHER',
                                'value' => 100,
                            ],
                        ],
                    ],
                    'usage_limits' => [
                        [
                            'on' => 'LIMIT_ON_CARD_NUMBER',
                            'maximum_value' => 2,
                        ],
                    ],
                    'benefits_types' => [
                        'BENEFIT_TYPE_NO_COST_EMI',
                    ],
                    'rule_groups' => [
                        'CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER' => [
                            'rules' => [
                                [
                                    'when_expression' => 'Order.TotalAmount >= 500000',
                                    'then' => [
                                        [
                                            'no_cost_emi' => [
                                                [
                                                    'discount' => [
                                                        'percent_discount' => 1000,
                                                        'applicable_on' => 'Order.total_amount',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'CHANNEL_RZP_CHECKOUT.STAGE_AVAIL' => [
                            'rules' => [
                                [
                                    'when_expression' => 'Order.TotalAmount >= 500000 && PaymentInstrument.Method == \"emi\" && PaymentInstrument.Issuer == \"HDFC\" && PaymentInstrument.CardType == \"debit\" && PaymentInstrument.EmiTenure == 6',
                                    'then' => [
                                        [
                                            'no_cost_emi' => [
                                                [
                                                    'discount' => [
                                                        'percent_discount' => 1000,
                                                        'applicable_on' => 'Order.total_amount',
                                                    ],
                                                    'tenure' => 6,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'publish' => [
                'continue_txn_on_failure' => 0,
                'offer_type' => 'OFFER_TYPE_STAGE_REGULAR',
                'channel_name' => 'CHANNEL_RZP_CHECKOUT',
            ],
        ]);

        $this->startTest();
    }

    public function testCreateHDFCDebitCardNoCostEMIOfferWithoutDuration(): void
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $this->startTest();
    }

    public function testCreateHDFCDebitCardEMIOffer(): void
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $this->offersEngineMock->shouldReceive('createOffer')->times(0)->andReturn( [
            'offer' => [
                'metadata' => [
                    'offer_id' => 'offer_10000000000000',
                    'name' => 'Test Offer',
                    'display_name' => 'Test Offer',
                    'description' => 'HDFC Debit Card EMI offers',
                    'terms' => [
                        'tnc' => 'HDFC Debit Card EMI offers',
                    ],
                    'advertiser_id' => 'rzp.merchant.10000000000000', // Replace with the actual advertiser ID
                    'created_by' => 'rzp_merchant',
                    'state' => 'STATE_CREATED',
                    'offer_on' => 'BENEFICIARY_TYPE_SELF',
                    'currency' => 'INR',
                    'schedules' => [
                        'starts_at' => 1514764800,
                        'ends_at' => 1546300800,
                    ],
                ],
                'spec' => [
                    'allowed_channels' => [
                        'CHANNEL_RZP_CHECKOUT',
                    ],
                    'funding' => [
                        'type' => 'BENEFICIARY_TYPE_SELF',
                        'split' => [
                            [
                                'type' => 'VALUE_OPTION_PERCENTAGE',
                                'bearer' => 'USER_TYPE_PUBLISHER',
                                'value' => 100,
                            ],
                        ],
                    ],
                    'usage_limits' => [
                        [
                            'on' => 'LIMIT_ON_CARD_NUMBER',
                            'maximum_value' => 2,
                        ],
                    ],
                    'benefits_types' => [
                        'BENEFIT_TYPE_NO_COST_EMI',
                    ],
                    'rule_groups' => [
                        'CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER' => [
                            'rules' => [
                                [
                                    'when_expression' => 'Order.TotalAmount >= 500000',
                                    'then' => [
                                        [
                                            'no_cost_emi' => [
                                                [
                                                    'discount' => [
                                                        'percent_discount' => 1000,
                                                        'applicable_on' => 'Order.total_amount',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'CHANNEL_RZP_CHECKOUT.STAGE_AVAIL' => [
                            'rules' => [
                                [
                                    'when_expression' => 'Order.TotalAmount >= 500000 && PaymentInstrument.Method == \"emi\" && PaymentInstrument.Issuer == \"HDFC\" && PaymentInstrument.CardType == \"debit\" && PaymentInstrument.EmiTenure == 6',
                                    'then' => [
                                        [
                                            'no_cost_emi' => [
                                                [
                                                    'discount' => [
                                                        'percent_discount' => 1000,
                                                        'applicable_on' => 'Order.total_amount',
                                                    ],
                                                    'tenure' => 6,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'publish' => [
                'continue_txn_on_failure' => 0,
                'offer_type' => 'OFFER_TYPE_STAGE_REGULAR',
                'channel_name' => 'CHANNEL_RZP_CHECKOUT',
            ],
        ]);

        $this->startTest();
    }

    public function testPaymentMethodTypeForCreditCardOfferCreation(): void
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $this->startTest();
    }


    public function testCreateCardOfferWithMaxPaymentCount()
    {
        $this->startTest();
    }

//    public function testOfferPrivateAuth()
//    {
//        $this->fixtures->merchant->addFeatures(['offer_private_auth']);
//
//        $this->ba->privateAuth();
//
//        $this->startTest();
//    }
//
//    public function testOfferPrivateAuthWithoutFeature()
//    {
//        $this->ba->privateAuth();
//
//        $this->startTest();
//    }

    public function testOfferCreateBulk()
    {
        $this->ba->adminAuth();

        $this->startTest();

        $offers = $this->getDbEntities('offer');

        $this->assertEquals('100000Razorpay', $offers[1]->getMerchantId());
        $this->assertEquals('10000000000000', $offers[0]->getMerchantId());
    }

    public function testBulkDeactivateOffer()
    {

        $offer1 = $this->fixtures->create('offer:card');
        $offer2 = $this->fixtures->create('offer:wallet');
        $invalidOffer = 'invalid_offer_id';
        $offersArray = [$offer1->getPublicId(),$offer2->getPublicId(),$invalidOffer];

        $response = new Core();
        $response = $response->bulkDeactivateOffers($offersArray);

        $this->assertEquals(2, count($response['successful']));
        $this->assertEquals(1, count($response['failed']));
        $this->assertEquals($offer1->getPublicId(), $response['successful'][0]);
        $this->assertEquals($offer2->getPublicId(), $response['successful'][1]);
        $this->assertEquals($invalidOffer, $response['failed'][0]);


    }

    public function testCreateCardOfferWithLinkedOfferIds()
    {
        $offer1 = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['content']['linked_offer_ids'] = (array) $offer1->getPublicId();

        $this->testData[__FUNCTION__]['response']['content'][0]['linked_offer_ids'] = (array) $offer1->getId();

        $offer2 = $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        (new Core())->bulkDeactivateOffers([$offer1->getPublicId(), 'offer_' . $offer2[0]['id']]);
    }

    public function testCreateCardOfferWithInvalidLinkedOfferIds()
    {
        $offer = $this->fixtures->create('offer:card', [
            'merchant_id' => '100000Razorpay'
        ]);

        $this->testData[__FUNCTION__]['request']['content']['linked_offer_ids'] = (array) $offer->getPublicId();

        $this->startTest();
    }

    public function testCreateWalletOffer()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');

        $this->startTest();
    }

    public function testCreateNetbankingOffer()
    {
        $this->startTest();
    }

    public function testCreateFlatCashbackOffer()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');

        $this->startTest();
    }

    public function testCreateIdenticalOffers()
    {
        $this->markTestSkipped();
        $offer = $this->fixtures->create('offer:card');

        $this->startTest();
    }

    public function testCreateOfferWithoutCashbackCriteria()
    {
        $this->startTest();
    }

    public function testCreateCardOfferWithInvalidNetwork()
    {
        $this->startTest();
    }

    public function testCreateCardOfferWithUnsupportedNetwork()
    {
        $this->startTest();
    }

    public function testCreateWalletOfferWithInvalidWallet()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');

        $this->startTest();
    }

    public function testCreateNetbankingOfferWithInvalidBankCode()
    {
        $this->startTest();
    }

    public function testCreateOfferWithInvalidPaymentMethod()
    {
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');

        $this->startTest();
    }

    public function testCreateOfferWithInvalidIssuer()
    {
        $this->startTest();
    }

    public function testCreateOfferWithPercentRateAndFlatCashback()
    {
        $this->startTest();
    }

    public function testCreateOfferWithInvalidOfferPeriod()
    {
        $this->startTest();
    }

    public function testCreateNCEmiSubventionOffer()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->startTest();
    }

    public function testEmiSubventionOfferWithInvalidAmount()
    {
        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->startTest();
    }

    public function testEmiSubventionWithDuration()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $this->startTest();
    }

    public function testEmiSubventionWithIssuerAndNetwork()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->startTest();
    }

    public function testOfferWithInvalidIssuer()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->startTest();
    }

    public function testInvalidEmiDuration()
    {
        $this->startTest();
    }

    public function testCreateOfferWithCorporateOrRetailIssuer()
    {
        $this->startTest();
    }

    public function testCreateOfferValidateMaxCashback()
    {
        $this->startTest();
    }

    public function testConflictingEmiSubOffers()
    {
        $this->markTestSkipped();
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->create('offer:emi_subvention', [
            'payment_method_type'=>'credit',
            'emi_durations' => [6]
        ]);

        $this->startTest();
    }

    public function testCreateOfferBajaj()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $this->startTest();
    }

    public function testCreateOfferInternationalEmi()
    {
        $this->fixtures->merchant->enableEmi();
        $this->startTest();
    }

    public function testCreateOfferValidateMethodType()
    {
        $this->startTest();
    }

    public function testAddIinsToCardOffer()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $offer->getPublicId();

        $this->startTest();
    }

    public function testAddIinsInvalidFormat()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->startTest();
    }

    public function testAddIinsToNonCardOffer()
    {
        $offer = $this->fixtures->create('offer:wallet');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->startTest();
    }

    public function testUpdateExistingOffer()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $offer->getPublicId();

        $this->startTest();
    }

    public function testUpdateWalletOfferWithMaxPaymentCount()
    {
        $offer = $this->fixtures->create('offer:wallet');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->startTest();
    }

    public function testUpdateCardOfferWithNoMaxPaymentCount()
    {
        $offer1 = $this->fixtures->create('offer:card');

        $offer2 = $this->fixtures->create('offer:card', [
            'max_payment_count' => null,
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer2->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['linked_offer_ids'] = (array) $offer1->getPublicId();

        $this->startTest();
    }

    public function testUpdateCardOfferWithInvalidLinkedOfferIds()
    {
        $offer1 = $this->fixtures->create('offer:card', [
            'merchant_id' => '100000Razorpay'
        ]);

         $offer2 = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer2->getPublicId();

        $this->testData[__FUNCTION__]['request']['content']['linked_offer_ids'] = (array) $offer1->getPublicId();

        $this->startTest();
    }

    public function testGetMultipleOffers()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->startTest();
    }

    public function testFetchOfferById()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $offer->getPublicId();

        $this->startTest();
    }

    public function testFetchSubscriptionOfferById()
    {
        $this->mockRazorX(__FUNCTION__, 'offer_on_subscription', 'on');

        $offer = $this->fixtures->create('offer:card',
            [
                'active'       => 1,
                'product_type' => 'subscription',
            ]);

        $subOffer = $this->fixtures->create('subscription_offers_master', [
            'redemption_type' => 'cycle',
            'applicable_on'   => 'both',
            'no_of_cycles'    => 10,
            'offer_id'        => $offer->getId(),
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $offer->getPublicId();

        $this->startTest();
    }

    public function testDeactivateOffer()
    {
        $offer = $this->fixtures->create('offer:card');

        $this->testData[__FUNCTION__]['request']['url'] = '/offers/' . $offer->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $offer->getPublicId();

        $this->startTest();
    }

    public function testDeactivateAllOffer()
    {
        $offer = $this->fixtures->create('offer:expired');

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__]['response']['content'] = [$offer->getPublicId()];

        $this->startTest();
    }

    public function testCreateOfferValidateMerchant()
    {
        $this->fixtures->merchant->disableCard('10000000000000');

        $this->startTest();
    }

    public function testCreateCardOfferWithInvalidIinLength()
    {
        $this->markTestSkipped("marking this as skipped because we now offer creation for more than 6 digit iins too.");
        $this->startTest();
    }

    public function testCreateCardOfferWithInvalidFullNetworkName()
    {
        $this->startTest();
    }

    public function testCreateOfferMinAmount()
    {
        $this->startTest();
    }

    public function testCreateOfferWithSameIIN()
    {
        $testData = $this->testData[__FUNCTION__];

        $offer = $this->runRequestResponseFlow($testData);

        (new Core())->bulkDeactivateOffers(['offer_' . $offer[0]['id']]);

    }

    //The following test case is not related to offer, but adding it here because it has been tested for the get offers route
    public function testDbRequestsBeforeMigrationMetric()
    {
        Trace::shouldReceive('histogram')->zeroOrMoreTimes();

        Trace::shouldReceive('info', 'debug', 'addRecord', 'error')->zeroOrMoreTimes();

        Trace::shouldReceive('traceException')->zeroOrMoreTimes();

        $actualData = [];

        Trace::shouldReceive('count')->andReturnUsing(function ($metric, $data, $count = 1) use (&$actualData)
        {
            $data['count'] = $count;
            $actualData[$metric] = $data;
        });

        $offer = $this->fixtures->create('offer:card');
        $this->startTest();
        App::forgetInstance(DbRequestsBeforeMigrationMetric::class);

        $this->assertArrayHasKey('db_requests_before_migration', $actualData);
        $this->assertEquals('offer_fetch_multiple', $actualData['db_requests_before_migration']['route']);
        $this->assertEquals('offer', $actualData['db_requests_before_migration']['table_name']);
        $this->assertEquals('read', $actualData['db_requests_before_migration']['action']);
        $this->assertGreaterThanOrEqual(1, $actualData['db_requests_before_migration']['count']);

    }

    public function testCreateCardlessEmiOfferWithIssuer()
    {
        $this->fixtures->merchant->enableCardlessEmi(Account::TEST_ACCOUNT);
        $this->startTest();
    }

    public function testCreateCardlessEmiOfferWithoutIssuer()
    {
        $this->fixtures->merchant->enableCardlessEmi(Account::TEST_ACCOUNT);
        $this->startTest();
    }

    public function testAdminFetchOffer()
    {
        $offersEngineMock = Mockery::mock('RZP\Services\OffersEngine', [$this->app])
                                         ->makePartial()
                                         ->shouldAllowMockingProtectedMethods();

        $offersEngineMock->shouldReceive('sendRequest')
                         ->andReturnUsing(
                             function(string $endpoint,
                                      string $method,
                                      array  $data = [],
                                      bool   $throwExceptionOnFailure = true,
                                      int    $timeout = 60
                             ) {

                                 self::assertEquals('v1/admin/offers?page_size=20&page=1&channel=CHANNEL_RZP_CHECKOUT', $endpoint);
                                 self::assertEquals("GET", $method);

                                 self::assertTrue($throwExceptionOnFailure);

                                 $testOffersEngineOfferEntity1 = [
                                     'metadata' => [
                                         'offer_id'      => 'offer_10000000000000',
                                         'name'          => 'Test Offer',
                                         'display_name'  => 'Test Offer',
                                         'description'   => 'Some more details',
                                         'terms'         => [
                                             'tnc' => 'Some more details',
                                         ],
                                         'advertiser_id' => 'rzp.merchant.10000000000000', // Replace with the actual advertiser ID
                                         'created_by'    => 'rzp_merchant',
                                         'state'         => 'STATE_CREATED',
                                         'offer_on'      => 'BENEFICIARY_TYPE_SELF',
                                         'currency'      => 'INR',
                                         'schedules'     => [
                                             'starts_at' => 1514764800,
                                             'ends_at'   => 1546300800,
                                         ],
                                     ],
                                     'spec'     => [
                                         'allowed_channels' => [
                                             'CHANNEL_RZP_CHECKOUT',
                                         ],
                                         'funding'          => [
                                             'type'  => 'BENEFICIARY_TYPE_SELF',
                                             'split' => [
                                                 [
                                                     'type'   => 'VALUE_OPTION_PERCENTAGE',
                                                     'bearer' => 'USER_TYPE_PUBLISHER',
                                                     'value'  => 100,
                                                 ],
                                             ],
                                         ],
                                         'benefits_types'   => [
                                             'BENEFIT_TYPE_DISCOUNT',
                                         ],
                                         'rule_groups'      => [
                                             'CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER' => [
                                                 'rules' => [
                                                     [
                                                         'when_expression' => 'true',
                                                         'then'            => [
                                                             [
                                                                 'discount' => [
                                                                     [
                                                                         'percent_discount' => 1000,
                                                                         'applicable_on'    => 'Order.total_amount',
                                                                     ],
                                                                 ],
                                                             ],
                                                         ],
                                                     ],
                                                 ],
                                             ],
                                             'CHANNEL_RZP_CHECKOUT.STAGE_AVAIL'    => [
                                                 'rules' => [
                                                     [
                                                         'when_expression' => 'true && PaymentInstrument.Method == \"card\" && PaymentInstrument.Iin in [\"411111\"]',
                                                         'then'            => [
                                                             [
                                                                 'discount' => [
                                                                     [
                                                                         'percent_discount' => 1000,
                                                                         'applicable_on'    => 'Order.total_amount',
                                                                     ],
                                                                 ],
                                                             ],
                                                         ],
                                                     ],
                                                 ],
                                             ],
                                         ],
                                     ],
                                 ];
                                 $testOffersEngineOfferEntity2 = $testOffersEngineOfferEntity1;
                                 $testOffersEngineOfferEntity2['metadata']['offer_id'] = 'offer_10000000000001';
                                 $testOffersEngineOfferEntity2['metadata']['advertiser_id'] = '8K4v0EqHDl342o';

                                 return [
                                     'offers'           => [
                                         $testOffersEngineOfferEntity1,
                                         $testOffersEngineOfferEntity2
                                     ],
                                     'offer_publishers' => [
                                         [
                                             'offer_id'                => '10000000000001',
                                             'publisher_id'            => 'IE4v1EwHDl342o',
                                             'channel_name'            => 'CHANNEL_RZP_CHECKOUT',
                                             'starts_at'               => 1514764800,
                                             'ends_at'                 => 1546300800,
                                             'state'                   => 'STATE_PUBLISHED',
                                             'continue_txn_on_failure' => false,
                                             'offer_type'              => 'OFFER_TYPE_STAGE_HIDDEN',
                                             'auto_apply'              => false,
                                             'published_at'            => 0,
                                         ],
                                         [
                                             'offer_id'                => '10000000000000',
                                             'publisher_id'            => '8K4v0EqHDl342o',
                                             'channel_name'            => 'CHANNEL_RZP_CHECKOUT',
                                             'starts_at'               => 1514764800,
                                             'ends_at'                 => 1546300800,
                                             'state'                   => 'STATE_PUBLISHED',
                                             'continue_txn_on_failure' => true,
                                             'offer_type'              => 'OFFER_TYPE_STAGE_REGULAR',
                                             'auto_apply'              => false,
                                             'published_at'            => 0,
                                         ]
                                     ],
                                     'total_offers'     => 2,
                                     'page'             => 1,
                                     'page_size'        => 20
                                 ];
                             }
                         )->times(1);

        $this->app['offers_engine'] = $offersEngineMock;

        $this->ba->adminAuth('live');

        $response = $this->startTest();

        $this->assertTrue(is_array($response));

        $this->assertNotEmpty($response);
    }

    public function testAdminFetchOfferWithMerchantId()
    {
        $offersEngineMock = Mockery::mock('RZP\Services\OffersEngine', [$this->app])
                                   ->makePartial()
                                   ->shouldAllowMockingProtectedMethods();

        $offersEngineMock->shouldReceive('sendRequest')
                         ->andReturnUsing(
                             function(string $endpoint,
                                      string $method,
                                      array  $data = [],
                                      bool   $throwExceptionOnFailure = true,
                                      int    $timeout = 60
                             ) {

                                 self::assertEquals('v1/admin/offers?page_size=20&page=1&publisher_id=rzp.merchant.8K4v0EqHDl342o&channel=CHANNEL_RZP_CHECKOUT', $endpoint);
                                 self::assertEquals("GET", $method);

                                 self::assertTrue($throwExceptionOnFailure);

                                 return [
                                     'offers'           => [
                                         [
                                             'metadata' => [
                                                 'offer_id'      => 'offer_10000000000000',
                                                 'name'          => 'Test Offer',
                                                 'display_name'  => 'Test Offer',
                                                 'description'   => 'Some more details',
                                                 'terms'         => [
                                                     'tnc' => 'Some more details',
                                                 ],
                                                 'advertiser_id' => 'rzp.merchant.8K4v0EqHDl342o', // Replace with the actual advertiser ID
                                                 'created_by'    => 'rzp_merchant',
                                                 'state'         => 'STATE_CREATED',
                                                 'offer_on'      => 'BENEFICIARY_TYPE_SELF',
                                                 'currency'      => 'INR',
                                                 'schedules'     => [
                                                     'starts_at' => 1514764800,
                                                     'ends_at'   => 1546300800,
                                                 ],
                                             ],
                                             'spec'     => [
                                                 'allowed_channels' => [
                                                     'CHANNEL_RZP_CHECKOUT',
                                                 ],
                                                 'funding'          => [
                                                     'type'  => 'BENEFICIARY_TYPE_SELF',
                                                     'split' => [
                                                         [
                                                             'type'   => 'VALUE_OPTION_PERCENTAGE',
                                                             'bearer' => 'USER_TYPE_PUBLISHER',
                                                             'value'  => 100,
                                                         ],
                                                     ],
                                                 ],
                                                 'benefits_types'   => [
                                                     'BENEFIT_TYPE_DISCOUNT',
                                                 ],
                                                 'rule_groups'      => [
                                                     'CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER' => [
                                                         'rules' => [
                                                             [
                                                                 'when_expression' => 'true',
                                                                 'then'            => [
                                                                     [
                                                                         'discount' => [
                                                                             [
                                                                                 'percent_discount' => 1000,
                                                                                 'applicable_on'    => 'Order.total_amount',
                                                                             ],
                                                                         ],
                                                                     ],
                                                                 ],
                                                             ],
                                                         ],
                                                     ],
                                                     'CHANNEL_RZP_CHECKOUT.STAGE_AVAIL'    => [
                                                         'rules' => [
                                                             [
                                                                 'when_expression' => 'true && PaymentInstrument.Method == \"card\" && PaymentInstrument.Iin in [\"411111\"]',
                                                                 'then'            => [
                                                                     [
                                                                         'discount' => [
                                                                             [
                                                                                 'percent_discount' => 1000,
                                                                                 'applicable_on'    => 'Order.total_amount',
                                                                             ],
                                                                         ],
                                                                     ],
                                                                 ],
                                                             ],
                                                         ],
                                                     ],
                                                 ],
                                             ],
                                         ]
                                     ],
                                     'offer_publishers' => [
                                         [
                                             'offer_id'                => '10000000000000',
                                             'publisher_id'            => '8K4v0EqHDl342o',
                                             'channel_name'            => 'CHANNEL_RZP_CHECKOUT',
                                             'starts_at'               => 1514764800,
                                             'ends_at'                 => 1546300800,
                                             'state'                   => 'STATE_PUBLISHED',
                                             'continue_txn_on_failure' => false,
                                             'offer_type'              => 'OFFER_TYPE_STAGE_REGULAR',
                                             'auto_apply'              => false,
                                             'published_at'            => 0,
                                         ],
                                     ],
                                     'total_offers'     => 1,
                                     'page'             => 1,
                                     'page_size'        => 20
                                 ];
                             }
                         )->times(1);

        $this->app['offers_engine'] = $offersEngineMock;

        $this->ba->adminAuth('live');

        $response = $this->startTest();

        $this->assertTrue(is_array($response));

        $this->assertNotEmpty($response);
    }

    public function testAdminFetchOfferById()
    {
        $offersEngineMock = Mockery::mock('RZP\Services\OffersEngine', [$this->app])
                                   ->makePartial()
                                   ->shouldAllowMockingProtectedMethods();

        $offersEngineMock->shouldReceive('sendRequest')
                         ->andReturnUsing(
                             function(string $endpoint,
                                      string $method,
                                      array  $data = [],
                                      bool   $throwExceptionOnFailure = true,
                                      int    $timeout = 60
                             ) {

                                 self::assertEquals('v1/admin/offers?page_size=1&page=1&channel=CHANNEL_RZP_CHECKOUT&offer_ids=offer_10000000000000', $endpoint);
                                 self::assertEquals("GET", $method);

                                 self::assertTrue($throwExceptionOnFailure);

                                 return [
                                     'offers'           => [
                                         [
                                             'metadata' => [
                                                 'offer_id'      => 'offer_10000000000000',
                                                 'name'          => 'Test Offer',
                                                 'display_name'  => 'Test Offer',
                                                 'description'   => 'Some more details',
                                                 'terms'         => [
                                                     'tnc' => 'Some more details',
                                                 ],
                                                 'advertiser_id' => 'rzp.merchant.10000000000000', // Replace with the actual advertiser ID
                                                 'created_by'    => 'rzp_merchant',
                                                 'state'         => 'STATE_CREATED',
                                                 'offer_on'      => 'BENEFICIARY_TYPE_SELF',
                                                 'currency'      => 'INR',
                                                 'schedules'     => [
                                                     'starts_at' => 1514764800,
                                                     'ends_at'   => 1546300800,
                                                 ],
                                             ],
                                             'spec'     => [
                                                 'allowed_channels' => [
                                                     'CHANNEL_RZP_CHECKOUT',
                                                 ],
                                                 'funding'          => [
                                                     'type'  => 'BENEFICIARY_TYPE_SELF',
                                                     'split' => [
                                                         [
                                                             'type'   => 'VALUE_OPTION_PERCENTAGE',
                                                             'bearer' => 'USER_TYPE_PUBLISHER',
                                                             'value'  => 100,
                                                         ],
                                                     ],
                                                 ],
                                                 'benefits_types'   => [
                                                     'BENEFIT_TYPE_DISCOUNT',
                                                 ],
                                                 'rule_groups'      => [
                                                     'CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER' => [
                                                         'rules' => [
                                                             [
                                                                 'when_expression' => 'true',
                                                                 'then'            => [
                                                                     [
                                                                         'discount' => [
                                                                             [
                                                                                 'percent_discount' => 1000,
                                                                                 'applicable_on'    => 'Order.total_amount',
                                                                             ],
                                                                         ],
                                                                     ],
                                                                 ],
                                                             ],
                                                         ],
                                                     ],
                                                     'CHANNEL_RZP_CHECKOUT.STAGE_AVAIL'    => [
                                                         'rules' => [
                                                             [
                                                                 'when_expression' => 'true && PaymentInstrument.Method == \"card\" && PaymentInstrument.Iin in [\"411111\"]',
                                                                 'then'            => [
                                                                     [
                                                                         'discount' => [
                                                                             [
                                                                                 'percent_discount' => 1000,
                                                                                 'applicable_on'    => 'Order.total_amount',
                                                                             ],
                                                                         ],
                                                                     ],
                                                                 ],
                                                             ],
                                                         ],
                                                     ],
                                                 ],
                                             ],
                                         ],
                                     ],
                                     'offer_publishers' => [
                                         [
                                             'offer_id'                => '10000000000000',
                                             'publisher_id'            => '10000000000pub',
                                             'channel_name'            => 'CHANNEL_RZP_CHECKOUT',
                                             'starts_at'               => 1514764800,
                                             'ends_at'                 => 1546300800,
                                             'state'                   => 'STATE_PUBLISHED',
                                             'continue_txn_on_failure' => false,
                                             'offer_type'              => 'OFFER_TYPE_STAGE_REGULAR',
                                             'auto_apply'              => false,
                                             'published_at'            => 0,
                                         ],
                                     ],
                                     'total_offers'     => 1,
                                     'page'             => 1,
                                     'page_size'        => 1
                                 ];
                             }
                         )->times(1);

        $this->app['offers_engine'] = $offersEngineMock;

        $this->ba->adminAuth('live');

        $response = $this->startTest();

        $this->assertTrue(is_array($response));

        $this->assertNotEmpty($response);
    }

    public function testAdminFetchOfferByIdFailure()
    {
        $offersEngineMock = Mockery::mock('RZP\Services\OffersEngine', [$this->app])
                                   ->makePartial()
                                   ->shouldAllowMockingProtectedMethods();

        $offersEngineMock->shouldReceive('sendRequest')
                         ->andReturnUsing(
                             function(string $endpoint,
                                      string $method,
                                      array  $data = [],
                                      bool   $throwExceptionOnFailure = true,
                                      int    $timeout = 60
                             ) {

                                 self::assertEquals('v1/admin/offers?page_size=1&page=1&channel=CHANNEL_RZP_CHECKOUT&offer_ids=offer_10000000000000', $endpoint);
                                 self::assertEquals("GET", $method);

                                 self::assertTrue($throwExceptionOnFailure);

                                 return [
                                     'offers'           => [
                                         [
                                             'metadata' => [
                                                 'offer_id'      => 'offer_10000000000000',
                                                 'name'          => 'Test Offer',
                                                 'display_name'  => 'Test Offer',
                                                 'description'   => 'Some more details',
                                                 'terms'         => [
                                                     'tnc' => 'Some more details',
                                                 ],
                                                 'advertiser_id' => 'rzp.merchant.10000000000000', // Replace with the actual advertiser ID
                                                 'created_by'    => 'rzp_merchant',
                                                 'state'         => 'STATE_CREATED',
                                                 'offer_on'      => 'BENEFICIARY_TYPE_SELF',
                                                 'currency'      => 'INR',
                                                 'schedules'     => [
                                                     'starts_at' => 1514764800,
                                                     'ends_at'   => 1546300800,
                                                 ],
                                             ],
                                             'spec'     => [
                                                 'allowed_channels' => [
                                                     'CHANNEL_RZP_CHECKOUT',
                                                 ],
                                                 'funding'          => [
                                                     'type'  => 'BENEFICIARY_TYPE_SELF',
                                                     'split' => [
                                                         [
                                                             'type'   => 'VALUE_OPTION_PERCENTAGE',
                                                             'bearer' => 'USER_TYPE_PUBLISHER',
                                                             'value'  => 100,
                                                         ],
                                                     ],
                                                 ],
                                                 'benefits_types'   => [
                                                     'BENEFIT_TYPE_DISCOUNT',
                                                 ],
                                                 'rule_groups'      => [
                                                     'CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER' => [
                                                         'rules' => [
                                                             [
                                                                 'when_expression' => 'true',
                                                                 'then'            => [
                                                                     [
                                                                         'discount' => [
                                                                             [
                                                                                 'percent_discount' => 1000,
                                                                                 'applicable_on'    => 'Order.total_amount',
                                                                             ],
                                                                         ],
                                                                     ],
                                                                 ],
                                                             ],
                                                         ],
                                                     ],
                                                     'CHANNEL_RZP_CHECKOUT.STAGE_AVAIL'    => [
                                                         'rules' => [
                                                             [
                                                                 'when_expression' => 'true && PaymentInstrument.Method == \"card\" && PaymentInstrument.Iin in [\"411111\"]',
                                                                 'then'            => [
                                                                     [
                                                                         'discount' => [
                                                                             [
                                                                                 'percent_discount' => 1000,
                                                                                 'applicable_on'    => 'Order.total_amount',
                                                                             ],
                                                                         ],
                                                                     ],
                                                                 ],
                                                             ],
                                                         ],
                                                     ],
                                                 ],
                                             ],
                                         ]
                                     ],
                                     'offer_publishers' => [],
                                     'total_offers'     => 1,
                                     'page'             => 1,
                                     'page_size'        => 1
                                 ];
                             }
                         )->times(1);

        $this->app['offers_engine'] = $offersEngineMock;

        $this->ba->adminAuth('live');

        $this->startTest();
    }

    public function testAdminFetchOfferByInvalidIdValidationFailure()
    {
        $offersEngineMock = Mockery::mock('RZP\Services\OffersEngine', [$this->app])
                                   ->makePartial()
                                   ->shouldAllowMockingProtectedMethods();

        $offersEngineMock->shouldReceive('sendRequest')
                         ->times(0);

        $this->app['offers_engine'] = $offersEngineMock;

        $this->ba->adminAuth('live');

        $this->startTest();
    }

    public function testFetchOffersDiscountForSubscription()
    {
        $offer = $this->fixtures->create('offer', [
            'merchant_id'    => '10000000000000',
            'starts_at'      => 1414764800,
            'product_type'   => 'subscription',
            'payment_method' => 'upi',
        ]);

        $subOffer = $this->fixtures->create('subscription_offers_master', [
            'redemption_type' => 'cycle',
            'applicable_on'   => 'both',
            'no_of_cycles'    => 10,
            'offer_id'        => $offer->getId(),
        ]);

        $payment = $this->fixtures->payment->create(
            [
                'merchant_id'     => '10000000000000',
                'amount'          => 1000,
                'currency'        => 'INR',
                'method'          => 'upi',
                'status'          => 'captured',
                'bank'            => 'BARB',
                'gateway'         => 'hdfc_debit_emi',
                'captured_at'     => Carbon::now(Timezone::IST)->getTimestamp(),
                'reference2'      => '1038203',
                'subscription_id' => $subOffer->getId(),
            ]
        );

        $this->testData[__FUNCTION__]['request']['content']['offer']           = 'offer_' . $offer->getId();
        $this->testData[__FUNCTION__]['request']['content']['payment_id']      = $payment->getId();
        $this->testData[__FUNCTION__]['request']['content']['subscription_id'] = $subOffer->getId();

        $this->startTest();
    }

    public function getOffersEngineMockResponse(): array
    {
        return [
            'offer'   => [
                'metadata' => [
                    'offer_id'      => 'offer_10000000000000',
                    'name'          => 'Test Offer',
                    'display_name'  => 'Test Offer',
                    'description'   => 'Some more details',
                    'terms'         => [
                        'tnc' => 'Some more details',
                    ],
                    'advertiser_id' => 'rzp.merchant.10000000000000', // Replace with the actual advertiser ID
                    'state'         => 'STATE_CREATED',
                    'offer_on'      => 'BENEFICIARY_TYPE_SELF',
                    'currency'      => 'INR',
                    'schedules'     => [
                        'starts_at' => 1514764800,
                        'ends_at'   => 1546300800,
                    ],
                ],
                'spec'     => [
                    'allowed_channels' => [
                        'CHANNEL_RZP_CHECKOUT',
                    ],
                    'funding'          => [
                        'type'  => 'BENEFICIARY_TYPE_SELF',
                        'split' => [
                            [
                                'type'   => 'VALUE_OPTION_PERCENTAGE',
                                'bearer' => 'USER_TYPE_PUBLISHER',
                                'value'  => 100,
                            ],
                        ],
                    ],
                    'benefits_types'   => [
                        'BENEFIT_TYPE_DISCOUNT',
                    ],
                    'rule_groups'      => [
                        'CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER' => [
                            'rules' => [
                                [
                                    'when_expression' => 'true',
                                    'then'            => [
                                        [
                                            'discount' => [
                                                'percent_discount' => 1000,
                                                'applicable_on'    => 'Order.total_amount',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'CHANNEL_RZP_CHECKOUT.STAGE_AVAIL'    => [
                            'rules' => [
                                [
                                    'when_expression' => 'true && PaymentInstrument.Method == \"card\" && PaymentInstrument.CardType == \"credit\" && PaymentInstrument.CardNetwork == \"VISA\" && PaymentInstrument.Issuer == \"HDFC\"',
                                    'then'            => [
                                        [
                                            'discount' => [
                                                [
                                                    'percent_discount' => 1000,
                                                    'applicable_on'    => 'Order.total_amount',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'publish' => [
                'continue_txn_on_failure' => 0,
                'offer_type'              => 'OFFER_TYPE_STAGE_REGULAR',
                'channel_name'            => 'CHANNEL_RZP_CHECKOUT',
                'total_usage'             => "200",
            ]
        ];
    }
    public function testFetchOffersCreateInfoWithoutEmiPlans()
    {
        $this->ba->offersEngineAuth();

        $this->startTest();
    }

    public function testFetchOffersCreateInfoWithoutMethodsAndWithoutEmiPlans()
    {
        $this->ba->offersEngineAuth();

        $response = $this->startTest();

        $this->assertEmpty($response);
    }

    public function testFetchOffersCreateInfoWithoutMethods()
    {
        $this->fixtures->create('emi_plan', ['bank' => 'HDFC', 'duration' => '6', 'rate' => '1399']);

        $this->ba->offersEngineAuth();

        $this->startTest();
    }

    public function testFetchOffersCreateInfoSuccess()
    {
        $this->fixtures->create('emi_plan', ['bank' => 'HDFC', 'duration' => '6', 'rate' => '1399']);

        $this->ba->offersEngineAuth();

        $this->startTest();
    }
    public function testFetchOffersCreateInfoMerchantNotFound()
    {
        $this->ba->offersEngineAuth();

        $this->startTest();
    }
    public function testFetchOffersCreateInfoMerchantMethodsNotFound()
    {
        $this->ba->offersEngineAuth();

        $this->startTest();
    }

    public function testFetchOffersWithGlobalLimitsFromOE()
    {
        $this->markTestSkipped("marking this as skipped due to concurrency issues with config keys.")

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => true,
                ConfigKey::OFFERS_ENGINE_SERVICE_ENABLED => true,
            ]);

        $offerResponse = [
            'offer'            => [
                'metadata' => [
                    'offer_id'      => 'offer_10000000000000',
                    'name'          => 'Test Offer',
                    'display_name'  => 'Test Offer',
                    'advertiser_id' => 'rzp.merchant.10000000000000',
                    'created_by'    => 'rzp_merchant',
                    'state'         => 'STATE_CREATED',
                    'offer_on'      => 'BENEFICIARY_TYPE_SELF',
                    'currency'      => 'INR',
                    'schedules'     => [
                        'starts_at' => 1514764800,
                        'ends_at'   => 1546300800,
                    ],
                ],
                'spec'     => [
                    'allowed_channels' => [
                        'CHANNEL_RZP_CHECKOUT',
                    ],
                    'funding'          => [
                        'type'  => 'BENEFICIARY_TYPE_SELF',
                        'split' => [
                            [
                                'type'   => 'VALUE_OPTION_PERCENTAGE',
                                'bearer' => 'USER_TYPE_PUBLISHER',
                                'value'  => 100,
                            ],
                        ],
                    ],
                    'benefits_types'   => [
                        'BENEFIT_TYPE_DISCOUNT',
                    ],
                    "usage_limits"     => [
                        [
                            "maximum_value" => 300,
                            "on"            => "LIMIT_ON_OFFER",
                            "limit_type"    => "LIMIT_TYPE_COUNT"
                        ]
                    ],
                    'rule_groups'      => [
                        'CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER' => [
                            'rules' => [
                                [
                                    'when_expression' => 'true',
                                    'then'            => [
                                        [
                                            'discount' => [
                                                'percent_discount' => 1000,
                                                'applicable_on'    => 'Order.total_amount',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'CHANNEL_RZP_CHECKOUT.STAGE_AVAIL'    => [
                            'rules' => [
                                [
                                    'when_expression' => 'true && PaymentInstrument.Method == \"card\" && PaymentInstrument.CardType == \"credit\" && PaymentInstrument.CardNetwork == \"VISA\" && PaymentInstrument.Issuer == \"HDFC\"',
                                    'then'            => [
                                        [
                                            'discount' => [
                                                [
                                                    'percent_discount' => 1000,
                                                    'applicable_on'    => 'Order.total_amount',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'offer_publishers' => [
                [
                    'continue_txn_on_failure' => 0,
                    'offer_type'              => 'OFFER_TYPE_STAGE_REGULAR',
                    'channel_name'            => 'CHANNEL_RZP_CHECKOUT',
                    'total_usage'             => '200',
                ]
            ]
        ];

        $OffersEngineMock = Mockery::mock(OffersEngine::class, [$this->app])
                                   ->shouldAllowMockingProtectedMethods()->makePartial();

        $OffersEngineMock->shouldReceive('sendRequest')
                         ->andReturnUsing(function(string $endpoint,
                                                   string $method,
                                                   array  $data = [],
                                                   bool   $throwExceptionOnFailure = true,
                                                   int    $timeout = 60
                         ) use ($offerResponse) {
                             self::assertEquals('v1/offers/offer_10000000000000?publisher_id=rzp.merchant.10000000000000', $endpoint);
                             self::assertEquals("GET", $method);

                             self::assertTrue($throwExceptionOnFailure);

                             return $offerResponse;
                         })->times(1);

        $this->app->instance('offers_engine', $OffersEngineMock);

        $this->startTest();

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => false,
                ConfigKey::OFFERS_ENGINE_SERVICE_ENABLED => false,
            ]);
    }

    public function testFetchDefaultOffersAndTestCache()
    {
        $this->markTestSkipped("marking this as skipped due to concurrency issues with config keys.");

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => true,
                ConfigKey::OFFERS_ENGINE_SERVICE_ENABLED => true,
            ]);

        $offersEngineMock = Mockery::mock('RZP\Services\OffersEngine', [$this->app])
                                   ->makePartial()
                                   ->shouldAllowMockingProtectedMethods();

        $offersEngineMock->shouldReceive('sendRequest')
                         ->andReturnUsing(
                             function(string $endpoint,
                                      string $method,
                                      array  $data = [],
                                      bool   $throwExceptionOnFailure = true,
                                      int    $timeout = 60
                             ) {

                                 self::assertEquals('v1/offers?offer_type=OFFER_TYPE_STAGE_REGULAR&' .
                                                    'status=STATUS_ACTIVE&publisher_id=rzp.merchant.10000000000000&' .
                                                    'channel=CHANNEL_RZP_CHECKOUT&page_size=200&page=1', $endpoint);

                                 self::assertEquals("GET", $method);

                                 self::assertTrue($throwExceptionOnFailure);

                                 return [
                                     'offers'           => [
                                         [
                                             'metadata' => [
                                                 'offer_id'      => 'offer_10000000000000',
                                                 'name'          => 'Test Offer',
                                                 'display_name'  => 'Test Offer',
                                                 'description'   => 'Some more details',
                                                 'terms'         => [
                                                     'tnc' => 'Some more details',
                                                 ],
                                                 'advertiser_id' => 'rzp.merchant.10000000000000', // Replace with the actual advertiser ID
                                                 'created_by'    => 'rzp_merchant',
                                                 'state'         => 'STATE_CREATED',
                                                 'offer_on'      => 'BENEFICIARY_TYPE_SELF',
                                                 'currency'      => 'INR',
                                                 'schedules'     => [
                                                     'starts_at' => 1514764800,
                                                     'ends_at'   => 1546300800,
                                                 ],
                                             ],
                                             'spec'     => [
                                                 'allowed_channels' => [
                                                     'CHANNEL_RZP_CHECKOUT',
                                                 ],
                                                 'funding'          => [
                                                     'type'  => 'BENEFICIARY_TYPE_SELF',
                                                     'split' => [
                                                         [
                                                             'type'   => 'VALUE_OPTION_PERCENTAGE',
                                                             'bearer' => 'USER_TYPE_PUBLISHER',
                                                             'value'  => 100,
                                                         ],
                                                     ],
                                                 ],
                                                 'benefits_types'   => [
                                                     'BENEFIT_TYPE_DISCOUNT',
                                                 ],
                                                 'rule_groups'      => [
                                                     'CHANNEL_RZP_CHECKOUT.STAGE_DISCOVER' => [
                                                         'rules' => [
                                                             [
                                                                 'when_expression' => 'true',
                                                                 'then'            => [
                                                                     [
                                                                         'discount' => [
                                                                             [
                                                                                 'percent_discount' => 1000,
                                                                                 'applicable_on'    => 'Order.total_amount',
                                                                             ],
                                                                         ],
                                                                     ],
                                                                 ],
                                                             ],
                                                         ],
                                                     ],
                                                     'CHANNEL_RZP_CHECKOUT.STAGE_AVAIL'    => [
                                                         'rules' => [
                                                             [
                                                                 'when_expression' => 'true && PaymentInstrument.Method == \"card\" && PaymentInstrument.Iin in [\"411111\"]',
                                                                 'then'            => [
                                                                     [
                                                                         'discount' => [
                                                                             [
                                                                                 'percent_discount' => 1000,
                                                                                 'applicable_on'    => 'Order.total_amount',
                                                                             ],
                                                                         ],
                                                                     ],
                                                                 ],
                                                             ],
                                                         ],
                                                     ],
                                                 ],
                                             ],
                                         ],
                                     ],
                                     'offer_publishers' => [
                                         [
                                             'offer_id'                => '10000000000000',
                                             'publisher_id'            => '10000000000pub',
                                             'channel_name'            => 'CHANNEL_RZP_CHECKOUT',
                                             'starts_at'               => 1514764800,
                                             'ends_at'                 => 1546300800,
                                             'state'                   => 'STATE_PUBLISHED',
                                             'continue_txn_on_failure' => false,
                                             'offer_type'              => 'OFFER_TYPE_STAGE_REGULAR',
                                             'auto_apply'              => false,
                                             'published_at'            => 0,
                                         ],
                                     ],
                                     'total_offers'     => 1,
                                     'page'             => 1,
                                     'page_size'        => 1
                                 ];
                             }
                         )->times(1);

        $this->app['offers_engine'] = $offersEngineMock;

        $initialResponse = (new Core())->fetchDefaultOffersForMerchant('10000000000000', true);

        $cachedResponse = (new Core())->fetchDefaultOffersForMerchant('10000000000000', true);

        $this->assertEquals(json_encode($initialResponse), json_encode($cachedResponse));

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::OFFERS_ENGINE_REVERSE_SHADOW_ENABLED => false,
                ConfigKey::OFFERS_ENGINE_SERVICE_ENABLED => false,
            ]);

        app('redis')->del('08d363be4174b2635591f700b08f58a43b0a94894999511f22fda05b212e6dca');
    }
}
