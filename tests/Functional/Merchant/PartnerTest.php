<?php

namespace RZP\Tests\Functional\Merchant\Partner;

use DB;
use Mail;
use Mockery;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Batch;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\User\Role;
use Razorpay\OAuth\Application;
use Illuminate\Http\UploadedFile;
use RZP\Models\Merchant\Request;
use RZP\Models\Settings\Accessor;
use RZP\Models\Merchant\AccessMap;
use RZP\Services\SalesForceClient;
use RZP\Models\BankingAccount\Channel;
use RZP\Mail\Merchant\PartnerOnBoarded;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\Merchant\MerchantTest;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Mail\Merchant\CreateSubMerchantAffiliate;
use RZP\Models\Merchant\MerchantApplications\Entity;

class PartnerTest extends OAuthTestCase
{
    use PartnerTrait;
    use BatchTestTrait;

    const PARTNER                = 'partner';
    const ACTIVATION             = 'activation';
    const DEACTIVATION           = 'deactivation';
    const DUMMY_APP_ID_1         = '8ckeirnw84ifke';
    const DUMMY_APP_ID_2         = '10000RandomApp';
    const DUMMY_APP_ID_3         = '11111RandomApp';
    const DEFAULT_MERCHANT_ID    = '10000000000000';
    const DEFAULT_SUBMERCHANT_ID = '10000000000009';
    const RZP_ORG                = '100000razorpay';

    protected function setUp(): void
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

    public function testDocumentUploadForSubmerchant()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->merchant->addFeatures(['partner_sub_kyc_access'], self::DEFAULT_MERCHANT_ID);

        $this->updateUploadDocumentData(__FUNCTION__);

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertArrayNotHasKey('promoter_address_url', $content['verification']['required_fields']);

        $merchantDocument = $this->getDbLastEntity('merchant_document');

