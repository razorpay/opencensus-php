<?php

namespace RZP\Tests\Functional\Merchant\Partner;

use Config;
use DB;
use Mail;
use Event;
use Queue;
use Carbon\Carbon;
use RZP\Constants\Country;
use RZP\Constants\Mode;
use App\User\Constants;
use RZP\Constants\Timezone;
use RZP\Services\Elfin\Impl\Gimli;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\MocksPartnershipsService;
use Neves\Events\TransactionalClosureEvent;
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Jobs\MigrateResellerToPurePlatformPartnerJob;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Batch;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Error\PublicErrorCode;
use RZP\Models\Partner\NotifyPartnerAboutPartnerTypeSwitch;
use RZP\Mail\Merchant\ResellerToPurePlatformPartnerSwitchEmail;
use RZP\Models\Merchant\Consent\Details\Repository as MerchantConsentDetailsRepo;
use RZP\Models\Pricing\DefaultPlan;
use RZP\Models\User\BankingRole;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\Metric as MerchantMetric;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\User\Role;
use RZP\Services\RazorXClient;
use Razorpay\OAuth\Application;
use Illuminate\Http\UploadedFile;
use RZP\Models\Merchant\Request;
use RZP\Models\Settings\Accessor;
use RZP\Tests\Functional\Helpers\CreateLegalDocumentsTrait;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Models\Merchant\AccessMap;
use RZP\Services\Mock\Settlements\Api;
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
use RZP\Tests\Functional\Helpers\Salesforce\SalesforceTrait;
use RZP\Mail\Merchant\RazorpayX\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateForX;
use RZP\Mail\Merchant\Capital\LineOfCredit\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateForLOC;

