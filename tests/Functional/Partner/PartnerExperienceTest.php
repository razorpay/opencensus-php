<?php

namespace RZP\Tests\Functional\Merchant\Partner;

use Config;
use DB;
use Mail;
use Event;
use Queue;
use Carbon\Carbon;
use RZP\Constants\Mode;
use App\User\Constants;
use ReflectionFunction;
use RZP\Constants\Mode as EnvMode;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\User\Role;
use RZP\Services\Mock\Raven;
use WpOrg\Requests\Response;
use RZP\Services\RazorXClient;
use Razorpay\OAuth\Application;
use Illuminate\Http\UploadedFile;
use RZP\Models\Merchant\Request;
use RZP\Tests\Functional\Partner;
use RZP\Models\Settings\Accessor;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Services\SalesForceClient;
use RZP\Services\Elfin\Impl\Gimli;
use RZP\Mail\Merchant\PartnerOnBoarded;
use RZP\Tests\Traits\TestsWebhookEvents;
use Neves\Events\TransactionalClosureEvent;
use RZP\Mail\Merchant\PartnerTypeSwitchEmail;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Traits\MocksPartnershipsService;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Tests\Functional\Merchant\MerchantTest;
use RZP\Models\Merchant\MerchantApplications\Entity;
use RZP\Jobs\MigrateResellerToPurePlatformPartnerJob;
use RZP\Jobs\MigratePurePlatformToResellerPartnerJob;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Partner\NotifyPartnerAboutPartnerTypeSwitch;
use RZP\Tests\Functional\Helpers\CreateLegalDocumentsTrait;
use RZP\Tests\Functional\Helpers\Salesforce\SalesforceTrait;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;
use RZP\Models\Merchant\Consent\Details\Repository as MerchantConsentDetailsRepo;

class PartnerExperienceTest extends OAuthTestCase
{
    use TestsMetrics;
    use MocksSplitz;
    use PartnerTrait;
    use SalesforceTrait;
    use BatchTestTrait;
    use CreateLegalDocumentsTrait;
    use TestsWebhookEvents;
    use MocksPartnershipsService;

    const PARTNER                       = 'partner';
    const ACTIVATION                    = 'activation';
    const DEACTIVATION                  = 'deactivation';
    const DUMMY_APP_ID_1                = '8ckeirnw84ifke';
    const DUMMY_APP_ID_2                = '10000RandomApp';
    const DUMMY_APP_ID_3                = '11111RandomApp';
    const DEFAULT_MERCHANT_ID           = '10000000000000';
    const DEFAULT_PARTNER_ID            = '1X4hRFHFx4UiXt';
    const DEFAULT_SUBMERCHANT_ID        = '10000000000009';
    const DEFAULT_SUBMERCHANT_ID_2      = '10000000000010';
    const DEFAULT_USER_ID               = 'RazorpayUserId';
    const RZP_ORG                       = '100000razorpay';
    const CURLEC_DEFAULT_MERCHANT_ID    = '10000121212121';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PartnerExperienceTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

