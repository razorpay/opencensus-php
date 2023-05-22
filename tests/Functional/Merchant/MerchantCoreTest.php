<?php


namespace Functional\Merchant;

use RZP\Constants\Mode;
use RZP\Tests\Traits\MocksPartnershipsService;
use RZP\Exception\LogicException as LogicException;
use RZP\Models\Feature;
use RZP\Models\Merchant\Core;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Models\Partner\Config\Repository as PartnerConfigRepo;
use RZP\Models\Merchant\Entity as MerchantEntity;

use RZP\Models\User\Role;
use RZP\Services\Mock\Settlements\Api;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;

class MerchantCoreTest extends OAuthTestCase
{
    use PartnerTrait;
    use DbEntityFetchTrait;
    use SettlementTrait;
    use MocksPartnershipsService;


    const RZP_ORG  = '100000razorpay';

    protected $core;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->core = new Core();
    }

    public function testAggregatorToResellerPartnerTypeUpdate()
    {
        list($merchantId, $submerchantId, $newAppId, $client, $app) = $this->createAggregatorPartnerAndSubmerchantAndFetchMocks();

        $this->setUpAuthServiceMocks($merchantId, $client->getApplicationId(), $app->getId(), $newAppId);

        $this->fixtures->create('referrals');

        $this->createCommissionForPartner($merchantId, $submerchantId, $newAppId);

        $this->mockPartnershipsServiceTreatment([], ['status_code' => 200], 'createPartnerMigrationAudit');

        $this->core->migrateAggregatorToResellerPartner($merchantId);

        // partner type should be updated as reseller
        $merchant = $this->getDbEntityById('merchant', $merchantId);
        $this->assertEquals('reseller', $merchant->getPartnerType());

        // new merchant application with referred type should be created
        $updatedApplicationsOnLive = $this->getDbEntities('merchant_application',
            ['application_id' => '8ckeirnw84ifke'],'live')->toArray();
        $updatedApplicationsOnTest = $this->getDbEntities('merchant_application',
            ['application_id' => '8ckeirnw84ifke'],'test')->toArray();
        $this->assertCount(1, $updatedApplicationsOnLive);
        $this->assertEquals('referred', $updatedApplicationsOnLive[0]['type']);
        $this->assertCount(1, $updatedApplicationsOnTest);
        $this->assertEquals('referred', $updatedApplicationsOnTest[0]['type']);

        // all existing applications for partner should be deleted
        $oldApplicationsOnLive = $this->getDbEntities('merchant_application',
            ['application_id' => $client->getApplicationId()],'live')->toArray();
        $oldApplicationsOnTest = $this->getDbEntities('merchant_application',
            ['application_id' => $client->getApplicationId()],'test')->toArray();
        $this->assertCount(0, $oldApplicationsOnLive);
        $this->assertCount(0, $oldApplicationsOnTest);

        // referral link data should not be deleted
        $referrals = $this->getDbEntity('referrals',
            ['merchant_id' => $merchantId, 'product' => 'primary']);
        $this->assertNotEmpty($referrals->getReferralLink());

        // merchant access map should be updated with new application id
        $accessMapsOnLive = $this->getDbEntities('merchant_access_map',
            ['entity_type' => 'application', 'entity_owner_id' => $merchantId],'live')->toArray();
        $accessMapsOnTest = $this->getDbEntities('merchant_access_map',
            ['entity_type' => 'application', 'entity_owner_id' => $merchantId],'test')->toArray();
        $this->assertCount(2, $accessMapsOnLive);
        $this->assertEquals($newAppId, $accessMapsOnLive[0]['entity_id']);
        $this->assertCount(2, $accessMapsOnTest);
        $this->assertEquals($newAppId, $accessMapsOnTest[0]['entity_id']);

        // commissions data should not be deleted for partner
        $commission = $this->getDbEntities("commission", ["partner_id" => $merchantId]);
        $this->assertCount(1, $commission);

        $partnerPGUserOnLive = $this->getDbEntities(
            'merchant_user',
            ['merchant_id' =>$submerchantId, 'role' => 'owner', 'product' => 'primary'],'live');
        $partnerPGUserOnTest = $this->getDbEntities(
            'merchant_user',
            ['merchant_id' =>$submerchantId, 'role' => 'owner', 'product' => 'primary'],'test');
        $this->assertCount(0, $partnerPGUserOnLive);
        $this->assertCount(0, $partnerPGUserOnTest);
    }

    public function testAggregatorToResellerPartnerTypeUpdateException()
    {
        list($merchantId, $submerchantId, $newAppId, $client) = $this->createAggregatorPartnerAndSubmerchantAndFetchMocks();

        $this->fixtures->create('referrals');

        $this->createCommissionForPartner($merchantId, $submerchantId, $newAppId);

        $oldApplications = $this->getDbEntities('merchant_application',
            ['application_id' => $client->getApplicationId()],'live');

        $oldApplications[0]->delete();

        $this->expectException(LogicException::class);

        $this->expectErrorMessage("Data is not synced on Live and Test DB");

        $this->core->migrateAggregatorToResellerPartner($merchantId);
    }

    public function testDeleteSwitchMerchantAccessForPartner()
    {
        list($merchantId) = $this->createResellerPartnerAndSubmerchant();

        $this->fixtures->user->createUserForMerchant('101submerchant');

        $this->fixtures->create('user:user_merchant_mapping', [
            'user_id'     => User::MERCHANT_USER_ID,
            'merchant_id' => '101submerchant',
            'role'        => 'owner',
        ]);

        $this->core->removeSubmerchantDashboardAccessOfPartner($merchantId);

        // verify partner submerchant mapping is present
        $accessMaps = $this->getDbEntities('merchant_access_map',
                                           ['entity_type' => 'application', 'entity_owner_id' => $merchantId])->toArray();
        $this->assertCount(1, $accessMaps);

        // verify partner user mapping is present
        $partnerUserMapping = $this->fixtures
            ->user
            ->getMerchantUserMapping($merchantId, User::MERCHANT_USER_ID)
            ->toArray();
        $this->assertNotEmpty($partnerUserMapping);

        // verify that the merchant user mappings have been deleted
        $submerchantUserMapping = $this->fixtures
            ->user
            ->getMerchantUserMapping('101submerchant', User::MERCHANT_USER_ID)
            ->toArray();
        $this->assertEmpty($submerchantUserMapping);
    }

    public function testFetchPartnerIdForSubmerchantNSSMigration()
    {
        $this->setUpNonPurePlatformPartnerAndSubmerchant('10000000000000','100submerchant');

        $this->app->singleton('settlements_api', function($app)
        {
            $implementation = Api::class ;

            return new $implementation($app, 'aggregate_settlement_parent');
        });
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_SETTLEMENT_SERVICE]);

        $result = (new Core())->fetchAggregateSettlementForNSSParent('100submerchant');

        $this->assertEquals($result,'10000000000000');
    }

    public function testFetchPartnerIdForSubmerchantNSSMigrationWithAggSettlementDisabled()
    {
        $this->setUpNonPurePlatformPartnerAndSubmerchant('10000000000000','100submerchant');

        $this->app->singleton('settlements_api', function($app)
        {
            $implementation = Api::class ;

            return new $implementation($app, 'aggregate_settlement_parent_false');
        });
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_SETTLEMENT_SERVICE]);

        $result = (new Core())->fetchAggregateSettlementForNSSParent('100submerchant');

        $this->assertEquals($result,'');
    }

    public function testFetchPartnerIdForSubmerchantNSSMigrationWithFeatureDisabled()
    {
        $this->setUpNonPurePlatformPartnerAndSubmerchant('10000000000000','100submerchant');

        $result = (new Core())->fetchAggregateSettlementForNSSParent('100submerchant');

        $this->assertEquals($result,'');
    }

    public function testFetchPartnerIdForSubmerchantNSSMigrationWithNoPartner()
    {
        $this->fixtures->merchant->createAccount('100submerchant');

        $result = (new Core())->fetchAggregateSettlementForNSSParent('100submerchant');

        $this->assertEquals($result,'');
    }

    public function testFetchPartnerIdForSubmerchantNSSMigrationWithMultiplePartners()
    {
        $this->setUpNonPurePlatformPartnerAndSubmerchant('10000000000000','100submerchant');

        $this->createAggregatorPartnerAndLinkSubmerchant('10000000000001', '100submerchant');

        $result = (new Core())->fetchAggregateSettlementForNSSParent('100submerchant');

        $this->assertEquals($result,'');
    }

    protected function createAggregatorPartnerAndLinkSubmerchant(string $partnerId, string $submerchantId)
    {
        $partnerType = 'aggregator';

        $this->fixtures->merchant->createAccount($partnerId);

        $attributes =  ['merchant_id' => $partnerId, 'partner_type' => $partnerType];

        $this->createPartnerApplicationAndGetClientByEnv('dev',$attributes);

        $this->fixtures->merchant->edit($partnerId, ['partner_type' => $partnerType]);

        $user = $this->fixtures->user->createUserForMerchant($partnerId, [], Role::OWNER, Mode::LIVE);

        $this->fixtures->merchant->editPricingPlanId('1hDYlICobzOCYt');

        $appIds = (new MerchantApplications\Core)->getMerchantAppIds($partnerId, [MerchantApplications\Entity::MANAGED]);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'id'              => 'J00dqRlTehtNbv',
                'merchant_id'     => $submerchantId,
                'entity_id'       => $appIds[0],
                'entity_type'     => 'application',
                'entity_owner_id' => $partnerId,
            ]
        );
    }

    protected function createAggregatorPartnerAndSubmerchantAndFetchMocks(string $merchantId = '10000000000000', string $submerchantId = '100submerchant', string $newAppId = '8ckeirnw84ifke')
    {
        $client = $this->setUpNonPurePlatformPartnerAndSubmerchant($merchantId, $submerchantId);

        $this->fixtures->merchant->edit($merchantId, ['name' => 'et', 'website' => 'http://www.monahan.com/harum-fuga-quae-culpa-quod']);

        $app = $this->createSubmerchantMappingForReferredApp($merchantId, '101submerchant');

        $this->ba->adminAuth();

        return [$merchantId, $submerchantId, $newAppId, $client, $app];
    }

    private function createSubmerchantMappingForReferredApp(string $merchantId, string $submerchantId)
    {
        $app = $this->createOAuthApplication(['merchant_id' => $merchantId, 'partner_type' => 'reseller']);

        $this->fixtures->merchant->createAccount('101submerchant');

        $this->fixtures->create(
            'merchant_access_map',
            [
                'id'              => 'J00dqRlTehtNbv',
                'merchant_id'     => '101submerchant',
                'entity_id'       => $app->getId(),
                'entity_type'     => 'application',
                'entity_owner_id' => $merchantId,
            ]
        );

        return $app;
    }

    protected function createCommissionForPartner(string $partnerId, string $submerchantId, string $appId)
    {
        $config = $this->createConfigForPartnerApp($appId);

        $payment = $this->createPaymentEntities(1, $submerchantId);

        $commissionAttributes = [
            'source_id'         => $payment->getId(),
            'partner_id'        => $partnerId,
            'partner_config_id' => $config->getId(),
        ];

        $commission = $this->fixtures->create('commission:commission_and_sync_es', $commissionAttributes);

        return $commission;
    }

    protected function setUpAuthServiceMocks(string $merchantId, string $managedApp, string $referredApp, string $newAppId)
    {
        $this->authServiceMock
            ->expects($this->exactly(3))
            ->method('sendRequest')
            ->withConsecutive(
                ['applications', 'POST',
                 [
                     'name' => 'et',
                     'website' => 'http://www.monahan.com/harum-fuga-quae-culpa-quod',
                     'merchant_id' => $merchantId,
                     'type' => 'partner'
                 ]
                ],
                ['applications/'.$managedApp, 'PUT', ['merchant_id' => $merchantId]
                ],
                ['applications/'.$referredApp, 'PUT', ['merchant_id' => $merchantId]
                ])
            ->willReturnOnConsecutiveCalls($app = ['id'=> $newAppId], [], []);
    }

    private function createResellerPartnerAndSubmerchant(string $submerchantId = '101submerchant', string $appId = 'reseller84ifke')
    {
        list($partner, $app) = $this->createPartnerAndApplication(['partner_type' => 'reseller'], ['id' => $appId]);

        $partnerId = $partner->getId();
        $this->fixtures->merchant->edit($partnerId, ['name' => 'et', 'website' => 'http://www.monahan.com/harum-fuga-quae-culpa-quod']);
        $this->createConfigForPartnerApp($app->getId());

        list($subMerchant, $accessMap) = $this->createSubMerchant($partner, $app, ['id' => $submerchantId], ['id' => 'J00dqRlTeStNzb']);
        $this->createConfigForPartnerApp($app->getId(), $subMerchant->getId());

        $this->ba->adminAuth();

        return [$partnerId, $app, $subMerchant];
    }

    public function testFetchAllMerchantEntitiesRelatedInfoWithId()
    {
        $this->fixtures->merchant->createAccount('100submerchant');

        $result = (new Core())->fetchAllMerchantEntitiesRelatedInfo(['100submerchant'], "id");

        $this->assertNotEmpty($result);
        $this->assertArrayKeysExist($result, ['merchant_info', 'count', 'error']);
        $this->assertArrayKeysExist($result['merchant_info'][0], ['merchant','merchant_detail','merchant_business_detail','merchant_website','merchant_verification_detail','bvs_validation','merchant_document']);
        $this->assertEquals(1, $result['count']);
        $this->assertEquals("", $result['error']['code']);
        $this->assertEquals("", $result['error']['description']);
    }

    public function testFetchAllMerchantEntitiesRelatedInfoWithEmail()
    {
        $result = (new Core())->fetchAllMerchantEntitiesRelatedInfo(['test@razorpay.com'], "email");

        $this->assertNotEmpty($result);
        $this->assertArrayKeysExist($result, ['merchant_info', 'count', 'error']);
        $this->assertArrayKeysExist($result['merchant_info'][0], ['merchant','merchant_detail','merchant_business_detail','merchant_website','merchant_verification_detail','bvs_validation','merchant_document']);
        $this->assertEquals(1, $result['count']);
        $this->assertEquals("", $result['error']['code']);
        $this->assertEquals("", $result['error']['description']);
    }
}