class PartnerTest extends OAuthTestCase
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
    const DEFAULT_SUBMERCHANT_ID        = '10000000000009';
    const DEFAULT_SUBMERCHANT_ID_2      = '10000000000010';
    const RZP_ORG                       = '100000razorpay';
    const CURLEC_DEFAULT_MERCHANT_ID    = '10000121212121';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PartnerTestData.php';

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

    public function testSubmerchantKYCByPartnerWithInvalidSubmerchant()
    {
        $this->createResellerPartnerSubmerchant();

        $this->fixtures->merchant->addFeatures(['partner_sub_kyc_access'], self::DEFAULT_MERCHANT_ID);

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

    public function testPartnerSubmerchantDeLinkViaBatchWithNssExpEnabled()
    {
        $this->testPartnerSubmerchantLinkViaBatch();

        $this->mockSplitzEvaluation();

        $this->app->singleton('settlements_api', function($app)
        {
            $implementation = Api::class ;

            return new $implementation($app, 'settle_to_enabled');
        });

        $this->fixtures->merchant->addFeatures(['new_settlement_service'], self::DEFAULT_SUBMERCHANT_ID);

        $expectedDimensions = [
            'partner_id'     => '10000000000000',
        ];

        $metricCaptured = false;

        $metricsMock = $this->createMetricsMock();

        $this->mockAndCaptureCountMetric(MerchantMetric::AGGREGATE_SETTLEMENT_UNLINKING_REQUEST_SUCCESS,
            $metricsMock, $metricCaptured, $expectedDimensions);

        $testData = $this->testData['testPartnerSubmerchantDeLinkViaBatch'];

        $this->startTest($testData);

        $this->assertTrue($metricCaptured);

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
                                                                            'entity_owner_id' => '10000000000000'], 'live');
        $this->assertNull($accessMapEntity);

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
                                                                            'entity_owner_id' => '10000000000000'], 'test');
        $this->assertNull($accessMapEntity);
    }

    public function testPartnerSubmerchantDeLinkFailureViaBatchWithNssExpEnabled()
    {
        $this->testPartnerSubmerchantLinkViaBatch();

        $this->mockSplitzEvaluation();

        $this->app->singleton('settlements_api', function($app)
        {
            $implementation = Api::class ;

            return new $implementation($app, 'failure');
        });

        $this->fixtures->merchant->addFeatures(['new_settlement_service'], self::DEFAULT_SUBMERCHANT_ID);

        $testData = $this->testData['testPartnerSubmerchantDeLinkViaBatch'];

        $this->startTest($testData);

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
                                                                            'entity_owner_id' => '10000000000000'], 'live');
        $this->assertNotNull($accessMapEntity);

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
                                                                            'entity_owner_id' => '10000000000000'], 'test');
        $this->assertNotNull($accessMapEntity);
    }

    public function testDeLinkSubMWithMultiplePartnersViaBatchWithNssExpEnabled()
    {
        $partnerId = '10000000000003';

        $this->createPartnerAndLinkWithSubmerchant($partnerId, '10000000000009');

        $this->mockSplitzEvaluation($partnerId);

        $this->app->singleton('settlements_api', function($app)
        {
            $implementation = Api::class ;

            return new $implementation($app, 'settle_to_enabled');
        });

        $this->fixtures->merchant->addFeatures(['new_settlement_service'], self::DEFAULT_SUBMERCHANT_ID);

        $expectedDimensions = [
            'partner_id'     => $partnerId,
        ];

        $metricCaptured = false;

        $metricsMock = $this->createMetricsMock();

        $this->mockAndCaptureCountMetric(MerchantMetric::AGGREGATE_SETTLEMENT_UNLINKING_REQUEST_SUCCESS,
            $metricsMock, $metricCaptured, $expectedDimensions);

        $testData = $this->testData['testPartnerSubmerchantDeLinkViaBatch'];

        $testData['request']['content'][0]['partner_id'] = $partnerId;
        $testData['response']['content']['items'][0]['partner_id']= $partnerId;

        $this->startTest($testData);

        $this->assertFalse($metricCaptured);

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
            'entity_owner_id' => '10000000000003'], 'live');
        $this->assertNull($accessMapEntity);

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
            'entity_owner_id' => '10000000000003'], 'test');
        $this->assertNull($accessMapEntity);
    }

    /**
     * This testcase validates:
     * 1. Add NSS feature on default sub-merchant.
     * 2. Link default sub-merchant with default partner and agg. settlement is enabled for the partner.
     * 3. Verify merchant_access_map DB entity is created.
     * 4. Create another partner and link it with the default sub-m.
     * 5. Sub-merchant is removed from agg. settlement and is verified by the metrics captured.
     * 5. Verify merchant_access_map DB entity is created.
     */
    public function testLinkMultiplePartnersAggregateSubMerchant()
    {
        $this->mockSplitzEvaluation();
        $this->mockSplitzEvaluation(10000000000003);

        $this->app->singleton('settlements_api', function($app)
        {
            $implementation = Api::class ;

            return new $implementation($app, 'settle_to_enabled');
        });

        $this->fixtures->merchant->addFeatures(['new_settlement_service'], self::DEFAULT_SUBMERCHANT_ID);

        $this->testPartnerSubmerchantLinkViaBatch();

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
            'entity_owner_id' => '10000000000000'], 'live');

        $this->assertNotNull($accessMapEntity);

        $metricCaptured = false;

        $metricsMock = $this->createMetricsMock();

        $partnerId = '10000000000003';

        $expectedDimensions = [
            'partner_id'     => $partnerId
        ];

        $this->mockAndCaptureCountMetric(MerchantMetric::AGG_SETTLEMENT_SUBM_MULTIPLE_PARTNER_LINK_REQUEST_SUCCESS_TOTAL,
            $metricsMock, $metricCaptured, $expectedDimensions);

        $this->createPartnerAndLinkWithSubmerchant($partnerId, '10000000000009');

        $this->assertTrue($metricCaptured);

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
            'entity_owner_id' => '10000000000003'], 'live');

        $this->assertNotNull($accessMapEntity);
    }

    /**
     * This testcase validates:
     * 1. Add NSS feature on default sub-merchant.
     * 2. Link default sub-merchant with default partner.
     * 3. Verify merchant_access_map DB entity is created.
     * 4. Create another partner and link it with the default sub-m.
     * 5. Verify merchant_access_map DB entity is created and metric related to agg. settlement is not captured.
     */
    public function testLinkMultiplePartnersForSubMerchant()
    {
        $this->mockSplitzEvaluation();
        $this->mockSplitzEvaluation(10000000000003);

        $this->fixtures->merchant->addFeatures(['new_settlement_service'], self::DEFAULT_SUBMERCHANT_ID);

        $this->testPartnerSubmerchantLinkViaBatch();

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
            'entity_owner_id' => '10000000000000'], 'live');

        $this->assertNotNull($accessMapEntity);

        $metricCaptured = false;

        $metricsMock = $this->createMetricsMock();

        $partnerId = '10000000000003';

        $expectedDimensions = [
            'partner_id'     => $partnerId
        ];

        $this->mockAndCaptureCountMetric(MerchantMetric::AGG_SETTLEMENT_SUBM_MULTIPLE_PARTNER_LINK_REQUEST_SUCCESS_TOTAL,
            $metricsMock, $metricCaptured, $expectedDimensions);

        $this->createPartnerAndLinkWithSubmerchant($partnerId, '10000000000009');

        $this->assertFalse($metricCaptured);

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
            'entity_owner_id' => '10000000000003'], 'live');

        $this->assertNotNull($accessMapEntity);
    }

    /**
     * This testcase validates:
     * 1. Add NSS feature on default sub-merchant.
     * 2. Sub-merchant on agg. settlement with another merchant.
     * 3. Create a partner and link it with the default sub-m.
     * 4. Metric related to removal from agg. settlement is not captured.
     * 5. Verify merchant_access_map DB entity is created.
     */
    public function testLinkPartnersAggregateSubMerchant()
    {
        $this->mockSplitzEvaluation(10000000000003);

        $this->app->singleton('settlements_api', function($app)
        {
            $implementation = Api::class ;

            return new $implementation($app, 'settle_to_enabled');
        });

        $this->fixtures->merchant->addFeatures(['new_settlement_service'], self::DEFAULT_SUBMERCHANT_ID);

        $metricCaptured = false;

        $metricsMock = $this->createMetricsMock();

        $partnerId = '10000000000003';

        $expectedDimensions = [
            'partner_id'     => $partnerId
        ];

        $this->mockAndCaptureCountMetric(MerchantMetric::AGG_SETTLEMENT_SUBM_MULTIPLE_PARTNER_LINK_REQUEST_SUCCESS_TOTAL,
            $metricsMock, $metricCaptured, $expectedDimensions);

        $this->createPartnerAndLinkWithSubmerchant($partnerId, '10000000000009');

        $this->assertFalse($metricCaptured);

        $accessMapEntity = $this->getDbEntity('merchant_access_map', ['merchant_id'     => self::DEFAULT_SUBMERCHANT_ID,
            'entity_owner_id' => '10000000000003'], 'live');

        $this->assertNotNull($accessMapEntity);
    }

    protected function createPartnerAndLinkWithSubmerchant(string $partnerId, string $submerchantId)
    {
        $partnerType = 'reseller';

        $this->fixtures->merchant->create(['id' => $partnerId]);

        $this->allowAdminToAccessMerchant($partnerId);

        $this->fixtures->merchant->edit($partnerId, ['partner_type' => $partnerType]);

        $this->fixtures->user->createUserForMerchant($partnerId);

        $defaults = [
            'id'          => '8ckeirnw84ifle',
            'merchant_id' => '10000000000003',
            'name'        => 'Internal',
            'website'     => 'https://www.razorpay.com',
            'logo_url'    => '/logo/app_logo.png',
            'category'    => null,
            'type'        => 'partner',
            'partner_type' => 'reseller'
        ];

        $this->createOAuthApplication( $defaults, $partnerId);

        $this->ba->batchAppAuth();

        $testData = $this->testData['testPartnerSubmerchantLinkViaBatch'];

        $testData['request']['content'][0]['partner_id'] = $partnerId;
        $testData['response']['content']['items'][0]['partner_id']= $partnerId;

        $this->startTest($testData);
    }

    private function mockSplitzEvaluation(string $id = '10000000000000')
    {
        $input = [
            "experiment_id" => "JmNwFyivyRzcg3",
            "id" => $id,
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

    public function testRemovePartnerToPartnerAccessMap()
    {
        $this->allowAdminToAccessPartnerMerchant();

        $partnerUser = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID)->primaryOwner();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'aggregator']);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator']);

        $accessMap = $this->getAccessMapArray('application', $app->getId(), self::DEFAULT_MERCHANT_ID, self::DEFAULT_MERCHANT_ID);

        $this->fixtures->create('merchant_access_map', $accessMap);

        $this->ba->adminAuth();

        $mapping = DB::table('merchant_users')->where('merchant_id', '=', self::DEFAULT_MERCHANT_ID)
            ->where('user_id', '=', $partnerUser->getId())
            ->get();

        $this->assertNotEmpty($mapping);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest(self::DEFAULT_MERCHANT_ID);

        $testData = $this->testData[__FUNCTION__];

        $response = $this->sendRequest($testData['request']);

        $response->assertStatus(204);

        $partner = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID);

        $this->assertEquals(null, $partner->getReferrer());

        $submerchantOwners = $partner->owners()->get()->toArrayPublic();

        $this->assertEquals(1, $submerchantOwners['count']);

        $mapping = DB::table('merchant_users')->where('merchant_id', '=', self::DEFAULT_MERCHANT_ID)
            ->where('user_id', '=', $partnerUser->getId())
            ->get();

        $this->assertNotEmpty($mapping);
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

    public function testFetchBankingAccountEntitiesForPartnerSubmerchantsWithInvalidRole()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $mode = Mode::TEST;

        $this->allowAdminToAccessPartnerMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' =>  Merchant\Constants::BANK_CA_ONBOARDING_PARTNER]);

        $partnerUser2 = $this->fixtures->user->createBankingUserForMerchant(self::DEFAULT_MERCHANT_ID, [], 'admin');

        $this->createSubmerchantAndUser();

        $submerchantId = '10000000000011';

        $this->allowAdminToAccessMerchant($submerchantId);

        $this->fixtures->user->createUserForMerchant($submerchantId);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' =>  Merchant\Constants::BANK_CA_ONBOARDING_PARTNER]);

        $appId = $app->getId();

        // Link new submerchants to the partner account
        $accessMap = $this->getAccessMapArray('application', $appId, self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);

        $this->fixtures->on($mode)->create('merchant_access_map',$accessMap);

        $accessMap = $this->getAccessMapArray('application', $appId, $submerchantId, self::DEFAULT_MERCHANT_ID);

        $this->fixtures->on($mode)->create('merchant_access_map',$accessMap);

        $this->app['config']->set('applications.banking_account.mock', true);

        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', self::DEFAULT_SUBMERCHANT_ID, $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $data = [
            \RZP\Models\BankingAccount\Entity::PINCODE => '560030',
            \RZP\Models\BankingAccount\Entity::CHANNEL => 'rbl',
            'activation_detail' => [
                \RZP\Models\BankingAccount\Activation\Detail\Entity::BUSINESS_CATEGORY => 'partnership',
                \RZP\Models\BankingAccount\Activation\Detail\Entity::SALES_TEAM        => 'self_serve'
            ]
        ];

        $feature = $this->fixtures->on('live')->create('feature', [
            'entity_id'   => self::DEFAULT_MERCHANT_ID,
            'name'        => Feature\Constants::RBL_BANK_LMS_DASHBOARD,
            'entity_type' => 'merchant',
        ]);

        $request = [
            'method'  => 'post',
            'url'     => '/banking_accounts_dashboard',
            'content' => $data
        ];

        Mail::fake();

        $response = $this->makeRequestAndGetContent($request);

        $this->ba->addXBankLMSOriginHeader();

        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID, $partnerUser2->getId());

        $this->startTest();
    }

    public function testFetchBankingAccountEntitiesForPartnerSubmerchantsWithRole()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $mode = Mode::TEST;

        $this->allowAdminToAccessPartnerMerchant();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => Merchant\Constants::BANK_CA_ONBOARDING_PARTNER]);

        $partnerUser = $this->fixtures->user->createBankingUserForMerchant(self::DEFAULT_MERCHANT_ID, [], BankingRole::BANK_MID_OFFICE_POC);

        $this->createSubmerchantAndUser();

        $submerchantId = '10000000000011';

        $this->allowAdminToAccessMerchant($submerchantId);

        $this->fixtures->user->createUserForMerchant($submerchantId);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' =>  Merchant\Constants::BANK_CA_ONBOARDING_PARTNER]);

        $appId = $app->getId();

        // Link new submerchants to the partner account
        $accessMap = $this->getAccessMapArray('application', $appId, self::DEFAULT_SUBMERCHANT_ID, self::DEFAULT_MERCHANT_ID);

        $this->fixtures->on($mode)->create('merchant_access_map',$accessMap);

        $accessMap = $this->getAccessMapArray('application', $appId, $submerchantId, self::DEFAULT_MERCHANT_ID);

        $this->fixtures->on($mode)->create('merchant_access_map',$accessMap);

        $this->app['config']->set('applications.banking_account.mock', true);

        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', self::DEFAULT_SUBMERCHANT_ID, $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $data = [
            \RZP\Models\BankingAccount\Entity::PINCODE => '560030',
            \RZP\Models\BankingAccount\Entity::CHANNEL => 'rbl',
            'activation_detail' => [
                \RZP\Models\BankingAccount\Activation\Detail\Entity::BUSINESS_CATEGORY => 'partnership',
                \RZP\Models\BankingAccount\Activation\Detail\Entity::SALES_TEAM        => 'self_serve'
            ]
        ];

        $request = [
            'method'  => 'post',
            'url'     => '/banking_accounts_dashboard',
            'content' => $data
        ];

        Mail::fake();

        $this->makeRequestAndGetContent($request);

        $this->ba->proxyAuth('rzp_test_' . $submerchantId);

        $this->ba->addXOriginHeader();

        $this->makeRequestAndGetContent($request);

        $feature = $this->fixtures->on('live')->create('feature', [
            'entity_id'   => self::DEFAULT_MERCHANT_ID,
            'name'        => Feature\Constants::RBL_BANK_LMS_DASHBOARD,
            'entity_type' => 'merchant',
        ]);

        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID, $partnerUser->getId());

        $this->ba->addXBankLMSOriginHeader();

        $this->ba->addXBankLMSOriginHeader();

        $this->startTest();
    }

    public function testFetchBankingAccountEntitiesForPartnerSubmerchants()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->createPartnerAndAddMultipleSubmerchants();

        $this->fixtures->on('live')->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => Merchant\Constants::BANK_CA_ONBOARDING_PARTNER]);

        $this->app['config']->set('applications.banking_account.mock', true);

        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', self::DEFAULT_SUBMERCHANT_ID, $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $data = [
            \RZP\Models\BankingAccount\Entity::PINCODE => '560030',
            \RZP\Models\BankingAccount\Entity::CHANNEL => 'rbl',
            'activation_detail' => [
                \RZP\Models\BankingAccount\Activation\Detail\Entity::BUSINESS_CATEGORY => 'partnership',
                \RZP\Models\BankingAccount\Activation\Detail\Entity::SALES_TEAM        => 'self_serve'
            ]
        ];

        $request = [
            'method'  => 'post',
            'url'     => '/banking_accounts_dashboard',
            'content' => $data
        ];

        Mail::fake();

        $response = $this->makeRequestAndGetContent($request);

        $this->ba->proxyAuth();

        $this->ba->addXBankLMSOriginHeader();

        $feature = $this->fixtures->on('live')->create('feature', [
            'entity_id'   => self::DEFAULT_MERCHANT_ID,
            'name'        => Feature\Constants::RBL_BANK_LMS_DASHBOARD,
            'entity_type' => 'merchant',
        ]);

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

        $app = Application\Entity::factory()->create([
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

    public function testSendSubmerchantPasswordResetLinkWhenSubMerchantUserDoesNotExistForCapitalProduct()
    {
        Mail::fake();

        $this->ba->proxyAuth();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, [
            'partner_type' => 'aggregator'
        ]);

        $this->fixtures->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, [
            'email' => 'testing@example.com',
        ]);

        $app = Application\Entity::factory()->create([
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

        Mail::assertQueued(CreateSubMerchantAffiliateForLOC::class, 1);

        Mail::assertQueued(CreateSubMerchantAffiliateForLOC::class, function($mail) {

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

        $app = Application\Entity::factory()->create([
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

        $app = Application\Entity::factory()->create([
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

    public function testSendSubmerchantPasswordResetLinkWithPrimaryProduct()
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

        $app = Application\Entity::factory()->create([
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

    public function testSendSubmerchantPasswordResetLinkWithBankingProduct()
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
            'product'     => 'banking',
        ]);

        $this->ba->proxyAuth('rzp_test_' . $partnerMerchantId, User::MERCHANT_USER_ID);

        $app = Application\Entity::factory()->create([
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

        Mail::assertQueued(CreateSubMerchantAffiliateForX::class, 1);

        Mail::assertQueued(CreateSubMerchantAffiliateForX::class, function($mail) {

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('org', $viewData);
            $this->assertArrayHasKey('token', $viewData);
            $this->assertArrayHasKey('merchant', $viewData);
            $this->assertArrayHasKey('subMerchant', $viewData);
            $this->assertEquals(self::DEFAULT_SUBMERCHANT_ID, $viewData['subMerchant']['id']);

            return true;
        });
    }

    public function testSendSubmerchantPasswordResetLinkWithCapitalProduct()
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
            'product'     => 'banking',
        ]);

        $this->ba->proxyAuth('rzp_test_' . $partnerMerchantId, User::MERCHANT_USER_ID);

        $app = Application\Entity::factory()->create([
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

        Mail::assertQueued(CreateSubMerchantAffiliateForLOC::class, 1);

        Mail::assertQueued(CreateSubMerchantAffiliateForLOC::class, function($mail) {

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('org', $viewData);
            $this->assertArrayHasKey('token', $viewData);
            $this->assertArrayHasKey('merchant', $viewData);
            $this->assertArrayHasKey('subMerchant', $viewData);
            $this->assertEquals(self::DEFAULT_SUBMERCHANT_ID, $viewData['subMerchant']['id']);

            return true;
        });
    }

    public function testSendSubmerchantPasswordResetLinkWithInvalidProduct()
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
            'product'     => 'banking',
        ]);

        $this->ba->proxyAuth('rzp_test_' . $partnerMerchantId, User::MERCHANT_USER_ID);

        $app = Application\Entity::factory()->create([
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
    }

    public function testUpdateActivatedCurlecPartnerTypeAsResellerUsingProxyAuth()
    {
        Mail::fake();

        $org = $this->fixtures->create('org:curlec_org');

        $merchantAttributes = [
            'id' => self::CURLEC_DEFAULT_MERCHANT_ID,
            'live' => 1,
            'activated_at' => Carbon::now()->subDays(2)->getTimestamp(),
            'org_id' => $org->getId()
        ];

        $merchant = $this->fixtures->create('merchant', $merchantAttributes);

        $app = ['id'=>'8ckeirnw84ifke'];

        $commissionPricingPlan = [
            'plan_id' => 'LFbIN2OyJaNzUO',
            'plan_name' => 'testPPMYCommissionPlan',
            'feature' => 'payment',
            'type' => 'pricing',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 170,
            'fixed_rate' => 0,
            'org_id'    => $org->getId(),
        ];

        $subMerchantPricingPlan = [
            'plan_id' => 'LFbrOUOTRSyAqq',
            'plan_name' => 'testSubMPricingPlan',
            'feature' => 'payment',
            'type' => 'pricing',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 250,
            'fixed_rate' => 0,
            'org_id'    => $org->getId(),
        ];

        $this->fixtures->create('pricing', $commissionPricingPlan);

        $this->fixtures->create('pricing', $subMerchantPricingPlan);

        $this->fixtures->merchant->edit( self::CURLEC_DEFAULT_MERCHANT_ID, [
            'org_id'    => $org->getId(),
            'country_code'=> 'MY'
        ]);


        $requestParams1 = [
            'merchant_id' => $merchant->getId(),
            'name'     => $merchant->getName(),
            'website'  => $merchant->getWebsite() ?: 'https://www.curlec.com',
            'type'     => self::PARTNER,
        ];

        $this->authServiceMock
            ->expects($this->exactly(1))
            ->method('sendRequest')
            ->with('applications', 'POST', $requestParams1)
            ->will($this->returnCallback(
                function($route, $method, $params) {
                    return ['id'=>'8ckeirnw84ifke'];
                }
            ));

        $this->fixtures->merchant->createDummyCurlecPartnerApp(['partner_type' => 'reseller']);

        $now = Carbon::now()->getTimestamp();

        $this->fixtures->merchant->edit(self::CURLEC_DEFAULT_MERCHANT_ID,
            [
                'activated'    => true,
                'activated_at' => $now
            ]);

        $this->fixtures->create('merchant_detail:sane',
            [
                'merchant_id'       => self::CURLEC_DEFAULT_MERCHANT_ID,
                'activation_status' => 'activated'
            ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant(
            self::CURLEC_DEFAULT_MERCHANT_ID, [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_' .   self::CURLEC_DEFAULT_MERCHANT_ID, $merchantUser['id']);

        $this->startTest();

        //check if referral links are created for the partner
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'banking', Mode::TEST));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'primary', Mode::TEST));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'banking', Mode::LIVE));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'primary', Mode::LIVE));

        $expectedPartner = $this->getDbEntityById('merchant', self::CURLEC_DEFAULT_MERCHANT_ID, 'live');

        $expectedPartnerConfig = $this->getDbEntity('partner_config');

        $this->assertEquals(
            DefaultPlan::DEFAULT_PARTNERS_PRICING_PLANS['MY']['dev'][DefaultPlan::PARTNER_COMMISSION_PLAN_ID_KEY],
            $expectedPartnerConfig['implicit_plan_id'], "commission plan_id matched"
        );
        $this->assertEquals(
            DefaultPlan::DEFAULT_PARTNERS_PRICING_PLANS['MY']['dev'][DefaultPlan::SUBMERCHANT_PRICING_OF_ONBOARDED_PARTNERS_KEY],
            $expectedPartnerConfig['default_plan_id'], "submerchants plan_id matched"
        );

        $merchantApplications = (new MerchantApplications\Repository())->fetchMerchantApplication(self::CURLEC_DEFAULT_MERCHANT_ID, Merchant\Constants::MERCHANT_ID);

        $applicationTypes = $merchantApplications->pluck(Entity::TYPE)->toArray();

        $applicationType = $applicationTypes[0];

        $this->assertEquals($applicationType, MerchantApplications\Entity::REFERRED);

        $this->assertTrue($expectedPartner->isResellerPartner());

        Mail::assertQueued(PartnerOnBoarded::class);
    }



    public function testUpdateActivatedCurlecPartnerTypeAsAggregatorUsingProxyAuth()
    {
        Mail::fake();

        $org = $this->fixtures->create('org:curlec_org');

        $merchantAttributes = [
            'id' => self::CURLEC_DEFAULT_MERCHANT_ID,
            'live' => 1,
            'activated_at' => Carbon::now()->subDays(2)->getTimestamp(),
            'org_id' => $org->getId()
        ];

        $merchant = $this->fixtures->create('merchant', $merchantAttributes);

        $commissionPricingPlan = [
            'plan_id' => 'LFbIN2OyJaNzUO',
            'plan_name' => 'testPPMYCommissionPlan',
            'feature' => 'payment',
            'type' => 'pricing',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 170,
            'fixed_rate' => 0,
            'org_id'    => $org->getId(),
        ];

        $subMerchantPricingPlan = [
            'plan_id' => 'LFbrOUOTRSyAqq',
            'plan_name' => 'testSubMPricingPlan',
            'feature' => 'payment',
            'type' => 'pricing',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 250,
            'fixed_rate' => 0,
            'org_id'    => $org->getId(),
        ];

        $this->fixtures->create('pricing', $commissionPricingPlan);

        $this->fixtures->create('pricing', $subMerchantPricingPlan);

        $this->fixtures->merchant->edit( self::CURLEC_DEFAULT_MERCHANT_ID, [
            'org_id'    => $org->getId(),
            'country_code'=> 'MY'
        ]);

        $now = Carbon::now()->getTimestamp();

        $this->fixtures->merchant->edit(self::CURLEC_DEFAULT_MERCHANT_ID,
            [
                'activated'    => true,
                'activated_at' => $now
            ]);

        $this->fixtures->create('merchant_detail:sane',
            [
                'merchant_id'       => self::CURLEC_DEFAULT_MERCHANT_ID,
                'activation_status' => 'activated'
            ]);

        $requestParams1 = [
            'merchant_id' => $merchant->getId(),
            'name'     => $merchant->getName(),
            'website'  => $merchant->getWebsite() ?: 'https://www.curlec.com',
            'type'     => self::PARTNER,
        ];

        $requestParams2 = [
            'merchant_id' => $merchant->getId(),
            'name'     => Merchant\Entity::REFERRED_APPLICATION,
            'website'  => $merchant->getWebsite() ?: 'https://www.curlec.com',
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

        $this->fixtures->merchant->createDummyCurlecPartnerApp(['partner_type' => 'aggregator'], false);

        $this->fixtures->merchant->createDummyReferredAppForManaged(['partner_type' => 'aggregator'], false);

        $merchantUser = $this->fixtures->user->createUserForMerchant(
            self::CURLEC_DEFAULT_MERCHANT_ID, [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_' .   self::CURLEC_DEFAULT_MERCHANT_ID, $merchantUser['id']);

        $this->startTest();

        //check if referral links are created for the partner
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'banking', Mode::TEST));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'primary', Mode::TEST));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'banking', Mode::LIVE));
        $this->assertNotNull($this->checkReferrals($merchant->getId(), 'primary', Mode::LIVE));

        $expectedPartner = $this->getDbEntityById('merchant', self::CURLEC_DEFAULT_MERCHANT_ID, 'live');

        $expectedPartnerConfig = $this->getDbEntity('partner_config');

        $this->assertEquals(
            DefaultPlan::DEFAULT_PARTNERS_PRICING_PLANS['MY']['dev'][DefaultPlan::PARTNER_COMMISSION_PLAN_ID_KEY],
            $expectedPartnerConfig['implicit_plan_id'], "commission plan_id matched"
        );
        $this->assertEquals(
            DefaultPlan::DEFAULT_PARTNERS_PRICING_PLANS['MY']['dev'][DefaultPlan::SUBMERCHANT_PRICING_OF_ONBOARDED_PARTNERS_KEY],
            $expectedPartnerConfig['default_plan_id'], "submerchants plan_id matched"
        );
        $merchantApplications = (new MerchantApplications\Repository())->fetchMerchantApplication(self::CURLEC_DEFAULT_MERCHANT_ID, Merchant\Constants::MERCHANT_ID);

        $applicationTypes = $merchantApplications->pluck(Entity::TYPE)->toArray();

        $expectedAppTypes = ['managed', 'referred'];

        $this->assertArraySelectiveEqualsWithCount($expectedAppTypes, $applicationTypes);

        $this->assertTrue($expectedPartner->isAggregatorPartner());

        Mail::assertQueued(PartnerOnBoarded::class);
    }

    public function testUpdateInActiveCurlecPartnerTypeAsResellerUsingProxyAuth()
    {

        Mail::fake();

        $org = $this->fixtures->create('org:curlec_org');

        $this->fixtures->merchant->edit( self::DEFAULT_MERCHANT_ID, [
            'org_id'    => $org->getId(),
            'country_code'=> 'MY'
        ]);

        $this->fixtures->merchant->createDummyCurlecPartnerApp(['partner_type' => 'reseller']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdateInActiveCurlecPartnerTypeAsAggregatorUsingProxyAuth()
    {

        Mail::fake();

        $org = $this->fixtures->create('org:curlec_org');

        $this->fixtures->merchant->edit( self::DEFAULT_MERCHANT_ID, [
            'org_id'    => $org->getId(),
            'country_code'=> 'MY'
        ]);

        $this->fixtures->merchant->createDummyCurlecPartnerApp(['partner_type' => 'aggregator']);

        $this->ba->proxyAuth();

        $this->startTest();
    }


    public function testUpdatePartnerTypeAsBankOnboardingPartner()
    {
        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, 'live');

        $app = ['id'=>'8ckeirnw84ifke'];

        $this->mockAuthServiceCreateApplication($merchant, $app);

        $this->ba->adminAuth();

        $this->startTest();

        $expectedPartner = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, 'live');

        $merchantApplications = (new MerchantApplications\Repository())->fetchMerchantApplication(self::DEFAULT_MERCHANT_ID, Merchant\Constants::MERCHANT_ID);

        $applicationTypes = $merchantApplications->pluck(Entity::TYPE)->toArray();

        $applicationType = $applicationTypes[0];

        $this->assertEquals($applicationType, MerchantApplications\Entity::MANAGED);

        $this->assertTrue($expectedPartner->isBankCaOnboardingPartner());
    }

    protected function mockBvsService()
    {
        $mock = $this->mockCreateLegalDocument();

        $mock->expects($this->once())->method('createLegalDocument')->withAnyParameters();
    }

    public function testFetchBankingAccountStatusWithVerifiedPanForRBL()
    {
        $this->createPartnerAndAddMultipleSubmerchants(Mode::LIVE);

        $this->mockAllSplitzResponseDisable();

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

        $this->mockAllSplitzResponseDisable();

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

    public function testAggregatorToResellerBulkUpdate()
    {
        $merchantId = '10000000000000';

        $this->setUpNonPurePlatformPartner();

        $this->fixtures->merchant->edit($merchantId, ['name' => 'et', 'website' => 'http://www.monahan.com/harum-fuga-quae-culpa-quod']);

        $this->ba->privateAuth();

        $this->mockAllSplitzTreatment();

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

        $app = $this->createResellerApp();

        $this->fixtures->on('test')->create('merchant_detail:sane',[
            'merchant_id' => $app->merchant_id,
            'contact_name'=> 'randomName',
            'contact_mobile'=> '9123456789',
            'business_type' => 2
        ]);

        $this->fixtures->on('live')->create('merchant_detail:sane',[
            'merchant_id' => $app->merchant_id,
            'contact_name'=> 'randomName',
            'contact_mobile'=> '9123456789',
            'business_type' => 2
        ]);

        $this->fixtures->on('live')->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['business_type' => 2, 'activation_status' => 'activated', 'contact_mobile'=> '9123456788']);
        $this->fixtures->on('test')->merchant_detail->edit(self::DEFAULT_SUBMERCHANT_ID, ['business_type' => 2, 'activation_status' => 'activated', 'contact_mobile'=> '9123456788']);

        $this->fixtures->on('test')->edit('merchant', self::DEFAULT_SUBMERCHANT_ID, ['name' => 'submerchant']);
        $this->fixtures->on('live')->edit('merchant', self::DEFAULT_SUBMERCHANT_ID, ['name' => 'submerchant']);
        $this->fixtures->on('test')->user->createUserForMerchant(self::DEFAULT_SUBMERCHANT_ID, ['email' => 'testing@example.com','contact_mobile'=> '9123456788']);

        $this->createMerchantAccessMap($app->getId(), self::DEFAULT_SUBMERCHANT_ID);
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

    protected function createResellerPartnerAndAddBankingSubmerchants()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, [
            'partner_type' => Merchant\Constants::RESELLER,
            'email'        => 'test@example.com',
        ]);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID_2]);

        $app = $this->createResellerApp();

        $this->createMerchantAccessMap($app->getId(), self::DEFAULT_SUBMERCHANT_ID);
        $this->createMerchantAccessMap($app->getId(), self::DEFAULT_SUBMERCHANT_ID_2);

        $this->fixtures->user->createBankingUserForMerchant(self::DEFAULT_SUBMERCHANT_ID);
        $this->fixtures->user->createBankingUserForMerchant(self::DEFAULT_SUBMERCHANT_ID_2);

    }

    public function markBankingSubmerchantAsCapitalSubmerchant(string $merchantId, string $partnerId = self::DEFAULT_MERCHANT_ID)
    {
        $submerchant = $this->getDbEntityById('merchant', $merchantId, 'live');
        (new Merchant\Core())->appendTag(
            $submerchant,
            Merchant\Constants::CAPITAL_LOC_PARTNERSHIP_TAG_PREFIX . $partnerId
        );
    }

    /**
     * Given: A partner
     * When: Partner fetches submerchants with an invalid product, i.e., not one of primary, banking or capital
     * Then: Partner receives a 400 bad request
     *
     * @return void
     */
    public function testFetchPartnerSubmerchantsWithInvalidProduct(): void
    {
        $this->createResellerPartnerAndAddBankingSubmerchants();

        $this->ba->proxyAuth();

        $this->startTest();

    }

    /**
     * Given: A partner whitelisted under partnership for capital experiment with 2 capital submerchants
     * When: Partner fetches capital submerchants
     * Then: Partner should receive 2 capital submerchant
     *
     * @return void
     */
    public function testFetchPartnerSubmerchantsForCapital(): void
    {
        $this->createResellerPartnerAndAddBankingSubmerchants();

        $this->markBankingSubmerchantAsCapitalSubmerchant(PartnerTest::DEFAULT_SUBMERCHANT_ID);
        $this->markBankingSubmerchantAsCapitalSubmerchant(PartnerTest::DEFAULT_SUBMERCHANT_ID_2);

        $this->mockCapitalPartnershipSplitzExperiment();

        $this->mockRazorxTreatment();

        $this->ba->proxyAuth();

        $this->startTest();

    }

    /**
     * Given: A partner whitelisted under partnership for capital experiment with 2 capital submerchants
     * When: Partner fetches capital submerchant by its ID
     * Then: Partner should receive 1 capital submerchant with that ID
     *
     * @return void
     */
    public function testFetchPartnerSubmerchantsForCapitalById(): void
    {
        $this->createResellerPartnerAndAddBankingSubmerchants();

        $this->markBankingSubmerchantAsCapitalSubmerchant(PartnerTest::DEFAULT_SUBMERCHANT_ID);
        $this->markBankingSubmerchantAsCapitalSubmerchant(PartnerTest::DEFAULT_SUBMERCHANT_ID_2);

        $this->mockCapitalPartnershipSplitzExperiment();

        $this->mockRazorxTreatment();

        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/submerchants/' . PartnerTest::DEFAULT_SUBMERCHANT_ID;

        $testData['response']['content'] = [
            'id'     => 'acc_' . PartnerTest::DEFAULT_SUBMERCHANT_ID,
            'entity' => 'merchant',
        ];

        $this->startTest($testData);

        $testData['request']['url'] = '/submerchants/' . PartnerTest::DEFAULT_SUBMERCHANT_ID_2;

        $testData['response']['content'] = [
            'id'     => 'acc_' . PartnerTest::DEFAULT_SUBMERCHANT_ID_2,
            'entity' => 'merchant',
        ];

        $this->startTest($testData);
    }

    /**
     * Given: A partner whitelisted under partnership for capital experiment with 2 capital submerchants
     * When: Partner fetches capital submerchant by its ID
     * Then: Partner should receive 1 capital submerchant with that ID
     *
     * @return void
     */
    public function testFetchPartnerSubmerchantsForCapitalByBankingSubmerchantId(): void
    {
        $this->createResellerPartnerAndAddBankingSubmerchants();

        $this->markBankingSubmerchantAsCapitalSubmerchant(PartnerTest::DEFAULT_SUBMERCHANT_ID);

        $this->mockCapitalPartnershipSplitzExperiment();

        $this->mockRazorxTreatment();

        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/submerchants/' . PartnerTest::DEFAULT_SUBMERCHANT_ID_2;

        $this->startTest($testData);
    }

    /**
     * Given: A partner NOT whitelisted under partnership for capital experiment with 2 banking submerchant
     * When: Partner fetches capital submerchants
     * Then: Partner should receive 0 submerchants
     *
     * @return void
     */
    public function testFetchPartnerSubmerchantsForCapitalWhenPartnerNotEligible(): void
    {
        $this->createResellerPartnerAndAddBankingSubmerchants();

        $this->markBankingSubmerchantAsCapitalSubmerchant(PartnerTest::DEFAULT_SUBMERCHANT_ID);
        $this->markBankingSubmerchantAsCapitalSubmerchant(PartnerTest::DEFAULT_SUBMERCHANT_ID_2);

        $this->mockRazorxTreatment();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Given: A partner whitelisted under partnership for capital experiment with 1 capital and 1 banking submerchant
     * When: Partner fetches banking submerchants
     * Then: Partner should receive 1 banking submerchant
     *
     * @return void
     */
    public function testFetchPartnerBankingSubmerchantsForCapital(): void
    {
        $this->createResellerPartnerAndAddBankingSubmerchants();

        $this->markBankingSubmerchantAsCapitalSubmerchant(PartnerTest::DEFAULT_SUBMERCHANT_ID);

        $this->mockCapitalPartnershipSplitzExperiment();

        $this->mockRazorxTreatment();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Given: A partner whitelisted under partnership for capital experiment with 1 capital and 1 banking submerchant
     * When: Partner fetches capital submerchants
     * Then: Partner should receive 1 capital submerchant
     *
     * @return void
     */
    public function testFetchPartnerSubmerchantsForCapitalBankingSubmerchantsAlsoPresent(): void
    {
        $this->createResellerPartnerAndAddBankingSubmerchants();

        $this->markBankingSubmerchantAsCapitalSubmerchant(PartnerTest::DEFAULT_SUBMERCHANT_ID);

        $this->mockCapitalPartnershipSplitzExperiment();

        $this->mockRazorxTreatment();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Given: A partner whitelisted under partnership for capital experiment with 2 capital submerchants
     * When: Partner fetches capital submerchants' applications from LOS Service
     * Then: Partner should receive applications for 2 capital submerchants'
     *
     * @return void
     */
    public function testFetchCapitalApplicationsForSubmerchants(): void
    {
        $this->app['config']->set('applications.loan_origination_system.mock', true);

        $this->createResellerPartnerAndAddBankingSubmerchants();

        $this->markBankingSubmerchantAsCapitalSubmerchant(PartnerTest::DEFAULT_SUBMERCHANT_ID);
        $this->markBankingSubmerchantAsCapitalSubmerchant(PartnerTest::DEFAULT_SUBMERCHANT_ID_2);

        $this->mockCapitalPartnershipSplitzExperiment();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Given: A partner NOT whitelisted under partnership for capital experiment with 2 capital submerchants
     * When: Partner fetches capital submerchants' applications from LOS Service
     * Then: Partner should receive BAD_REQUEST_URL_NOT_FOUND
     *
     * @return void
     */
    public function testFetchCapitalApplicationsForSubmerchantsPartnerNotEligible(): void
    {
        $this->app['config']->set('applications.loan_origination_system.mock', true);

        $this->createResellerPartnerAndAddBankingSubmerchants();

        $this->markBankingSubmerchantAsCapitalSubmerchant(PartnerTest::DEFAULT_SUBMERCHANT_ID);
        $this->markBankingSubmerchantAsCapitalSubmerchant(PartnerTest::DEFAULT_SUBMERCHANT_ID_2);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Given: A partner whitelisted under partnership for capital experiment with 1 capital and 1 banking submerchant
     * When: Partner fetches capital both submerchants' applications from LOS Service
     * Then: Partner should receive applications for only 1 capital submerchants' applications
     *
     * @return void
     */
    public function testFetchCapitalApplicationsForSubmerchantsIgnoreBankingSubmerchant(): void
    {
        $this->app['config']->set('applications.loan_origination_system.mock', true);

        $this->createResellerPartnerAndAddBankingSubmerchants();

        $this->markBankingSubmerchantAsCapitalSubmerchant(PartnerTest::DEFAULT_SUBMERCHANT_ID);

        $this->mockCapitalPartnershipSplitzExperiment();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPartnerFeatureCheckBySubmerchantWithFeatureEnabled()
    {
        $this->createResellerPartnerSubmerchant();

        $user = $this->createMerchantUser(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_SUBMERCHANT_ID, $user->id);

        $client = $this->createPartnerApplicationAndGetClientByEnv('dev', ['merchant_id' => '10000000000001', 'partner_type' => 'aggregator']);

        $this->fixtures->merchant->create(['id' => $client->getMerchantId()]);

        // assign the feature to the aggregator partner
        $this->fixtures->merchant->addFeatures(['route_partnerships'], $client->getMerchantId());

        $this->createMerchantAccessMap($client->getApplicationId(), self::DEFAULT_SUBMERCHANT_ID);

        $accessMap = $this->getDbEntity('merchant_access_map', ['entity_id' => $client->getApplicationId()]);

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['entity_owner_id' => $client->getMerchantId()]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/submerchant/partner_feature_check/' . 'route_partnerships';

        $this->startTest($testData);
    }

    public function testPartnerFeatureCheckBySubmerchantWithInvalidFeatureName()
    {
        $this->createResellerPartnerSubmerchant();

        $user = $this->createMerchantUser(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_SUBMERCHANT_ID, $user->id);

        $client = $this->createPartnerApplicationAndGetClientByEnv('dev', ['merchant_id' => '10000000000001', 'partner_type' => 'aggregator']);

        $this->fixtures->merchant->create(['id' => $client->getMerchantId()]);

        // assign the feature to the aggregator partner
        $this->fixtures->merchant->addFeatures(['route_partnerships'], $client->getMerchantId());

        $this->createMerchantAccessMap($client->getApplicationId(), self::DEFAULT_SUBMERCHANT_ID);

        $accessMap = $this->getDbEntity('merchant_access_map', ['entity_id' => $client->getApplicationId()]);

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['entity_owner_id' => $client->getMerchantId()]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/submerchant/partner_feature_check/route_partnerships_new';

        $this->startTest($testData);
    }

    public function testPartnerFeatureCheckBySubmerchantWithEmptyFeatureName()
    {
        $this->createResellerPartnerSubmerchant();

        $user = $this->createMerchantUser(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_SUBMERCHANT_ID, $user->id);

        $client = $this->createPartnerApplicationAndGetClientByEnv('dev', ['merchant_id' => '10000000000001', 'partner_type' => 'aggregator']);

        $this->fixtures->merchant->create(['id' => $client->getMerchantId()]);

        // assign the feature to the aggregator partner
        $this->fixtures->merchant->addFeatures(['route_partnerships'], $client->getMerchantId());

        $this->createMerchantAccessMap($client->getApplicationId(), self::DEFAULT_SUBMERCHANT_ID);

        $accessMap = $this->getDbEntity('merchant_access_map', ['entity_id' => $client->getApplicationId()]);

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['entity_owner_id' => $client->getMerchantId()]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/submerchant/partner_feature_check/' . ' ';

        $this->startTest($testData);
    }

    public function testPartnerFeatureCheckBySubmerchantWithFeatureDisabled()
    {
        $this->createResellerPartnerSubmerchant();

        $user = $this->createMerchantUser(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_SUBMERCHANT_ID, $user->id);

        $client = $this->createPartnerApplicationAndGetClientByEnv('dev', ['merchant_id' => '10000000000001', 'partner_type' => 'aggregator']);

        $this->fixtures->merchant->create(['id' => $client->getMerchantId()]);

        $this->createMerchantAccessMap($client->getApplicationId(), self::DEFAULT_SUBMERCHANT_ID);

        $accessMap = $this->getDbEntity('merchant_access_map', ['entity_id' => $client->getApplicationId()]);

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['entity_owner_id' => $client->getMerchantId()]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/submerchant/partner_feature_check/' . 'route_partnerships';

        $this->startTest($testData);
    }

    public function testPartnerFeatureCheckBySubmerchantWithFeatureEnabledOnOAuthApp()
    {
        $this->createResellerPartnerSubmerchant();

        $user = $this->createMerchantUser(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_SUBMERCHANT_ID, $user->id);

        $client = $this->createPartnerApplicationAndGetClientByEnv('dev', ['merchant_id' => '10000000000001', 'partner_type' => 'aggregator']);

        $this->fixtures->merchant->create(['id' => $client->getMerchantId()]);

        $this->createMerchantAccessMap($client->getApplicationId(), self::DEFAULT_SUBMERCHANT_ID);

        $accessMap = $this->getDbEntity('merchant_access_map', ['entity_id' => $client->getApplicationId()]);

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['entity_owner_id' => $client->getMerchantId()]);

        $oauthClient1 = $this->createPartnerApplicationAndGetClientByEnv('dev', ['merchant_id' => '10000000000002', 'partner_type' => 'pure_platform']);

        $this->fixtures->merchant->create(['id' => $oauthClient1->getMerchantId(), 'partner_type' => 'pure_platform']);

        $this->createMerchantAccessMap($oauthClient1->getApplicationId(), self::DEFAULT_SUBMERCHANT_ID);

        $accessMap = $this->getDbEntity('merchant_access_map', ['entity_id' => $oauthClient1->getApplicationId()]);

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['entity_owner_id' => $oauthClient1->getMerchantId()]);

        $oauthClient2 = $this->createPartnerApplicationAndGetClientByEnv('dev', ['merchant_id' => '10000000000002', 'partner_type' => 'pure_platform']);

        $this->createMerchantAccessMap($oauthClient2->getApplicationId(), self::DEFAULT_SUBMERCHANT_ID);

        $accessMap = $this->getDbEntity('merchant_access_map', ['entity_id' => $oauthClient2->getApplicationId()]);

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['entity_owner_id' => $oauthClient2->getMerchantId()]);

        $oauthClient3 = $this->createPartnerApplicationAndGetClientByEnv('dev', ['merchant_id' => '10000000000002', 'partner_type' => 'pure_platform']);

        $this->createMerchantAccessMap($oauthClient3->getApplicationId(), self::DEFAULT_SUBMERCHANT_ID);

        $accessMap = $this->getDbEntity('merchant_access_map', ['entity_id' => $oauthClient3->getApplicationId()]);

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['entity_owner_id' => $oauthClient3->getMerchantId()]);

        $this->fixtures->merchant->addFeatures(['route_partnerships'], $oauthClient2->getApplicationId(), 'application');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/submerchant/partner_feature_check/' . 'route_partnerships';

        // the response will contain partner id and app id as well (since the feature is enabled on the oauth app)
        $testData['response']['content'] = array_merge($testData['response']['content'], ['partner_id' => '10000000000002']);

        $this->startTest($testData);
    }

    public function testPartnerFeatureCheckBySubmerchantWithFeatureDisabledOnOAuthApp()
    {
        $this->createResellerPartnerSubmerchant();

        $user = $this->createMerchantUser(self::DEFAULT_SUBMERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_SUBMERCHANT_ID, $user->id);

        $client = $this->createPartnerApplicationAndGetClientByEnv('dev', ['merchant_id' => '10000000000001', 'partner_type' => 'aggregator']);

        $this->fixtures->merchant->create(['id' => $client->getMerchantId()]);

        $this->createMerchantAccessMap($client->getApplicationId(), self::DEFAULT_SUBMERCHANT_ID);

        $accessMap = $this->getDbEntity('merchant_access_map', ['entity_id' => $client->getApplicationId()]);

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['entity_owner_id' => $client->getMerchantId()]);

        $oauthClient1 = $this->createPartnerApplicationAndGetClientByEnv('dev', ['merchant_id' => '10000000000002', 'partner_type' => 'pure_platform']);

        $this->fixtures->merchant->create(['id' => $oauthClient1->getMerchantId(), 'partner_type' => 'pure_platform']);

        $this->createMerchantAccessMap($oauthClient1->getApplicationId(), self::DEFAULT_SUBMERCHANT_ID);

        $accessMap = $this->getDbEntity('merchant_access_map', ['entity_id' => $oauthClient1->getApplicationId()]);

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['entity_owner_id' => $oauthClient1->getMerchantId()]);

        $oauthClient2 = $this->createPartnerApplicationAndGetClientByEnv('dev', ['merchant_id' => '10000000000002', 'partner_type' => 'pure_platform']);

        $this->createMerchantAccessMap($oauthClient2->getApplicationId(), self::DEFAULT_SUBMERCHANT_ID);

        $accessMap = $this->getDbEntity('merchant_access_map', ['entity_id' => $oauthClient2->getApplicationId()]);

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['entity_owner_id' => $oauthClient2->getMerchantId()]);

        $oauthClient3 = $this->createPartnerApplicationAndGetClientByEnv('dev', ['merchant_id' => '10000000000002', 'partner_type' => 'pure_platform']);

        $this->createMerchantAccessMap($oauthClient3->getApplicationId(), self::DEFAULT_SUBMERCHANT_ID);

        $accessMap = $this->getDbEntity('merchant_access_map', ['entity_id' => $oauthClient3->getApplicationId()]);

        $this->fixtures->edit('merchant_access_map', $accessMap['id'], ['entity_owner_id' => $oauthClient3->getMerchantId()]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/submerchant/partner_feature_check/' . 'route_partnerships';

        $this->startTest($testData);
    }
}
