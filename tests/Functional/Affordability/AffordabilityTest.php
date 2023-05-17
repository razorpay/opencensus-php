<?php

namespace RZP\Tests\Functional\Affordability;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use RZP\Constants\Mode;
use RZP\Models\Emi\ProcessingFeePlan;
use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Offer\Entity as OfferEntity;
use RZP\Models\Offer\EntityOffer\Entity as EntityOfferEntity;
use RZP\Models\Order\ProductType;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Services\Dcs\Features\Constants as DcsConstants;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class AffordabilityTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $testDataFilePath = __DIR__ . '/helpers/AffordabilityTestData.php';

    private $expectedCardlessEmiResponse = [
        'earlysalary' => [
            'enabled' => true,
            'min_amount' => 300000,
        ],
        'zestmoney' => [
            'enabled' => true,
            'min_amount' => 9900,
        ],
        'barb' => [
            'enabled' => true,
            'min_amount' => 500000,
        ],
        'hdfc' => [
            'enabled' => true,
            'min_amount' => 500000,
        ],
        'kkbk' => [
            'enabled' => true,
            'min_amount' => 300000,
        ],
        'fdrl' => [
            'enabled' => true,
            'min_amount' => 500000,
        ],
        'idfb' => [
            'enabled' => true,
            'min_amount' => 500000,
        ],
        'icic' => [
            'enabled' => true,
            'min_amount' => 700000,
        ],
        'hcin' => [
            'enabled' => true,
            'min_amount' => 50000,
        ],
        'walnut369' => [
            'enabled' => true,
            'min_amount' => 90000,
        ],
        'sezzle' => [
            'enabled' => true,
            'min_amount' => 20000,
        ],
        'krbe' => [
            'enabled' => true,
            'min_amount' => 240000,
        ],
        'cshe' => [
            'enabled' => true,
            'min_amount' => 100000,
        ],
        'tvsc' => [
            'enabled' => true,
            'min_amount' => 300000,
        ],
    ];

    private $expectedPaylaterResponse = [
        'epaylater' => [
            'enabled' => true,
            'min_amount' => null,
        ],
        'getsimpl' => [
            'enabled' => true,
            'min_amount' => 100,
        ],
        'icic' => [
            'enabled' => true,
            'min_amount' => 100,
        ],
        'hdfc' => [
            'enabled' => true,
            'min_amount' => 100000,
        ],
        'kkbk' => [
            'enabled' => true,
            'min_amount' => 200000,
        ],
        'lazypay' => [
            'enabled' => true,
            'min_amount' => 100,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->affordabilityInternalAppAuth();

        $this->fixtures->merchant->addFeatures([Constants::AFFORDABILITY_WIDGET], Account::TEST_ACCOUNT);
    }

    public function testFeatureDisabledOnAffordabilityWidget()
    {
        $this->fixtures->merchant->removeFeatures([Constants::AFFORDABILITY_WIDGET]);
        $response = $this->startTest();
        $this->assertFalse($response['enabled']);
    }

    public function testFeatureEnabledOnAffordabilityWidget()
    {
        $response = $this->startTest();
        $this->assertTrue($response['enabled']);
    }

    public function testFeatureEnabledOnRzpAffordabilityWidget()
    {
        $this->fixtures->merchant->removeFeatures([Constants::AFFORDABILITY_WIDGET]);
        $this->fixtures->merchant->addFeatures([DcsConstants::AffordabilityWidgetSet], Account::TEST_ACCOUNT);
        $response = $this->startTest();
        $this->assertTrue($response['enabled']);
    }

    public function testPaylaterOnAffordabilityWidget()
    {
        $this->fixtures->merchant->enablePayLater(Account::TEST_ACCOUNT);
        $response = $this->startTest();

        $this->assertTrue($response['enabled']);
        $this->assertEquals($response['entities']['paylater']['providers'], $this->expectedPaylaterResponse);
    }

    public function testEmiOnAffordabilityWidget()
    {
        $this->fixtures->merchant->enableEmi(Account::TEST_ACCOUNT);
        $this->fixtures->merchant->enableCreditEmiProviders(['HDFC' => 1]);
        $this->fixtures->emiPlan->create([
            'id'          => '10101010101312',
            'merchant_id' => Account::TEST_ACCOUNT,
            'bank'        => 'HDFC',
            'type'        => 'credit',
            'rate'        => 1200,
            'min_amount'  => 300000,
            'duration'    => 3,
        ]);

        $expectedEmiResponse = [
            'HDFC' => [
                'values' => [
                    [
                        'duration' => 3,
                        'interest' => 12,
                        'subvention' => "customer",
                        'min_amount' => 300000,
                        'merchant_payback' => "5.18",
                        'processing_fee_plan' => (new ProcessingFeePlan())->getProcessingFeePlan('HDFC','credit' , '3')
                    ],
                ],
            ],
        ];

        $response = $this->startTest();

        $this->assertTrue($response['enabled']);
        $this->assertEquals($response['entities']['emi']['items'], $expectedEmiResponse);
    }

    public function testCardlessEmiOnAffordabilityWidget()
    {
        $this->fixtures->merchant->enableCardlessEmi(Account::TEST_ACCOUNT);

        $response = $this->startTest();

        $this->assertTrue($response['enabled']);
        $this->assertEquals($response['entities']['cardless_emi']['providers'], $this->expectedCardlessEmiResponse);
    }

    public function testAffordabilityWidgetSuite()
    {
        $this->fixtures->merchant->enablePaylater(Account::TEST_ACCOUNT);
        $this->fixtures->merchant->enableEmi(Account::TEST_ACCOUNT);
        $this->fixtures->merchant->enableCreditEmiProviders(['HDFC' => 1]);
        $this->fixtures->emiPlan->create([
            'id'          => '10101010101312',
            'merchant_id' => Account::TEST_ACCOUNT,
            'bank'        => 'HDFC',
            'type'        => 'credit',
            'rate'        => 1200,
            'min_amount'  => 300000,
            'duration'    => 3,
        ]);
        $this->fixtures->merchant->enableCardlessEmi(Account::TEST_ACCOUNT);

        $response = $this->startTest();

        $expectedEmiResponse = [
            'HDFC' => [
                'values' => [
                    [
                        'duration' => 3,
                        'interest' => 12,
                        'subvention' => "customer",
                        'min_amount' => 300000,
                        'merchant_payback' => "5.18",
                        'processing_fee_plan' => (new ProcessingFeePlan())->getProcessingFeePlan('HDFC','credit' , '3')
        ],
                ],
            ],
        ];

        $this->assertTrue($response['enabled']);
        $this->assertEquals($response['entities']['paylater']['providers'], $this->expectedPaylaterResponse);
        $this->assertEquals($response['entities']['emi']['items'], $expectedEmiResponse);
        $this->assertEquals($response['entities']['cardless_emi']['providers'], $this->expectedCardlessEmiResponse);
    }

    public function testFetchedOffersAreSortedByPopularity(): void
    {
        $offerAttributes = [
            OfferEntity::DEFAULT_OFFER => true,
            OfferEntity::ACTIVE => true,
        ];
        $offer1 = $this->fixtures->create('offer:card', $offerAttributes);
        $offer1->saveOrFail();
        $offer1->refresh();

        $offer2 = $this->fixtures->create('offer:wallet', $offerAttributes);
        $offer2->saveOrFail();
        $offer2->refresh();

        $offer3 = $this->fixtures->create('offer:emi_subvention', $offerAttributes);
        $offer3->saveOrFail();
        $offer3->refresh();

        $offersUsageData = [
            $offer1->getId() => 1,
            $offer2->getId() => 3,
            $offer3->getId() => 2,
        ];

        foreach ($offersUsageData as $offerId => $count) {
            for ($i = 0; $i < $count; $i++) {
                $this->createPaymentAndEntityOfferEntities(Account::TEST_ACCOUNT, $offerId);
            }
        }

        $visible = OfferEntity::getVisibleForAffordability();
        $offerItems = [
            Arr::only($offer2->toArray(), $visible), // Most Popular
            Arr::only($offer3->toArray(), $visible),
            Arr::only($offer1->toArray(), $visible), // Least Popular
        ];

        $this->testData[__FUNCTION__]['response']['content']['entities']['offers']['items'] = $offerItems;

        $response = $this->startTest();

        $this->assertEquals($offerItems, $response['entities']['offers']['items']);
    }

    public function testFetchOffersReturnsAllActiveOffersIrrespectiveOfTheirDefaultCheckoutVisibility(): void
    {
        $offerAttributes = [
            OfferEntity::STARTS_AT => Carbon::now()->subMonth()->getTimestamp(),
            OfferEntity::ENDS_AT => Carbon::now()->addMonth()->getTimestamp(),
            OfferEntity::ACTIVE => true,
        ];
        // Create an offer with default checkout visibility turned off.
        $offerAttributes[OfferEntity::DEFAULT_OFFER] = false;
        $offer1 = $this->fixtures->create('offer:card', $offerAttributes);
        $offer1->saveOrFail();
        $offer1->refresh();

        // Create an offer with default checkout visibility turned on.
        $offerAttributes[OfferEntity::DEFAULT_OFFER] = true;
        $offer2 = $this->fixtures->create('offer:wallet', $offerAttributes);
        $offer2->saveOrFail();
        $offer2->refresh();

        $offersUsageData = [
            $offer1->getId() => 1,
            $offer2->getId() => 2,
        ];

        foreach ($offersUsageData as $offerId => $count) {
            for ($i = 0; $i < $count; $i++) {
                $this->createPaymentAndEntityOfferEntities(Account::TEST_ACCOUNT, $offerId);
            }
        }

        $visible = OfferEntity::getVisibleForAffordability();
        $offerItems = [
            Arr::only($offer2->toArray(), $visible), // Most Popular
            Arr::only($offer1->toArray(), $visible), // Least Popular
        ];

        $this->testData[__FUNCTION__]['response']['content']['entities']['offers']['items'] = $offerItems;

        $response = $this->startTest();

        $this->assertEquals($offerItems, $response['entities']['offers']['items']);
    }

    public function testFetchOffersReturnsEmptyResponseWhenThereIsNoActiveOffer(): void
    {
        // Create an Offer but don't mark it as active
        $offer1 = $this->fixtures->create(
            'offer:card',
            [
                OfferEntity::STARTS_AT => Carbon::now()->subMonth()->getTimestamp(),
                OfferEntity::ENDS_AT => Carbon::now()->addMonth()->getTimestamp(),
                OfferEntity::DEFAULT_OFFER => true,
                OfferEntity::ACTIVE => false,
            ]
        );
        $offer1->saveOrFail();

        $response = $this->startTest();

        $this->assertEquals([], $response['entities']['offers']['items']);
    }

    public function testColorAndImageAreSentInOptionsField(): void
    {
        /** @var MerchantEntity $merchant */
        $merchant = $this->fixtures->create('merchant', [
            MerchantEntity::BRAND_COLOR => '1234FF',
            MerchantEntity::LOGO_URL => '/logos/merchant_logo.png',
        ]);

        $merchantId = $merchant->getId();

        $this->fixtures->merchant->addFeatures([Constants::AFFORDABILITY_WIDGET], $merchantId);

        $testKey = $this->fixtures->on(Mode::TEST)->create('key', ['merchant_id' => $merchantId, 'id' => $merchantId]);

        $this->testData[__FUNCTION__]['request']['content']['key'] = $testKey->getPublicKey(Mode::TEST);

        $this->startTest();
    }

    public function testFetchOffersDoesNotReturnSubscriptionBasedOffers(): void
    {
        $cardOffer = $this->fixtures->create(
            'offer:card',
            [
                OfferEntity::ACTIVE       => true,
                OfferEntity::PRODUCT_TYPE => ProductType::SUBSCRIPTION,
            ]
        );
        $cardOffer->saveOrFail();

        $subscriptionOffer = $this->fixtures->create('subscription_offers_master', [
            'redemption_type' => 'cycle',
            'applicable_on'   => 'both',
            'no_of_cycles'    => 10,
            'offer_id'        => $cardOffer->getId(),
        ]);
        $subscriptionOffer->saveOrFail();

        $walletOffer = $this->fixtures->create('offer:wallet');
        $walletOffer->saveOrFail();

        $visible = OfferEntity::getVisibleForAffordability();
        $offerItems = [
            Arr::only($walletOffer->toArray(), $visible),
        ];

        $this->testData[__FUNCTION__]['response']['content']['entities']['offers']['items'] = $offerItems;

        $response = $this->startTest();

        $this->assertCount(1, $response['entities']['offers']['items']);
        $this->assertNotEquals('card', $response['entities']['offers']['items'][0]['payment_method']);
    }

    protected function createPaymentAndEntityOfferEntities(string $merchantId, string $offerId)
    {
        $yesterday = Carbon::yesterday()->getTimestamp();

        $payment = $this->fixtures->create('payment', [
            PaymentEntity::MERCHANT_ID => $merchantId,
            PaymentEntity::AUTHORIZED_AT => $yesterday,
            PaymentEntity::CREATED_AT => $yesterday,
        ]);

        $this->fixtures->create('entity_offer', [
            EntityOfferEntity::ENTITY_ID => $payment->getId(),
            EntityOfferEntity::ENTITY_TYPE => 'payment',
            EntityOfferEntity::ENTITY_OFFER_TYPE => 'offer',
            EntityOfferEntity::OFFER_ID => $offerId,
            EntityOfferEntity::CREATED_AT => $yesterday,
        ]);
    }
}