        $this->fixtures->merchant_detail->create(['merchant_id' => self::DEFAULT_SUBMERCHANT_ID]);

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $this->storkMock);

        $this->merchantTestUtil = new MerchantTest();

        $this->ba->privateAuth();
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
            null,
            true);
    }

    public function testRequestKycAccessByPartner()
    {
        $this->ba->proxyAuth();
        $this->createResellerPartnerSubmerchant(true);

        $this->fixtures->on('test')->edit('merchant_detail', self::DEFAULT_SUBMERCHANT_ID, ['contact_mobile' => '+919123456789']);
        $this->fixtures->on('live')->edit('merchant_detail', self::DEFAULT_SUBMERCHANT_ID, ['contact_mobile' => '+919123456789']);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $merchantTestUtil = new MerchantTest();
        $merchantTestUtil->expectStorkSmsRequest($storkMock, 'Sms.Submerchant_kyc_access.Requested', '+919123456789', [
            'subMerchantName'      => 'submerchant',
        ], 2);

        $response1 = $this->startTest();

        // running twice shouldn't give any error
        $response2 = $this->startTest();
        $this->assertEquals($response1['id'], $response2['id']);
    }

    public function testUpdateKycAccessWithApprovedStatusWithNoRecord()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->create('referrals', ['merchant_id' => self::DEFAULT_MERCHANT_ID]);

        $user = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_SUBMERCHANT_ID, $user['id']);

        $response = $this->startTest();

        $kycAccess = $this->getDbLastEntity('partner_kyc_access_state');

        $accessMap = $this->getDbLastEntity('merchant_access_map');

        $this->assertEquals($kycAccess->getState(), 'approved');

        $this->assertEquals($accessMap->hasKycAccess(), true);
    }

    public function testUpdateKycAccessWithApprovedStatusWithRecord()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->create('partner_kyc_access_state', ['state' => 'pending_approval'] );

        $this->fixtures->create('referrals', ['merchant_id' => self::DEFAULT_MERCHANT_ID]);

        $user = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_SUBMERCHANT_ID, $user['id']);

        $this->runRequestResponseFlow($this->testData['testUpdateKycAccessWithApprovedStatusWithNoRecord']);

        $kycAccess = $this->getDbLastEntity('partner_kyc_access_state');

        $accessMap = $this->getDbLastEntity('merchant_access_map');

        $this->assertEquals($kycAccess->getState(), 'approved');

        $this->assertEquals($accessMap->hasKycAccess(), true);
    }

    public function testUpdateKycAccessWithRejectedStatusWithNoRecord()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->create('partner_kyc_access_state', ['state' => 'pending_approval'] );

        $this->fixtures->create('referrals', ['merchant_id' => self::DEFAULT_MERCHANT_ID]);

        $user = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_SUBMERCHANT_ID, $user['id']);

        $this->runRequestResponseFlow($this->testData['testUpdateKycAccessWithRejectedStatusWithRecord']);

        $kycAccess = $this->getDbLastEntity('partner_kyc_access_state');

        $this->assertEquals($kycAccess->getState(), 'rejected');
    }

    public function testUpdateKycAccessWithRejectedStatusWithRecord()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->create('partner_kyc_access_state', ['state' => 'pending_approval'] );

        $this->fixtures->create('referrals', ['merchant_id' => self::DEFAULT_MERCHANT_ID]);

        $user = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_SUBMERCHANT_ID, $user['id']);

        $this->startTest();

        $kycAccess = $this->getDbLastEntity('partner_kyc_access_state');

        $this->assertEquals($kycAccess->getState(), 'rejected');
    }

    public function testConfirmKycAccessRequest()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->create('partner_kyc_access_state');

        $this->ba->directAuth();

        $razorxMock = $this->getMockBuilder(Merchant\Core::class)
                           ->setMethods(['isRazorxExperimentEnable'])
                           ->getMock();

        $razorxMock->expects($this->any())
                   ->method('isRazorxExperimentEnable')
                   ->willReturn(true);

        $this->mockAllSplitzTreatment();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $merchantTestUtil = new MerchantTest();
        $merchantTestUtil->expectStorkSmsRequest($storkMock, 'sms.onboarding.partner_submerchant_kyc_access_approved', '+919123456789', [
            'subMerchantId'   => self::DEFAULT_SUBMERCHANT_ID,
            'subMerchantName' => 'submerchant'
        ]);

        $whatsappTextRegex = '/submerchant with MID: 10000000000009 has approved your request to perform their KYC. Visit your Partner Dashboard, to access their KYC form./';
        $merchantTestUtil->expectStorkWhatsappRequest($storkMock, $whatsappTextRegex, '+919123456789', true);

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

        $razorxMock = $this->getMockBuilder(Merchant\Core::class)
                           ->setMethods(['isRazorxExperimentEnable'])
                           ->getMock();

        $razorxMock->expects($this->any())
                   ->method('isRazorxExperimentEnable')
                   ->willReturn(true);

        $this->mockAllSplitzTreatment();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $merchantTestUtil = new MerchantTest();
        $merchantTestUtil->expectStorkSmsRequest($storkMock, 'sms.onboarding.partner_submerchant_kyc_access_rejected', '+919123456789', [
            'subMerchantId'   => self::DEFAULT_SUBMERCHANT_ID,
            'subMerchantName' => 'submerchant'
        ]);

        $whatsappTextRegex = 'submerchant with MID: 10000000000009 has rejected your request to perform their Razorpay KYC. Visit Partner Dashboard to resend this request.';
        $merchantTestUtil->expectStorkWhatsappRequest($storkMock, $whatsappTextRegex, '+919123456789', false);

        $this->runRequestResponseFlow($this->testData['testRejectKycAccessRequest']);

        // calling confirm now should work fine
        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);

        // revoke access
        $user = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);
        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_SUBMERCHANT_ID, $user['id']);
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

    public function testGetKycAccessStatusPending()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->create('partner_kyc_access_state', ['state' => 'pending_approval']);

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID);

        $this->fixtures->create('referrals', ['merchant_id' => self::DEFAULT_MERCHANT_ID]);

        $user = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_SUBMERCHANT_ID, $user['id']);

        $testData = $this->testData['testGetKycAccessStatusPending'];

        $testData['response']['content']['partner_name'] = $merchant->getName();

        $this->runRequestResponseFlow($testData);
    }

    public function testGetKycAccessStatusNonPending()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->create('partner_kyc_access_state', ['state' => 'approved'] );

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID);

        $this->fixtures->create('referrals', ['merchant_id' => self::DEFAULT_MERCHANT_ID]);

        $user = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_SUBMERCHANT_ID, $user['id']);

        $testData = $this->testData['testGetKycAccessStatusNonPending'];

        $testData['response']['content']['partner_name'] = $merchant->getName();

        $this->runRequestResponseFlow($testData);
    }

    public function testGetKycAccessStatusNoRecord()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->create('referrals', ['merchant_id' => self::DEFAULT_MERCHANT_ID]);

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID);

        $user = $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_SUBMERCHANT_ID, $user['id']);

        $testData = $this->testData['testGetKycAccessStatusPending'];

        $testData['response']['content']['partner_name'] = $merchant->getName();

        $this->runRequestResponseFlow($testData);
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

    public function testSubmerchantPresignUpByPartner()
    {
        $this->createResellerPartnerSubmerchant(true, true);

        $this->fixtures->merchant->addFeatures(['partner_sub_kyc_access'], self::DEFAULT_MERCHANT_ID);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_SUBMERCHANT_ID);
    }

    public function testfetchSubmerchantActivationByPartner()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->merchant->addFeatures(['partner_sub_kyc_access'], self::DEFAULT_MERCHANT_ID);

        $testData = $this->testData[__FUNCTION__];

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

        $this->mockAllSplitzResponseDisable();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsOptimised()
    {
        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

        $this->createPartnerAndAddMultipleSubmerchants();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsOptimisedWithContactNoFilter()
    {
        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->createResellerPartnerSubmerchant();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsOptimisedWithContactMobileFilter()
    {
        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->createResellerPartnerSubmerchant();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsOptimisedWithEmailFilter()
    {
        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessMerchant(self::DEFAULT_SUBMERCHANT_ID);

        $this->createResellerPartnerSubmerchant();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsFilters()
    {
        $this->createPartnerAndAddMultipleSubmerchants();

        $this->ba->adminProxyAuth();

        $this->mockAllSplitzResponseDisable();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsFiltersOptimised()
    {
        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

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

        $this->mockAllSplitzResponseDisable();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsTypeFilterOptimised()
    {
        $partnerUser = $this->createPartnerAndUser();

        $this->createSubmerchantAndUser(Mode::TEST, 'subm1@xyz.com');
        $app = $this->fixtures->merchant->createDummyReferredAppForManaged([
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type'=> 'reseller',
        ]);

        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map', $accessMap);

        $submerchantId = '10000000000011';
        $this->allowAdminToAccessMerchant($submerchantId);
        $this->fixtures->on(Mode::TEST)->merchant->edit($submerchantId, [
            'name' => 'random_name_1',
            'email' => 'subm2@xyz.com',
        ]);
        $this->fixtures->on(Mode::TEST)->user->createUserForMerchant($submerchantId, ['email' => 'subm2@xyz.com']);

        $this->fixtures->user->createUserForMerchant($submerchantId);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $this->addUserToMerchant($partnerUser, $submerchantId, 'owner');
        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), $submerchantId, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map',$accessMap);

        $this->ba->adminProxyAuth();

        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsFilterByActivationStatus()
    {
        $partnerUser = $this->createPartnerAndUser();

        $this->createSubmerchantAndUser(Mode::TEST, 'subm1@xyz.com');
        $app = $this->fixtures->merchant->createDummyReferredAppForManaged([
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type'=> 'reseller',
        ]);

        $this->fixtures->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['activation_status' => null]);

        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map', $accessMap);

        $submerchantId = '10000000000011';
        $this->allowAdminToAccessMerchant($submerchantId);
        $this->fixtures->on(Mode::TEST)->merchant->edit($submerchantId, [
            'name' => 'random_name_1',
            'email' => 'subm2@xyz.com',
        ]);

        $this->fixtures->merchant_detail->edit($submerchantId, ['activation_status' => 'activated']);

        $this->fixtures->user->createUserForMerchant($submerchantId, ['email' => 'subm2@xyz.com']);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $this->addUserToMerchant($partnerUser, $submerchantId, 'owner');
        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), $submerchantId, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map',$accessMap);

        $this->ba->adminProxyAuth();

        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsFilterByActivationStatusAllActivated()
    {
        $partnerUser = $this->createPartnerAndUser();

        $this->createSubmerchantAndUser(Mode::TEST, 'subm1@xyz.com');
        $app = $this->fixtures->merchant->createDummyReferredAppForManaged([
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type'=> 'reseller',
        ]);

        $this->fixtures->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['activation_status' => 'activated']);

        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map', $accessMap);

        $submerchantId = '10000000000011';
        $this->allowAdminToAccessMerchant($submerchantId);
        $this->fixtures->on(Mode::TEST)->merchant->edit($submerchantId, [
            'name' => 'random_name_1',
            'email' => 'subm2@xyz.com',
        ]);

        $this->fixtures->merchant_detail->edit($submerchantId, ['activation_status' => null]);

        $this->fixtures->user->createUserForMerchant($submerchantId, ['email' => 'subm2@xyz.com']);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $this->addUserToMerchant($partnerUser, $submerchantId, 'owner');
        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), $submerchantId, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map',$accessMap);

        $this->ba->adminProxyAuth();

        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsFilterByActivationStatusForAllActivatedEmpty()
    {
        $partnerUser = $this->createPartnerAndUser();

        $this->createSubmerchantAndUser(Mode::TEST, 'subm1@xyz.com');
        $app = $this->fixtures->merchant->createDummyReferredAppForManaged([
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type'=> 'reseller',
        ]);

        $this->fixtures->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['activation_status' => 'under_review']);

        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map', $accessMap);

        $submerchantId = '10000000000011';
        $this->allowAdminToAccessMerchant($submerchantId);
        $this->fixtures->on(Mode::TEST)->merchant->edit($submerchantId, [
            'name' => 'random_name_1',
            'email' => 'subm2@xyz.com',
        ]);

        $this->fixtures->merchant_detail->edit($submerchantId, ['activation_status' => 'kyc_qualified_unactivated']);

        $this->fixtures->user->createUserForMerchant($submerchantId, ['email' => 'subm2@xyz.com']);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $this->addUserToMerchant($partnerUser, $submerchantId, 'owner');
        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), $submerchantId, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map',$accessMap);

        $this->ba->adminProxyAuth();

        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsFilterByActivationStatusAllUnderReview()
    {
        $partnerUser = $this->createPartnerAndUser();

        $this->createSubmerchantAndUser(Mode::TEST, 'subm1@xyz.com');
        $app = $this->fixtures->merchant->createDummyReferredAppForManaged([
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type'=> 'reseller',
        ]);

        $this->fixtures->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['activation_status' => 'kyc_qualified_unactivated']);

        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map', $accessMap);

        $submerchantId = '10000000000011';
        $this->allowAdminToAccessMerchant($submerchantId);
        $this->fixtures->on(Mode::TEST)->merchant->edit($submerchantId, [
            'name' => 'random_name_1',
            'email' => 'subm2@xyz.com',
        ]);

        $this->fixtures->merchant_detail->edit($submerchantId, ['activation_status' => 'under_review']);

        $this->fixtures->user->createUserForMerchant($submerchantId, ['email' => 'subm2@xyz.com']);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $this->addUserToMerchant($partnerUser, $submerchantId, 'owner');
        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), $submerchantId, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map',$accessMap);

        $submerchantId = '10000000000012';
        $this->allowAdminToAccessMerchant($submerchantId);
        $this->fixtures->on(Mode::TEST)->merchant->edit($submerchantId, [
            'name' => 'random_name_3',
            'email' => 'subm3@xyz.com',
        ]);

        $this->fixtures->merchant_detail->edit($submerchantId, ['activation_status' => null]);

        $this->fixtures->user->createUserForMerchant($submerchantId, ['email' => 'subm3@xyz.com']);

        $this->addUserToMerchant($partnerUser, $submerchantId, 'owner');
        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), $submerchantId, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map',$accessMap);

        $this->ba->adminProxyAuth();

        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsFilterByActivationStatusForAllUnderReviewEmpty()
    {
        $partnerUser = $this->createPartnerAndUser();

        $this->createSubmerchantAndUser(Mode::TEST, 'subm1@xyz.com');
        $app = $this->fixtures->merchant->createDummyReferredAppForManaged([
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type'=> 'reseller',
        ]);

        $this->fixtures->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['activation_status' => 'activated']);

        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map', $accessMap);

        $submerchantId = '10000000000011';
        $this->allowAdminToAccessMerchant($submerchantId);
        $this->fixtures->on(Mode::TEST)->merchant->edit($submerchantId, [
            'name' => 'random_name_1',
            'email' => 'subm2@xyz.com',
        ]);

        $this->fixtures->merchant_detail->edit($submerchantId, ['activation_status' => 'needs_clarification']);

        $this->fixtures->user->createUserForMerchant($submerchantId, ['email' => 'subm2@xyz.com']);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $this->addUserToMerchant($partnerUser, $submerchantId, 'owner');
        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), $submerchantId, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map',$accessMap);

        $this->ba->adminProxyAuth();

        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsFilterByActivationStatusRejected()
    {
        $partnerUser = $this->createPartnerAndUser();

        $this->createSubmerchantAndUser(Mode::TEST, 'subm1@xyz.com');
        $app = $this->fixtures->merchant->createDummyReferredAppForManaged([
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'partner_type'=> 'reseller',
        ]);

        $this->fixtures->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['activation_status' => 'rejected']);

        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map', $accessMap);

        $submerchantId = '10000000000011';
        $this->allowAdminToAccessMerchant($submerchantId);
        $this->fixtures->on(Mode::TEST)->merchant->edit($submerchantId, [
            'name' => 'random_name_1',
            'email' => 'subm2@xyz.com',
        ]);

        $this->fixtures->merchant_detail->edit($submerchantId, ['activation_status' => null]);

        $this->fixtures->user->createUserForMerchant($submerchantId, ['email' => 'subm2@xyz.com']);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $this->addUserToMerchant($partnerUser, $submerchantId, 'owner');
        $accessMap = $this->getAccessMapArray(
            'application', $app->getId(), $submerchantId, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->create('merchant_access_map',$accessMap);

        $this->ba->adminProxyAuth();

        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

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

    public function testFetchPartnerSubmerchantsPurePlatformOptimised()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();
        $this->fixtures->on(Mode::TEST)->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, [
            'name' => 'random_name_1',
            'email' => 'subm1@xyz.com',
        ]);
        $partnerUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);
        $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID, ['email' => 'subm1@xyz.com']);

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

        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

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

    public function testFetchPartnerSubmerchantsPurePlatformFiltersOptimised()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->allowAdminToAccessSubMerchant();
        $this->fixtures->on(Mode::TEST)->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, [
            'name' => 'random_name_1',
            'email' => 'subm1@xyz.com',
        ]);

        $partnerUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $this->fixtures->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID, ['email' => 'subm1@xyz.com']);

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

        $this->mockSubmerchantFetchMultipleOptimisedExperiment();
        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testFetchPartnerSubmerchantsPaginationFilters()
    {
        $this->createPartnerAndAddMultipleSubmerchants();

        $this->mockAllSplitzResponseDisable();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testFetchPartnerSubmerchantsPaginationFiltersOptimised()
    {
        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

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

    /**
     * Tests the list submerchants api when there are no submerchants for the partner
     */
    public function testFetchPartnerSubmerchantsEmptyListOptimised()
    {
        $this->createPartnerAndUser();

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $this->ba->adminProxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->mockSubmerchantFetchMultipleOptimisedExperiment();

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
        $this->createPartnerAndUser();

        $partnerUser = $this->fixtures->on(Mode::TEST)->user->createUserForMerchant(
            self::DEFAULT_MERCHANT_ID, [], 'sellerapp'
        );

        $subMerchantUser = $this->createSubmerchantAndUser();

        $this->ba->proxyAuth('rzp_test_10000000000000', $partnerUser->getId());

        $this->startTest();
    }

    public function testCreatePartnerSubmerchantWithValidContactMobileForX()
    {
        $this->createPartnerAndUser();

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator']);

        $this->ba->proxyAuth();

        $this->mockRazorxTreatment();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedParms = [
            'subMerchantName' => 'random_name_1'
        ];

        (new MerchantTest())->expectStorkSmsRequest($storkMock,'sms.onboarding.partner_submerchant_invite_v2', '+919999999999', $expectedParms);

        $this->startTest();
    }

    public function testCreatePartnerSubmerchantWithValidContactMobileForPrimary()
    {
        Mail::fake();

        $this->createPartnerAndUser();

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator']);

        $merchantDetailArray = $this->fixtures->merchant_detail->createMerchantDetail(
            [
                'merchant_id'   => self::DEFAULT_MERCHANT_ID,
                'business_type' => 2,
                'contact_email' => 'user@email.com'
            ]);

        $this->fixtures->merchant_detail->createAssociateMerchant($merchantDetailArray);

        $this->ba->proxyAuth();

        $razorxMock = $this->getMockBuilder(Merchant\Core::class)
                           ->setMethods(['isRazorxExperimentEnable'])
                           ->getMock();

        $razorxMock->expects($this->any())
                   ->method('isRazorxExperimentEnable')
                   ->willReturn(true);

        $this->mockAllSplitzTreatment();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        // test clipping name to 25 characters
        $expectedParams = [
            'subMerchantName' => 'some_very_long_long_na...'
        ];

        $merchantTestUtil = new MerchantTest();
        $merchantTestUtil->expectStorkSmsRequest($storkMock, 'Sms.Partnerships.Add_sub_merchant_partner', '+919123456789', $expectedParams);
        $whatsappTextRegex = '/some_very_long_long_na\.\.\. \(\w{14}\) has been added as your affiliate account on Razorpay\. We have sent an invite mail to user@example\.com for setting up their Razorpay account password\. They must login and submit the activation form with KYC details to start transacting\./';
        $merchantTestUtil->expectStorkWhatsappRequest($storkMock, $whatsappTextRegex, '+919123456789', true);

        $this->startTest();
    }

    public function testCreatePartnerSubmerchantWithInvalidContactMobile()
    {
        $this->createPartnerAndUser();

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testAddPartnerAddedFeaturesToSubmerchantOnMode()
    {
        list($application, $accessMap) = $this->createPartnerMerchantAndSubMerchant(MerchantConstants::AGGREGATOR);
        $partner = $this->getDbEntity('merchant', ['id' => $application->getMerchantId()]);
        $merchant = $accessMap->merchant()->first();

        $this->fixtures->on('test')->merchant->addFeatures(
            ['feature_bbps'],
            $application->getId(),
            Feature\Constants::PARTNER_APPLICATION
        );

        $core = new \RZP\Models\Merchant\Core;

        $this->app['rzp.mode'] = Mode::TEST;

        $core->addPartnerAddedFeaturesToSubmerchantOnMode($merchant, $partner, Mode::TEST);

        $testFeature = $this->getDbEntities(
            'feature',
            ['entity_id' => $merchant->getId(), 'name' => 'feature_bbps'],
            Mode::TEST
        )->toArray();
        $this->assertNotEmpty($testFeature);

        $liveFeature = $this->getDbEntities(
            'feature',
            ['entity_id' => $merchant->getId(), 'name' => 'feature_bbps'],
            Mode::LIVE
        )->toArray();
        $this->assertEmpty($liveFeature);
    }

    public function testAdd_SourcedByWalnut369_PartnerAddedFeatureToSubmerchantOnMode()
    {
        list($application, $accessMap) = $this->createPartnerMerchantAndSubMerchant(MerchantConstants::AGGREGATOR);
        $partner = $this->getDbEntity('merchant', ['id' => $application->getMerchantId()]);
        $merchant = $accessMap->merchant()->first();

        $this->fixtures->on('test')->merchant->addFeatures(
            ['sourced_by_walnut369'],
            $partner->getId(),
            Feature\Constants::MERCHANT
        );

        $core = new \RZP\Models\Merchant\Core;

        $this->app['rzp.mode'] = Mode::TEST;

        $testFeature = $this->getDbEntities(
            'feature',
            ['entity_id' => $merchant->getId(), 'name' => 'sourced_by_walnut369'],
            Mode::TEST
        )->toArray();
        $this->assertEmpty($testFeature);

        $core->addPartnerAddedFeaturesToSubmerchantOnMode($merchant, $partner, Mode::TEST);

        $testFeature = $this->getDbEntities(
            'feature',
            ['entity_id' => $merchant->getId(), 'name' => 'sourced_by_walnut369'],
            Mode::TEST
        )->toArray();
        $this->assertNotEmpty($testFeature);

        $liveFeature = $this->getDbEntities(
            'feature',
            ['entity_id' => $merchant->getId(), 'name' => 'sourced_by_walnut369'],
            Mode::LIVE
        )->toArray();
        $this->assertEmpty($liveFeature);

        $core->addPartnerAddedFeaturesToSubmerchantOnMode($merchant, $partner, Mode::LIVE);

        $liveFeature = $this->getDbEntities(
            'feature',
            ['entity_id' => $merchant->getId(), 'name' => 'sourced_by_walnut369'],
            Mode::LIVE
        )->toArray();
        $this->assertNotEmpty($liveFeature);

    }

    public function testCreatePartnerSubmerchantWithProduct()
    {
        $this->createPartnerAndUser();
        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);
        $this->ba->proxyAuth();

        $this->fixtures->on('test')->create('merchant_detail:sane', [
            'merchant_id' => $app->merchant_id
        ]);

        $this->fixtures->on('live')->create('merchant_detail:sane', [
            'merchant_id' => $app->merchant_id
        ]);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'id'          => 'IBb9OU2WPuCC29',
                'entity_type' => 'application',
                'entity_id'   => $app->getId(),
                'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
            ]
        );

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

        $this->assertEquals(1, $subMerchantBankingOwners['count']);

        $submerchantUserId = $subMerchantBankingOwners['items'][0]['id'];

        $subMerchantUser = DB::table('merchant_users')->where('user_id', '=', $submerchantUserId)->where('product', '=', 'banking')->get();

        $this->assertEquals('banking', $subMerchantUser[0]->product);

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

    protected function mockBvsService()
    {
        $mock = $this->mockCreateLegalDocument();

        $mock->expects($this->once())->method('createLegalDocument')->withAnyParameters();
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

    public function testCreateLegalDocsConsentForResellerPartner()
    {
        Mail::fake();

        $this->fixtures->create('merchant_detail:sane',
            [
                'merchant_id'       => self::DEFAULT_MERCHANT_ID,
            ]);

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID);

        $app = ['id'=>'8ckeirnw84ifke'];

        $this->mockAuthServiceCreateApplication($merchant, $app);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->mockBvsService();

        $this->ba->proxyAuth();

        $this->startTest();

        $merchantConsents = $this->getDbLastEntity('merchant_consents');

        $termsDetails = (new MerchantConsentDetailsRepo())->getById($merchantConsents->getDetailsId());

        $expectedTerms        =  MerchantConstants::RAZORPAY_PARTNERSHIP_TERMS;
        $expectedConsentFor   =  'Partnership_Terms & Conditions';

        $this->assertEquals($merchant->getId(), $merchantConsents->getMerchantId());
        $this->assertEquals($expectedConsentFor, $merchantConsents->getConsentFor());
        $this->assertEquals($expectedTerms, $termsDetails->getURL());

        $this->assertEquals('initiated', $merchantConsents->getStatus());
    }

    public function testSkipCreateLegalDocsConsentForResellerPartnerIfConsentsExists()
    {
        Mail::fake();

        $this->fixtures->create('merchant_detail:sane',
            [
                'merchant_id'       => self::DEFAULT_MERCHANT_ID,
            ]);

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID);

        $app = ['id'=>'8ckeirnw84ifke'];

        $this->mockAuthServiceCreateApplication($merchant, $app);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->ba->proxyAuth();

        $this->fixtures->create('merchant_consents',
            [
                'merchant_id' => $merchant->getId(),
                'consent_for' => 'Partnership_Terms & Conditions',
                'status'      => 'initiated'
            ]);

        $expectedMerchantConsents = $this->getDbLastEntity('merchant_consents');

        $testData = $this->testData['testCreateLegalDocsConsentForResellerPartner'];

        $this->runRequestResponseFlow($testData);

        $merchantConsents = $this->getDbLastEntity('merchant_consents');

        $this->assertEquals($expectedMerchantConsents, $merchantConsents);

    }

    public function testUpdatePartnerTypeUsingProxyAuthWithInvalidPartnerType()
    {
        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, 'live');

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
        $partnerUser = $this->createPartnerAndUser($mode, 'partner@xyz.com');

        $this->createSubmerchantAndUser($mode, 'subm1@xyz.com');

        $submerchantId = '10000000000011';
        $this->allowAdminToAccessMerchant($submerchantId);
        $this->fixtures->on($mode)->merchant->edit($submerchantId, [
            'name' => 'random_name_1',
            'email' => 'subm2@xyz.com',
        ]);
        $this->fixtures->on($mode)->user->createUserForMerchant($submerchantId, ['email' => 'subm2@xyz.com']);

        $app = $this->fixtures->on($mode)->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $appId = $app->getId();

        // Link new submerchants to the partner account
        $accessMap = $this->getAccessMapArray(
            'application', $appId, self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->on($mode)->create('merchant_access_map',$accessMap);
        $accessMap = $this->getAccessMapArray(
            'application', $appId, $submerchantId, self::DEFAULT_MERCHANT_ID
        );
        $this->fixtures->on($mode)->create('merchant_access_map',$accessMap);

        $this->fixtures->on($mode)->user->createUserMerchantMapping([
            'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
            'user_id'     => $partnerUser->getUserId(),
            'role'        => 'owner',
        ]);

        $this->fixtures->on($mode)->user->createUserMerchantMapping([
            'merchant_id' => $submerchantId,
            'user_id'     => $partnerUser->getUserId(),
            'role'        => 'owner',
        ]);

        return $appId;
    }

    protected function createPartnerAndUser(string $mode = Mode::TEST, $email = 'partner@hotmail.com')
    {
        $this->allowAdminToAccessPartnerMerchant();

        $this->fixtures->on($mode)->merchant->edit(
            self::DEFAULT_MERCHANT_ID,
            ['partner_type' => 'fully_managed', 'email' => $email]
        );

        return $this->fixtures->on($mode)->user->createUserForMerchant(
            self::DEFAULT_MERCHANT_ID, ['email' => $email]
        );
    }

    protected function createSubmerchantAndUser(string $mode = Mode::TEST, string $email = 'mills.rudolph@smith.biz')
    {
        $this->allowAdminToAccessSubMerchant();

        $this->fixtures->on($mode)->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, [
            'name' => 'random_name_1',
            'email' => $email,
        ]);

        $this->fixtures->on($mode)->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['activation_status' => 'under_review']);

        $submerchantUser = $this->fixtures->on($mode)->user->createUserForMerchant(
            self::DEFAULT_SUBMERCHANT_ID, ['email' => $email]
        );

        return $submerchantUser;
    }

    protected function getAccessMapArray($entityType, $entityId, $merchantId, $entityOwnerId, string $id = null)
    {
        $accessMap = [
            'entity_type'     => $entityType,
            'entity_id'       => $entityId,
            'merchant_id'     => $merchantId,
            'entity_owner_id' => $entityOwnerId,
        ];

        if(empty($id) === false)
        {
            $accessMap = array_merge($accessMap, ['id' => $id]);
        }
        return $accessMap;
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

    protected function createResellerApp()
    {
        $app = Application\Entity::factory()->create(
            [
                'id'          => random_integer(10),
                'merchant_id' => self::DEFAULT_MERCHANT_ID,
                'type'        => Merchant\Constants::PARTNER
            ]
        );

        $this->createMerchantApplication(self::DEFAULT_MERCHANT_ID, Merchant\Constants::RESELLER, $app->getId());

        return $app;

    }

    protected function createMerchantAccessMap(string $appId, string $submerchantId)
    {
        $this->fixtures->create(
            'merchant_access_map',
            [
                'id'          => random_integer(14),
                'entity_type' => 'application',
                'entity_id'   => $appId,
                'merchant_id' => $submerchantId,
            ]
        );
    }

    public function createResellerPartnerSubmerchant(bool $isContactMobileVerified = false, bool $addPricing = false)
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

        $app = $this->createResellerApp();

        $this->fixtures->on('test')->create('merchant_detail:sane',[
            'merchant_id'       => $app->merchant_id,
            'contact_name'      => 'randomName',
            'contact_mobile'    => '9123456789',
            'business_type'     => 2,
            'contact_email'     => 'test@example.com',
        ]);

        $this->fixtures->on('live')->create('merchant_detail:sane',[
            'merchant_id'      => $app->merchant_id,
            'contact_name'     => 'randomName',
            'contact_mobile'   => '9123456789',
            'business_type'    => 2,
            'contact_email'    => 'test@example.com',
        ]);

        $this->fixtures->on('live')->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['business_type' => 2, 'activation_status' => 'activated', 'contact_mobile'=> '9123456788']);
        $this->fixtures->on('test')->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['business_type' => 2, 'activation_status' => 'activated', 'contact_mobile'=> '9123456788']);

        $this->fixtures->on('test')->edit('merchant', self::DEFAULT_SUBMERCHANT_ID, ['name' => 'submerchant']);
        $this->fixtures->on('live')->edit('merchant', self::DEFAULT_SUBMERCHANT_ID, ['name' => 'submerchant']);
        $this->fixtures->on('test')->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID, ['email' => 'testing@example.com','contact_mobile'=> '9123456788', 'contact_mobile_verified' => $isContactMobileVerified]);

        $this->createMerchantAccessMap($app->getId(), self::DEFAULT_SUBMERCHANT_ID);

        if ($addPricing == true)
        {
            $subMerchantPricingPlan = [
                'plan_id'             => 'LFbrOUOTRSyAqq',
                'plan_name'           => 'testSubMPricingPlan',
                'feature'             => 'payment',
                'type'                => 'pricing',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 250,
                'fixed_rate'          => 0,
                'org_id'              => $subMerchant['org_id'],
            ];

            $this->fixtures->create('pricing', $subMerchantPricingPlan);
            $this->fixtures->edit('merchant', self::DEFAULT_SUBMERCHANT_ID, ['pricing_plan_id' => 'LFbrOUOTRSyAqq']);
        }
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
        return $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => $merchantId ,
                'product' => $product
            ],
            $mode
        );
    }

    private function mockRazorxTreatment()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');
    }

    public function testPartnerSalesPoc() : void
    {
        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID);

        $expectedResponse = [
            'Enabler_POC__r' => [
                'Name'  => 'Test Razorpay',
                'Email' => 'test.sales@example.com',
                'Phone' => '9876543210',
                'Title' => 'Partnerships',
            ]
        ];

        $this->setUpSalesforceMock();

        $this->mockAllSplitzTreatment();

        $this->mockSalesforceRequest(self::DEFAULT_MERCHANT_ID, $expectedResponse, 'getPartnershipSalesPOCForMerchantId');

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testSelfServePartnerSalesPoc() : void
    {
        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID);

        $expectedResponse = [
            'Enabler_POC__r' => [
                'Name'  => 'Partner Self Serve',
                'Email' => 'test.sales@example.com',
                'Phone' => '9876543210',
                'Title' => 'Partnerships',
            ]
        ];

        $this->setUpSalesforceMock();

        $this->mockAllSplitzTreatment();

        $this->mockSalesforceRequest(self::DEFAULT_MERCHANT_ID, $expectedResponse, 'getPartnershipSalesPOCForMerchantId');

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testEmptyPartnerSalesPoc() : void
    {
        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID);

        $expectedResponse = [];

        $this->setUpSalesforceMock();

        $this->mockAllSplitzTreatment();

        $this->mockSalesforceRequest(self::DEFAULT_MERCHANT_ID, $expectedResponse, 'getPartnershipSalesPOCForMerchantId');

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testRequestPartnerMigration() : void
    {
        $merchant = $this->setupResellerForPartnerRequestMigration();

        $expectedResponse = ['status_code'=> 200, 'response'=> []];
        $requestInput = $this->testData[__FUNCTION__]['request']['content'];
        $input = $this->createPartnerMigrationAuditInput($merchant, $requestInput);
        $this->mockPartnershipsServiceTreatment($input, $expectedResponse, 'createPartnerMigrationAudit');

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_MERCHANT_ID);

        $this->mockBvsService();

        $this->startTest();

        // assert that consents in stored
        $merchantConsents = $this->getDbLastEntity('merchant_consents');

        $merchantConsentDetails = (new MerchantConsentDetailsRepo())->getById($merchantConsents->getDetailsId());

        $expectedTerms        =  $requestInput['terms']['consent'];
        $expectedConsentFor   =  'Partner_Type_Switch_Terms & Conditions';

        $this->assertEquals($merchant->getId(), $merchantConsents->getMerchantId());
        $this->assertEquals($expectedConsentFor, $merchantConsents->getConsentFor());
        $this->assertEquals($expectedTerms, $merchantConsentDetails->getURL());

        $this->assertEquals('initiated', $merchantConsents->getStatus());
    }

    public function testRequestPartnerMigrationError() : void
    {
        $merchant = $this->setupResellerForPartnerRequestMigration();

        $expectedResponse = ['status_code'=> 401, 'response'=> []];

        $requestInput = $this->testData[__FUNCTION__]['request']['content'];

        $input = $this->createPartnerMigrationAuditInput($merchant, $requestInput);

        $this->mockPartnershipsServiceTreatment($input, $expectedResponse, 'createPartnerMigrationAudit');

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    private function setupResellerForPartnerRequestMigration()
    {
        $this->fixtures->edit('merchant', self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller',]);
        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID);
        $this->fixtures->create('merchant_consents',
            [
                'merchant_id' => self::DEFAULT_MERCHANT_ID,
                'consent_for' => 'Partnership_Terms & Conditions',
                'status'      => 'initiated',
                'created_at'  => Carbon::now()->getTimestamp()
            ]);
        $this->fixtures->create('merchant_detail:sane', ['merchant_id'       => self::DEFAULT_MERCHANT_ID]);

        $partnerOnboardingTs = Carbon::now()->getTimestamp();
        $expRequestData = [
            'mid'                   => self::DEFAULT_MERCHANT_ID ,
            'partner_onboarding_ts' => $partnerOnboardingTs
        ];
        $expInput = [
            'id'            => self::DEFAULT_MERCHANT_ID,
            'experiment_id' => $this->app['config']->get('app.partner_type_switch_exp_id'),
            'request_data'  => json_encode($expRequestData),
        ];
        $expOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];
        $this->mockSplitzTreatment($expInput, $expOutput);

        return $merchant;
    }

    public function testRequestPartnerMigrationInputValidation() : void
    {
        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testMigrateResellerToPurePlatform(): void
    {
        Mail::fake();
        Event::fake();
        $this->mockAllSplitzTreatment();

        list($partnerId, $app, $subMerchant) = $this->createResellerPartnerAndSubmerchant();

        $this->authServiceMock
            ->expects($this->exactly(1))
            ->method('sendRequest')
            ->with( 'applications/'.$app->getId(), 'PUT', ['merchant_id' => $partnerId] )
            ->willReturn([]);
        $this->mockPartnershipsServiceTreatment([], ['status_code' => 200], 'createPartnerMigrationAudit');

        $beforeMigrationPartnerConfig = $this->getDbEntities('partner_config');

        $this->startTest();

        Event::assertDispatched(TransactionalClosureEvent::class);

        $partner = $this->getDbEntity('merchant', ['id' => $partnerId]);
        $subMerchant = $this->getDbEntity('merchant', ['id' => $subMerchant->getId()]);

        $this->assertEquals('pure_platform', $partner->getPartnerType());

        $this->assertEquals([], $this->getDbEntities('merchant_application')->toArray());
        $this->assertEquals([], $this->getDbEntities('merchant_access_map')->toArray());
        $this->assertEquals([], $this->getDbEntities('partner_kyc_access_state')->toArray());
        $this->assertEquals([], $subMerchant->tagNames());
        $userDeviceDetails = $this->getDbEntity('user_device_detail', ['merchant_id' => $partnerId, 'user_id' => 'MerchantUser01']);
        $this->assertEquals('easy_onboarding', $userDeviceDetails['signup_campaign']);

        // validate if default partner config created for pure platform partner
        $partnerConfig = $this->getDbEntities('partner_config');

        $this->assertCount(2, $partnerConfig);
        $this->assertEquals($beforeMigrationPartnerConfig[0]['id'], $partnerConfig[0]['id']);
        $this->assertEquals($beforeMigrationPartnerConfig[1]['id'], $partnerConfig[1]['id']);
    }

    public function testMigrateResellerToPurePlatformJobSent(): void
    {
        Queue::fake();
        $this->mockAllSplitzTreatment();

        $defaultPartnerId = 'DefaultPartner';

        $this->createResellerPartnerAndSubmerchant();

        $testData = $this->testData['testMigrateResellerToPurePlatform'];
        $this->startTest($testData);

        Queue::assertPushed(
            MigrateResellerToPurePlatformPartnerJob::class,
            function ($job) use($defaultPartnerId) {
                $this->assertEquals($defaultPartnerId, $job->getMerchantId());

                return true;
            }
        );
    }

    public function testNotifyPartnerAboutResellerToPPPartnerTypeSwitch()
    {
        Mail::fake();
        $this->mockAllSplitzTreatment();
        $this->mockGimliURLShortener();
        $defaultPartnerId = 'DefaultPartner';

        $this->testMigrateResellerToPurePlatform();

        $partner = $this->getDbEntity('merchant', ['id' => $defaultPartnerId]);
        $notifyUsecase = new NotifyPartnerAboutPartnerTypeSwitch($partner, 'reseller', 'pure_platform');

        $this->merchantTestUtil->expectStorkSmsRequest(
            $this->storkMock,
            'Sms.Partnerships.Partner_type_reseller_to_pure_platform_v3',
            $partner->merchantDetail->getContactMobile(),
            [
                'partnerName'         => $partner->getName() ,
                'platformDocsLink'    => "https://rzp.io/i/partner",
                'partnerSupportEmail' => 'partners@razorpay.com'
            ]
        );

        $notifyUsecase->notify();

        Mail::assertSent(PartnerTypeSwitchEmail::class, function ($mail) use($partner) {
            $subject = 'Partner account type updated to Platform Partner type';
            $from = [
                0 => [
                    'name'    => 'Razorpay Partner Program',
                    'address' => 'partnercommunication@razorpay.com',
                ]
            ];
            $to = [
                0 => [
                    'address' => $partner->getEmail(),
                    'name'    => $partner->getName(),
                ]
            ];

            $this->assertSame($subject, $mail->subject);
            $this->assertArraySelectiveEquals($from, $mail->from);
            $this->assertArraySelectiveEquals($to, $mail->to);
            $this->assertSame('emails.mjml.merchant.partner.notify.reseller_to_pure_platform_switch', $mail->view);
            $this->assertEquals('IN', $mail->viewData['country_code']);

            return true;

        });
    }

    public function testMigratePurePlatformToReseller(): void
    {
        Mail::fake();
        Event::fake();
        $this->mockAllSplitzTreatment();

        list($oauthApp, $accessMap, $partner, $newReferredAppId) = $this->setUpPurePlatformPartnerForSuccesfulPartnerTypeSwitch();
        $this->mockPartnershipsServiceTreatment([], ['status_code' => 200], 'createPartnerMigrationAudit');

        $this->startTest();

        $this->assertSuccessfulPPToResellerPartnerTypeSwitch(
            $partner, $accessMap, $oauthApp, $newReferredAppId
        );
    }

    public function testMigratePPToResellerWhenActiveTokenIsExpiredAndRevoked()
    {
        Mail::fake();
        Event::fake();
        $this->mockAllSplitzTreatment();

        list($oauthApp, $accessMap, $partner, $newReferredAppId) = $this->setUpPurePlatformPartnerForSuccesfulPartnerTypeSwitch(
            [
                [ 'expires_at' => strtotime('-2 weeks'), 'revoked' => true ],
            ]
        );
        $this->mockPartnershipsServiceTreatment([], ['status_code' => 200], 'createPartnerMigrationAudit');

        $testData = $this->testData['testMigratePurePlatformToReseller'];
        $this->startTest($testData);

        $this->assertSuccessfulPPToResellerPartnerTypeSwitch(
            $partner, $accessMap, $oauthApp, $newReferredAppId
        );
    }

    public function testMigratePPToResellerWhenActiveTokenIsNotExpiredAndRevoked()
    {
        Mail::fake();
        Event::fake();
        $this->mockAllSplitzTreatment();

        list($oauthApp, $accessMap, $partner, $newReferredAppId) = $this->setUpPurePlatformPartnerForSuccesfulPartnerTypeSwitch(
            [
                [ 'expires_at' => strtotime('+2 weeks'), 'revoked' => true ],
            ]
        );
        $this->mockPartnershipsServiceTreatment([], ['status_code' => 200], 'createPartnerMigrationAudit');

        $testData = $this->testData['testMigratePurePlatformToReseller'];
        $this->startTest($testData);

        $this->assertSuccessfulPPToResellerPartnerTypeSwitch(
            $partner, $accessMap, $oauthApp, $newReferredAppId
        );
    }

    public function testMigratePPToResellerWhenActiveTokenIsNotExpiredAndNotRevoked()
    {
        Mail::fake();
        Event::fake();
        $this->mockAllSplitzTreatment();

        list($oauthApp, $accessMap, $partner) = $this->setUpPurePlatformPartnerForFailedPartnerTypeSwitch(
            [
                [ 'expires_at' => strtotime('+2 weeks'), 'revoked' => null ],
            ]
        );

        $testData = $this->testData['testMigratePurePlatformToReseller'];
        $this->startTest($testData);

        $this->assertFailedPPToResellerPartnerTypeSwitch(
            $partner, $accessMap, $oauthApp
        );
    }

    public function testMigratePPToResellerWhenOneSubMIsFirstLinkedToOverriddenConfig()
    {
        Mail::fake();
        Event::fake();
        $this->mockAllSplitzTreatment();

        list($oauthApp1, $oauthApp2, $subMIds, $partner, $newReferredAppId) =
            $this->setUpPurePlatformPartnerWhenOneSubMIsFirstLinkedToOverriddenConfig();
        $this->mockPartnershipsServiceTreatment([], ['status_code' => 200], 'createPartnerMigrationAudit');

        $testData = $this->testData['testMigratePurePlatformToReseller'];
        $this->startTest($testData);

        Event::assertDispatched(TransactionalClosureEvent::class);

        $partner = $this->getDbEntity('merchant', ['id' => $partner->getId()]);
        $subMerchants = $this->getDbEntities('merchant', ['id' => $subMIds]);
        $accessMaps = $this->getDbEntities('merchant_access_map', ['entity_owner_id' => $partner->getId()])->toArray();
        $referrals = $this->getDbEntities('referrals', ['merchant_id' => $partner->getId()]);

        $this->assertEquals('reseller', $partner->getPartnerType());

        $this->assertEquals([], $this->getDbEntities(
            'merchant_application', ['application_id' => [$oauthApp1->getId(), $oauthApp2->getId()]]
        )->toArray());
        $this->assertEquals([], $this->getDbEntities(
            'partner_config', ['entity_id' => [$oauthApp1->getId(), $oauthApp2->getId()]]
        )->toArray());
        $this->assertEquals([], $this->getDbEntities(
            'partner_config', ['origin_id' => [$oauthApp1->getId(), $oauthApp2->getId()]]
        )->toArray());
        $this->assertEquals([], $this->getDbEntities(
            'merchant_access_map', ['entity_id' => [$oauthApp1->getId(), $oauthApp2->getId()]]
        )->toArray());

        $this->assertCount(1, $this->getDbEntities(
            'merchant_application', ['application_id' => $newReferredAppId]
        )->toArray());
        $this->assertCount(1, $this->getDbEntities('partner_config', ['entity_id' => $newReferredAppId])->toArray());
        $this->assertCount(1, $this->getDbEntities(
            'partner_config', ['origin_id' => $newReferredAppId, 'entity_id' => $subMIds[0]]
        )->toArray());

        $this->assertCount(2, $accessMaps);
        $this->assertEquals($newReferredAppId, $accessMaps[0]['entity_id']);
        $this->assertEquals($newReferredAppId, $accessMaps[1]['entity_id']);
        $this->assertEquals(['Ref-' . $partner->getId()], $subMerchants->first()->tagNames());
        $this->assertEquals(['Ref-' . $partner->getId()], $subMerchants->last()->tagNames());

        $this->assertCount(3, $referrals);
    }

    public function testNotifyPartnerAboutPPToResellerPartnerTypeSwitch()
    {
        Mail::fake();
        $this->mockAllSplitzTreatment();
        $defaultPartnerId = '1000000000plat';

        $this->testMigratePurePlatformToReseller();

        $partner = $this->getDbEntity('merchant', ['id' => $defaultPartnerId]);
        $notifyUsecase = new NotifyPartnerAboutPartnerTypeSwitch($partner, 'pure_platform', 'reseller');

        $this->mockStorkSmsCall($partner);

        $notifyUsecase->notify();

        $this->assertMailSentForPPToReseller($partner);
    }

    private function setUpPurePlatformPartnerWhenOneSubMIsFirstLinkedToOverriddenConfig()
    {
        $subM1ID = Partner\Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;
        $subM2ID = '101submerchant';
        $oauthApp2ID = '1000001platApp';
        list($oauthApp1, $oauthApp2, $accessMaps, $partner) = $this->createPPPartnerWith2AppsAnd2SubMerchants($subM2ID, $oauthApp2ID);

        $this->fixtures->create('merchant_detail:sane', ['merchant_id' => $partner->getId()]);
        // App 1 - SubM1 has overridden config
        $this->createConfigForPartnerApp($oauthApp1->getId());
        $this->createConfigForPartnerApp(
            $oauthApp1->getId(),
            $subM1ID
        );

        // App 2 - SubM2 has overridden config
        $this->createConfigForPartnerApp($oauthApp2->getId());
        $this->createConfigForPartnerApp(
            $oauthApp2->getId(),
            $subM2ID
        );

        $this->ba->adminAuth();
        $partnerId = $partner->getId();

        $newReferredAppId = '8ckeirnw84ifke';
        $this->authServiceMock
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->withConsecutive(
                [ 'tokens', 'GET', ['merchant_id' => $partnerId] ],
                [
                    'applications', 'POST',
                    [
                        'name' => 'Referred application',
                        'website' => $partner->getWebsite(),
                        'merchant_id' => $partnerId,
                        'type' => 'partner'
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(['items' => []], $app = ['id'=> $newReferredAppId]);

        $referredAppAttributes = [
            'merchant_id' => $partnerId,
            'partner_type'=> 'referred',
            'id' => $newReferredAppId,
            'name' => 'referred'
        ];
        $this->fixtures->merchant->createDummyPartnerApp($referredAppAttributes, false);

        return [$oauthApp1, $oauthApp2, [$subM1ID, $subM2ID], $partner, $newReferredAppId];
    }

    private function assertFailedPPToResellerPartnerTypeSwitch(
        $partner, $accessMap, $oauthApp
    )
    {
        Event::assertNotDispatched(TransactionalClosureEvent::class);

        $partner = $this->getDbEntity('merchant', ['id' => $partner->getId()]);
        $subMerchant = $this->getDbEntity('merchant', ['id' => $accessMap->getMerchantId()]);
        $accessMaps = $this->getDbEntities('merchant_access_map', ['entity_owner_id' => $partner->getId()])->toArray();
        $referrals = $this->getDbEntities('referrals', ['merchant_id' => $partner->getId()]);

        $this->assertEquals('pure_platform', $partner->getPartnerType());

        $this->assertCount(1, $this->getDbEntities(
            'merchant_application', ['application_id' => $oauthApp->getId()]
        )->toArray());
        $this->assertCount(1, $this->getDbEntities('partner_config', ['entity_id' => $oauthApp->getId()])->toArray());
        $this->assertCount(1, $this->getDbEntities('partner_config', ['origin_id' => $oauthApp->getId()])->toArray());

        $this->assertCount(1, $accessMaps);
        $this->assertEquals($oauthApp->getId(), $accessMaps[0]['entity_id']);
        $this->assertEmpty($subMerchant->tagNames());

        $this->assertCount(0, $referrals);
    }

    private function assertSuccessfulPPToResellerPartnerTypeSwitch(
        $partner, $accessMap, $oauthApp, $newReferredAppId
    )
    {
        Event::assertDispatchedTimes(TransactionalClosureEvent::class, 3);

        Event::assertDispatched(TransactionalClosureEvent::class, function ($listener) use($partner) {
            $reflection = new ReflectionFunction($listener->getClosure());
            $reflection->invoke();
            return true;
        });

        $this->assertMailSentForPPToReseller($partner);

        $partner = $this->getDbEntity('merchant', ['id' => $partner->getId()]);
        $subMerchant = $this->getDbEntity('merchant', ['id' => $accessMap->getMerchantId()]);
        $accessMaps = $this->getDbEntities('merchant_access_map', ['entity_owner_id' => $partner->getId()])->toArray();
        $referrals = $this->getDbEntities('referrals', ['merchant_id' => $partner->getId()]);

        $this->assertEquals('reseller', $partner->getPartnerType());

        $this->assertEquals([], $this->getDbEntities(
            'merchant_application', ['application_id' => $oauthApp->getId()]
        )->toArray());
        $this->assertEquals([], $this->getDbEntities('partner_config', ['entity_id' => $oauthApp->getId()])->toArray());
        $this->assertEquals([], $this->getDbEntities('partner_config', ['origin_id' => $oauthApp->getId()])->toArray());

        $this->assertCount(1, $this->getDbEntities(
            'merchant_application', ['application_id' => $newReferredAppId]
        )->toArray());
        $this->assertCount(1, $this->getDbEntities('partner_config', ['entity_id' => $newReferredAppId])->toArray());
        $this->assertCount(1, $this->getDbEntities('partner_config', ['origin_id' => $newReferredAppId])->toArray());

        $this->assertCount(1, $accessMaps);
        $this->assertEquals($newReferredAppId, $accessMaps[0]['entity_id']);
        $this->assertEquals(['Ref-' . $partner->getId()], $subMerchant->tagNames());

        $this->assertCount(3, $referrals);
    }

    private function assertMailSentForPPToReseller($partner)
    {
        Mail::assertSent(PartnerTypeSwitchEmail::class, function ($mail) use($partner) {
            $subject = 'Partner account type updated to Reseller Partner type';
            $from = [
                0 => [
                    'name'    => 'Razorpay Partner Program',
                    'address' => 'partnercommunication@razorpay.com',
                ]
            ];
            $to = [
                0 => [
                    'address' => $partner->getEmail(),
                    'name'    => $partner->getName(),
                ]
            ];

            $this->assertSame($subject, $mail->subject);
            $this->assertArraySelectiveEquals($from, $mail->from);
            $this->assertArraySelectiveEquals($to, $mail->to);
            $this->assertSame('emails.mjml.merchant.partner.notify.pure_platform_to_reseller_switch', $mail->view);
            $this->assertEquals('IN', $mail->viewData['country_code']);

            return true;

        });
    }

    public function testMigratePurePlatformToResellerJobSent(): void
    {
        Queue::fake();
        $this->mockAllSplitzTreatment();

        $defaultPartnerId = '1000000000plat';

        $this->createPurePlatFormMerchantAndSubMerchant();
        $this->ba->adminAuth();

        $testData = $this->testData['testMigratePurePlatformToReseller'];
        $this->startTest($testData);

        Queue::assertPushed(
            MigratePurePlatformToResellerPartnerJob::class,
            function ($job) use($defaultPartnerId) {
                $this->assertEquals($defaultPartnerId, $job->getMerchantId());

                return true;
            }
        );
    }

    public function testPartnerEmailUpdate()
    {
        $this->ba->adminAuth();

        $this->fixtures->merchant->createAccount(self::DEFAULT_PARTNER_ID);

        $this->setUpNonPurePlatformPartnerAndSubmerchant(self::DEFAULT_PARTNER_ID);

        $this->fixtures->merchant->edit(
            self::DEFAULT_PARTNER_ID,
            [
                'email' => 'random@gmail.com'
            ]
        );

        $this->startTest();

        $partnerUsers = $this->fixtures->user->getMerchantOwnerUsers(self::DEFAULT_PARTNER_ID);
        $partnerUser = $this->fixtures->user->getUserById($partnerUsers[0]->user_id);
        $this->assertEquals($partnerUser[0]->email, 'partner1@razorpay.com');
    }

    public function testPartnerEmailUpdateWithExistingUser()
    {
        $this->ba->adminAuth();

        $this->fixtures->merchant->createAccount(self::DEFAULT_PARTNER_ID);

        $this->setUpNonPurePlatformPartnerAndSubmerchant(self::DEFAULT_PARTNER_ID);

        $user = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID,
            [
                'email' => 'partner1@razorpay.com'
            ]);

        $this->fixtures->merchant->edit(
            self::DEFAULT_PARTNER_ID,
            [
                'email' => 'random@gmail.com'
            ]
        );

        $this->startTest();

        $partnerUsers = $this->fixtures->user->getMerchantOwnerUsers(self::DEFAULT_PARTNER_ID);
        $partnerUser = $this->fixtures->user->getUserById($partnerUsers[0]->user_id);

        $this->assertEquals($partnerUser[0]->id, $user->getId());
        $this->assertEquals($partnerUser[0]->email, 'partner1@razorpay.com');
    }

    public function testPartnerEmailUpdateWithTeamUser()
    {
        $this->ba->adminAuth();

        $this->fixtures->merchant->createAccount(self::DEFAULT_PARTNER_ID);

        $this->setUpNonPurePlatformPartnerAndSubmerchant(self::DEFAULT_PARTNER_ID);

        $user = $this->fixtures->user->createUserForMerchant(self::DEFAULT_PARTNER_ID,
            [
                'email' => 'partner1@razorpay.com'
            ], Role::FINANCE);

        $this->fixtures->merchant->edit(
            self::DEFAULT_PARTNER_ID,
            [
                'email' => 'random@gmail.com'
            ]
        );

        $this->startTest();

        $partnerUsers = $this->fixtures->user->getMerchantOwnerUsers(self::DEFAULT_PARTNER_ID);
        $partnerUser = $this->fixtures->user->getUserById($partnerUsers[0]->user_id);

        $this->assertEquals($partnerUsers[0]->user_id, $user->getId());
        $this->assertEquals($partnerUser[0]->email, 'partner1@razorpay.com');
    }

    public function testPartnerEmailUpdateWithBankingAndPrimaryTeamUser()
    {
        $this->ba->adminAuth();

        $this->fixtures->merchant->createAccount(self::DEFAULT_PARTNER_ID);

        $this->setUpNonPurePlatformPartnerAndSubmerchant(self::DEFAULT_PARTNER_ID);

        $this->fixtures->user->createUserMerchantMapping( ['user_id' => 'RazorpayUserId', 'merchant_id' => self::DEFAULT_PARTNER_ID, 'role' => Role::OWNER, 'product' => 'banking']);

        $user = $this->fixtures->user->createUserForMerchant(self::DEFAULT_PARTNER_ID,
            [
                'email' => 'partner1@razorpay.com'
            ], Role::FINANCE);

        $this->fixtures->merchant->edit(
            self::DEFAULT_PARTNER_ID,
            [
                'email' => 'random@gmail.com'
            ]
        );

        $this->startTest();

        $partnerUsers = $this->fixtures->user->getMerchantOwnerUsers(self::DEFAULT_PARTNER_ID,);
        $partnerBankingUser = $this->fixtures->user->getMerchantOwnerUsers(self::DEFAULT_PARTNER_ID,'banking');
        $submOwnerUser = $this->fixtures->user->getMerchantOwnerUsers('100submerchant');

        $partnerUser = $this->fixtures->user->getUserById($partnerUsers[0]->user_id);

        $this->assertEquals($partnerUsers[0]->user_id, $user->getId());
        $this->assertEquals($partnerBankingUser[0]->user_id, $user->getId());
        $this->assertEquals($submOwnerUser[0]->user_id, $user->getId());

        $this->assertEquals($partnerUser[0]->email, 'partner1@razorpay.com');
    }

    /**
     * Test partner email update with subm as partner
     * consider this scenario and we are updating the email for user2
     * PartnerId1 -> user1 -> owner
     * SubMid1 -> user1 -> owner
     * SubMid1 -> user2 -> owner
     * subSubMid1 -> user2 -> owner
     * @return void
     */
    public function testPartnerEmailUpdateWithSubmAsPartner()
    {
        $this->ba->adminAuth();

        // create partner and submerchant
        $this->fixtures->merchant->createAccount(self::DEFAULT_PARTNER_ID);

        $this->setUpNonPurePlatformPartnerAndSubmerchant(self::DEFAULT_PARTNER_ID);

        $this->fixtures->user->createUserMerchantMapping( ['user_id' => 'RazorpayUserId', 'merchant_id' => self::DEFAULT_PARTNER_ID, 'role' => Role::OWNER, 'product' => 'banking']);

        $financeTeamUser = $this->fixtures->user->createUserForMerchant('100submerchant',
            [
                'email' => 'subm@razorpay.com'
            ], Role::FINANCE);

        // create owner user for sub merchant
        $user = $this->fixtures->user->createUserForMerchant('100submerchant',
            [
                'email' => 'randomsubm@gmail.com'
            ], Role::OWNER);

        // update sub merchant email
        $this->fixtures->merchant->edit(
            '100submerchant',
            [
                'email' => 'randomsubm@gmail.com',
                'partner_type' => 'aggregtor'
            ]
        );

        // create submerchant 2
        $this->fixtures->merchant->createAccount(self::DEFAULT_SUBMERCHANT_ID_2);

        // create owner user for sub merchant 2
        $this->fixtures->user->createUserMerchantMapping( ['user_id' => $user->getId(), 'merchant_id' => self::DEFAULT_SUBMERCHANT_ID_2, 'role' => Role::OWNER]);

        $this->startTest();

        $partnerOwnerUsers = $this->fixtures->user->getMerchantOwnerUsers(self::DEFAULT_PARTNER_ID,);
        $subm2ownerUser = $this->fixtures->user->getMerchantOwnerUsers(self::DEFAULT_SUBMERCHANT_ID_2);
        $subm1OwnerUsers = $this->fixtures->user->getMerchantOwnerUsers('100submerchant');

        $submUser = $this->fixtures->user->getUserById($subm2ownerUser[0]->user_id);

        $this->assertEquals(count($subm1OwnerUsers), 2);
        $this->assertNotEquals($partnerOwnerUsers[0]->user_id, $subm2ownerUser[0]->user_id);

        $this->assertEquals($submUser[0]->email, 'subm@razorpay.com');
        $this->assertEquals($subm2ownerUser[0]->user_id, $financeTeamUser->getId());
    }

    private function setUpPurePlatformPartnerForSuccesfulPartnerTypeSwitch(array $activeTokens = []): array
    {
        list($oauthApp, $accessMap, $partner) = $this->createPurePlatFormMerchantAndSubMerchant(['id' => 'J00dqRlTeStNzb']);
        $this->fixtures->create('merchant_detail:sane', ['merchant_id' => $partner->getId()]);
        $this->createConfigForPartnerApp(Partner\Constants::DEFAULT_PLATFORM_APP_ID);
        $this->createConfigForPartnerApp(
            Partner\Constants::DEFAULT_PLATFORM_APP_ID,
            Partner\Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID
        );

        $this->ba->adminAuth();
        $partnerId = $partner->getId();

        $newReferredAppId = '8ckeirnw84ifke';
        $this->authServiceMock
            ->expects($this->exactly(3))
            ->method('sendRequest')
            ->withConsecutive(
                [ 'tokens', 'GET', ['merchant_id' => $partnerId] ],
                [
                    'applications', 'POST',
                    [
                        'name' => 'Referred application',
                        'website' => $partner->getWebsite(),
                        'merchant_id' => $partnerId,
                        'type' => 'partner'
                    ]
                ],
                ['applications/'.$oauthApp->getId(), 'PUT', ['merchant_id' => $partnerId]]
            )
            ->willReturnOnConsecutiveCalls(['items' => $activeTokens], $app = ['id'=> $newReferredAppId], []);

        $this->mockStorkSmsCall($partner);
        $this->mockStorkWebhooksCall();

        $referredAppAttributes = [
            'merchant_id' => $partnerId,
            'partner_type'=> 'referred',
            'id' => $newReferredAppId,
            'name' => 'referred'
        ];
        $this->fixtures->merchant->createDummyPartnerApp($referredAppAttributes, false);

        return [$oauthApp, $accessMap, $partner, $newReferredAppId];
    }

    private function mockStorkSmsCall($partner)
    {
        $this->mockGimliURLShortener();
        $this->merchantTestUtil->expectStorkSmsRequest(
            $this->storkMock,
            'Sms.Partnerships.Partner_type_pure_platform_to_reseller_v3',
            $partner->merchantDetail->getContactMobile(),
            [
                'partnerName'         => $partner->getName() ,
                'platformDocsLink'    => "https://rzp.io/i/partner",
                'partnerSupportEmail' => 'partners@razorpay.com'
            ]
        );
    }

    private function mockStorkWebhooksCall()
    {
        $this->storkMock
            ->shouldReceive('request')
            ->times(2)
            ->andReturnUsing(
                function() {
                    $resp              = new Response();
                    $resp->success     = true;
                    $resp->status_code = 200;
                    $resp->body        = json_encode(
                        []
                    );

                    return $resp;
                }
            );
    }

    private function setUpPurePlatformPartnerForFailedPartnerTypeSwitch(array $activeTokens = []): array
    {
        list($oauthApp, $accessMap, $partner) = $this->createPurePlatFormMerchantAndSubMerchant(['id' => 'J00dqRlTeStNzb']);
        $this->fixtures->create('merchant_detail:sane', ['merchant_id' => $partner->getId()]);
        $this->createConfigForPartnerApp(Partner\Constants::DEFAULT_PLATFORM_APP_ID);
        $this->createConfigForPartnerApp(
            Partner\Constants::DEFAULT_PLATFORM_APP_ID,
            Partner\Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID
        );

        $this->ba->adminAuth();
        $partnerId = $partner->getId();

        $this->authServiceMock
            ->expects($this->exactly(1))
            ->method('sendRequest')
            ->withConsecutive(
                [ 'tokens', 'GET', ['merchant_id' => $partnerId] ]
            )
            ->willReturnOnConsecutiveCalls(['items' => $activeTokens]);

        return [$oauthApp, $accessMap, $partner];
    }

    protected function mockGimliURLShortener()
    {
        $gimli = $this->createMock(Gimli::class);
        $gimli->method('expandAndGetMetadata')->willReturn(null);

        $elfin = $this->createMock(ElfinService::class);
        $elfin->method('driver')->willReturn($gimli);
        $elfin->method('shorten')->willReturn(
            "https://rzp.io/i/partner"
        );

        $this->app->instance('elfin', $elfin);
    }

    public function testFetchEntitiesForPartnershipService()
    {
        [$partnerId, $app, $subMerchant] = $this->createResellerPartnerAndSubmerchant();

        $this->fixtures->merchant_detail->edit($partnerId, [
            'business_registered_address' => 'Koramangala',
            'business_registered_state'   => 'KARNATAKA',
            'business_registered_pin'     => 560047,
            'business_dba'                => 'test',
            'business_name'               => 'rzp_test',
            'business_registered_city'    => 'Bangalore',
            'activation_status'           => 'activated',
            'gstin'                       => '29ABCDE1234L1Z1'
        ]);
        $this->fixtures->create('partner_activation', ['merchant_id' => $partnerId,'activation_status' => 'activated']);
        $this->fixtures->create('balance', [
            'type'           => 'commission',
            'merchant_id'    => $partnerId,
            'balance'        => 300000,
            'id'             => "balanceIdTest1"
        ]);

        $this->ba->partnershipServiceAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/internal/partnerships/merchant?ids='.$partnerId.'&expand=merchant,merchant_detail,partner_activation,commission_balance';

        $this->runRequestResponseFlow($testData);
    }

    public function testFetchPartialEntitiesForPartnershipService()
    {
        [$partnerId, $app, $subMerchant] = $this->createResellerPartnerAndSubmerchant();
        $this->createMerchant('partnerMerchId','merch',true);
        $this->fixtures->merchant_detail->edit('partnerMerchId', [
            'gstin'  => '37ABCDE1234L1Z1'
        ]);
        $this->ba->partnershipServiceAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/internal/partnerships/merchant?ids='.$partnerId.',partnerMerchId'.'&expand=merchant,tax_components';

        $this->runRequestResponseFlow($testData);
    }

    public function testAggregatorToResellerBulkUpdate()
    {
        $merchantId = '10000000000000';

        $this->setUpNonPurePlatformPartner();

        $this->fixtures->merchant->edit($merchantId, ['name' => 'et', 'website' => 'http://www.monahan.com/harum-fuga-quae-culpa-quod']);

        $this->ba->privateAuth();

        $this->mockAllSplitzTreatment();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    private function createResellerPartnerAndSubmerchant(string $submerchantId = '101submerchant', string $appId = 'reseller84ifke')
    {
        list($partner, $app) = $this->createPartnerAndApplication(['partner_type' => 'reseller'], ['id' => $appId]);

        $partnerId = $partner->getId();
        $this->fixtures->merchant->edit($partnerId, ['name' => 'et', 'website' => 'http://www.monahan.com/harum-fuga-quae-culpa-quod']);
        $this->createConfigForPartnerApp($app->getId());

        list($subMerchant, $accessMap) = $this->createSubMerchant($partner, $app, ['id' => $submerchantId], ['id' => 'J00dqRlTeStNzb']);
        $this->createConfigForPartnerApp($app->getId(), $subMerchant->getId());
        $this->createMerchantUser($subMerchant->getId());
        $subMerchant->tag('ref-'.$partnerId);

        $this->ba->adminAuth();

        return [$partnerId, $app, $subMerchant];
    }

    private function mockSubmerchantFetchMultipleOptimisedExperiment(): void
    {
        $input = [
            "id"            => self::DEFAULT_MERCHANT_ID,
            "experiment_id" => "KHTp8UvvI3JWnn",
            'request_data'  => json_encode([
                'mid' => self::DEFAULT_MERCHANT_ID,
                'auth_type' => 'private'
            ]),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];
        $this->mockSplitzTreatment($input, $output);
    }

    private function createPartnerMigrationAuditInput(Merchant\Entity $merchant, array $input)
    {
        return [
            'partner_id'       => $merchant->getId(),
            'status'           => "requested",
            'old_partner_type' => $merchant->getPartnerType(),
            'audit_log'        => [
                'actor_id'     => $merchant->primaryowner()->getId(),
                "actor_type"   => $merchant->primaryowner()->getEntityName(),
                'actor_email'  => $merchant->primaryowner()->getEmail()
            ],
            'freshdesk_params' => [
                'phone_no'     => $input['phone_no'],
                'website_url'  => $input['website_url'],
                'description'  => $input['other_info'],
                'name'         => $merchant->getName(),
                'email'        => $merchant->getEmail()
            ]
        ];
    }


    // The following testcase would create a merchant entity for the subM, attach the subM to the partner via referral code
    public function testUserRegisterWithMobileWithReferralCode()
    {
        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:signup_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
                          ->setConstructorArgs([$this->app])
                          ->onlyMethods(['generateOtp'])
                          ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
                           ->willReturn($smsPayload);

        $testData = & $this->testData[__FUNCTION__];

        //Create a dummy partner to fetch primary referral link
        $partnerMerchant = $this->createPartner('aggregator');

        $referralLink = $this->getDbEntity('referrals', ['product' => 'primary']);

        $referralCode = $referralLink['ref_code'];

        $testData['request']['content']['partner_referral_code'] = $referralCode;

        $this->ba->dashboardGuestAppAuth();

        $this->mockAllSplitzTreatment();

        $response = $this->startTest();

        $createdSubM = $this->getDbLastEntity('merchant');

        $accessMap = $this->getDbEntity('merchant_access_map',
                                        ['entity_owner_id' => $partnerMerchant['id'],
                                         'merchant_id' => $createdSubM['id']
                                        ]);
        $this->assertNotNull($accessMap);

        $this->assertEquals($partnerMerchant['id'], $accessMap['entity_owner_id']);
    }

    public function testLinkSubMerchantForPPReferralFlow()
    {
        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:signup_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $this->mockDcsFetchConfiguration();

        $testData = & $this->testData['testUserRegisterWithMobileWithReferralCode'];

        $partnerMerchant = $this->createPartner('pure_platform');

        $partnerId = $partnerMerchant['id'];

        $application = DB::Connection('auth')
                ->table('applications')
                ->orderBy('created_at', 'desc')
                ->first();

        $testData['request']['content']['source_app_id'] = $application->id;

        $testData['request']['content']['oauth_referral'] = true;

        $this->ba->dashboardGuestAppAuth();

        $this->mockAllSplitzTreatment();

        $response = $this->runRequestResponseFlow($testData);

        $createdSubM = $this->getDbLastEntity('merchant');

        $accessMap = $this->getDbEntity('merchant_access_map',
            ['entity_owner_id' => $partnerId,
                'merchant_id' => $createdSubM['id']
            ]);
        $this->assertNotNull($accessMap);

        $this->assertEquals($partnerId, $accessMap['entity_owner_id']);
    }


    // The following testcase would create a merchant for the subM, wouldn't attach the subM to partner as the experiment is disabled
    public function testUserRegisterWithMobileWithReferralCodeWithExperimentDisable()
    {
        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context'    => 'user_id:signup_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
                          ->setConstructorArgs([$this->app])
                          ->onlyMethods(['generateOtp'])
                          ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
                           ->willReturn($smsPayload);

        $testData = &$this->testData['testUserRegisterWithMobileWithReferralCode'];

        $partnerMerchant = $this->createPartner('aggregator');

        $referralLink = $this->getDbEntity('referrals', ['product' => 'primary']);

        $referralCode = $referralLink['ref_code'];

        $testData['request']['content']['partner_referral_code'] = $referralCode;

        $this->ba->dashboardGuestAppAuth();

        $this->mockAllSplitzTreatment([
                                          "response" => [
                                              "variant" => [
                                                  "name" => 'disable',
                                              ]
                                          ]
                                      ]);

        $response = $this->runRequestResponseFlow($testData);

        $createdSubM = $this->getDbLastEntity('merchant');

        $accessMap = $this->getDbEntity('merchant_access_map',
                                        ['entity_owner_id' => $partnerMerchant['id'],
                                         'merchant_id'     => $createdSubM['id']
                                        ]);
        $this->assertNull($accessMap);

    }

    // The test case checks if create signup source method is called for phantom source
    public function testUserRegisterWithMobileThroughPhantomWithAppId()
    {
        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context'    => 'user_id:signup_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
                          ->setConstructorArgs([$this->app])
                          ->onlyMethods(['generateOtp'])
                          ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
                           ->willReturn($smsPayload);
        $this->mockDcsFetchConfiguration();

        $this->mockPartnershipsServiceTreatment([], [], 'createSubMSignupSource');

        $testData = &$this->testData['testUserRegisterWithMobileWithReferralCode'];

        $this->createPartner('aggregator');

        $testData['request']['content']['source_app_id'] = '8ckeirnw84ifke';

        $testData['request']['content']['source'] = 'phantom';

        $this->ba->dashboardGuestAppAuth();

        $this->mockAllSplitzTreatment();

        $this->runRequestResponseFlow($testData);
    }

    public function testFetchOauthApplicationDetailsFromPayment()
    {
        $accessToken = $this->setPurePlatformContext(Mode::TEST);

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPaymentOAuth($payment);

        $this->capturePaymentByOAuth($response['razorpay_payment_id'], $payment['amount'], $accessToken);

        $payment = $this->getLastEntity('payment', true);

        $merchantId = Partner\Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;

        $user = $this->fixtures->user->createUserForMerchant($merchantId);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['payment_id'] = substr($payment['id'],4);

        $this->ba->proxyAuth('rzp_test_' .$merchantId , $user['id']);

        $app = DB::Connection('auth')
            ->table('applications')
            ->orderBy('created_at', 'desc')
            ->first();

        $testData['response']['content']['application']['name'] = $app->name;

        $this->startTest($testData);
    }

    public function testFetchOauthApplicationDetailsFromPaymentForPartnerAuth()
    {
        $payment = $this->getDefaultPaymentArray();

        $partnerId = '100000Razorpay';
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev', [], $partnerId);

        $submerchantId = '10000000000000';

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $submerchantId,
            ]
        );

        $response = $this->doPartnerAuthPayment($payment, $client->getId(), $submerchantId);

        $app = DB::Connection('auth')
            ->table('applications')
            ->orderBy('created_at', 'desc')
            ->first();


        $user = $this->fixtures->user->createUserForMerchant($submerchantId);

        $testData = $this->testData['testFetchOauthApplicationDetailsFromPayment'];

        $testData['request']['content']['payment_id'] = PublicEntity::stripDefaultSign($response['razorpay_payment_id']);

        $this->ba->proxyAuth('rzp_test_' .$submerchantId , $user['id']);

        $response = $this->startTest($testData);

        $this->assertEmpty($response['application']);
    }

    public function testFetchPlatformPartnerConfig()
    {
        $liveMode = $this->app['basicauth']->getLiveConnection();
        $this->createPurePlatformPartnerWithDefaultConfig();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/partner_configs?partner_id=' . self::DEFAULT_MERCHANT_ID;
        $this->ba->adminAuth($liveMode);

        $this->startTest($testData);
    }

    /**
     * tests if partner level config is cascaded to application when partner level config is edited from admin dashboard
     **/
    public function testEditPlatformPartnerConfig()
    {
        $liveMode = $this->app['basicauth']->getLiveConnection();
        $partnerConfig =  $this->createPurePlatformPartnerWithDefaultConfig();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/partner_configs/' . $partnerConfig[0]['id'];
        $this->ba->adminAuth($liveMode);

        $this->startTest($testData);

        $partnerConfig = $this->getDbEntities('partner_config');

        $partnerConfig->toArray();

        $this->assertEquals($partnerConfig[0]['commissions_enabled'], $partnerConfig[1]['commissions_enabled']);

    }

    /**
     * tests if partner level overriden subm config is cascaded to application when subm authorizes a new oauth app
     **/
    public function testPartnerConfigCreateDuringOAuthAppMerchantMap()
    {
        $application = $this->createOAuthApplication(["partner_type" => "pure_platform"]);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest('10000000000000');

        $testDataToReplace = [
            'request'  => [
                'content' => [
                    'application_id' => $application->getId(),
                ]
            ],
            'response' => [
                'content'     => [
                    'entity_id'   => $application->getId(),
                ],
            ],
        ];

        $this->createConfigForPlatformPartner('10000000000000','10000000000000');
        $this->startTest($testDataToReplace);

        $liveMapping = $this->getLastEntity(
            'merchant_access_map',
            true,
            'live');

        $testMapping = $this->getLastEntity(
            'merchant_access_map',
            true,
            'test');

        $this->assertEquals($application->getId(), $liveMapping['entity_id']);

        $this->assertEquals($application->getId(), $testMapping['entity_id']);

        $partnerconfig = $this->getDbLastEntity('partner_config');

        $this->assertNotEmpty($partnerconfig);
        $this->assertEquals('merchant', $partnerconfig['entity_type']);
        $this->assertEquals('application', $partnerconfig['origin_type']);
        $this->assertEquals('10000000000000', $partnerconfig['entity_id']);
    }

    /**
     * tests if partner level config is cascaded to application when a new application is created
    **/
    public function testCascadePartnerConfigDuringCreateApplication()
    {
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $createParams = [
            'name'     => 'fdsfsd',
            'website'  => 'https://www.example.com',
            'logo_url' => '/logo/app_logo.png',
        ];

        $requestParams = array_merge($requestParams, $createParams);

        $res = $this->setAuthServiceMockDetail(
            'applications',
            'POST',
            $requestParams,
            1,
            ['id' => '8ckeirnw84ifke']);

        $this->createPurePlatformPartnerWithDefaultConfig();

        $application = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'pure_platform']);

        $this->ba->proxyAuth();

        $this->startTest();

        $partnerConfig = $this->getDbEntities('partner_config');

        $partnerConfig->toArray();

        $this->assertEquals($partnerConfig[2]['entity_id'], '8ckeirnw84ifke');

        $this->assertEquals($partnerConfig[2]['entity_type'], 'application');
    }

    public function testRemoveDashboardAccessToPartnerSubMerchant()
    {
        $this->fixtures->merchant->createAccount(self::DEFAULT_PARTNER_ID);

        $this->setUpNonPurePlatformPartnerAndSubmerchant(self::DEFAULT_PARTNER_ID,);

        $this->fixtures->merchant->edit(
            '100submerchant',
            [
                'email' => 'random.subm@gmail.com',
            ]
        );
        $this->fixtures->merchant->edit(
            self::DEFAULT_PARTNER_ID,
            [
                'email' => 'random@gmail.com',
            ]
        );

        $this->fixtures->user->createUserForMerchantONLiveAndTest('100submerchant', ['id'=> 'RazorpayUser12', 'email'=> 'random.subm@gmail.com'], Role::OWNER);

        $this->fixtures->user->createUserMerchantMapping( ['user_id' => 'RazorpayUser12', 'merchant_id' => self::DEFAULT_PARTNER_ID, 'role' => Role::OWNER, 'product' => 'primary']);

        $this->fixtures->user->createUserMerchantMapping( ['user_id' => 'RazorpayUserId', 'merchant_id' => '100submerchant', 'role' => Role::VIEW_ONLY, 'product' => 'banking']);

        $this->ba->privateAuth();

        $this->startTest();

        $partnerUsers = $this->fixtures->user->getMerchantOwnerUsers(self::DEFAULT_PARTNER_ID,);
        $submOwnerUser = $this->fixtures->user->getMerchantOwnerUsers('100submerchant');
        $submNonPrimaryUsers = $this->fixtures->user->getMerchantUserMapping('100submerchant','RazorpayUserId','banking');

        $this->assertCount(2,$partnerUsers);
        $this->assertCount(1,$submOwnerUser);
        $this->assertCount(0,$submNonPrimaryUsers);

        $this->assertEquals($submOwnerUser[0]->user_id, 'RazorpayUser12');
    }


    private function createPurePlatformPartnerWithDefaultConfig()
    {
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData['testCreatePurePlatformPlatformRequest'];

        // Create a merchant request
        $merchantRequest = $this->createMerchantRequest(
            self::ACTIVATION,
            true,
            Merchant\Constants::PURE_PLATFORM);

        $merchantRequestId = $merchantRequest->getPublicId();
        $this->mockAllSplitzTreatment();

        $attributes = array_merge([], ['merchant_id' => self::DEFAULT_MERCHANT_ID, 'partner_type' => Merchant\Constants::PURE_PLATFORM, 'type' => 'partner']);

        $application = $this->createOAuthApplication($attributes);

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->runRequestResponseFlow($testData);

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, $liveMode);

        $this->assertTrue($merchant->isPartner());

        // validate if default partner config created for pure platform partner
        $partnerConfig = $this->getDbEntities('partner_config');

        $this->assertNotEmpty($partnerConfig);
        $this->assertEquals("merchant", $partnerConfig[0]['entity_type']);
        $this->assertEquals(self::DEFAULT_MERCHANT_ID, $partnerConfig[0]['entity_id']);
        $this->assertEquals("application", $partnerConfig[1]['entity_type']);
        $this->assertEquals($application->getId(), $partnerConfig[1]['entity_id']);

        return $partnerConfig;
    }

    public function mockDcsFetchConfiguration() {

        $dcsConfigService = $this->getMockBuilder( DcsConfigService::class)
                                 ->setConstructorArgs([$this->app])
                                 ->getMock();

        $this->app->instance('dcs_config_service', $dcsConfigService);

        $this->app['dcs_config_service']
             ->method('fetchConfiguration')
             ->willReturn([
                 "disable_captcha" => true
             ]);
    }
}
