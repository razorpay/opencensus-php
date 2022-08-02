<?php


namespace Functional\Merchant;

use RZP\Constants\Mode;
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
        list($merchantId, $submerchantId, $newAppId, $client) = $this->createAggregatorPartnerAndSubmerchantAndFetchMocks();

        $this->fixtures->create('referrals');

        $this->createCommissionForPartner($merchantId, $submerchantId, $newAppId);

        $this->core->updateAggregatorToReseller($merchantId);

        // partner type should be updated as reseller
        $merchant = $this->getDbEntityById('merchant', $merchantId);
        $this->assertEquals('reseller', $merchant->getPartnerType());

        // new merchant application with referred type should be created
        $updatedApplications = $this->getDbEntities('merchant_application',
            ['application_id' => '8ckeirnw84ifke'])->toArray();
        $this->assertCount(1, $updatedApplications);
        $this->assertEquals('referred', $updatedApplications[0]['type']);

        // all existing applications for partner should be deleted
        $oldApplications = $this->getDbEntities('merchant_application',
            ['application_id' => $client->getApplicationId()])->toArray();
        $this->assertCount(0, $oldApplications);

        // referral link data should not be deleted
        $referrals = $this->getDbEntity('referrals',
            ['merchant_id' => $merchantId, 'product' => 'primary']);
        $this->assertNotEmpty($referrals->getReferralLink());

        // merchant access map should be updated with new application id
        $accessMaps = $this->getDbEntities('merchant_access_map',
            ['entity_type' => 'application', 'entity_owner_id' => $merchantId])->toArray();
        $this->assertCount(2, $accessMaps);
        $this->assertEquals($newAppId, $accessMaps[0]['entity_id']);

        // commissions data should not be deleted for partner
        $commission = $this->getDbEntities("commission", ["partner_id" => $merchantId]);
        $this->assertCount(1, $commission);
    }

    public function testResellerToAggregatorPartnerWithNewAuth()
    {
        list(
            $partnerId, $resellerAppId, $managedAppId, $referredAppId, $subMerchant
            ) = $this->createResellerPartnerAndSubmerchantAndFetchMocks();

        $input = ["merchant_id" => $partnerId, "new_auth_create" => true];
        $this->core->migrateResellerToAggregatorPartner($input);

        // partner type should be updated as reseller
        $merchantOnLive = $this->getDbEntityById('merchant', $partnerId, 'live');
        $merchantOnTest = $this->getDbEntityById('merchant', $partnerId, 'test');
        $this->assertEquals('aggregator', $merchantOnLive->getPartnerType());
        $this->assertEquals('aggregator', $merchantOnTest->getPartnerType());
        $this->assertOnMerchantApplication($partnerId, $managedAppId, $referredAppId);
        $this->assertOnAccessMaps($partnerId, $subMerchant, $managedAppId);
        $this->assertOnPartnerConfigs($resellerAppId, $managedAppId, $referredAppId);
        $this->assertOnMerchantUser($merchantOnLive, $subMerchant);
    }

    private function assertOnMerchantApplication(string $partnerId, string $managedAppId, string $referredAppId)
    {
        // new merchant applications should be created for managed and referred
        $applicationsOnLive = $this->getDbEntities('merchant_application', ['merchant_id' => $partnerId], 'live')->toArray();
        $applicationsOnTest = $this->getDbEntities('merchant_application', ['merchant_id' => $partnerId], 'test')->toArray();
        $this->assertCount(2, $applicationsOnLive);
        $this->assertEquals('managed', $applicationsOnLive[0]['type']);
        $this->assertEquals($managedAppId, $applicationsOnLive[0]['application_id']);
        $this->assertEquals('referred', $applicationsOnLive[1]['type']);
        $this->assertEquals($referredAppId, $applicationsOnLive[1]['application_id']);

        $this->assertCount(2, $applicationsOnTest);
        $this->assertEquals('managed', $applicationsOnTest[0]['type']);
        $this->assertEquals($managedAppId, $applicationsOnTest[0]['application_id']);
        $this->assertEquals('referred', $applicationsOnTest[1]['type']);
        $this->assertEquals($referredAppId, $applicationsOnTest[1]['application_id']);
    }

    private function assertOnAccessMaps(string $partnerId, MerchantEntity $subMerchant, string $managedAppId)
    {
        // merchant access map should be updated with new application id
        $accessMapsOnLive = $this->getDbEntities(
            'merchant_access_map',
            ['entity_type' => 'application', 'entity_owner_id' => $partnerId],
            Mode::LIVE
        )->toArray();
        $accessMapsOnTest = $this->getDbEntities(
            'merchant_access_map',
            ['entity_type' => 'application', 'entity_owner_id' => $partnerId],
            Mode::TEST
        )->toArray();
        $this->assertCount(1, $accessMapsOnLive);
        $this->assertEquals($managedAppId, $accessMapsOnLive[0]['entity_id']);
        $this->assertEquals($subMerchant->getId(), $accessMapsOnLive[0]['merchant_id']);
        $this->assertCount(1, $accessMapsOnTest);
        $this->assertEquals($managedAppId, $accessMapsOnTest[0]['entity_id']);
        $this->assertEquals($subMerchant->getId(), $accessMapsOnTest[0]['merchant_id']);
    }

    private function assertOnPartnerConfigs(string $resellerAppId, string $managedAppId, string $referredAppId)
    {
        // old partner configs are still present
        $partnerConfigRepo = new PartnerConfigRepo();
        $oldConfigsOnLive = $partnerConfigRepo->fetchAllConfigForApps([$resellerAppId], Mode::LIVE);
        $this->assertCount(0, $oldConfigsOnLive);
        $oldConfigsOnTest = $partnerConfigRepo->fetchAllConfigForApps([$resellerAppId], Mode::TEST);
        $this->assertCount(0, $oldConfigsOnTest);

        // partner configs of managed apps should be updated
        $managedConfigsOnLive = $partnerConfigRepo->fetchAllConfigForApps([$managedAppId], Mode::LIVE);
        $this->assertCount(2, $managedConfigsOnLive);
        $managedConfigsOnTest = $partnerConfigRepo->fetchAllConfigForApps([$managedAppId], Mode::TEST);
        $this->assertCount(2, $managedConfigsOnTest);

        // one partner config of referred app should be created
        $referredConfigsOnLive = $partnerConfigRepo->fetchAllConfigForApps([$referredAppId], Mode::LIVE);
        $this->assertCount(1, $referredConfigsOnLive);
        $referredConfigsOnTest = $partnerConfigRepo->fetchAllConfigForApps([$referredAppId], Mode::TEST);
        $this->assertCount(1, $referredConfigsOnTest);
    }

    private function assertOnMerchantUser(MerchantEntity $partner, MerchantEntity $subMerchant)
    {
        // partner user should be created for subM: owner role for PG, view_only role for X
        $partnerUserId = $partner->primaryOwner()->getId();
        $partnerXUserOnLive = $this->getDbEntities(
            'merchant_user',
            ['merchant_id' => $subMerchant->getId(), 'user_id' => $partnerUserId, 'role' => 'view_only', 'product' => 'banking'],
            'live'
        );
        $partnerXUserOnTest = $this->getDbEntities(
            'merchant_user',
            ['merchant_id' => $subMerchant->getId(), 'user_id' => $partnerUserId, 'role' => 'view_only', 'product' => 'banking'],
            'test'
        );
        $this->assertCount(0, $partnerXUserOnLive);
        $this->assertCount(0, $partnerXUserOnTest);

        $partnerPGUserOnLive = $this->getDbEntities(
            'merchant_user',
            ['merchant_id' => $subMerchant->getId(), 'user_id' => $partnerUserId, 'role' => 'owner', 'product' => 'primary'],
            'live'
        );
        $partnerPGUserOnTest = $this->getDbEntities(
            'merchant_user',
            ['merchant_id' => $subMerchant->getId(), 'user_id' => $partnerUserId, 'role' => 'owner', 'product' => 'primary'],
            'test'
        );
        $this->assertCount(1, $partnerPGUserOnLive);
        $this->assertCount(1, $partnerPGUserOnTest);
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

        $this->setUpAuthServiceMocks($merchantId, $client->getApplicationId(), $app->getId(), $newAppId);

        return [$merchantId, $submerchantId, $newAppId, $client];
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

    private function createResellerPartnerAndSubmerchantAndFetchMocks(string $submerchantId = '101submerchant')
    {
        list($partnerId, $app, $subMerchant) = $this->createResellerPartnerAndSubmerchant($submerchantId);
        $oldAppId = $app->getId();

        $createParams = [
            'website' => 'http://www.monahan.com/harum-fuga-quae-culpa-quod',
            'merchant_id' => $partnerId,
            'type' => 'partner'
        ];
        $managedAppRequestParams = array_merge(['name' => 'et'], $createParams);
        $referredAppRequestParams = array_merge(['name' => 'Referred application'], $createParams);
        $managedAppId = 'managedr64ifke';
        $referredAppId = 'referred64ifke';
        $this->createManagedAndResellerOAuthApp($partnerId, $managedAppId, $referredAppId);

        $this->authServiceMock
            ->expects($this->exactly(3))
            ->method('sendRequest')
            ->withConsecutive(
                ['applications', 'POST', $managedAppRequestParams],
                ['applications', 'POST', $referredAppRequestParams],
                ['applications/'.$app->getId(), 'PUT', ['merchant_id' => $partnerId]])
            ->willReturnOnConsecutiveCalls($app = ['id'=> $managedAppId], ['id'=> $referredAppId], []);

        return [$partnerId, $oldAppId, $managedAppId, $referredAppId, $subMerchant];
    }

    private function createManagedAndResellerOAuthApp(string $partnerId, string $managedAppId, string $referredAppId)
    {
        $managedAppAttributes = [
            'merchant_id' => $partnerId,
            'partner_type'=> 'managed',
            'id' => $managedAppId,
            'name' => 'managed'
        ];
        $this->fixtures->merchant->createDummyPartnerApp($managedAppAttributes, false);

        $referredAppAttributes = [
            'merchant_id' => $partnerId,
            'partner_type'=> 'referred',
            'id' => $referredAppId,
            'name' => 'referred'
        ];
        $this->fixtures->merchant->createDummyPartnerApp($referredAppAttributes, false);
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
}
