<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Models\Merchant;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

use Razorpay\OAuth\Application;

class PartnerConfigTest extends OAuthTestCase
{
    use PartnerTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

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
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigWhenBothAppAndPartnerIdNotSent()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigWhenBothAppAndPartnerIdSent()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigForNonPlatformPartnerUsingAppId()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigForNonPlatformPartnerUsingPartnerId()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigForPlatformPartnerUsingAppId()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigForPlatformPartnerUsingPartnerId()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigForPlatFormPartnerAgain()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->create(
            'partner_config',
            [
                'entity_id'       => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ]
        );

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

        $this->startTest();
    }

    public function testAddingConfigForSubMerchantUsingAppId()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingConfigForSubMerchantUsingPartnerId()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddingSubmerchantConfigWhenAppConfigAlreadyPresent()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->create(
            'partner_config',
            [
                'entity_type'     => 'application',
                'entity_id'       => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ]
        );

        $this->startTest();
    }

    public function testAddingConfigForSubMerchantNotMappedToApp()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

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

        $this->startTest();
    }

    public function testEditingConfig()
    {
        $this->allowAdminToAccessMerchant(Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->fixtures->create(
            'partner_config',
            [
                'id'                  => Constants::DEFAULT_PARTNER_CONFIGS_ID,
                'entity_id'           => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id'     => Pricing::DEFAULT_PRICING_PLAN_ID,
                'commissions_enabled' => 1,
            ]
        );

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/partner_configs/'. Constants::DEFAULT_PARTNER_CONFIGS_ID;

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
