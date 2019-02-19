<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Models\Merchant;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

use Razorpay\OAuth\Application;

class PartnerConfigTest extends OAuthTestCase
{
    use OAuthTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    const DEFAULT_MERCHANT_ID                 = '10000000000000';
    const DEFAULT_SUBMERCHANT_ID              = '10000000000019';
    const DEFAULT_PARTNER_CONFIGS_ID          = '100configId001';

    const DEFAULT_PLATFORM_APP_ID             = '1000000platApp';
    const DEFAULT_PLATFORM_SUBMERCHANT_ID     = '100submerchant';
    const DEFAULT_PLATFORM_MERCHANT_ID        = '1000000000plat';

    const DEFAULT_NON_PLATFORM_APP_ID         = '1000nonplatApp';
    const DEFAULT_NON_PLATFORM_SUBMERCHANT_ID = '10submerchant1';
    const DEFAULT_NON_PLATFORM_MERCHANT_ID    = '100nonplatform';

    public function setUp()
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

    public function testAddingConfigForNonPartner()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigWhenBothAppAndPartnerIdNotSent()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigWhenBothAppAndPartnerIdSent()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigForNonPlatformPartnerUsingAppId()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigForNonPlatformPartnerUsingPartnerId()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigForPlatformPartnerUsingAppId()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigForPlatformPartnerUsingPartnerId()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigForPlatFormPartnerAgain()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->create(
            'partner_config',
            [
                'entity_id'       => self::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ]
        );

        $this->startTest();
    }

    public function testAddingConfigForSubmerchantAgain()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->create(
            'partner_config',
            [
                'entity_type'     => 'merchant',
                'entity_id'       => self::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'origin_type'     => 'application',
                'origin_id'       => self::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ]
        );

        $this->startTest();
    }

    public function testAddingConfigForSubMerchantUsingAppId()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigForSubMerchantUsingPartnerId()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingSubmerchantConfigWhenAppConfigAlreadyPresent()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->create(
            'partner_config',
            [
                'entity_type'     => 'application',
                'entity_id'       => self::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ]
        );

        $this->startTest();
    }

    public function testAddingConfigForSubMerchantNotMappedToApp()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testGettingConfigUsingAppId()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->create(
            'partner_config',
            [
                'entity_id' => self::DEFAULT_NON_PLATFORM_APP_ID,
            ]
        );

        $this->fixtures->create(
            'partner_config',
            [
                'entity_type'     => 'merchant',
                'entity_id'       => self::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'origin_type'     => 'application',
                'origin_id'       => self::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ]
        );

        $this->startTest();
    }

    public function testGettingConfigForAppUsingSubMerchant()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->create(
            'partner_config',
            [
                'entity_id' => self::DEFAULT_NON_PLATFORM_APP_ID,
            ]
        );

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
        $this->allowAdminToAccessMerchant(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->create(
            'partner_config',
            [
                'entity_id'   => self::DEFAULT_NON_PLATFORM_APP_ID,
                'entity_type' => 'application',
            ]
        );

        $this->fixtures->create(
            'partner_config',
            [
                'entity_type'     => 'merchant',
                'entity_id'       => self::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'origin_type'     => 'application',
                'origin_id'       => self::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ]
        );

        $this->startTest();
    }

    public function testEditingConfig()
    {
        $this->allowAdminToAccessMerchant(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->create(
            'partner_config',
            [
                'id'                  => self::DEFAULT_PARTNER_CONFIGS_ID,
                'entity_id'           => self::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id'     => Pricing::DEFAULT_PRICING_PLAN_ID,
                'commissions_enabled' => 1,
            ]
        );

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/partner_configs/'. self::DEFAULT_PARTNER_CONFIGS_ID;

        $this->startTest($testData);
    }

    public function createPurePlatFormMerchantAndSubMerchant()
    {
        $this->fixtures->merchant->createAccount(self::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->fixtures->merchant->createAccount(self::DEFAULT_PLATFORM_SUBMERCHANT_ID);

        $this->fixtures->merchant->edit(
            self::DEFAULT_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::PURE_PLATFORM,
            ]
        );

        $this->createOAuthApplication(
            [
                'merchant_id' => self::DEFAULT_PLATFORM_MERCHANT_ID,
                'id'          => self::DEFAULT_PLATFORM_APP_ID,
            ]
        );

        $this->fixtures->create(
            'merchant_access_map',
            [
                'merchant_id'     => self::DEFAULT_PLATFORM_SUBMERCHANT_ID,
                'entity_id'       => self::DEFAULT_PLATFORM_APP_ID,
                'entity_type'     => 'application',
                'entity_owner_id' => self::DEFAULT_PLATFORM_MERCHANT_ID,
            ]
        );
    }

    public function createNonPurePlatFormMerchantAndSubMerchant()
    {
        $this->fixtures->merchant->createAccount(self::DEFAULT_NON_PLATFORM_MERCHANT_ID);
        $this->fixtures->merchant->createAccount(self::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID);

        $this->fixtures->merchant->edit(
            self::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::RESELLER,
            ]
        );

        $this->createOAuthApplication(
            [
                'merchant_id' => self::DEFAULT_NON_PLATFORM_MERCHANT_ID,
                'type'        => Application\Type::PARTNER,
                'id'          => self::DEFAULT_NON_PLATFORM_APP_ID,
            ]
        );

        $this->fixtures->create(
            'merchant_access_map',
            [
                'merchant_id'     => self::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'entity_id'       => self::DEFAULT_NON_PLATFORM_APP_ID,
                'entity_type'     => 'application',
                'entity_owner_id' => self::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            ]
        );
    }
}
