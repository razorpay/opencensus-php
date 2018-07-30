<?php

namespace RZP\Tests\Functional\Merchant\Partner;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Request;
use RZP\Models\Settings\Accessor;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class PartnerTest extends OAuthTestCase
{
    use OAuthTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    const PARTNER                = 'partner';
    const ACTIVATION             = 'activation';
    const DEACTIVATION           = 'deactivation';
    const DEFAULT_MERCHANT_ID    = '10000000000000';
    const DEFAULT_SUBMERCHANT_ID = '10000000000009';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PartnerTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

        $this->fixtures->merchant_detail->create(['merchant_id' => self::DEFAULT_SUBMERCHANT_ID]);

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->ba->privateAuth();
    }

    public function testMarkingMerchantAsPartner()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    /**
     * Tests marking a merchant as a partner after the merchant has been marked and unmarked as a partner before
     */
    public function testMarkingMerchantAsPartnerAgain()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->createMerchantRequest(self::ACTIVATION, true);

        // Using a different the merchant request id here
        $this->createMerchantRequest(
            self::DEACTIVATION,
            false,
            Merchant\Constants::RESELLER,
            [
                'id' => 'mrId1000000001',
            ]);

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testUnmarkingMerchantAsPartner()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testMarkingMerchantAsPartnerMissingType()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }
    public function testMarkingMerchantAsPartnerInvalidType()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testMarkingMerchantAsPartnerInvalidNameToType()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testMerchantMarksSelfAsPartner()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantUnmarksSelfAsPartner()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMarkAsPartnerWithMissingSubmission()
    {
        $merchantRequest = $this->createMerchantRequest(self::ACTIVATION, false);

        $merchantRequestId = $merchantRequest->getPublicId();

        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);
    }

    public function testApprovingMarkAsPartnerMerchantRequest()
    {
        // Create a merchant request
        $merchantRequest = $this->createMerchantRequest(self::ACTIVATION, true);

        $merchant = $merchantRequest->merchant;

        // Mock create application call to auth service
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $createParams = [
            'name'     => $merchant->getName(),
            'website'  => $merchant->getWebsite(),
            'type'     => self::PARTNER,
        ];

        $requestParams = array_merge($requestParams, $createParams);

        $this->setAuthServiceMockDetail('applications', 'POST', $requestParams);

        // Set the admin auth
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, $liveMode);

        $this->assertTrue($merchant->isPartner());

        $this->assertEquals($merchant->getPartnerType(), Merchant\Constants::RESELLER);
    }

    /**
     * Test approving the partner activation merchant request for a merchant who is already a partner.
     */
    public function testMarkPartnerAsPartner()
    {
        $merchantId = '10000000000000';

        $merchantRequest = $this->createMerchantRequest('activation', true);

        $merchantRequestId = $merchantRequest->getPublicId();

        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->markMerchantAsPartner($merchantId, Merchant\Constants::RESELLER);

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);
    }

    public function testApprovingUnmarkAsPartnerMerchantRequest()
    {
        $merchantId = self::DEFAULT_MERCHANT_ID;

        $this->createDummyPartnerApp();

        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->setAuthServiceMockDetail('applications/8ckeirnw84ifke', 'PUT', $requestParams);

        // Set the admin auth
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->markMerchantAsPartner($merchantId, Merchant\Constants::RESELLER);

        $merchant = $this->getDbEntityById('merchant', $merchantId, $liveMode);

        $this->assertTrue($merchant->isPartner());

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        // Create a merchant request
        $merchantRequest = $this->createMerchantRequest(self::DEACTIVATION);

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', $merchantId, $liveMode);

        $this->assertFalse($merchant->isPartner());
    }

    public function testApprovingPurePlatformActivationRequest()
    {
        // Set the admin auth
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        // Create a merchant request
        $merchantRequest = $this->createMerchantRequest(
            self::ACTIVATION,
            true,
            Merchant\Constants::PURE_PLATFORM);

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, $liveMode);

        $this->assertTrue($merchant->isPartner());
    }

    public function testApprovingPurePlatformDeactivationRequest()
    {
        // Set the admin auth
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $merchantId = self::DEFAULT_MERCHANT_ID;

        $this->markMerchantAsPartner($merchantId, Merchant\Constants::PURE_PLATFORM);

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        // Create a merchant request
        $merchantRequest = $this->createMerchantRequest(self::DEACTIVATION);

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', $merchantId, $liveMode);

        $this->assertFalse($merchant->isPartner());
    }

    public function testUnmarkNonPartnerMerchantAsPartner()
    {
        $merchantRequest = $this->createMerchantRequest('deactivation', true);

        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, $liveMode);

        $this->assertFalse($merchant->isPartner());
    }

    public function testLinkedAccountMarkedAsPartner()
    {
        // Create a merchant request
        $merchantRequest = $this->createMerchantRequest(self::ACTIVATION, true);

        $this->fixtures->merchant->createAccount('100DemoAccount');

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['parent_id' => '100DemoAccount']);

        // Set the admin auth
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);
    }

    /**
     * Test that the flow raises an exception when
     * the admin who does not have access to a submerchant tries to link it to a partner merchant.
     */
    public function testAddPartnerAccessMapSubmerchantAccessUnauthorized()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->createDummyPartnerApp();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddPartnerAccessMap()
    {
        $partner = $this->allowAdminToAccessPartnerMerchant();

        $submerchant = $this->allowAdminToAccessSubMerchant();

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'fully_managed']);

        $this->createDummyPartnerApp();

        $this->ba->adminAuth();

        $this->startTest();

        // Fully managed will create a user with the role:owner for the submerchant
        $partnerUser = $partner->users()->first()->toArrayPublic();

        $merchantUsers = $submerchant->users()->get()->toArrayPublic();

        $this->assertEquals(2, $merchantUsers['count']);

        $userIds = array_map(function($item){
            return $item['id'];
        }, $merchantUsers['items']);

        $this->assertContains($partnerUser['id'], $userIds);

        $submerchant = $this->getDbEntityById('merchant', self::DEFAULT_SUBMERCHANT_ID);

        $this->assertEquals(self::DEFAULT_MERCHANT_ID, $submerchant->getReferrer());
    }

    public function testAddPartnerAccessMapForDiffOrgSubmerchant()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $org = $this->fixtures->create('org');

        $this->fixtures->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, ['org_id' => $org->getId()]);

        $this->createDummyPartnerApp();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddAccessMapWithoutPartnerContext()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->createDummyPartnerApp();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddAccessMapToPurePlatform()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'pure_platform']);

        $this->createDummyPartnerApp();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddAccessMapToNonPartner()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $this->createDummyPartnerApp();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddPartnerAccessMapAgain()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $app = $this->createDummyPartnerApp();

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_type' => 'application',
                'entity_id'   => $app->getId(),
                'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
            ]);

        $this->ba->adminAuth();

        // If the entity already exists, the existing entity is returned
        $accessMap = $this->getDbLastEntity('merchant_access_map');

        $response = $this->startTest();

        $this->assertEquals($accessMap->toArrayPublic(), $response);
    }

    public function testRemovePartnerAccessMap()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $submerchant = $this->allowAdminToAccessSubMerchant();

        $partnerUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->addUserToMerchant($partnerUser, self::DEFAULT_SUBMERCHANT_ID, 'owner');

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $app = $this->createDummyPartnerApp();

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_type' => 'application',
                'entity_id'   => $app->getId(),
                'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
            ]);

        $submerchant->retag(['Ref-' . self::DEFAULT_MERCHANT_ID]);

        $this->assertEquals(self::DEFAULT_MERCHANT_ID, $submerchant->getReferrer());

        $merchantUsers = $submerchant->users()->get()->toArrayPublic();

        $this->assertEquals(2, $merchantUsers['count']);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $response = $this->sendRequest($testData['request']);

        $response->assertStatus(204);

        $submerchant = $this->getDbEntityById('merchant', self::DEFAULT_SUBMERCHANT_ID);

        $this->assertEquals(null, $submerchant->getReferrer());

        $merchantUsers = $submerchant->users()->get()->toArrayPublic();

        // The above test should not delete the mapping. Dashboard access has to be revoked separately.
        $this->assertEquals(2, $merchantUsers['count']);
    }

    public function testRemoveNonExistingPartnerAccessMap()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->createDummyPartnerApp();

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $response = $this->sendRequest($testData['request']);

        $response->assertStatus(204);
    }

    public function testRemovePartnerAccessMapAgain()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->createDummyPartnerApp();

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $response = $this->sendRequest($testData['request']);

        $response->assertStatus(204);
    }

    public function testNoSubmerchantAccountAccessForReseller()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $submerchant = $this->allowAdminToAccessSubMerchant();

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->createDummyPartnerApp();

        $merchantUsers = $submerchant->users()->get()->toArrayPublic();

        $this->assertEquals(0, $merchantUsers['count']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchant()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $submerchant = $this->allowAdminToAccessSubMerchant();

        $partnerUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $submerchantUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->addUserToMerchant($partnerUser, self::DEFAULT_SUBMERCHANT_ID, 'owner');

        $submerchantOwners = $submerchant->owners()->get()->toArrayPublic();

        $this->assertEquals(2, $submerchantOwners['count']);

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['activation_status' => 'under_review']);

        $app = $this->createDummyPartnerApp();

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_type' => 'application',
                'entity_id'   => $app->getId(),
                'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
            ]);

        $this->ba->adminProxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['response']['content']['user'] = $submerchantUser->toArrayPublic();

        $this->startTest($testData);
    }

    public function testFetchPartnerSubmerchants()
    {
        $this->createPartnerAndAddMultipleSubmerchants();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsFilters()
    {
        $this->createPartnerAndAddMultipleSubmerchants();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsPaginationFilters()
    {
        $this->createPartnerAndAddMultipleSubmerchants();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    /**
     * Tests the list submerchants api when there are no submerchants for the partner
     */
    public function testFetchPartnerSubmerchantsEmptyList()
    {
        $this->createPartnerAndUser();

        $this->createDummyPartnerApp();

        $this->ba->adminProxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testFetchPartnerSubmerchantProxyAuth()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $submerchant = $this->allowAdminToAccessSubMerchant();

        $partnerUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $submerchantUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->addUserToMerchant($partnerUser, self::DEFAULT_SUBMERCHANT_ID, 'owner');

        $submerchantOwners = $submerchant->owners()->get()->toArrayPublic();

        $this->assertEquals(2, $submerchantOwners['count']);

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $app = $this->createDummyPartnerApp();

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_type' => 'application',
                'entity_id'   => $app->getId(),
                'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
            ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $partnerUser->getId());

        $testData = $this->testData[__FUNCTION__];

        $testData['response']['content']['user'] = $submerchantUser->toArrayPublic();

        $this->startTest($testData);
    }

    public function testFetchPartnerSubmerchantProxyAuthSellerApp()
    {
        $partnerUser = $this->createPartnerAndUser();

        $this->createSubmerchantAndUser();

        $this->ba->proxyAuth('rzp_test_10000000000000', $partnerUser->getId(), 'sellerapp');

        $this->startTest();
    }

    protected function createMerchantRequest(
        string $merchantRequestName,
        bool $createSubmission = false,
        string $partnerType = Merchant\Constants::RESELLER,
        array $attributes = [])
    {
        $defaults = [
            Request\Entity::MERCHANT_ID => self::DEFAULT_MERCHANT_ID,
            Request\Entity::TYPE        => self::PARTNER,
            Request\Entity::NAME        => $merchantRequestName,
        ];

        $attributes = array_merge($attributes, $defaults);

        $merchantRequest = $this->fixtures->create('merchant_request:default_merchant_request', $attributes);

        if (($merchantRequestName === self::ACTIVATION) and ($createSubmission === true))
        {
            $data = [
                'partner_type' => $partnerType,
            ];

            Accessor::for ($merchantRequest, self::PARTNER)->upsert($data)->save();
        }

        return $merchantRequest;
    }

    protected function allowAdminToAccessMerchant(string $merchantId)
    {
        $merchant = Merchant\Entity::find($merchantId);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);

        return $merchant;
    }

    protected function allowAdminToAccessPartnerMerchant()
    {
        return $this->allowAdminToAccessMerchant(self::DEFAULT_MERCHANT_ID);
    }

    protected function allowAdminToAccessSubMerchant()
    {
        return $this->allowAdminToAccessMerchant(self::DEFAULT_SUBMERCHANT_ID);
    }

    protected function markMerchantAsPartner(string $merchantId, string $partnerType)
    {
        $this->fixtures->merchant->edit($merchantId, ['partner_type' => $partnerType]);
    }

    protected function addUserToMerchant($user, $merchantId, $role)
    {
        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => $role,
        ];

        return $this->fixtures->create('user:user_merchant_mapping', $mappingData);
    }

    protected function createPartnerAndAddMultipleSubmerchants()
    {
        $this->createPartnerAndUser();

        $this->createSubmerchantAndUser();

        $submerchantId = '10000000000011';

        $this->allowAdminToAccessMerchant($submerchantId);

        $this->fixtures->user->createUserForMerchant($submerchantId);

        $app = $this->createDummyPartnerApp();

        // Link new submerchants to the partner account
        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_type' => 'application',
                'entity_id'   => $app->getId(),
                'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
            ]);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_type' => 'application',
                'entity_id'   => $app->getId(),
                'merchant_id' => $submerchantId,
            ]);
    }

    protected function createPartnerAndUser()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'fully_managed']);

        $partnerUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        return $partnerUser;
    }

    protected function createDummyPartnerApp(array $attributes = [])
    {
        $defaults = [
            'id'          => '8ckeirnw84ifke',
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'name'        => 'Internal',
            'website'     => 'https://www.razorpay.com',
            'logo_url'    => '/logo/app_logo.png',
            'category'    => null,
            'type'        => self::PARTNER,
        ];

        $attributes = array_merge($defaults, $attributes);

        return $this->createOAuthApplication($attributes);
    }

    protected function createSubmerchantAndUser()
    {
        $this->allowAdminToAccessSubMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, [
            'name' => 'random_name_1',
            'email' => 'user@example.com',
        ]);

        $this->fixtures->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['activation_status' => 'under_review']);

        $submerchantUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        return $submerchantUser;
    }
}
