<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Http\OAuth;
use RZP\Models\Merchant;
use RZP\Models\Partner\Config\Entity;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

use Razorpay\OAuth\Application;

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

        $this->createOAuthApplication(
            [
                'merchant_id' => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
                'type'        => Application\Type::PARTNER,
                'id'          => Constants::DEFAULT_NON_PLATFORM_APP_ID,
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
}
