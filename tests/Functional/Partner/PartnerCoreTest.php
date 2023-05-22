<?php

namespace RZP\Tests\Functional\Partner\Commission;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Constants\Product;
use RZP\Models\Partner\Core;
use RZP\Constants\Mode as EnvMode;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Traits\MocksPartnershipsService;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Partner\Config\Repository as PartnerConfigRepo;
use RZP\Models\Merchant\MerchantApplications as MerchantApplications;

class PartnerCoreTest extends OAuthTestCase
{
    use PartnerTrait;
    use DbEntityFetchTrait;
    use MocksPartnershipsService;

    const RZP_ORG  = '100000razorpay';

    protected $core;

    /**
     * @var object
     */
    private $authServiceMock;

    /**
     * @var MerchantApplications\Core
     */
    private $merchantApplicationCore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->merchantApplicationCore = new MerchantApplications\Core();

        $this->core = new Core();
    }

    /**
     * This testcase validates the following
     * 1. Migrates Reseller partner to Aggregator with new auth
     * 2. Removes referred merchant application of partner
     * 3. Creates new managed and referred merchant applications
     * 4. Updates default and overridden partner configs with managed app
     * 5. Updates one subM accessMap with managed app
     * 6. Creates merchant_users with owner role for subM PG dashboard
     * 7. Creates merchant_users with view_only role for subM Banking dashboard
     */
    public function testResellerToAggregatorPartnerWithNewAuth()
    {
        list(
            $partnerId, $resellerAppId, $managedAppId, $referredAppId, $subMerchant
            ) = $this->createResellerPartnerAndSubmerchantAndFetchMocks();

        $input = ["merchant_id" => $partnerId, "new_auth_create" => true];
        $this->mockPartnershipsServiceTreatment([], [], 'createPartnerMigrationAudit');
        $this->app['rzp.mode'] = EnvMode::TEST;

        $this->core->migrateResellerToAggregatorPartner($input);

        // partner type should be updated as aggregator
        $merchantOnLive = $this->getDbEntityById('merchant', $partnerId, 'live');
        $merchantOnTest = $this->getDbEntityById('merchant', $partnerId, 'test');
        $this->assertEquals('aggregator', $merchantOnLive->getPartnerType());
        $this->assertEquals('aggregator', $merchantOnTest->getPartnerType());
        $this->assertOnMerchantApplication($partnerId, $resellerAppId, $managedAppId, $referredAppId);
        $this->assertOnAccessMaps($partnerId, $subMerchant, $managedAppId);
        $this->assertOnPartnerConfigs($resellerAppId, $managedAppId, $referredAppId);
        $this->assertOnMerchantUser($merchantOnLive, $subMerchant);
    }

    /**
     * This testcase validates the following
     * 1. Migrates Reseller partner to Aggregator with old auth restoration
     * 2. Removes referred merchant application of partner
     * 3. Restores old managed and referred merchant applications
     * 4. Updates default and overridden partner configs with managed app
     * 5. Updates one subM accessMap with managed app
     * 6. Creates merchant_users with owner role for subM PG dashboard
     * 7. Creates merchant_users with view_only role for subM Banking dashboard
     */
    public function testResellerToAggregatorPartnerWithOldAuth()
    {
        list(
            $partnerId, $resellerAppId, $oldManagedAppId, $oldReferredAppId, $subMerchant
            ) = $this->setupAggrTurnedResellerPartner();

        $this->authServiceMock
            ->expects($this->exactly(1))
            ->method('sendRequest')
            ->withConsecutive(
                ['applications/restore', 'PUT', [
                    'merchant_id' => $partnerId,
                    'app_ids_to_restore' => [ $oldManagedAppId, $oldReferredAppId ],
                    'app_ids_to_delete' => [ $resellerAppId ]
                ]]
            )
            ->willReturnOnConsecutiveCalls(
                $app = []
            );
        $this->mockPartnershipsServiceTreatment([], ['status_code' => 200], 'createPartnerMigrationAudit');
        $this->app['rzp.mode'] = EnvMode::TEST;

        $input = [ "merchant_id" => $partnerId, "new_auth_create" => false ];
        $this->core->migrateResellerToAggregatorPartner($input);

        // partner type should be updated as aggregator
        $merchantOnLive = $this->getDbEntityById('merchant', $partnerId, 'live');
        $merchantOnTest = $this->getDbEntityById('merchant', $partnerId, 'test');
        $this->assertEquals('aggregator', $merchantOnLive->getPartnerType());
        $this->assertEquals('aggregator', $merchantOnTest->getPartnerType());
        $this->assertOnDeletedMerchantApplication($partnerId, $resellerAppId, $oldManagedAppId, $oldReferredAppId);
        $this->assertOnMerchantApplication($partnerId, $resellerAppId, $oldManagedAppId, $oldReferredAppId);
        $this->assertOnAccessMaps($partnerId, $subMerchant, $oldManagedAppId);
        $this->assertOnPartnerConfigs($resellerAppId, $oldManagedAppId, $oldReferredAppId);
        $this->assertOnMerchantUser($merchantOnLive, $subMerchant);
    }

    /**
     * This testcase validates the following
     * 1. The subM is deleted on test, creating a data mismatch on live and test
     * 2. Throws LogicException
     */
    public function testResellerToAggrPartnerWithOldAuthDataMismatch()
    {
        list(
            $partnerId, $resellerAppId, $oldManagedAppId, $oldReferredAppId, $subMerchant
            ) = $this->setupAggrTurnedResellerPartner();

        $merchantApp = $this->getTrashedDbEntity('merchant_application', ['application_id' => $oldReferredAppId], 'test');
        $merchantApp->forceDelete();

        $input = [ "merchant_id" => $partnerId, "new_auth_create" => false ];

        $this->expectException(Exception\LogicException::class);

        $this->core->migrateResellerToAggregatorPartner($input);
    }

    private function assertOnDeletedMerchantApplication(
        string $partnerId, string $resellerAppId, string $managedAppId, string $referredAppId
    )
    {
        // new merchant applications should be created for managed and referred
        $applicationsOnLive = $this->getTrashedDbEntities(
            'merchant_application', ['merchant_id' => $partnerId], 'live'
        )->whereNotIn('application_id', [$resellerAppId])->toArray();
        $applicationsOnTest = $this->getTrashedDbEntities(
            'merchant_application', ['merchant_id' => $partnerId], 'test'
        )->whereNotIn('application_id', [$resellerAppId])->toArray();

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

    private function assertOnMerchantApplication(
        string $partnerId, string $resellerAppId, string $managedAppId, string $referredAppId
    )
    {
        // old merchant applications should deleted
        $applicationsOnLive = $this->getDbEntities('merchant_application', ['application_id' => $resellerAppId], 'live');
        $applicationsOnTest = $this->getDbEntities('merchant_application', ['application_id' => $resellerAppId], 'test');
        $this->assertCount(0, $applicationsOnLive);
        $this->assertCount(0, $applicationsOnTest);

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
        $this->createMerchantUser($subMerchant);

        $this->ba->adminAuth();

        return [$partnerId, $app, $subMerchant];
    }

    private function createMerchantUser($subMerchant)
    {
        $this->fixtures->create('user', ['id' => 'MerchantUser02', 'email' => $subMerchant['email']]);
        $userMapping = [
            'merchant_id' => $subMerchant->getId(),
            'user_id'     => 'MerchantUser02',
            'role'        => 'owner',
            'product'     => Product::PRIMARY
        ];
        $this->fixtures->user->createUserMerchantMapping($userMapping, 'test');
        $this->fixtures->user->createUserMerchantMapping($userMapping, 'live');
    }

    private function setupAggrTurnedResellerPartner(string $partnerId = '10000000000000', string $submerchantId = '100submerchant')
    {
        $this->setUpNonPurePlatformPartnerAndSubmerchant($partnerId, $submerchantId);
        $this->fixtures->merchant->edit($submerchantId, ['parent_id' => null]);
        $this->createOAuthApplication(['merchant_id' => $partnerId, 'partner_type' => 'reseller']);

        $subMerchant = $this->getDbEntity('merchant', ['id' => $submerchantId]);
        $this->createMerchantUser($subMerchant);
        $aggregatorAppIds = $this->merchantApplicationCore->getMerchantAppIds($partnerId);

        foreach ($aggregatorAppIds as $appId)
        {
            $this->merchantApplicationCore->deleteByApplication($appId);
        }
        $this->fixtures->merchant->edit($partnerId, [ 'partner_type' => 'reseller' ]);

        $appAttributes = [
            'merchant_id' => $partnerId,
            'partner_type'=> 'reseller',
            'id' => "resellerS00000"
        ];
        $resellerApp = $this->fixtures->merchant->createDummyPartnerApp($appAttributes);

        $accessMapId = $this->getDbEntities('merchant_access_map', ['entity_owner_id' => $partnerId])->getIds()[0];
        $this->fixtures->edit('merchant_access_map', $accessMapId, [ "entity_id" => $resellerApp->getId() ]);

        $this->createConfigForPartnerApp($aggregatorAppIds[1]);
        $this->createConfigForPartnerApp($resellerApp->getId());
        $this->createConfigForPartnerApp($resellerApp->getId(), $submerchantId);

        $this->ba->adminAuth();

        return [ $partnerId, $resellerApp->getId(), $aggregatorAppIds[0], $aggregatorAppIds[1], $subMerchant ];
    }
}
