<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Models\Merchant;
use RZP\Models\Feature as Feature;
use RZP\Models\Partner\Config\Entity;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Constants\Entity as EntityConstants;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Partner\Config\Constants as PartnerConfigConstants;

use Razorpay\OAuth\Application;
use RZP\Models\Merchant\Constants as MerchantConstants;

class PartnerConfigTest extends OAuthTestCase
{
    use PartnerTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PartnerConfigTestData.php';
        parent::setUp();

        $this->createPurePlatFormMerchantAndSubMerchant();
        $this->createNonPurePlatFormMerchantAndSubMerchant();

        $this->ba->privateAuth();
    }

    protected function allowAdminToAccessMerchant(string $merchantId)
    {
        $merchant = Merchant\Entity::find($merchantId);

        $admin    = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);

        $this->ba->adminProxyAuth($merchantId, 'rzp_test_'.$merchantId);

        return $merchant;
    }

    protected function mockSplitzTreatment($output)
    {
        $this->splitzMock = \Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->byDefault()
            ->andReturn($output);
    }

    public function testSubmerchantPricingplanUpsertViaBatch() {

        $this->allowAdminToAccessMerchant(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->createImplicitPricingPlan();

        $this->ba->batchAuth();

        $this->fixtures->create("partner_config", [
            'entity_id' => Constants::DEFAULT_PLATFORM_APP_ID,
            'entity_type' => 'application',
            'implicit_plan_id' => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            'default_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
        ]);

        $this->fixtures->create("partner_config", [
            'entity_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            'entity_type' => 'application',
            'implicit_plan_id' => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            'default_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
        ]);

        $configsBeforeExecution = $this->getDbEntities('partner_config');

        $this->ba->batchAppAuth();

        $this->startTest();

        $configsAfterExecution = $this->getDbEntities('partner_config');

        $upsertedSubmerchantPartnerConfig = $this->getDbEntity('partner_config');

        $this->assertEquals(sizeof($configsAfterExecution) , sizeof($configsBeforeExecution) + 2);

        $this->assertEquals('merchant', $upsertedSubmerchantPartnerConfig['entity_type']);

        $this->assertEquals(Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID, $upsertedSubmerchantPartnerConfig['entity_id']);

        $this->assertEquals('application',$upsertedSubmerchantPartnerConfig['origin_type']);

        $this->assertEquals(Constants::DEFAULT_NON_PLATFORM_APP_ID, $upsertedSubmerchantPartnerConfig['origin_id']);

    }

    public function testAddingConfigForNonPartner()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_MERCHANT_ID);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigWithDefaultPaymentMethods()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigWithDefaultPaymentMethodsForPurePlatform()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditingConfigWithSettingDefaultPaymentMethodsToEmpty()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $partnerConfig = $this->fixtures->create(
            'partner_config',
            [
                'id'                   => Constants::DEFAULT_PARTNER_CONFIGS_ID,
                'entity_type'          => 'merchant',
                'entity_id'            => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'origin_type'          => 'application',
                'origin_id'            => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'commissions_enabled'  => 1,
                'implicit_plan_id'     => '10ZeroPricingP',
                'explicit_plan_id'     => '10ZeroPricingP',
                'explicit_refund_fees' => 1,
                'default_payment_methods' => [
                    Merchant\Methods\Entity::NETBANKING  => true,
                    Merchant\Methods\Entity::CREDIT_CARD => true,
                    Merchant\Methods\Entity::DEBIT_CARD  => true,
                ],
            ]
        );

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/partner_configs/'. Constants::DEFAULT_PARTNER_CONFIGS_ID;

        $this->ba->adminAuth();

        $this->startTest($testData);
    }

    public function testAddingConfigWithIncorrectDefaultPaymentMethods()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigWhenBothAppAndPartnerIdNotSent()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigWhenBothAppAndPartnerIdSent()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigForNonPlatformPartnerUsingAppId()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        // Resellers cannot set settle_to_partner attribute sent in the request
        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigForNonPlatformPartnerUsingPartnerId()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->createMerchantApplication('100nonplatform', 'reseller', '1000nonplatApp');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigForPlatformPartnerUsingAppId()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingInvalidConfigForPlatformPartner()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigForSubvention()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigWithExpiryForSubvention()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigForPlatformPartnerUsingPartnerId()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigForPlatFormPartnerAgain()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->createMerchantApplication('100nonplatform', 'reseller', '1000nonplatApp');

        $this->fixtures->create(
            'partner_config',
            [
                'entity_id'       => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ]
        );

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigForSubmerchantAgain()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->create(
            'partner_config',
            [
                'entity_type'     => 'merchant',
                'entity_id'       => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'origin_type'     => 'application',
                'origin_id'       => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ]
        );

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigForSubMerchantUsingAppId()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigForSubMerchantUsingPartnerId()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->createMerchantApplication('100nonplatform', 'reseller', '1000nonplatApp');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingSubmerchantConfigWhenAppConfigAlreadyPresent()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->createMerchantApplication('100nonplatform', 'reseller', '1000nonplatApp');

        $this->fixtures->create(
            'partner_config',
            [
                'entity_type'     => 'application',
                'entity_id'       => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ]
        );

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddingConfigForSubMerchantNotMappedToApp()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->createMerchantApplication('100nonplatform', 'reseller', '1000nonplatApp');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGettingConfigUsingAppId()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->create(
            'partner_config',
            [
                'entity_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            ]
        );

        $this->fixtures->create(
            'partner_config',
            [
                'entity_type'     => 'merchant',
                'entity_id'       => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'origin_type'     => 'application',
                'origin_id'       => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ]
        );

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGettingConfigsByPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->createConfigForPartnerApp($app->getId());
        $this->createSubMerchant($partner, $app);

        $merchantUser = $this->fixtures->user->createUserForMerchant($partner->getId());

        $this->ba->proxyAuth('rzp_test_' . $partner->getId(), $merchantUser['id']);

        $this->startTest();
    }

    public function testGettingConfigForAppUsingSubMerchant()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->create(
            'partner_config',
            [
                'entity_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            ]
        );

        $this->ba->adminAuth();

        $response = $this->startTest();

        $testEntity = $this->getDbLastEntity('partner_config', 'test');
        $liveEntity = $this->getDbLastEntity('partner_config', 'live');

        $this->assertNotNull($testEntity);
        $this->assertNotNull($liveEntity);

        $this->assertEquals($testEntity->getId(), $response['id']);
        $this->assertEquals($liveEntity->getId(), $response['id']);
    }

    public function testGettingOverriddenConfig()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->create(
            'partner_config',
            [
                'entity_id'   => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'entity_type' => 'application',
            ]
        );

        $this->fixtures->create(
            'partner_config',
            [
                'entity_type'     => 'merchant',
                'entity_id'       => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'origin_type'     => 'application',
                'origin_id'       => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ]
        );

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditingConfig()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        // Resellers cannot set settle_to_partner attribute sent in the request
        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->fixtures->create(
            'partner_config',
            [
                'id'                     => Constants::DEFAULT_PARTNER_CONFIGS_ID,
                'entity_type'            => 'application',
                'entity_id'              => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'commissions_enabled'    => 1,
                'implicit_plan_id'       => '10ZeroPricingP',
                'explicit_plan_id'       => '10ZeroPricingP',
                'explicit_refund_fees'   => 1,
            ]
        );

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/partner_configs/'. Constants::DEFAULT_PARTNER_CONFIGS_ID;

        $this->ba->adminAuth();

        $this->startTest($testData);
    }

    public function testEditingOverriddenConfig()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->fixtures->create(
            'partner_config',
            [
                'id'                   => Constants::DEFAULT_PARTNER_CONFIGS_ID,
                'entity_type'          => 'merchant',
                'entity_id'            => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'origin_type'          => 'application',
                'origin_id'            => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'commissions_enabled'  => 1,
                'implicit_plan_id'     => '10ZeroPricingP',
                'explicit_plan_id'     => '10ZeroPricingP',
                'explicit_refund_fees' => 1,
            ]
        );

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/partner_configs/'. Constants::DEFAULT_PARTNER_CONFIGS_ID;

        $this->ba->adminAuth();

        $this->startTest($testData);
    }

    /**
     * Unit test - Merchant\Core::getPartnerBankAccountIdsForSubmerchants()
     *
     * Asserts that the function returns the expected array when -
     *
     * 1. Partner config is defined only for an application
     * 2. Partner configs are defined for both - application and submerchant
     * 3. Partner configs are defined for both - application and submerchant and the submerchant config has
     * settle_to_flag set to false
     */
    public function testSettleToPartner()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $partnerBankAccount = $this->getDbEntity(
            'bank_account',
            [
                'merchant_id' => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
                'entity_id'   => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            ]);

        $this->fixtures->merchant->createAccount(Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'merchant_id'     => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
                'entity_id'       => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'entity_type'     => 'application',
                'entity_owner_id' => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            ]
        );

        $this->assertNotNull($partnerBankAccount);

        $merchantCore = (new Merchant\Core);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_NON_PLATFORM_APP_ID,
            null,
            [Entity::SETTLE_TO_PARTNER => true]);

        $merchantIds = [
            Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
            Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
        ];

        $results = $merchantCore->getPartnerBankAccountIdsForSubmerchants($merchantIds);

        $expectedResult = [
            Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID   => $partnerBankAccount->getId(),
            Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2 => $partnerBankAccount->getId(),
        ];

        $this->assertEquals($expectedResult, $results);

        // Overridden config with settle to partner as true
        $this->createConfigForPartnerApp(
            Constants::DEFAULT_NON_PLATFORM_APP_ID,
            Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
            [Entity::SETTLE_TO_PARTNER => true]);

        // Overridden config with settle to partner as false
        $this->createConfigForPartnerApp(
            Constants::DEFAULT_NON_PLATFORM_APP_ID,
            Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
            [Entity::SETTLE_TO_PARTNER => false]);

        $results = $merchantCore->getPartnerBankAccountIdsForSubmerchants($merchantIds);

        $expectedResult = [
            Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID => $partnerBankAccount->getId(),
        ];

        $this->assertEquals($expectedResult, $results);
    }

    public function testEditingConfigToSubventionModel()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        // Resellers cannot set settle_to_partner attribute sent in the request
        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->fixtures->create(
            'partner_config',
            [
                'id'                     => Constants::DEFAULT_PARTNER_CONFIGS_ID,
                'entity_id'              => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'commissions_enabled'    => 1,
                'implicit_plan_id'       => '10ZeroPricingP',
                'explicit_plan_id'       => '10ZeroPricingP',
                'explicit_refund_fees'   => 1,
            ]
        );

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/partner_configs/'. Constants::DEFAULT_PARTNER_CONFIGS_ID;

        $this->ba->adminAuth();

        $this->startTest($testData);
    }

    public function createNonPurePlatFormMerchantAndSubMerchant()
    {
        $this->fixtures->merchant->createAccount(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);
        $this->fixtures->merchant->createAccount(Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::RESELLER,
            ]
        );

        $this->fixtures->create('merchant_detail:sane', ['merchant_id' => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID]);

        $this->createOAuthApplication(
            [
                'merchant_id' => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
                'type'        => Application\Type::PARTNER,
                'id'          => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'partner_type'=> MerchantConstants::AGGREGATOR
            ]
        );

        $this->fixtures->create(
            'merchant_access_map',
            [
                'merchant_id'     => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'entity_id'       => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'entity_type'     => 'application',
                'entity_owner_id' => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            ]
        );
    }

    public function testCreatePartnersSubMerchantConfigWithNullConfigInDB()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->ba->adminAuth();

        $this->fixtures->create("partner_config", [
            'entity_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            'entity_type' => 'application',
            'default_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
        ]);

        $this->startTest();

        $partnerConfig = $this->getDbEntity('partner_config');

        $this->assertNotNull($partnerConfig['sub_merchant_config']);

        $this->assertEquals('{"max_payment_amount":[{"value":"2000011","business_type":"individual"}]}' , $partnerConfig['sub_merchant_config'] );
    }

    public function testCreatePartnersSubMerchantConfigGmvLimitForNoDoc()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $featureParams = [
            Feature\Entity::ENTITY_ID   => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Feature\Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Feature\Core())->create($featureParams, true);

        $this->ba->adminAuth();

        $this->fixtures->create("partner_config", [
            'entity_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            'entity_type' => 'application',
            'default_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
        ]);

        $this->startTest();

        $partnerConfig = $this->getDbEntity('partner_config');

        $this->assertNotNull($partnerConfig['sub_merchant_config']);

        $this->assertEquals('{"gmv_limit":[{"value":"5100000","set_for":"no_doc_submerchants"}]}' , $partnerConfig['sub_merchant_config'] );
    }

    public function testDeletePartnersSubMerchantConfigGmvLimitForNoDoc()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $featureParams = [
            Feature\Entity::ENTITY_ID   => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Feature\Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Feature\Core())->create($featureParams, true);

        $this->ba->adminAuth();

        $this->fixtures->create("partner_config", [
            'entity_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            'entity_type' => 'application',
            'default_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
            'sub_merchant_config' => json_decode('{"gmv_limit":[{"value":4000000,"set_for":"no_doc_submerchants"}]}'),
        ]);

        $testData = $this->testData['testCreatePartnersSubMerchantConfigGmvLimitForNoDoc'];

        $testData['request']['method'] = 'PUT';

        $this->startTest($testData);

        $partnerConfig = $this->getDbEntity('partner_config');

        $this->assertNotNull($partnerConfig['sub_merchant_config']);

        $this->assertEquals('{"gmv_limit":[]}' , $partnerConfig['sub_merchant_config']);
    }

    public function testSetGmvLimitForNonNoDocPartnerNegative()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->ba->adminAuth();

        $this->fixtures->create("partner_config", [
            'entity_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            'entity_type' => 'application',
            'default_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
            'sub_merchant_config' => json_decode('{"gmv_limit":[{"value":4000000,"set_for":"no_doc_submerchants"}]}'),
        ]);

        $this->startTest();
    }

    public function testInvalidCreatePartnersSubMerchantConfigGmvLimitForNoDoc()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $featureParams = [
            Feature\Entity::ENTITY_ID   => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Feature\Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Feature\Core())->create($featureParams, true);

        $this->ba->adminAuth();

        $this->fixtures->create("partner_config", [
            'entity_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            'entity_type' => 'application',
            'default_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
        ]);

        $testData = $this->testData['testCreatePartnersSubMerchantConfigInvalidParameters'];

        $testData['request']['content']['attribute_name'] = 'gmv_limit';
        $testData['request']['content']['parameters'] = ['set_for' => 'invalid_value'];

        $this->startTest($testData);
    }

    public function testMultiplePartnersSubMerchantConfigs()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $featureParams = [
            Feature\Entity::ENTITY_ID   => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Feature\Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Feature\Core())->create($featureParams, true);

        $this->ba->adminAuth();

        $this->fixtures->create("partner_config", [
            'entity_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            'entity_type' => 'application',
            'default_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
            'sub_merchant_config' => json_decode('{"max_payment_amount":[{"value":"2000011","business_type":"not_yet_registered"}]}'),
        ]);

        $testData = $this->testData['testCreatePartnersSubMerchantConfigGmvLimitForNoDoc'];

        $this->startTest($testData);

        $partnerConfig = $this->getDbEntity('partner_config');

        $this->assertNotNull($partnerConfig['sub_merchant_config']);

        $this->assertEquals('{"max_payment_amount":[{"value":"2000011","business_type":"not_yet_registered"}],"gmv_limit":[{"value":"5100000","set_for":"no_doc_submerchants"}]}' , $partnerConfig['sub_merchant_config'] );
    }

    public function testCreatePartnersSubMerchantConfigWithConfigInDB()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->ba->adminAuth();

        $this->fixtures->create("partner_config", [
            'entity_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            'entity_type' => 'application',
            'default_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
            'sub_merchant_config' => json_decode('{"max_payment_amount":[{"value":"2000000","business_type":"individual"}]}')
        ]);

        $this->startTest();

        $partnerConfig = $this->getDbEntity('partner_config');

        $this->assertNotNull($partnerConfig['sub_merchant_config']);

        $this->assertEquals('{"max_payment_amount":[{"value":"2000000","business_type":"individual"},{"value":"2000011","business_type":"not_yet_registered"}]}' , $partnerConfig['sub_merchant_config'] );
    }

    public function testCreatePartnersSubMerchantConfigInvalidParameters()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->ba->adminAuth();

        $this->fixtures->create("partner_config", [
            'entity_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            'entity_type' => 'application',
            'default_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
        ]);

        $this->startTest();
    }

    public function testCreatePartnersSubMerchantConfigInvalidConfigName()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->ba->adminAuth();

        $this->fixtures->create("partner_config", [
            'entity_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            'entity_type' => 'application',
            'default_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
        ]);

        $this->startTest();
    }

    public function testCreatePartnersSubMerchantConfigInvalidPartner()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::FULLY_MANAGED,
            ]
        );

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpdatePartnersSubMerchantConfig()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->ba->adminAuth();

        $this->fixtures->create("partner_config", [
            'entity_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            'entity_type' => 'application',
            'default_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
            'sub_merchant_config' => json_decode('{"max_payment_amount":[{"value":"2000011","business_type":"individual"}]}', 1),
        ]);

        $this->startTest();

        $partnerConfig = $this->getDbEntity('partner_config');

        $this->assertEquals('{"max_payment_amount":[]}',$partnerConfig['sub_merchant_config']);
    }

    public function testUpdatePartnersSubMerchantConfigWithInvalidParameters()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpdatePartnersSubMerchantConfigWithInvalidConfigName()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpdatePartnersSubMerchantConfigWithInvalidPartnerType()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::FULLY_MANAGED,
            ]
        );

        $this->ba->adminAuth();

        $this->startTest();
    }

    protected function checkResponseFieldsForProxyOrInternalAuth(array $response)
    {
        // Fields that should be exposed only under admin auth as this is confidential info
        $protectedFields = [
            Entity::DEFAULT_PLAN_ID,
            Entity::IMPLICIT_PLAN_ID,
            Entity::EXPLICIT_PLAN_ID,
            Entity::DEFAULT_TDS_PERCENTAGE,
            Entity::TDS_PERCENTAGE,
            Entity::COMMISSIONS_ENABLED
        ];

        foreach ($protectedFields as $field)
        {
            self::assertArrayNotHasKey($field, $response);
        }
    }

    public function testFetchConfigByPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant', $partner->id, ['partner_type' => 'aggregator']);

        $partnerMeteData = [
            'brand_color' => '0000FF',
            'text_color'  => '000FFF',
            'brand_name'  => 'apple'
        ];

        $this->createConfigForPartnerApp($app->getId(), null, [Entity::PARTNER_METADATA => $partnerMeteData]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($partner->getId());

        $this->ba->proxyAuth('rzp_test_' . $partner->getId(), $merchantUser['id']);

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzOutput);

        $response = $this->startTest();

        $this->checkResponseFieldsForProxyOrInternalAuth($response);
    }

    public function testFetchConfigByPartnerWithDefaultValues()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant', $partner->id, ['partner_type' => 'aggregator']);

        $this->fixtures->edit('merchant_detail', $partner->id, ['business_name' => 'Business Partner']);

        $this->createConfigForPartnerApp($app->getId());

        $merchantUser = $this->fixtures->user->createUserForMerchant($partner->getId());

        $this->ba->proxyAuth('rzp_test_' . $partner->getId(), $merchantUser['id']);

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzOutput);

        $response = $this->startTest();

        $this->checkResponseFieldsForProxyOrInternalAuth($response);
    }

    public function testFetchPartnerConfigByInternalAppAuth()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant', $partner->id, ['partner_type' => 'aggregator']);

        $partnerMeteData = [
            'brand_color' => '0000FF',
            'text_color'  => '000FFF',
            'brand_name'  => 'google'
        ];

        $this->createConfigForPartnerApp($app->getId(), null, [Entity::PARTNER_METADATA => $partnerMeteData]);

        $this->fixtures->user->createUserForMerchant($partner->getId());

        $this->ba->dashboardGuestAppAuth();

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzOutput);

        $response = $this->startTest();

        $this->checkResponseFieldsForProxyOrInternalAuth($response);
    }

    // allow partner config fetch for only aggregator partners
    public function testFetchConfigByInvalidPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant', $partner->id, ['partner_type' => 'reseller']);

        $this->createConfigForPartnerApp($app->getId());

        $merchantUser = $this->fixtures->user->createUserForMerchant($partner->getId());;

        $this->ba->proxyAuth('rzp_test_' . $partner->getId(), $merchantUser['id']);

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzOutput);

        $this->startTest();

        $this->fixtures->edit('merchant', $partner->id, ['partner_type' => 'fully_managed']);

        $this->startTest();

        $this->fixtures->edit('merchant', $partner->id, ['partner_type' => 'pure_platform']);

        $this->startTest();
    }

    public function testUpdateAllowedConfigByPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant', $partner->id, ['partner_type' => 'aggregator']);

        $partnerConfig = $this->createConfigForPartnerApp($app->getId());

        $merchantUser = $this->fixtures->user->createUserForMerchant($partner->getId());;

        $this->ba->proxyAuth('rzp_test_' . $partner->getId(), $merchantUser['id']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/partner_config/'. $partnerConfig->id;

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzOutput);

        $response = $this->startTest($testData);

        $this->checkResponseFieldsForProxyOrInternalAuth($response);
    }

    public function testUpdateDisallowedConfigByPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant', $partner->id, ['partner_type' => 'aggregator']);

        $partnerConfig = $this->createConfigForPartnerApp($app->getId());

        $merchantUser = $this->fixtures->user->createUserForMerchant($partner->getId());;

        $this->ba->proxyAuth('rzp_test_' . $partner->getId(), $merchantUser['id']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/partner_config/'. $partnerConfig->id;

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzOutput);

        $this->startTest($testData);
    }

    public function testUploadBrandLogoByPartner()
    {
        $file = (new MerchantTest())->createUploadedFile('tests/Functional/Storage/a.png');

        copy($file, 'tests/Functional/Storage/a2.png');

        $testFile = (new MerchantTest())->createUploadedFile('tests/Functional/Storage/a2.png');

        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant', $partner->id, ['partner_type' => 'aggregator']);

        $partnerMeteData = [
            'brand_color' => '0000FF',
            'text_color'  => '000FFF',
            'brand_name'  => 'google'
        ];

        $partnerConfig = $this->createConfigForPartnerApp($app->getId(), null, [Entity::PARTNER_METADATA => $partnerMeteData]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($partner->getId());

        $this->ba->proxyAuth('rzp_test_' . $partner->getId(), $merchantUser['id']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/partner_config/'. $partnerConfig->id . '/logo';

        $testData['request']['files']['logo'] = $testFile;

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzOutput);

        $response = $this->startTest($testData);

        $this->checkResponseFieldsForProxyOrInternalAuth($response);

        $this->assertStringContainsString('/logos/', $response[Entity::PARTNER_METADATA][PartnerConfigConstants::LOGO_URL]);

        $this->assertStringStartsWith('https', $response[Entity::PARTNER_METADATA][PartnerConfigConstants::LOGO_URL]);
    }

    public function testFetchConfigWithApplicationIdByPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication(['partner_type' => 'pure_platform']);

        $partnerMeteData = [
            'brand_color' => '0000FF',
            'text_color'  => '000FFF',
            'brand_name'  => 'apple'
        ];

        $merchantUser = $this->fixtures->user->createUserForMerchant($partner->getId());

        $this->createConfigForPartnerApp($app->getId(), null, [Entity::PARTNER_METADATA => $partnerMeteData]);

        $this->ba->proxyAuth('rzp_test_' . $partner->getId(), $merchantUser['id']);

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzOutput);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['application_id'] = $app->getId();

        $response = $this->startTest($testData);

        $this->checkResponseFieldsForProxyOrInternalAuth($response);
    }

    public function testFetchConfigByPartnerWithDefaultValuesAndApplicationId()
    {
        list($partner, $app) = $this->createPartnerAndApplication(['partner_type' => 'pure_platform']);

        $this->fixtures->edit('merchant_detail', $partner->id, ['business_name' => 'Business Partner']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($partner->getId());

        $this->createConfigForPartnerApp($app->getId());

        $this->ba->proxyAuth('rzp_test_' . $partner->getId(), $merchantUser['id']);

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzOutput);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['application_id'] = $app->getId();

        $response = $this->startTest($testData);

        $this->checkResponseFieldsForProxyOrInternalAuth($response);
    }

    public function testFetchPartnerConfigWithApplicationIdByInternalAppAuth()
    {
        list($partner, $app) = $this->createPartnerAndApplication(['partner_type' => 'pure_platform']);

        $partnerMeteData = [
            'brand_color' => '0000FF',
            'text_color'  => '000FFF',
            'brand_name'  => 'google'
        ];

        $merchantUser = $this->fixtures->user->createUserForMerchant($partner->getId());

        $this->createConfigForPartnerApp($app->getId(), null, [Entity::PARTNER_METADATA => $partnerMeteData]);

        $this->ba->dashboardGuestAppAuth();

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzOutput);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['application_id'] = $app->getId();

        $response = $this->startTest($testData);

        $this->checkResponseFieldsForProxyOrInternalAuth($response);
    }

    // allow partner config fetch for only pure platform partners
    public function testFetchConfigWithApplicationIdByInvalidPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->fixtures->edit('merchant', $partner->id, ['partner_type' => 'reseller']);

        $this->createConfigForPartnerApp($app->getId());

        $merchantUser = $this->fixtures->user->createUserForMerchant($partner->getId());;

        $this->ba->proxyAuth('rzp_test_' . $partner->getId(), $merchantUser['id']);

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzOutput);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['application_id'] = $app->getId();

        $this->startTest($testData);

        $this->fixtures->edit('merchant', $partner->id, ['partner_type' => 'fully_managed']);

        $this->startTest($testData);

        $this->fixtures->edit('merchant', $partner->id, ['partner_type' => 'aggregator']);

        $this->startTest($testData);
    }

    public function testUpdateAllowedConfigWithApplicationIdByPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication(['partner_type' => 'pure_platform']);

        $partnerConfig = $this->createConfigForPartnerApp($app->getId());

        $merchantUser = $this->fixtures->user->createUserForMerchant($partner->getId());

        $this->ba->proxyAuth('rzp_test_' . $partner->getId(), $merchantUser['id']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/partner_config/'. $partnerConfig->id;

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzOutput);

        $response = $this->startTest($testData);

        $this->checkResponseFieldsForProxyOrInternalAuth($response);
    }
}