        $this->assertEquals('merchant', $merchantDocument['entity_type']);
        $this->assertEquals('10000000000009', $merchantDocument['entity_id']);
    }

    protected function updateUploadDocumentData(string $callee)
    {
        $testData = &$this->testData[$callee];

        $testData['request']['files']['file'] = new UploadedFile(
            __DIR__ . '/../Storage/a.png',
            'a.png',
            'image/png',
            filesize(__DIR__ . '/../Storage/a.png'),
            null,
            true);
    }

    public function testSubmerchantKYCByPartnerWithInvalidSubmerchant()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->merchant->addFeatures(['partner_sub_kyc_access'], self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testRequestKycAccessByPartner()
    {
        $this->ba->proxyAuth();
        $this->createResellerPartnerSubmerchant();

        $response1 = $this->startTest();

        // running twice shouldn't give any error
        $response2 = $this->startTest();
        $this->assertEquals($response1['id'], $response2['id']);
    }

    public function testConfirmKycAccessRequest()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->create('partner_kyc_access_state');

        $this->ba->directAuth();
        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        // calling confirm again should give error
        $this->runRequestResponseFlow($this->testData['testConfirmKycAccessRequestAgain']);

        // requesting kyc access again should give error
        $this->ba->proxyAuth();
        $this->runRequestResponseFlow($this->testData['testRequestKycAccessByPartnerAgain']);
    }

    public function testConfirmAfterRejectKycAccessRequest()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->create('partner_kyc_access_state');

        $this->ba->directAuth();
        $this->runRequestResponseFlow($this->testData['testRejectKycAccessRequest']);

        // calling confirm now should work fine
        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        // revoke access
        $user = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);
        $this->ba->proxyAuth('rzp_test_'.self::DEFAULT_SUBMERCHANT_ID, $user['id']);
        $this->runRequestResponseFlow($this->testData['testRevokeKycAccess']);
    }

    public function testRejectKycAccessRequestExceedsMaxTimes()
    {
        $this->createResellerPartnerSubmerchant();
        $this->fixtures->create('partner_kyc_access_state');
        $this->ba->directAuth();

        $data = $this->testData['testRejectKycAccessRequest'];
        $this->runRequestResponseFlow($data);

        $rejectAgain = $data;
        $rejectAgain['response']['content']['rejection_count'] = 2;
        $this->runRequestResponseFlow($rejectAgain);

        $rejectAgain = $data;
        $rejectAgain['response']['content']['rejection_count'] = 3;
        $this->runRequestResponseFlow($rejectAgain);

        // requesting kyc access again should give error
        $this->ba->proxyAuth();
        $this->runRequestResponseFlow($this->testData['testRequestKycAccessAfterMaxTimesRejected']);
    }

    public function testPartnerSubmerchantFetchForKycAccess()
    {
        $this->createResellerPartnerSubmerchant();
        $this->fixtures->create('partner_kyc_access_state');

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testSubmerchantKYCByPartnerWithMissingFeatureFlag()
    {
        $this->createResellerPartnerSubmerchant();
        $this->fixtures->create('partner_kyc_access_state');

        $this->ba->directAuth();
        $this->runRequestResponseFlow($this->testData['testConfirmKycAccessRequest']);

        $this->ba->proxyAuth();
        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testSubmerchantKYCByPartner()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->merchant->addFeatures(['partner_sub_kyc_access'], self::DEFAULT_MERCHANT_ID);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_SUBMERCHANT_ID);

        // assert legal entity data
        $legalEntity = $this->getDbEntityById('legal_entity', $merchant->getLegalEntityId());

        $this->assertEquals(1, $legalEntity->getBusinessTypeValue());
        $this->assertEquals($legalEntity->getMcc(), 8931);
        $this->assertEquals('financial_services', $legalEntity->getBusinessCategory());
        $this->assertEquals('accounting', $legalEntity->getBusinessSubcategory());
    }

    public function testfetchSubmerchantActivationByPartner()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->merchant->addFeatures(['partner_sub_kyc_access'], self::DEFAULT_MERCHANT_ID);

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
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

        $app = ['id'=>'8ckeirnw84ifke'];

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->mockAuthServiceCreateApplication($merchant, $app);

        // Set the admin auth
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        //check if referral links are created for the partner
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'banking', Mode::TEST));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'primary', Mode::TEST));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'banking', Mode::LIVE));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'primary', Mode::LIVE));

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, $liveMode);

        $merchantApplications = (new MerchantApplications\Repository())->fetchMerchantApplication(self::DEFAULT_MERCHANT_ID, Merchant\Constants::MERCHANT_ID);

        $applicationTypes = $merchantApplications->pluck(Entity::TYPE)->toArray();

        $applicationType = $applicationTypes[0];

        $this->assertEquals($applicationType, MerchantApplications\Entity::REFERRED);

        $this->assertTrue($merchant->isPartner());

        $this->assertEquals($merchant->getPartnerType(), Merchant\Constants::RESELLER);

        $this->assertTrue($merchant->isFeatureEnabled(Feature\Constants::GENERATE_PARTNER_INVOICE));
    }

    public function testApprovingMarkAsPartnerWebsiteMissingMerchantRequest()
    {
        // Create a merchant request
        $merchantRequest = $this->createMerchantRequest(self::ACTIVATION, true);

        $merchant = $merchantRequest->merchant;

        $this->fixtures->edit('merchant', $merchant->getId(), ['website' => '']);

        $merchant->reload();

        $app = ['id'=>'8ckeirnw84ifke'];

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->mockAuthServiceCreateApplication($merchant, $app);

        // Set the admin auth
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, $liveMode);

        $merchantApplications = (new MerchantApplications\Repository())->fetchMerchantApplication(self::DEFAULT_MERCHANT_ID, Merchant\Constants::MERCHANT_ID);

        $applicationTypes = $merchantApplications->pluck(Entity::TYPE)->toArray();

        $applicationType = $applicationTypes[0];

        $this->assertEquals($applicationType, MerchantApplications\Entity::REFERRED);

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
        $submerchant = $this->allowAdminToAccessSubMerchant();

        $merchantId = self::DEFAULT_MERCHANT_ID;

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator']);

        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->setAuthServiceMockDetail('applications/8ckeirnw84ifke', 'PUT', $requestParams);

        // Set the admin auth
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->markMerchantAsPartner($merchantId, Merchant\Constants::AGGREGATOR);

        $merchant = $this->getDbEntityById('merchant', $merchantId, $liveMode);

        $this->assertTrue($merchant->isPartner());

        // attach a submerchant to the partner and give dashboard access
        $partnerUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $submerchantUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        // create mapping on live mode too
        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
            'user_id'     => $submerchantUser['id'],
            'role'        => 'owner',
        ], 'live');

        // giving dashboard access
        $mappingParams = [
            'role' => 'owner',
            'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
            'created_at'  => Carbon::now()->getTimestamp(),
            'updated_at'  => Carbon::now()->getTimestamp(),
        ];

        $submerchant->setConnection('test')->users()->attach([$partnerUser['id'] => $mappingParams]);
        $submerchant->setConnection('live')->users()->attach([$partnerUser['id'] => $mappingParams]);

        $accessMap = [
            'id'              => 'CMe2wjY0hiWBrL',
            'entity_type'     => 'application',
            'entity_id'       => $app->getId(),
            'merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
            'entity_owner_id' => self::DEFAULT_MERCHANT_ID,
        ];
        $accessMap = $this->fixtures->create('merchant_access_map', $accessMap);

        $submerchantUsers = $submerchant->users()->get()->toArrayPublic();

        $this->assertEquals(2, $submerchantUsers['count']);

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        // Create a merchant request
        $merchantRequest = $this->createMerchantRequest(self::DEACTIVATION);

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', $merchantId, $liveMode);

        $this->assertFalse($merchant->isPartner());

        // test that on unmark, all dashboard access mappings, access maps are deleted
        $submerchant = $this->getDbEntityById('merchant', self::DEFAULT_SUBMERCHANT_ID, 'live');

        $submerchantUsers = $submerchant->setConnection('test')->users()->get()->toArrayPublic();
        $this->assertEquals(1, $submerchantUsers['count']);

        $submerchantUsers = $submerchant->setConnection('live')->users()->get()->toArrayPublic();
        $this->assertEquals(1, $submerchantUsers['count']);

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['id' => $accessMap->getId()], 'live');
        $this->assertNull($accessMapEntity);

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['id' => $accessMap->getId()], 'test');
        $this->assertNull($accessMapEntity);

        // Assert that the access maps are getting soft-deleted
        $accessMapEntity = (new AccessMap\Entity);

        $accessMapEntity->setConnection('test')
                        ->withTrashed()
                        ->findOrFail($accessMap->getId());

        $accessMapEntity->setConnection('live')
                        ->withTrashed()
                        ->findOrFail($accessMap->getId());

        $merchantApplications = (new MerchantApplications\Repository())->fetchMerchantApplication(self::DEFAULT_MERCHANT_ID, Merchant\Constants::MERCHANT_ID);

        $this->assertEquals(0, count($merchantApplications));
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

        $merchantApplications = (new MerchantApplications\Repository())->fetchMerchantApplication(self::DEFAULT_MERCHANT_ID, Merchant\Constants::MERCHANT_ID);

        $this->assertEquals(0, count($merchantApplications));

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

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testPartnerSubmerchantMap()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->ba->batchAppAuth();

        $this->expectstorkInvalidateAffectedOwnersCacheRequest(self::DEFAULT_SUBMERCHANT_ID);

        $this->startTest();
    }

    public function testPartnerSubmerchantLinkViaBatch()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->createDummyPartnerApp( ['partner_type' => 'reseller']);

        $this->ba->batchAppAuth();

        $this->startTest();

        $accessMapEntity = $this->getDbEntity('merchant_access_map');

        $this->assertEquals($accessMapEntity['entity_owner_id'], '10000000000000');
        $this->assertEquals($accessMapEntity['merchant_id'], '10000000000009');
    }

    public function testPartnerSubmerchantDeLinkViaBatch()
    {
        $this->testPartnerSubmerchantLinkViaBatch();

        $this->startTest();

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
                                                                      'entity_owner_id' => '10000000000000'], 'live');
        $this->assertNull($accessMapEntity);

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
                                                                      'entity_owner_id' => '10000000000000'], 'test');
        $this->assertNull($accessMapEntity);
    }

    public function testPartnerSubmerchantTypeUpdateViaBatch()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'aggregator']);

        $managedApp = $this->fixtures->merchant->createDummyPartnerApp( ['partner_type' => 'aggregator'], true);
        $referralApp = $this->fixtures->merchant->createDummyReferredAppForManaged( ['partner_type' => 'reseller'], true);

        $this->fixtures->create('user', ['id' => self::DEFAULT_MERCHANT_ID, 'email' => 'test@razorpay.com']);

        DB::connection('test')->table('merchant_users')
            ->insert([
                'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
                'user_id'     => self::DEFAULT_MERCHANT_ID,
                'role'        => 'owner',
                'created_at'  => 1793805150,
                'updated_at'  => 1793805150
            ]);

        $this->fixtures->create('merchant_access_map', [
            'entity_owner_id' => self::DEFAULT_MERCHANT_ID,
            'merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
            'entity_type'     => 'application',
            'entity_id'       => $referralApp->getId()
        ]);

        $this->ba->batchAppAuth();

        $this->startTest();

        $accessMapEntity = $this->getDbEntity('merchant_access_map');

        $this->assertEquals($accessMapEntity['entity_id'], $managedApp->getId());
    }



    public function testAddPartnerAccessMap()
    {
        $partner = $this->allowAdminToAccessPartnerMerchant();

        $submerchant = $this->allowAdminToAccessSubMerchant();

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'fully_managed']);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $this->ba->adminAuth();

        $existingTags = ['RandomTag-1', 'RandomTag-2'];

        $submerchant->retag($existingTags);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest(self::DEFAULT_SUBMERCHANT_ID);

        $this->startTest();

        // Fully managed will create a user with the role:owner for the submerchant
        $partnerUser = $partner->users()->where('product', '=', 'primary')->first()->toArrayPublic();

        $merchantUsers = $submerchant->users()->get()->toArrayPublic();

        $this->assertEquals(2, $merchantUsers['count']);

        $userIds = array_map(function($item){
            return $item['id'];
        }, $merchantUsers['items']);

        $this->assertContains($partnerUser['id'], $userIds);

        $submerchant = $this->getDbEntityById('merchant', self::DEFAULT_SUBMERCHANT_ID);

        $this->assertEquals(self::DEFAULT_MERCHANT_ID, $submerchant->getReferrer());

        array_push($existingTags, 'Ref-' . $partner->getId());

        $actualTags = $submerchant->tagNames();

        $this->assertEquals($existingTags, $actualTags);
    }

    public function testPartnerLinkItselfAsSubmerchant()
    {
        $partner = $this->allowAdminToAccessPartnerMerchant();

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'fully_managed']);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddPartnerAccessMapForDiffOrgSubmerchant()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $org = $this->fixtures->create('org');

        $this->fixtures->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, ['org_id' => $org->getId()]);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddAccessMapWithoutPartnerContext()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testFetchMerchantProducts()
    {
        $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->fixtures->user->createBankingUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchMerchantProductsForBankingProduct()
    {
        $this->fixtures->user->createBankingUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testAddAccessMapToPurePlatform()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'pure_platform']);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'pure_platform']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddAccessMapToNonPartner()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $this->fixtures->merchant->createDummyPartnerApp();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddPartnerAccessMapAgain()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $accessMapArray = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);

        $this->fixtures->create('merchant_access_map', $accessMapArray);

        $this->ba->adminAuth();

        // If the entity already exists, the existing entity is returned
        $accessMap = $this->getDbLastEntity('merchant_access_map');

        $response = $this->startTest();

        $this->assertEquals($accessMap->toArrayPublic(), $response);
    }

    public function testRemoveAccessMapForReseller()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $submerchant = $this->allowAdminToAccessSubMerchant();

        $partnerUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->addUserToMerchant($partnerUser, self::DEFAULT_SUBMERCHANT_ID, 'owner');

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);
        $this->fixtures->create('merchant_access_map', $accessMap);

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

    public function testRemoveAccessMapForAggregator()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $partnerUser = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID)->primaryOwner();

        $submerchant = $this->allowAdminToAccessSubMerchant();

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->addUserToMerchant($partnerUser, self::DEFAULT_SUBMERCHANT_ID, 'owner');

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'aggregator']);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator']);

        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);

        $this->fixtures->create('merchant_access_map', $accessMap);

        $submerchant->retag(['Ref-' . self::DEFAULT_MERCHANT_ID]);

        $this->assertEquals(self::DEFAULT_MERCHANT_ID, $submerchant->getReferrer());

        $submerchantOwners = $submerchant->owners()->get()->toArrayPublic();

        $this->assertEquals(2, $submerchantOwners['count']);

        $this->ba->adminAuth();

        $mapping = DB::table('merchant_users')->where('merchant_id', '=', self::DEFAULT_SUBMERCHANT_ID)
                     ->where('user_id', '=', $partnerUser->getId())
                     ->get();

        $this->assertNotEmpty($mapping);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest(self::DEFAULT_SUBMERCHANT_ID);

        $testData = $this->testData[__FUNCTION__];

        $response = $this->sendRequest($testData['request']);

        $response->assertStatus(204);

        $submerchant = $this->getDbEntityById('merchant', self::DEFAULT_SUBMERCHANT_ID);

        $this->assertEquals(null, $submerchant->getReferrer());

        $submerchantOwners = $submerchant->owners()->get()->toArrayPublic();

        $this->assertEquals(1, $submerchantOwners['count']);

        $mapping = DB::table('merchant_users')->where('merchant_id', '=', self::DEFAULT_SUBMERCHANT_ID)
                     ->where('user_id', '=', $partnerUser->getId())
                     ->get();

        $this->assertEmpty($mapping);
    }

    public function testRemoveNonExistingPartnerAccessMap()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);


        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

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

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

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

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $merchantUsers = $submerchant->users()->get()->toArrayPublic();

        $this->assertEquals(0, $merchantUsers['count']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testPartnerSubmerchantsBatch()
    {
        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $app = ['id'=>'8ckeirnw84ifke'];

        $this->mockAuthServiceCreateApplication($merchant, $app);

        $rows = $this->testData[__FUNCTION__ . 'FileRows'];

        $this->createAndPutExcelFileInRequest($rows, __FUNCTION__);

        // Default merchant to be used for tests
        $this->fixtures->create('merchant',
            [
                'id'            => '100DemoAccount',
                'email'         => 'test@razorpay.com',
                'billing_label' => 'Test Merchant'
            ]);

        // Default merchant to be used for tests
        $this->fixtures->create('merchant',
            [
                'id'            => '10000000000001',
                'email'         => 'test@razorpay.com',
                'billing_label' => 'Test Merchant'
            ]);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $entity = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $entity['success_count']);

        $this->assertEquals(0, $entity['failure_count']);

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);

        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->assertTrue($merchant->isPartner());

        $merchantAccessEntities = $this->getDbEntities('merchant_access_map' ,[], 'test');

        $this->assertCount(2, $merchantAccessEntities);
    }

    public function testPartnerSubmerchantsBatchInvalidId()
    {
        $rows = $this->testData[__FUNCTION__ . 'FileRows'];

        $this->createAndPutExcelFileInRequest($rows, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();

        $entity = $this->getLastEntity('batch', true);

        $this->assertEquals(1, $entity['failure_count']);
    }

    public function  testPartnerSubmerchantTypeChange()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $this->ba->adminAuth();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'aggregator']);

        $managedApp = $this->fixtures->merchant->createDummyPartnerApp( ['partner_type' => 'aggregator']);

        $referredApp = $this->fixtures->merchant->createDummyReferredAppForManaged( ['partner_type' => 'reseller']);

        $this->fixtures->create('user', ['id' => self::DEFAULT_MERCHANT_ID, 'email' => 'test@razorpay.com']);

        DB::connection('test')->table('merchant_users')
            ->insert([
                'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
                'user_id'     => self::DEFAULT_MERCHANT_ID,
                'role'        => 'owner',
                'created_at'  => 1793805150,
                'updated_at'  => 1793805150
            ]);

        $this->fixtures->create('merchant_access_map', [
            'entity_owner_id' => self::DEFAULT_MERCHANT_ID,
            'merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
            'entity_type'     => 'application',
            'entity_id'       => $referredApp->getId()
        ]);

        $this->startTest();

        $accessMapEntity = $this->getDbEntity('merchant_access_map');

        $this->assertEquals($accessMapEntity['entity_id'], $managedApp->getId());
    }

    public function testFetchPartnerSubmerchant()
    {
        // Failing intermittently way too often and hindering development. TODO: fix
        $this->markTestSkipped();

        $this->allowAdminToAccessPartnerMerchant();

        $submerchant = $this->allowAdminToAccessSubMerchant();

        $partnerUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $submerchantUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->addUserToMerchant($partnerUser, self::DEFAULT_SUBMERCHANT_ID, 'owner');

        $submerchantOwners = $submerchant->owners()->get()->toArrayPublic();

        $this->assertEquals(2, $submerchantOwners['count']);

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['activation_status' => 'under_review']);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);


        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);
        $this->fixtures->create('merchant_access_map', $accessMap);

        $this->ba->adminProxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['response']['content']['user'] = $submerchantUser->toArrayPublic();

        $this->startTest($testData);
    }

    /**
     * This test asserts the following things:
     * - presence of application key in the response for /submerchants/{id}
     */
    public function testFetchPartnerSubmerchantPurePlatform()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $submerchantUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'pure_platform']);

        $this->fixtures->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['activation_status' => 'under_review']);

        $app = $this->fixtures->merchant->createDummyPartnerApp([
                   'id'          => self::DUMMY_APP_ID_1,
                   'type'        => null,
                   'name'        => 'App 1',
                   'merchant_id' => self::DEFAULT_MERCHANT_ID,
                   'partner_type' => 'pure_platform',
               ]);

        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);
        $this->fixtures->create('merchant_access_map', $accessMap);

        $app = $this->fixtures->merchant->createDummyPartnerApp([
                   'id'          => self::DUMMY_APP_ID_2,
                   'type'        => null,
                   'name'        => 'App 2',
                   'merchant_id' => self::DEFAULT_MERCHANT_ID,
                   'partner_type' => 'pure_platform',
               ]);

        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);
        $this->fixtures->create('merchant_access_map', $accessMap);

        // Creating 3rd app for the same merchant so that the above-mentioned assertion for connected apps can be made.
        $this->fixtures->merchant->createDummyPartnerApp([
            'id'          => self::DUMMY_APP_ID_3,
            'type'        => null,
            'name'        => 'App 3',
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type' => 'pure_platform',
        ]);

        $this->ba->adminProxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['response']['content']['user'] = [
            'email'          => $submerchantUser->email,
            'contact_mobile' => $submerchantUser->contact_mobile
        ];

        $this->startTest($testData);
    }

    public function testFetchPartnerSubmerchantPurePlatformNoApps()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'pure_platform']);

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantPurePlatformMissingAppId()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'pure_platform']);

        $this->fixtures->merchant->createDummyPartnerApp([
            'id'          => self::DUMMY_APP_ID_1,
            'type'        => null,
            'name'        => 'App 1',
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type' => 'pure_platform',
        ]);

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantPurePlatformInvalidAppId()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'pure_platform']);

        $this->fixtures->merchant->createDummyPartnerApp([
            'id'          => self::DUMMY_APP_ID_1,
            'type'        => null,
            'name'        => 'App 1',
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type' => 'pure_platform',
        ]);

        $this->ba->adminProxyAuth();

        $this->startTest();
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

    public function testFetchPartnerSubmerchantsTypeFilter()
    {
        $partnerUser = $this->createPartnerAndUser();

        $this->createSubmerchantAndUser();

        $app = $this->fixtures->merchant->createDummyReferredAppForManaged([
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type'=> 'reseller',
        ]);

        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);

        $this->fixtures->create('merchant_access_map', $accessMap);

        $submerchantId = '10000000000011';

        $this->allowAdminToAccessMerchant($submerchantId);

        $this->fixtures->user->createUserForMerchant($submerchantId);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $this->addUserToMerchant($partnerUser, $submerchantId, 'owner');

        $accessMap = $this->getAccessMapArray('application', $app->getId(), $submerchantId, self::DEFAULT_MERCHANT_ID);

        $this->fixtures->create('merchant_access_map',$accessMap);

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsPurePlatform()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $partnerUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->addUserToMerchant($partnerUser, self::DEFAULT_SUBMERCHANT_ID, 'owner');

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'pure_platform']);

        $app = $this->fixtures->merchant->createDummyPartnerApp([
            'id'          => self::DUMMY_APP_ID_1,
            'type'        => null,
            'name'        => 'App 1',
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type' => 'pure_platform',
        ]);

        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);
        $this->fixtures->create('merchant_access_map', $accessMap);

        $app = $this->fixtures->merchant->createDummyPartnerApp([
            'id'          => self::DUMMY_APP_ID_2,
            'type'        => null,
            'name'        => 'App 2',
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type' => 'pure_platform',
        ]);

        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);
        $this->fixtures->create('merchant_access_map', $accessMap);

        $this->ba->adminProxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testFetchPartnerSubmerchantsPurePlatformFilters()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $partnerUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->addUserToMerchant($partnerUser, self::DEFAULT_SUBMERCHANT_ID, 'owner');

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'pure_platform']);

        $app = $this->fixtures->merchant->createDummyPartnerApp([
            'id'          => self::DUMMY_APP_ID_1,
            'type'        => null,
            'name'        => 'App 1',
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type' => 'pure_platform',
        ]);

        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);
        $this->fixtures->create('merchant_access_map', $accessMap);

        $app = $this->fixtures->merchant->createDummyPartnerApp([
            'id'          => self::DUMMY_APP_ID_2,
            'type'        => null,
            'name'        => 'App 2',
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type' => 'pure_platform',
        ]);

        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);
        $this->fixtures->create('merchant_access_map', $accessMap);

        $this->ba->adminProxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testFetchPartnerSubmerchantsPaginationFilters()
    {
        $this->createPartnerAndAddMultipleSubmerchants();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsDeleted()
    {
        $this->createPartnerAndUser();

        $this->createSubmerchantAndUser();

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        // Link new submerchants to the partner account
        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_type'     => 'application',
                'entity_id'       => $app->getId(),
                'merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
                'entity_owner_id' => self::DEFAULT_MERCHANT_ID,
                'deleted_at'      => 1504620540,
            ]);

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    /**
     * Tests the list submerchants api when there are no submerchants for the partner
     */
    public function testFetchPartnerSubmerchantsEmptyList()
    {
        $this->createPartnerAndUser();

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $this->ba->adminProxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testFetchPartnerSubmerchantProxyAuth()
    {
        // Failing intermittently way too often and hindering development. TODO: fix
        $this->markTestSkipped();

        $this->allowAdminToAccessPartnerMerchant();

        $submerchant = $this->allowAdminToAccessSubMerchant();

        $partnerUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $submerchantUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->addUserToMerchant($partnerUser, self::DEFAULT_SUBMERCHANT_ID, 'owner');

        $submerchantOwners = $submerchant->owners()->get()->toArrayPublic();

        $this->assertEquals(2, $submerchantOwners['count']);

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);
        $this->fixtures->create('merchant_access_map', $accessMap);

        $this->ba->proxyAuth('rzp_test_10000000000000', $partnerUser->getId());

        $testData = $this->testData[__FUNCTION__];

        $testData['response']['content']['user'] = $submerchantUser->toArrayPublic();

        $this->startTest($testData);
    }

    public function testFetchPartnerSubmerchantProxyAuthSellerApp()
    {
        $partnerUser = $this->createPartnerAndUser();

        $subMerchantUser = $this->createSubmerchantAndUser();

        $this->ba->proxyAuth('rzp_test_10000000000000', $partnerUser->getId());

        $this->startTest();
    }

    public function testCreatePartnerSubmerchantWithValidContactMobileForX()
    {
        $this->createPartnerAndUser();

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator']);

        $this->ba->proxyAuth();

        $razorxMock = $this->getMockBuilder(Merchant\Core::class)
            ->setMethods(['getTreatment'])
            ->getMock();

        $razorxMock->expects($this->any())
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        (new MerchantTest())->expectStorkSendSmsRequest($storkMock,'sms.onboarding.partner_submerchant_invite', '9999999999');

        $this->startTest();
    }

    public function testCreatePartnerSubmerchantWithInvalidContactMobile()
    {
        $this->createPartnerAndUser();

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreatePartnerSubmerchantWithProduct()
    {
        $this->createPartnerAndUser();

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $this->ba->proxyAuth();

        $razorxMock = $this->getMockBuilder(Merchant\Core::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $razorxMock->expects($this->any())
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        $this->mockSalesForce('sendPartnerLeadInfo', 1);

        $response = $this->startTest();

        // fetch submerchant id from response (ignoring prefix acc_)
        $submerchantId = substr($response['id'], 4);

        $subMerchant = $this->getDbEntityById('merchant', $submerchantId);

        $subMerchantPrimaryOwners = ($subMerchant->owners('primary')->get())->toArrayPublic();

        $this->assertEquals(0, $subMerchantPrimaryOwners['count']);

        $subMerchantBankingOwners = ($subMerchant->owners('banking')->get())->toArrayPublic();

        $this->assertEquals(2, $subMerchantBankingOwners['count']);

        $submerchantUserId = $subMerchantBankingOwners['items'][0]['id'];

        $subMerchantUser = DB::table('merchant_users')->where('user_id', '=', $submerchantUserId)->where('product', '=', 'banking')->get();

        $this->assertEquals('banking', $subMerchantUser[0]->product);

        $partnerUserId = $subMerchantBankingOwners['items'][1]['id'];

        $partnerUser = DB::table('merchant_users')->where('user_id', '=', $partnerUserId)->where('product', '=', 'primary')->get();

        $this->assertEquals('primary', $partnerUser[0]->product);

        $this->assertTrue($subMerchant->business_banking);
    }

    public function testDeleteRelatedEntitiesOnUnmarkingPartner()
    {
        $merchantId = self::DEFAULT_MERCHANT_ID;

        // Create an oauth application using factory
        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->setAuthServiceMockDetail('applications/8ckeirnw84ifke', 'PUT', $requestParams);

        // Set the admin auth
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->markMerchantAsPartner($merchantId, Merchant\Constants::RESELLER);

        $merchant = $this->getDbEntityById('merchant', $merchantId, $liveMode);

        $this->assertTrue($merchant->isPartner());

        // Add a partner user
        $partnerUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        // Add a submerchant user
        $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        // Add partner user to submerchant account
        $this->addUserToMerchant($partnerUser, self::DEFAULT_SUBMERCHANT_ID, 'owner');

        // Add a random user to the submerchant. Verifies later that the random user is not deleted.
        $nonPartnerUser = $this->fixtures->create('user');
        $this->addUserToMerchant($nonPartnerUser, self::DEFAULT_SUBMERCHANT_ID, 'admin');

        // Add the partner user to a random merchant who is not a submerchant to the partner.
        $randomMerchantId = '10000000000008';
        $randomMerchant = $this->fixtures->merchant->create(['id' => $randomMerchantId]);
        $this->addUserToMerchant($partnerUser, $randomMerchantId, 'manager');

        // Map the submerchant to the partner app
        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);
        $this->fixtures->create('merchant_access_map', $accessMap);

        // Add the partner user to access a linked account. Verifies later the mapping should not be deleted
        $linkedAccount = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);
        $mappingData   = [
            'user_id'     => $partnerUser->getId(),
            'merchant_id' => $linkedAccount->getId(),
            'role'        => Role::LINKED_ACCOUNT_OWNER,
        ];
        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $submerchant = $this->getDbEntityById('merchant', self::DEFAULT_SUBMERCHANT_ID);
        $submerchant->retag(['Ref-' . self::DEFAULT_MERCHANT_ID]);
        $this->assertEquals(self::DEFAULT_MERCHANT_ID, $submerchant->getReferrer());

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        // Create a merchant request
        $merchantRequest = $this->createMerchantRequest(self::DEACTIVATION);
        $merchantRequestId = $merchantRequest->getPublicId();
        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', $merchantId, $liveMode);
        $this->assertFalse($merchant->isPartner());

        // cleanup assertion - verify that the access maps have been deleted
        $accessMaps = $this->getDbEntities('merchant_access_map', ['entity_id' => $app->getId()])
                           ->toArray();
        $this->assertEmpty($accessMaps);

        // cleanup assertion - verify that the merchant user mappings have been deleted
        $partnerUserMapping = $this->fixtures
                                   ->user
                                   ->getMerchantUserMapping(self::DEFAULT_SUBMERCHANT_ID, $partnerUser->getId())
                                   ->toArray();
        $this->assertEmpty($partnerUserMapping);

        // cleanup assertion - verify that the merchant's team user mappings have not been deleted
        $nonPartnerUserMapping = $this->fixtures
                                      ->user
                                      ->getMerchantUserMapping(self::DEFAULT_SUBMERCHANT_ID, $nonPartnerUser->getId())
                                      ->toArray();
        $this->assertNotEmpty($nonPartnerUserMapping);

        // cleanup assertion - verify that the partner user still has access to the linked account
        $nonPartnerUserMapping = $this->fixtures
                                      ->user
                                      ->getMerchantUserMapping($linkedAccount->getId(), $partnerUser->getId())
                                      ->toArray();
        $this->assertNotEmpty($nonPartnerUserMapping);

        // cleanup assertion - verify that the partner user's access to other teams have not been deleted
        $partnerUserMapping = $this->fixtures
                                   ->user
                                   ->getMerchantUserMapping($randomMerchantId, $partnerUser->getId())
                                   ->toArray();
        $this->assertNotEmpty($partnerUserMapping);

        // cleanup assertion - verify that the ref tags have been deleted
        $submerchant = $this->getDbEntityById('merchant', self::DEFAULT_SUBMERCHANT_ID);
        $this->assertEquals(null, $submerchant->getReferrer());

        $merchantApplications = (new MerchantApplications\Repository())->fetchMerchantApplication(self::DEFAULT_MERCHANT_ID, Merchant\Constants::MERCHANT_ID);

        $this->assertEquals(0, count($merchantApplications));
    }

    public function testAddPartnerAccessMapForLinkedAccountSubmerchant()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, ['parent_id' => self::DEFAULT_MERCHANT_ID]);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSendSubmerchantPasswordResetLinkWhenMerchantIsNotAPartner()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSendSubmerchantPasswordResetLinkWhenSubMerchantUserDoesNotExist()
    {
        Mail::fake();

        $this->ba->proxyAuth();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, [
            'partner_type' => 'aggregator'
        ]);

        $this->fixtures->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, [
            'email' => 'testing@example.com',
        ]);

        $app = factory(Application\Entity::class)->create([
            'id' => random_integer(10),
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'type' => 'partner'
        ]);

        $this->createMerchantApplication($app->merchant_id, 'aggregator', $app->getId());

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_type' => 'application',
                'entity_id'   => $app->getId(),
                'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
            ]
        );

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantAffiliate::class, 1);

        Mail::assertQueued(CreateSubMerchantAffiliate::class, function($mail) {

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('org', $viewData);
            $this->assertArrayHasKey('token', $viewData);
            $this->assertArrayHasKey('merchant', $viewData);
            $this->assertArrayHasKey('subMerchant', $viewData);
            $this->assertEquals(self::DEFAULT_SUBMERCHANT_ID, $viewData['subMerchant']['id']);

            return true;
        });
    }

    public function testSendSubmerchantPasswordResetLinkWhenSubMerchantUserExistAndPartnerMappingDoesNotExist()
    {
        Mail::fake();

        $merchantId = self::DEFAULT_MERCHANT_ID;

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, [
            'partner_type' => 'aggregator',
            'email'        => 'test@example.com',
        ]);

        $this->fixtures->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, [
            'email' => 'testing@example.com',
        ]);

        $user = $this->fixtures->user->createEntityInTestAndLive('user', ['email' => 'testing@example.com']);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $app = factory(Application\Entity::class)->create([
            'id' => random_integer(10),
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'type' => 'partner'
        ]);

        $this->createMerchantApplication($app->merchant_id, 'aggregator', $app->getId());

        $this->fixtures->create('merchant_detail:sane',[
            'merchant_id' => $app->merchant_id,
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_type' => 'application',
                'entity_id'   => $app->getId(),
                'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
            ]
        );

        $this->startTest();

        $mapping = DB::table('merchant_users')->where('merchant_id', '=', self::DEFAULT_SUBMERCHANT_ID)
                                              ->where('user_id', '=', $user->getId())
                                              ->get();

        $this->assertNotEmpty($mapping);

        Mail::assertQueued(CreateSubMerchantAffiliate::class, 1);

        Mail::assertQueued(CreateSubMerchantAffiliate::class, function($mail) {

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('org', $viewData);
            $this->assertArrayHasKey('token', $viewData);
            $this->assertArrayHasKey('merchant', $viewData);
            $this->assertArrayHasKey('subMerchant', $viewData);
            $this->assertEquals(self::DEFAULT_SUBMERCHANT_ID, $viewData['subMerchant']['id']);

            return true;
        });
    }

    public function testSendSubmerchantPasswordResetLinkWhenSubMerchantUserAndPartnerMappingExist()
    {
        Mail::fake();

        $partnerMerchantId = self::DEFAULT_MERCHANT_ID;

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, [
            'partner_type' => 'aggregator',
            'email'        => 'test@example.com',
        ]);

        $this->fixtures->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, [
            'email' => 'testing@example.com',
        ]);

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
                                                             'user_id'     => User::MERCHANT_USER_ID,
                                                             'role'        => 'owner',
                                                         ]);

        $this->ba->proxyAuth('rzp_test_' . $partnerMerchantId, User::MERCHANT_USER_ID);

        $app = factory(Application\Entity::class)->create([
           'id' => random_integer(10),
           'merchant_id' => self::DEFAULT_MERCHANT_ID,
           'type' => 'partner'
        ]);

        $this->createMerchantApplication($app->merchant_id, 'aggregator', $app->getId());

        $this->fixtures->create('merchant_detail:sane',[
            'merchant_id' => $app->merchant_id,
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_type'     => 'application',
                'entity_id'       => $app->getId(),
                'entity_owner_id' => $partnerMerchantId,
                'merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
            ]
        );

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantAffiliate::class, 1);

        Mail::assertQueued(CreateSubMerchantAffiliate::class, function($mail) {

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('org', $viewData);
            $this->assertArrayHasKey('token', $viewData);
            $this->assertArrayHasKey('merchant', $viewData);
            $this->assertArrayHasKey('subMerchant', $viewData);
            $this->assertEquals(self::DEFAULT_SUBMERCHANT_ID, $viewData['subMerchant']['id']);

            return true;
        });
    }

    public function testGetAffiliatedPartnersForMerchant()
    {
        $this->createPartnerAndAddMultipleSubmerchants();

        // create another partner and attach submerchant
        $this->fixtures->merchant->createAccount('10000000000001');
        $this->fixtures->merchant->edit('10000000000001', ['partner_type' => 'reseller']);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['id' => '8ckeirnw84ifkf', 'partner_type' => 'reseller']);

        // Link new submerchants to the partner account
        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, '10000000000001');

        $this->fixtures->create('merchant_access_map',$accessMap);

        // add one more app for the same partner and map the submerchant to it
        $app = $this->fixtures->merchant->createDummyPartnerApp(['id' => '8ckeirnw84ifkg', 'partner_type' => 'reseller']);

        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, '10000000000001');

        $this->fixtures->create('merchant_access_map',$accessMap);

        $this->allowAdminToAccessMerchant('10000000000001');

        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $this->startTest();
    }

    public function testUpdatePartnerTypeAsResellerUsingProxyAuth()
    {
        Mail::fake();

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, 'live');

        $app = ['id'=>'8ckeirnw84ifke'];

        $this->mockAuthServiceCreateApplication($merchant, $app);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->ba->proxyAuth();

        $this->startTest();

        //check if referral links are created for the partner
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'banking', Mode::TEST));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'primary', Mode::TEST));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'banking', Mode::LIVE));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'primary', Mode::LIVE));

        $expectedPartner = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, 'live');

        $merchantApplications = (new MerchantApplications\Repository())->fetchMerchantApplication(self::DEFAULT_MERCHANT_ID, Merchant\Constants::MERCHANT_ID);

        $applicationTypes = $merchantApplications->pluck(Entity::TYPE)->toArray();

        $applicationType = $applicationTypes[0];

        $this->assertEquals($applicationType, MerchantApplications\Entity::REFERRED);

        $this->assertTrue($expectedPartner->isResellerPartner());

        Mail::assertQueued(PartnerOnBoarded::class);
    }

    public function testUpdatePartnerTypeAsAggregatorUsingProxyAuth()
    {
        Mail::fake();

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, 'live');

        $requestParams1 = [
            'merchant_id' => $merchant->getId(),
            'name'     => $merchant->getName(),
            'website'  => $merchant->getWebsite() ?: 'https://www.razorpay.com',
            'type'     => self::PARTNER,
        ];

        $requestParams2 = [
            'merchant_id' => $merchant->getId(),
            'name'     => Merchant\Entity::REFERRED_APPLICATION,
            'website'  => $merchant->getWebsite() ?: 'https://www.razorpay.com',
            'type'     => self::PARTNER,
        ];

        $this->authServiceMock
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->with('applications', 'POST', $this->logicalOr($requestParams1, $requestParams2))
            ->will($this->returnCallback(
                function($route, $method, $params) {
                    if($params['name'] === Merchant\Entity::REFERRED_APPLICATION) {
                        return ['id'=>'8ckeirnw84ifkf'];
                    }
                    return ['id'=>'8ckeirnw84ifke'];
                }
            ));

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator'], false);

        $this->fixtures->merchant->createDummyReferredAppForManaged(['partner_type' => 'reseller'], false);

        $this->ba->proxyAuth();

        $this->startTest();

        //check if referral links are created for the partner
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'banking', Mode::TEST));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'primary', Mode::TEST));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'banking', Mode::LIVE));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'primary', Mode::LIVE));

        $expectedPartner = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, 'live');

        $merchantApplications = (new MerchantApplications\Repository())->fetchMerchantApplication(self::DEFAULT_MERCHANT_ID, Merchant\Constants::MERCHANT_ID);

        $applicationTypes = $merchantApplications->pluck(Entity::TYPE)->toArray();

        $expectedAppTypes = ['managed', 'referred'];

        $this->assertArraySelectiveEqualsWithCount($expectedAppTypes, $applicationTypes);

        $this->assertTrue($expectedPartner->isAggregatorPartner());

        Mail::assertQueued(PartnerOnBoarded::class);
    }

    public function testUpdatePartnerTypeAsPurePlatformUsingProxyAuth()
    {
        Mail::fake();

        $merchantId = self::DEFAULT_MERCHANT_ID;

        $this->ba->proxyAuth();

        $this->startTest();

        //check if no referral links are created for the pure platform partner
        $this->assertNull($this->checkReferrals($merchantId, 'banking', Mode::TEST));
        $this->assertNull($this->checkReferrals($merchantId, 'primary', Mode::TEST));
        $this->assertNull($this->checkReferrals($merchantId, 'banking', Mode::LIVE));
        $this->assertNull($this->checkReferrals($merchantId, 'primary', Mode::LIVE));

        $expectedPartner = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, 'live');

        $this->assertTrue($expectedPartner->isPurePlatformPartner());

        $merchantApplication = (new MerchantApplications\Repository())->fetchMerchantApplication(self::DEFAULT_MERCHANT_ID, Merchant\Constants::MERCHANT_ID);

        $this->assertEmpty($merchantApplication);

        Mail::assertQueued(PartnerOnBoarded::class);
    }

    public function testUpdatePartnerTypeAsResellerUsingProxyAuthForActivatedMerchant()
    {
        Mail::fake();

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, 'live');

        $app = ['id'=>'8ckeirnw84ifke'];

        $this->mockAuthServiceCreateApplication($merchant, $app);

        $now = Carbon::now()->getTimestamp();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID,
                                        [
                                            'activated'    => true,
                                            'activated_at' => $now
                                        ]);

        $this->fixtures->create('merchant_detail:sane',
                                [
                                    'merchant_id'       => self::DEFAULT_MERCHANT_ID,
                                    'activation_status' => 'activated'
                                ]);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->ba->proxyAuth();

        $testData = $this->testData['testUpdatePartnerTypeAsResellerUsingProxyAuth'];

        $this->runRequestResponseFlow($testData);

        //check if referral links are created for the partner
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'banking', Mode::TEST));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'primary', Mode::TEST));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'banking', Mode::LIVE));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'primary', Mode::LIVE));

        $expectedPartner = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, 'live');

        $merchantApplications = (new MerchantApplications\Repository())->fetchMerchantApplication(self::DEFAULT_MERCHANT_ID, Merchant\Constants::MERCHANT_ID);

        $applicationTypes = $merchantApplications->pluck(Entity::TYPE)->toArray();

        $applicationType = $applicationTypes[0];

        $this->assertEquals(MerchantApplications\Entity::REFERRED, $applicationType);

        $this->assertTrue($expectedPartner->isResellerPartner());

        $partnerActivation = $this->getDbEntity('partner_activation');

        $this->assertEquals( self::DEFAULT_MERCHANT_ID, $partnerActivation->getMerchantId());

        $this->assertTrue(empty($partnerActivation->getActivatedAt()) === false);

        $this->assertEquals( 'activated', $partnerActivation->getActivationStatus());

        Mail::assertQueued(PartnerOnBoarded::class);
    }


    public function testUpdatePartnerTypeAsAggregatorUsingProxyAuthForActivatedMerchant()
    {
        Mail::fake();

        $now = Carbon::now()->getTimestamp();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID,
                                        [
                                            'activated'    => true,
                                            'activated_at' => $now
                                        ]);

        $this->fixtures->create('merchant_detail:sane',
                                [
                                    'merchant_id'       => self::DEFAULT_MERCHANT_ID,
                                    'activation_status' => 'activated'
                                ]);

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, 'live');

        $requestParams1 = [
            'merchant_id' => $merchant->getId(),
            'name'     => $merchant->getName(),
            'website'  => $merchant->getWebsite() ?: 'https://www.razorpay.com',
            'type'     => self::PARTNER,
        ];

        $requestParams2 = [
            'merchant_id' => $merchant->getId(),
            'name'     => Merchant\Entity::REFERRED_APPLICATION,
            'website'  => $merchant->getWebsite() ?: 'https://www.razorpay.com',
            'type'     => self::PARTNER,
        ];

        $this->authServiceMock
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->with('applications', 'POST', $this->logicalOr($requestParams1, $requestParams2))
            ->will($this->returnCallback(
                function($route, $method, $params) {
                    if($params['name'] === Merchant\Entity::REFERRED_APPLICATION) {
                        return ['id'=>'8ckeirnw84ifkf'];
                    }
                    return ['id'=>'8ckeirnw84ifke'];
                }
            ));

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator'], false);

        $this->fixtures->merchant->createDummyReferredAppForManaged(['partner_type' => 'reseller'], false);

        $this->ba->proxyAuth();

        $testData = $this->testData['testUpdatePartnerTypeAsAggregatorUsingProxyAuth'];

        $this->runRequestResponseFlow($testData);

        //check if referral links are created for the partner
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'banking', Mode::TEST));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'primary', Mode::TEST));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'banking', Mode::LIVE));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'primary', Mode::LIVE));

        $expectedPartner = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, 'live');

        $merchantApplications = (new MerchantApplications\Repository())->fetchMerchantApplication(self::DEFAULT_MERCHANT_ID, Merchant\Constants::MERCHANT_ID);

        $applicationTypes = $merchantApplications->pluck(Entity::TYPE)->toArray();

        $expectedAppTypes = ['managed', 'referred'];

        $this->assertArraySelectiveEqualsWithCount($expectedAppTypes, $applicationTypes);

        $this->assertTrue($expectedPartner->isAggregatorPartner());

        $partnerActivation = $this->getDbEntity('partner_activation');

        $this->assertEquals( self::DEFAULT_MERCHANT_ID, $partnerActivation->getMerchantId());

        $this->assertTrue(empty($partnerActivation->getActivatedAt()) === false);

        $this->assertEquals( 'activated', $partnerActivation->getActivationStatus());

        Mail::assertQueued(PartnerOnBoarded::class);
    }

    public function testUpdatePartnerTypeAsPurePlatformUsingProxyAuthForActivatedMerchant()
    {
        Mail::fake();

        $now = Carbon::now()->getTimestamp();

        $merchantId = self::DEFAULT_MERCHANT_ID;

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID,
                                        [
                                            'activated'    => true,
                                            'activated_at' => $now
                                        ]);

        $this->fixtures->create('merchant_detail:sane',
                                [
                                    'merchant_id'       => self::DEFAULT_MERCHANT_ID,
                                    'activation_status' => 'activated'
                                ]);

        $this->ba->proxyAuth();

        $testData = $this->testData['testUpdatePartnerTypeAsPurePlatformUsingProxyAuth'];

        $this->runRequestResponseFlow($testData);

        //check if no referral links are created for the pure platform partner
        $this->assertNull($this->checkReferrals($merchantId, 'banking', Mode::TEST));
        $this->assertNull($this->checkReferrals($merchantId, 'primary', Mode::TEST));
        $this->assertNull($this->checkReferrals($merchantId, 'banking', Mode::LIVE));
        $this->assertNull($this->checkReferrals($merchantId, 'primary', Mode::LIVE));

        $expectedPartner = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, 'live');

        $this->assertTrue($expectedPartner->isPurePlatformPartner());

        $merchantApplication = (new MerchantApplications\Repository())->fetchMerchantApplication(self::DEFAULT_MERCHANT_ID, Merchant\Constants::MERCHANT_ID);

        $this->assertEmpty($merchantApplication);

        $partnerActivation = $this->getDbEntity('partner_activation');

        $this->assertEquals( self::DEFAULT_MERCHANT_ID, $partnerActivation->getMerchantId());

        $this->assertTrue(empty($partnerActivation->getActivatedAt()) === false);

        $this->assertEquals( 'activated', $partnerActivation->getActivationStatus());

        Mail::assertQueued(PartnerOnBoarded::class);
    }

    public function testUpdatePartnerTypeUsingProxyAuthWithInvalidPartnerType()
    {
        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, 'live');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchBankingAccountStatusWithVerifiedPanForRBL()
    {
        $this->createPartnerAndAddMultipleSubmerchants(Mode::LIVE);

        $this->ba->adminProxyAuth();

        // create sub-merchant for banking product
        $this->fixtures->on(Mode::LIVE)->user->createBankingUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $ba = $this->createBankingAccount([], Mode::LIVE);

        $baActivationDetailParams = [
            'banking_account_id' => $ba->id,
            'business_pan_validation' => 'verified'
        ];

        $this->createBankingAccountActivationDetail($baActivationDetailParams, Mode::LIVE);

        $testData = $this->testData[__FUNCTION__];

        $rblStatusMap = Merchant\Constants::CA_STATUS_MAP[Channel::RBL];

        foreach ($rblStatusMap as $status => $mappedStatus)
        {
            $this->fixtures->on(Mode::LIVE)->edit('banking_account', $ba->id, ['status' => $status]);

            $this->fixtures->on(Mode::LIVE)->user->createBankingUserForMerchant(self::DEFAULT_SUBMERCHANT_ID, [], 'owner', 'live');

            $this->ba->proxyAuthLive();

            // since PAN is verified, update overall status when rbl status is 'created'
            if ($status === 'created')
            {
                $mappedStatus = 'Telephonic verification';
            }

            $testData['response']['content']['items'][0]['banking_account']['ca_status'] = $mappedStatus;

            $this->startTest($testData);
        }
    }

    public function testFetchBankingAccountStatusWithVariousPanStatusesForRBL()
    {
        $this->createPartnerAndAddMultipleSubmerchants(Mode::LIVE);

        $this->ba->adminProxyAuth();

        // create sub-merchant for banking product
        $this->fixtures->on(Mode::LIVE)->user->createBankingUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $ba = $this->createBankingAccount([], Mode::LIVE);

        $baActivationDetailParams = [
            'banking_account_id' => $ba->id,
        ];

        $baActivationDetail = $this->createBankingAccountActivationDetail($baActivationDetailParams, Mode::LIVE);

        // since the request response structure is same, no need for a new test data
        $testData = $this->testData['testFetchBankingAccountStatusWithVerifiedPanForRBL'];

        $panStatusMap = [
            'pending'           => 'PAN verification in progress',
            'initiated'         => 'PAN verification in progress',
            'failed'            => 'PAN Verification Failed',
            'not_matched'       => 'PAN Verification Failed',
            'incorrect_details' => 'PAN Verification Failed',
            'verified'          => 'Telephonic verification',
        ];

        foreach ($panStatusMap as $status => $mappedStatus)
        {
            $this->fixtures->on(Mode::LIVE)->edit('banking_account_activation_detail', $baActivationDetail->id,  ['business_pan_validation' => $status]);

            $this->fixtures->on(Mode::LIVE)->user->createBankingUserForMerchant(self::DEFAULT_SUBMERCHANT_ID, [], 'owner', 'live');

            $this->ba->proxyAuthLive();

            $testData['response']['content']['items'][0]['banking_account']['ca_status'] = $mappedStatus;

            $this->startTest($testData);
        }
    }

    public function testFetchSubmsBasedOnProductUsageStatus()
    {
        $partnerAppId = $this->createPartnerAndAddMultipleSubmerchants();

        $this->ba->adminProxyAuth();

        // create sub-merchant for banking product
        $submerchantId = '10000000000012';

        $this->allowAdminToAccessMerchant($submerchantId);

        $this->fixtures->user->createBankingUserForMerchant($submerchantId);

        $accessMap = $this->getAccessMapArray('application', $partnerAppId, $submerchantId, self::DEFAULT_MERCHANT_ID);

        $this->fixtures->create('merchant_access_map',$accessMap);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchSubmsBasedOnProductNotUsed()
    {
        $partnerAppId = $this->createPartnerAndAddMultipleSubmerchants();

        $this->ba->adminProxyAuth();

        // create sub-merchant for banking product
        $submerchantId = '10000000000012';

        $this->allowAdminToAccessMerchant($submerchantId);

        $this->fixtures->user->createBankingUserForMerchant($submerchantId);

        $accessMap = $this->getAccessMapArray('application', $partnerAppId, $submerchantId, self::DEFAULT_MERCHANT_ID);

        $this->fixtures->create('merchant_access_map',$accessMap);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testIsUsedPassedWithoutProductInQueryParam()
    {
        $this->ba->adminProxyAuth();

        $this->ba->proxyAuth();

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

    protected function mockAuthServiceCreateApplication(Merchant\Entity $merchant, array $response = [], $times = 1)
    {
        // Mock create application call to auth service
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $createParams = [
            'name'     => $merchant->getName(),
            'website'  => $merchant->getWebsite() ?: 'https://www.razorpay.com',
            'type'     => self::PARTNER,
        ];

        $requestParams = array_merge($requestParams, $createParams);

        $this->setAuthServiceMockDetail('applications', 'POST', $requestParams, $times, $response);
    }

    protected function createMerchantUser($merchantId)
    {
        $user = $this->fixtures->create('user');

        $this->addUserToMerchant($user, $merchantId, 'owner');

        return $user;
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

    protected function createPartnerAndAddMultipleSubmerchants(string $mode = Mode::TEST)
    {
        $this->createPartnerAndUser($mode);

        $this->createSubmerchantAndUser($mode);

        $submerchantId = '10000000000011';

        $this->allowAdminToAccessMerchant($submerchantId);

        $this->fixtures->on($mode)->user->createUserForMerchant($submerchantId);

        $app = $this->fixtures->on($mode)->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $appId = $app->getId();

        // Link new submerchants to the partner account
        $accessMap = $this->getAccessMapArray('application', $appId, self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);

        $this->fixtures->on($mode)->create('merchant_access_map',$accessMap);

        $accessMap = $this->getAccessMapArray('application', $appId, $submerchantId, self::DEFAULT_MERCHANT_ID);

        $this->fixtures->on($mode)->create('merchant_access_map',$accessMap);

        return $appId;
    }

    protected function createPartnerAndUser(string $mode = Mode::TEST)
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->fixtures->on($mode)->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'fully_managed']);

        $partnerUser = $this->fixtures->on($mode)->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID, [], 'sellerapp');

        return $partnerUser;
    }

    protected function createSubmerchantAndUser(string $mode = Mode::TEST)
    {
        $this->allowAdminToAccessSubMerchant();

        $this->fixtures->on($mode)->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, [
            'name' => 'random_name_1',
            'email' => 'user@example.com',
        ]);

        $this->fixtures->on($mode)->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['activation_status' => 'under_review']);

        $submerchantUser = $this->fixtures->on($mode)->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        return $submerchantUser;
    }

    protected function getAccessMapArray($entityType, $entityId, $merchantId, $entityOwnerId)
    {
        return [
                'entity_type'     => $entityType,
                'entity_id'       => $entityId,
                'merchant_id'     => $merchantId,
                'entity_owner_id' => $entityOwnerId,
            ];
    }

    protected function assertArraySelectiveEqualsWithCount(array $expected, array $actual)
    {
        $this->assertArraySelectiveEquals($expected, $actual);
        $this->assertCount(count($expected), $actual);
    }

    protected function createBankingAccount(array $extraParams = [], string $mode = Mode::TEST)
    {
        $defaultParams = [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'merchant_id'           => self::DEFAULT_SUBMERCHANT_ID,
        ];

        $params = array_merge($defaultParams, $extraParams);

        $ba1 = $this->fixtures->on($mode)->create('banking_account', $params);

        return $ba1;
    }

    protected function createBankingAccountActivationDetail(array $extraParams = [], string $mode = Mode::TEST)
    {
        $defaultParams = [
            'banking_account_id'      => $extraParams['banking_account_id'] ?? null,
            'business_pan_validation' => $extraParams['business_pan_validation'] ?? null,
        ];

        $params = array_merge($defaultParams, $extraParams);

        $baActivationDetail = $this->fixtures->on($mode)->create('banking_account_activation_detail', $params);

        return $baActivationDetail;
    }

    public function testPartnerActivationMigrationForNonActivatedPartners()
    {
        $this->createMerchants(null);

        $this->ba->adminAuth();

        $partnerActivationEntitiesBeforeMigration = $this->getDbEntities('partner_activation');

        $this->assertEquals(0, count($partnerActivationEntitiesBeforeMigration));

        $testData = $this->testData['executePartnerMigration'];

        $this->runRequestResponseFlow($testData);

        $partnerActivationEntitiesAfterMigration = $this->getDbEntities('partner_activation');

        $this->validatePartnerActivation($partnerActivationEntitiesAfterMigration, null);
    }

    public function testPartnerActivationMigrationForActivatedPartners()
    {
        $this->createMerchants("activated");

        $this->ba->adminAuth();

        $partnerActivationEntitiesBeforeMigration = $this->getDbEntities('partner_activation');

        $this->assertEquals(0, count($partnerActivationEntitiesBeforeMigration));

        $testData = $this->testData['executePartnerMigration'];

        $this->runRequestResponseFlow($testData);

        $partnerActivationEntitiesAfterMigration = $this->getDbEntities('partner_activation');

        $this->validatePartnerActivation($partnerActivationEntitiesAfterMigration, "activated");
    }

    public function testAggregatorToResellerBulkUpdate()
    {
        $merchantId = '10000000000000';

        $this->setUpNonPurePlatformPartner();

        $this->fixtures->merchant->edit($merchantId, ['name' => 'et', 'website' => 'http://www.monahan.com/harum-fuga-quae-culpa-quod']);

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    private function validatePartnerActivation($partnerActivationEntities, $activationStatus)
    {
        $this->assertEquals(10, count($partnerActivationEntities));

        for ($index = 0; $index < 10; $index++)
        {
            $partnerActivation = $partnerActivationEntities->get($index);

            $this->assertEquals($activationStatus, $partnerActivation->getActivationStatus());

            if (empty($activationStatus) === true)
            {
                $this->assertNull($partnerActivation->getActivatedAt());
            }
            else
            {
                $this->assertNotNull($partnerActivation->getActivatedAt());
            }
        }
    }

    private function createMerchants($activationStatus)
    {
        $id_prefix = '1cXSLlUU8V9s';

        for ($index = 10; $index < 20; $index++)
        {
            $suffix1 = stringify($index);
            $suffix2 = stringify(10 + $index);

            $merchantId1 = $id_prefix . $suffix1;
            $merchantId2 = $id_prefix . $suffix2;

            $this->createMerchant($merchantId1, $suffix1, true, $activationStatus);
            $this->createMerchant($merchantId2, $suffix2, false, $activationStatus);
        }
    }

    private function createMerchant(string $merchantId, string $suffix, bool $isPartner, $activationStatus = null)
    {
        if ($isPartner === true)
        {
            $this->fixtures->create('merchant', ['id' => $merchantId, 'partner_type' => 'reseller']);
        }
        else
        {
            $this->fixtures->create('merchant', ['id' => $merchantId]);
        }

        $this->fixtures->create('merchant_detail:sane', [
            'merchant_id'       => $merchantId,
            'business_type'     => 1,
            'contact_name'      => 'contact name' . $suffix,
            'contact_mobile'    => '8888888888',
            'activation_status' => $activationStatus
        ]);

        $this->fixtures->create('stakeholder',
                                [
                                    'merchant_id' => $merchantId,
                                    'name'        => 'stakeholder' . $suffix,
                                ]);

    }

    public function createResellerPartnerSubmerchant()
    {
        $merchantId = self::DEFAULT_MERCHANT_ID;

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, [
            'partner_type' => 'reseller',
            'email'        => 'test@example.com',
        ]);

        $this->fixtures->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, [
            'email' => 'testing@example.com',
        ]);


        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $app = factory(Application\Entity::class)->create([
                                                              'id' => random_integer(10),
                                                              'merchant_id' => self::DEFAULT_MERCHANT_ID,
                                                              'type' => 'partner'
                                                          ]);

        $this->createMerchantApplication($app->merchant_id, 'reseller', $app->getId());

        $this->fixtures->create('merchant_detail:sane',[
            'merchant_id' => $app->merchant_id,
            'contact_name'=> 'randomName',
            'business_type' => 2
        ]);

        $this->fixtures->on('test')->edit('merchant_detail', self::DEFAULT_SUBMERCHANT_ID, ['business_type' => 2]);
        $this->fixtures->on('live')->edit('merchant_detail', self::DEFAULT_SUBMERCHANT_ID, ['business_type' => 2]);

        $this->fixtures->on('test')->edit('merchant', self::DEFAULT_SUBMERCHANT_ID, ['name' => 'submerchant']);
        $this->fixtures->on('live')->edit('merchant', self::DEFAULT_SUBMERCHANT_ID, ['name' => 'submerchant']);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'id'          => 'IBb9OU2WPuCC29',
                'entity_type' => 'application',
                'entity_id'   => $app->getId(),
                'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
            ]
        );
    }

    private function mockSalesForce(string $method, int $count)
    {
        $salesforceClientMock = $this->getMockBuilder(SalesForceClient::class)
            ->setConstructorArgs([$this->app])
            ->getMock();

        $this->app->instance('salesforce', $salesforceClientMock);

        $salesforceClientMock->expects($this->exactly($count))->method($method);
    }

    private function checkReferrals(string $merchantId, string $product, string $mode = Mode::TEST)
    {
        $bankingReferral = $this->getDbEntity('referrals',
            [
                'merchant_id' => $merchantId , 'product' => $product
            ], $mode);

        return $bankingReferral;
    }
}
