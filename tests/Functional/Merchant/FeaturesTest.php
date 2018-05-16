<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\FileUploadTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Merchant\Request as MerchantRequest;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Mail\Merchant\FeatureEnabled as FeatureEnabledEmail;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class FeaturesTest extends TestCase
{
    use FileUploadTrait;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;
    use RequestResponseFlowTrait;

    const DEFAULT_MERCHANT_ID    = '10000000000000';
    const ONBOARDING_MERCHANT_ID = '10000000001017';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/FeaturesTestData.php';

        parent::setUp();

        //
        // testOnboardingRequestStatus creates an action_state in the live mode which requires an admin to
        // exist due to a foreign key constraint. Admin fixture is created in test and not in live, hence creating it
        // here.
        //
        $this->fixtures->on('live')->create('admin', [
            'id'     => Org::SUPER_ADMIN,
            'org_id' => Org::RZP_ORG,
        ]);

        $this->ba->adminAuth();
    }

    public function testAddInvalidFeatureToMerchant()
    {
        $this->startTest();
    }

    public function testAddDuplicateFeatureToMerchant()
    {
        $this->addFeatures(Mode::TEST);

        $this->startTest();
    }

    public function testApplicationFeatures()
    {
        $appId = '1000000DemoApp';

        $dummy = 'dummy';

        $this->addFeatures(
            Mode::TEST,
            true,
            [$dummy],
            Constants::APPLICATION,
            $appId);

        $this->verifyFeaturePresenceForEntity(Mode::TEST, Constants::APPLICATION, $appId, [$dummy]);

        $testData = $this->getDataToDeleteFeaturesFromEntity(Mode::TEST,
            true,
            $dummy,
            Constants::APPLICATION,
            $appId);

        $this->startTest($testData);
    }

    public function testAccountFeatures()
    {
        $accountId = '10000000000000';

        $dummy = 'dummy';

        $testData = $this->getDataToAddAccountFeatures(Mode::TEST,
            true,
            [$dummy],
            $accountId);

        $this->startTest($testData);

        $this->verifyFeaturePresenceForAccounts(Mode::TEST, $accountId, [$dummy]);

        $testData = $this->getDataToDeleteFeaturesFromEntity(Mode::TEST,
            true,
            $dummy,
            Constants::ACCOUNT,
            $accountId);

        $this->startTest($testData);
    }

    public function testDeleteNonExistentFeatureFromMerchant()
    {
        $this->ba->adminAuth('test', null, 'org_100000razorpay');

        $this->startTest();
    }

    public function testMultiAssignFeature()
    {
        $this->fixtures->create('merchant', ['id' => '10000000000001']);
        $this->fixtures->create('merchant', ['id' => '10000000000002']);
        $this->fixtures->create('merchant', ['id' => '10000000000003']);

        $this->startTest();
    }

    public function testMultiRemoveFeature()
    {
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000001',
                'name' => 'dummy'
            ]);
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000002',
                'name' => 'dummy'
            ]);
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000003',
                'name' => 'dummy'
            ]);

        $this->startTest();
    }

    public function testDummyFeatureRouteWithAccess()
    {
        $this->fixtures->merchant->addFeatures(['dummy']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testDummyFeatureRouteWithoutAccess()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    /**
     * Add a feature to test
     * Verify - Any feature added to test should not be added to live
     */
    public function testAddFeatureToTestVerifyAbsenceInLive()
    {
        $this->addFeatures(Mode::TEST);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeatureAbsence(Mode::LIVE);
    }

    /**
     * Add a feature to live
     * Verify - Any feature added to live should not be added to test
     */
    public function testAddFeatureToLiveVerifyAbsenceInTest()
    {
        $this->addFeatures(Mode::LIVE);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->verifyFeatureAbsence(Mode::TEST);
    }

    /**
     * Add a feature to the test database and sync it to live
     * Verify - Any feature added to test with the should_sync
     * flag, should be synced to live
     */
    public function testAddFeatureToTestSyncedToLive()
    {
        $this->addFeatures(Mode::TEST,true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);
    }

    /**
     * Add a feature to the live database and sync it to test
     * Verify - Any feature added to live with the should_sync
     * flag, should be synced to test
     */
    public function testAddFeatureToLiveSyncedToTest()
    {
        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->verifyFeaturePresence(Mode::TEST);
    }

    /**
     * Add a feature to the test database
     * Add a feature to the live database and sync it to test
     * Verify - Any feature added to live with the should_sync flag,
     * should not fail even if it is already present in test
     */
    public function testAddFeatureToTestAddFeatureToLiveSyncedToTest()
    {
        $this->addFeatures(Mode::TEST);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::LIVE);
    }

    /**
     * Add a feature to the live database
     * Add a feature to the test database and sync it to live
     * Verify - Any feature added to test with the should_sync flag,
     * should not fail even if it is already present in live
     */
    public function testAddFeatureToLiveAddFeatureToTestSyncedToLive()
    {
        $this->addFeatures(Mode::LIVE);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->addFeatures(Mode::TEST, true);

        $this->verifyFeaturePresence(Mode::TEST);
    }

    /**
     * Add a feature to the live database
     * Add a feature to the live database and sync it to test
     * Verify - Any feature added to live with the should_sync flag, should not
     * fail even if it is already present in test
     */
    public function testAddFeatureToLiveAddFeatureToLiveSyncedToTest()
    {
        $this->addFeatures(Mode::LIVE);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);
    }

    /**
     * Add a feature to the test database
     * Add a feature to the test database and sync it to live
     * Verify - Any feature added to live with the should_sync flag, should not
     * fail even if it is already present in live
     */
    public function testAddFeatureToTestAddFeatureToTestSyncedToLive()
    {
        $this->addFeatures(Mode::TEST);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->addFeatures(Mode::TEST, true);

        $this->verifyFeaturePresence(Mode::LIVE);
    }

    /**
     * Add a feature to live and sync it to test
     * Delete the feature from the test database
     * Verify - Any feature deleted from test should not be deleted from live
     */
    public function testDeleteFeatureFromTestAndVerifyPresenceInLive()
    {
        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->deleteFeature(Mode::TEST);

        $this->verifyFeatureAbsence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);
    }

    /**
     * Add a feature to live and sync it to test
     * Delete the feature from the live database
     * Verify - Any feature deleted from live should not be deleted from test
     */
    public function testDeleteFeatureFromLiveAndVerifyPresenceInTest()
    {
        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->deleteFeature(Mode::LIVE);

        $this->verifyFeatureAbsence(Mode::LIVE);

        $this->verifyFeaturePresence(Mode::TEST);
    }

    /**
     * Add a feature to live and sync it to test
     * Delete the feature from the test database and sync it to live
     * Verify - Any feature deleted from test and synced to live,
     * should be deleted from live as well
     */
    public function testDeleteFeatureFromTestSyncedToLive()
    {
        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->deleteFeature(Mode::TEST, true);

        $this->verifyFeatureAbsence(Mode::TEST);

        $this->verifyFeatureAbsence(Mode::LIVE);
    }

    /**
     * Add a feature to live and sync it to test
     * Delete the feature from the live database and sync it to test
     * Verify - Any feature deleted from live and synced to test,
     * should be deleted from test as well
     */
    public function testDeleteFeatureFromLiveSyncedToTest()
    {
        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->deleteFeature(Mode::LIVE, true);

        $this->verifyFeatureAbsence(Mode::TEST);

        $this->verifyFeatureAbsence(Mode::LIVE);
    }

    /**
     * Add a feature to live and sync it to test
     * Delete the feature from the live database.
     * Delete the feature from the test database and sync it to live
     * Verify - Deleting the feature from test with sync, should not
     * fail even if the feature does not exist on live
     */
    public function testDeleteFeatureFromLiveDeleteFeatureFromTestSyncedToLive()
    {
        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->deleteFeature(Mode::LIVE);

        $this->verifyFeatureAbsence(Mode::LIVE);

        $this->deleteFeature(Mode::TEST, true);

        $this->verifyFeatureAbsence(Mode::TEST);
    }

    /**
     * Add a feature to live and sync it to test
     * Delete the feature from the test database.
     * Delete the feature from the live database and sync it to test
     * Verify - Deleting the feature from test with sync, should not
     * fail even if the feature does not exist on test.
     */
    public function testDeleteFeatureFromTestDeleteFeatureFromLiveSyncedToTest()
    {
        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->deleteFeature(Mode::TEST);

        $this->verifyFeatureAbsence(Mode::TEST);

        $this->deleteFeature(Mode::LIVE, true);

        $this->verifyFeatureAbsence(Mode::LIVE);
    }

    /**
     * Adds subscriptions feature to the live database
     * Since the route is accessed by an admin, it should
     * allow even when should sync is sent as 1.
     */
    public function testAddFeatureNonEditableByAdminOnLive()
    {
        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $this->addFeatures(
            Mode::LIVE,
            true,
            ['subscriptions'],
            'merchant',
            $merchantId);

        $this->verifyFeaturePresence(Mode::TEST, ['subscriptions'], self::ONBOARDING_MERCHANT_ID);

        $this->verifyFeaturePresence(Mode::LIVE, ['subscriptions'], self::ONBOARDING_MERCHANT_ID);
    }

    /**
     * Fetch all onboarding questions
     */
    public function testGetOnboardingQuestions()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Post a request for subscriptions activation
     */
    public function testPostSubscriptionsOnboardingResponses()
    {
        $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response);

        $this->verifyMerchantRequest(
            Constants::SUBSCRIPTIONS,
            MerchantRequest\Type::PRODUCT,
            MerchantRequest\Status::UNDER_REVIEW);
    }

    /**
     * For Backward Compatibility : Assert that Merchant Request is also created and that the status, name, type is
     * as expected
     */
    public function verifyMerchantRequest($featureName, $featureType, $requestStatus, $mode = Mode::TEST)
    {
        $merchantRequest = $this->getDbLastEntityToArray('merchant_request', $mode);

        $this->assertNotEmpty($merchantRequest);

        $this->assertEquals($featureName, $merchantRequest[MerchantRequest\Entity::NAME]);

        $this->assertEquals($featureType, $merchantRequest[MerchantRequest\Entity::TYPE]);

        $this->assertEquals(
            $requestStatus,
            $merchantRequest[MerchantRequest\Entity::STATUS]
        );
    }

    /**
     * Enable a non-notifyFeature on Live mode
     */
    public function testFeatureEnabledEmailNonNotify()
    {
        Mail::fake();

        $this->addFeatures(Mode::LIVE, true);

        Mail::assertNotSent(FeatureEnabledEmail::class);
    }

    /*
     * Test cases for feature routes accessible from the merchant
     * dashboard begin from here.
     */

    /**
     * Fetches the features using the route used by the
     * merchant dashboard
     */
    public function testGetFeaturesAsMerchant()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * This function tests updating of a visible merchant feature: noflashcheckout
     */
    public function testUpdateMerchantFeatures()
    {
        $this->ba->proxyAuth();

        $this->startTest();

        $this->verifyFeaturePresence(Mode::TEST, ['noflashcheckout']);
    }

    /**
     * This function tests updating of a merchant feature with should_sync parameter
     */
    public function testAddMerchantFeaturesWithSyncOnLive()
    {
        $this->updateFeatureAsMerchant(
            'add',
            Mode::LIVE,
            'noflashcheckout',
            false,
            true);

        $this->verifyFeaturePresence(Mode::TEST, ['noflashcheckout']);

        $this->verifyFeaturePresence(Mode::LIVE, ['noflashcheckout']);
    }

    /**
     * This function tests updating of a merchant feature with should_sync parameter
     */
    public function testAddMerchantFeaturesWithSyncOnTest()
    {
        $this->updateFeatureAsMerchant(
            'add',
            Mode::TEST,
            'noflashcheckout',
            false,
            true);

        $this->verifyFeaturePresence(Mode::TEST, ['noflashcheckout']);

        $this->verifyFeaturePresence(Mode::LIVE, ['noflashcheckout']);
    }

    /**
     * This function tests updating of a non visble merchant feature: dummy
     */
    public function testUpdateMerchantProductFeatures()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * This function tests updating of a merchant feature that
     * can be updated on test but not live mode: marketplace.
     * This test also checks the format of the response received
     * to the merchant.
     */
    public function testAddMerchantEditableFeaturesOnTest()
    {
        $this->ba->proxyAuthTest();

        $this->startTest();
    }

    /**
     * This function tests updating of a merchant feature that can be
     * updated by the merchant on test but not live mode: marketplace.
     */
    public function testAddMerchantProductFeatures()
    {
        /*
         * $input[0] - $action
         * $input[1] - $addToMode
         * $input[2] - $featureName
         * $input[3] - $expectBadRequestException
         * $input[4] - $shouldSync
         */

        $inputs = [
            ['add', Mode::LIVE, 'marketplace', true, false],
            ['add', Mode::TEST, 'marketplace', true, true],
            ['add', Mode::LIVE, 'marketplace', true, true],
        ];

        foreach($inputs as $input)
        {
            $this->updateFeatureAsMerchant(
                $input[0],
                $input[1],
                $input[2],
                $input[3],
                $input[4]);
        }

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $inputs = [
            ['remove', Mode::LIVE, 'marketplace', true, false],
            ['remove', Mode::TEST, 'marketplace', true, true],
            ['remove', Mode::LIVE, 'marketplace', true, true],
        ];

        foreach($inputs as $input)
        {
            $this->updateFeatureAsMerchant(
                $input[0],
                $input[1],
                $input[2],
                $input[3],
                $input[4]);
        }
    }

    public function testUpdateOnboardingResponses()
    {
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $filestoreEntityId = $this->postOnboardingResponses($merchantId);

        $this->ba->adminAuth($liveMode, null, 'org_100000razorpay');

        $this->updateMarketplaceOnboardingResponse();

        $this->ba->proxyAuth('rzp_' . $liveMode . '_' . $merchantId);

        $testData = $this->testData[__FUNCTION__];

        // tests that if while updating the submission response, the file is not updated,
        // the previously stored file details are preserved.
        $testData['response']['content'][Constants::VENDOR_AGREEMENT] = "api/$merchantId/marketplace.vendor_agreement.pdf";

        $this->startTest($testData);
    }

    public function testResendOnboardingResponses()
    {
        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $this->createMarketplaceOnboardingResponse($merchantId);

        $this->createMarketplaceOnboardingResponse($merchantId, true);
    }

    public function testOnboardingRequestStatus()
    {
        Mail::fake();

        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $this->createMarketplaceOnboardingResponse($merchantId);

        // Test update status API
        $this->updateMarketplaceOnboardingResponseStatus($merchantId, 'rejected');

        $this->addFeatures(
            Mode::LIVE,
            false,
            [Constants::MARKETPLACE],
            Constants::MERCHANT,
            $merchantId);

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        // Test fetch by status and product API
        // Test auto approving of a request, when added in Live mode
        $this->verifyProductOnboardingSubmissionStatus('approved', Constants::MARKETPLACE);

        // Test the fetch status route
        $this->getMarketplaceOnboardingResponseStatus();

        Mail::assertQueued(FeatureEnabledEmail::class, function ($mail)
        {
            $this->assertEquals('Route', $mail->viewData['feature']);

            $documentation = 'route';
            $this->assertEquals($documentation, $mail->viewData['documentation']);

            return true;
        });
    }

    public function testOnboardingRequestStatusUpdateLeadingToMerchantRequestCreation()
    {
        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        // Test update status API
        $this->updateMarketplaceOnboardingResponseStatus($merchantId, 'rejected');
    }

    /**
     * The feature onboarding request should not be approved if the feature is enabled on Test mode
     * Also, email should not be sent to the merchant
     */
    public function testOnboardingRequestStatusForTestMode()
    {
        Mail::fake();

        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $this->createMarketplaceOnboardingResponse($merchantId);

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->addFeatures(
            Mode::TEST,
            false,
            [Constants::MARKETPLACE],
            Constants::MERCHANT,
            $merchantId);

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->verifyProductOnboardingSubmissionStatus('pending');

        // Tests the bulk update route
        $this->bulkUpdateFeatureActivationStatus(Constants::MARKETPLACE, $merchantId, 'rejected');

        Mail::assertNotSent(FeatureEnabledEmail::class);
    }

    public function testFetchMerchantFeatures()
    {
        $content = $this->startTest();

        $featuresWithValues = count(Constants::$featureValueMap);

        $featuresInResponse = count($content['all_features']);

        $this->assertEquals($featuresWithValues, $featuresInResponse);
    }

    /*
     * Helpers
     */

    public function createMarketplaceOnboardingResponse(string $merchantId, bool $expectError = false)
    {
        $this->ba->proxyAuth('rzp_live_' . $merchantId);

        $testData = $this->testData[__FUNCTION__];

        if ($expectError === true)
        {
            $errorDesc = PublicErrorDescription::BAD_REQUEST_MERCHANT_FEATURE_ACTIVATION_FORM_ALREADY_SUBMITTED;
            $testData['response'] = [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => $errorDesc,
                    ],
                ],
                'status_code' => 400,
            ];

            $testData['exception'] = [
                'class' => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_ACTIVATION_FORM_ALREADY_SUBMITTED,
            ];

            $this->startTest($testData);
        }
        else
        {
            $testData['response'] = [
                'content' => true
            ];

            $response = $this->makeRequestAndGetContent($testData['request']);

            $this->assertTrue($response);
        }
    }

    public function testPostOnboardingResponses()
    {
        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $this->postOnboardingResponses($merchantId);
    }

    public function updateMarketplaceOnboardingResponse()
    {
        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response);

        $this->verifyMerchantRequest(
            Constants::MARKETPLACE,
            MerchantRequest\Type::PRODUCT,
            MerchantRequest\Status::UNDER_REVIEW);
    }

    public function testRestrictedAccessFeatureEnabledAndAccessedByMerchant()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000000',
                'name' => 'virtual_accounts'
            ]);

        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $this->getDefaultVirtualAccountRequestArray();

        $this->startTest($testData);
    }

    public function testRestrictedAccessFeatureDisabledAndAccessedByMerchant()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $this->getDefaultVirtualAccountRequestArray();

        $this->startTest($testData);
    }

    /**
     * Post a request for product activation
     */
    protected function postOnboardingResponses(string $merchantId)
    {
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->proxyAuth('rzp_' . $liveMode . '_' . $merchantId);

        $url = storage_path("files/" . Constants::ONBOARDING .  "/" . Constants::VENDOR_AGREEMENT . ".pdf");

        $uploadedFile = $this->createUploadedFile($url);

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        $request['files'][Constants::VENDOR_AGREEMENT] = $uploadedFile;

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response);

        $testData = $this->testData['getOnboardingResponses'];

        $request = $testData['request'];

        $expectedResponse = $testData['response']['content'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $fileStoreData = $this->getDbLastEntityPublic('file_store', $liveMode);

        $testData = $this->testData['testFileStoreData'];

        $expectedOutput = $testData['response']['content'];

        $this->assertArraySelectiveEquals($expectedOutput, $fileStoreData);

        $fileStoreId = $fileStoreData['id'];

        $this->fixtures->stripSign($fileStoreId);

        $this->verifyMerchantRequest(
            Constants::MARKETPLACE,
            MerchantRequest\Type::PRODUCT,
            MerchantRequest\Status::UNDER_REVIEW);

        return $fileStoreId;
    }

    protected function verifyProductOnboardingSubmissionStatus(string $status, string $product = null)
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['status'] = $status;

        $testData['response']['content'][0]['status'] = $status;

        if ($product !== null)
        {
            $testData['request']['content']['product'] = $product;

            $testData['response']['content'][0]['product'] = $product;
        }

        $this->startTest($testData);
    }

    protected function updateMarketplaceOnboardingResponseStatus(string $merchantId, string $status)
    {
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode, null, 'org_100000razorpay');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['merchant_id'] = $merchantId;

        $testData['request']['content']['status'] = $status;

        $testData['response']['content']['marketplace_activation_status'] = $status;

        $this->startTest($testData);

        $this->verifyMerchantRequest(
            Constants::MARKETPLACE,
            MerchantRequest\Type::PRODUCT,
            MerchantRequest\Constants::getRequestStatusForOnboardingStatus($status),
            $liveMode);
    }

    /**
     * Simulates merchant behavior based on the
     * params received
     *
     * @param string $action
     * @param string $mode
     * @param string $featureName
     * @param bool   $expectBadRequestException
     * @param bool   $shouldSync
     */
    protected function updateFeatureAsMerchant(
        string $action,
        string $mode,
        string $featureName = 'noflashcheckout',
        bool $expectBadRequestException = false,
        bool $shouldSync = true)
    {
        $authMethod = 'proxyAuth' . studly_case($mode);

        $this->ba->$authMethod();

        $testData = $this->testData[__FUNCTION__];

        if ($action === 'add')
        {
            $testData['request']['content']['features'][$featureName] = '1';
        }
        else
        {
            $testData['request']['content']['features'][$featureName] = '0';
        }

        if ($expectBadRequestException === true)
        {
            $testData['response'] = [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE
                    ],
                ],
                'status_code' => 400,
            ];

            $testData['exception'] = [
                'class' => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE,
            ];
        }

        if ($shouldSync === true)
        {
            $testData['request']['content']['should_sync'] = 1;
        }

        $this->startTest($testData);
    }

    protected function createMerchantDetails(string $merchantId)
    {
        $attributes = ['id' => $merchantId, 'org_id' => Org::RZP_ORG];

        $detailsAttributes = ['merchant_id' => $merchantId, 'contact_email' => 'test@gmail.com'];

        $this->fixtures->on(Mode::LIVE)->create('merchant', $attributes);

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $detailsAttributes);

        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $detailsAttributes);

        $this->ba->proxyAuth('rzp_live_' . $merchantId);

        return $merchantId;
    }

    protected function getMarketplaceOnboardingResponseStatus()
    {
        $this->startTest();
    }

    protected function bulkUpdateFeatureActivationStatus($featureName, $merchantId, $status)
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = [
            $featureName => [
                $merchantId => $status
            ]
        ];

        $testData['response']['content'] = [
            'success'       => 1,
            'failed'        => 0,
            'failed_ids'    => []
        ];

        $this->startTest($testData);
    }

    /**
     * Adds a feature as an admin [admin auth].
     * This function tests the route POST /features
     *
     * @param string      $addToMode
     * @param bool        $shouldSync
     * @param array       $featureNames
     * @param string|null $entityType
     * @param string|null $entityId
     */
    protected function addFeatures(
        string $addToMode,
        bool $shouldSync = false,
        array $featureNames = ['dummy'],
        string $entityType = 'merchant',
        string $entityId = null)
    {
        $this->ba->adminAuth($addToMode);

        $testData = $this->testData[__FUNCTION__];

        if (empty($featureNames) === false)
        {
            $testData['request']['content']['names'] = $featureNames;
        }

        if ($shouldSync !== false)
        {
            $testData['request']['content']['should_sync'] = 1;
        }

        $testData['request']['content']['entity_type'] = $entityType;

        if ($entityId !== null)
        {
            $testData['request']['content']['entity_id'] = $entityId;
        }

        $this->startTest($testData);
    }

    /**
     * Adds a feature to an account as a merchant/application [private auth]
     * This function tests the route: POST /accounts/id/features
     *
     * @param string      $addToMode
     * @param bool        $shouldSync
     * @param array       $featureNames
     * @param string      $entityId
     */
    protected function getDataToAddAccountFeatures(
        string $addToMode,
        bool $shouldSync,
        array $featureNames,
        string $entityId)
    {
        if ($addToMode === Mode::LIVE)
        {
            $this->ba->privateAuth('rzp_live_TheLiveAuthKey');
        }
        else
        {
            $this->ba->privateAuth();
        }

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['names'] = $featureNames;

        $testData['request']['content']['should_sync'] = (int) $shouldSync;

        $testData['request']['url'] = '/accounts/me/features';

        $testData['response']['content'][0]['entity_id'] = $entityId;

        $testData['response']['content'][0]['entity_type'] = Constants::MERCHANT;

        return $testData;
    }

    /**
     * This function tests fetching features through the route:
     * GET /features/entity_id which has been deprecated by GET /features/entity_type/id
     *
     * This function ensures Backward compatibility is maintained
     *
     * @param string $mode
     * @param array  $featureNames
     * @param string $merchantId
     * @deprecated by verifyFeaturePresenceForEntity
     */
    protected function verifyFeaturePresence(
        string $mode,
        array $featureNames = ['dummy'],
        string $merchantId = self::DEFAULT_MERCHANT_ID)
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/features/' . $merchantId;

        $this->ba->adminAuth($mode);

        $response = $this->startTest($testData);

        $assignedFeatures = array_map(function ($feature)
        {
            return $feature["name"];
        }, $response["assigned_features"]);

        $assignedFeaturesInResponse = array_intersect($assignedFeatures, $featureNames);

        // Check if all the featureNames requested, are present in the assignedFeatures array
        $this->assertEquals(count($featureNames), count($assignedFeaturesInResponse));
    }

    /**
     * Performs a GET request based on the mode received and verifies the
     * absence of the features received as arguments
     *
     * @todo: Update the route to the new route. Ref: verifyFeaturePresenceForEntity()
     *
     * @param string $mode
     * @param array  $featureNames
     */
    protected function verifyFeatureAbsence(
        string $mode,
        array $featureNames = ['dummy'])
    {
        $this->ba->adminAuth($mode);

        $response = $this->startTest();

        $assignedFeatures = array_map(function ($feature)
        {
            return $feature["name"];
        }, $response["assigned_features"]);

        $assignedFeaturesInResponse = array_intersect($assignedFeatures, $featureNames);

        $this->assertEquals(0, count($assignedFeaturesInResponse));
    }

    /**
     * This function tests fetching features through the route:
     * GET /features/entity_type/id which can be used only by the admins [admin auth]
     *
     * @param string $mode
     * @param string $entityType
     * @param string $entityId
     * @param array  $featureNames
     */
    protected function verifyFeaturePresenceForEntity(
        string $mode,
        string $entityType,
        string $entityId,
        array $featureNames)
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/features/'. $entityType . 's/' . $entityId;

        $this->ba->adminAuth($mode, null, 'org_100000razorpay');

        $response = $this->startTest($testData);

        $assignedFeatures = array_map(function ($feature)
        {
            return $feature["name"];
        }, $response["assigned_features"]);

        $assignedFeaturesInResponse = array_intersect($assignedFeatures, $featureNames);

        // Check if all the featureNames requested, are present in the assignedFeatures array
        $this->assertEquals(count($featureNames), count($assignedFeaturesInResponse));
    }

    /**
     * This function specifically tests fetching features through
     * the route: GET /accounts/id/features which can be used by
     * accounts/applications [private auth]
     *
     * @param string $mode
     * @param string $entityId
     * @param array  $featureNames
     */
    protected function verifyFeaturePresenceForAccounts(
        string $mode,
        string $entityId,
        array $featureNames)
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/me/features';

        if ($mode === Mode::LIVE)
        {
            $this->ba->privateAuth('rzp_live_TheLiveAuthKey');
        }
        else
        {
            $this->ba->privateAuth();
        }

        $response = $this->startTest($testData);

        $assignedFeatures = array_map(function ($feature)
        {
            return $feature["name"];
        }, $response["assigned_features"]);

        $assignedFeaturesInResponse = array_intersect($assignedFeatures, $featureNames);

        // Check if all the featureNames requested, are present in the assignedFeatures array
        $this->assertEquals(count($featureNames), count($assignedFeaturesInResponse));
    }

    /**
     * Deletes a feature as an admin based on
     * the params received
     *
     * @param string $deleteFromMode
     * @param bool   $shouldSync
     * @param string $featureName
     * @deprecated by getDataToDeleteFeaturesFromEntity
     */
    protected function deleteFeature(
        string $deleteFromMode,
        bool $shouldSync = false,
        string $featureName = 'dummy')
    {
        $this->ba->adminAuth($deleteFromMode, null, 'org_100000razorpay');

        $testData = $this->testData[__FUNCTION__];

        if ($featureName === null)
        {
            $testData['request']['url'] = '/features/' . self::DEFAULT_MERCHANT_ID . '/' . $featureName;
        }

        if ($shouldSync === true)
        {
            $testData['request']['content']['should_sync'] = 1;
        }

        $this->startTest($testData);
    }

    protected function getDataToDeleteFeaturesFromEntity(
        string $deleteFromMode,
        bool $shouldSync,
        string $featureName,
        string $entityType,
        string $entityId): array
    {
        $this->ba->adminAuth($deleteFromMode, null, 'org_100000razorpay');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/' . $entityType . 's/' . $entityId . '/features/' . $featureName;

        $testData['request']['content']['should_sync'] = (int) $shouldSync;

        return $testData;
    }
}
