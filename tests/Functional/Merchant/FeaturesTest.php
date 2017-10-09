<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;
use Illuminate\Http\UploadedFile;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Mail\Merchant\FeatureEnabled as FeatureEnabledEmail;

class FeaturesTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/FeaturesTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    /**
     * Adds a feature as an admin based on
     * the params received
     *
     * @param string $addToMode
     * @param bool   $shouldSync
     * @param array  $featureNames
     */
    private function addFeatures(
        string $addToMode,
        bool $shouldSync = false,
        array $featureNames = ['dummy'], string $merchant_id = null)
    {
        $authMethod = 'appAuth' . studly_case($addToMode);

        $this->ba->$authMethod();

        $testData = $this->testData[__FUNCTION__];

        if (empty($featureNames) === false)
        {
            $testData['request']['content']['names'] = $featureNames;
        }

        if ($shouldSync !== false)
        {
            $testData['request']['content']['should_sync'] = 1;
        }

        if ($merchant_id !== null)
        {
            $testData['request']['content']['entity_id'] = $merchant_id;
        }

        $this->startTest($testData);
    }

    /**
     * Deletes a feature as an admin based on
     * the params received
     *
     * @param string $deleteFromMode
     * @param bool   $shouldSync
     * @param string $featureName
     */
    private function deleteFeature(
        string $deleteFromMode,
        bool $shouldSync = false,
        string $featureName = 'dummy')
    {
        $this->ba->adminAuth($deleteFromMode, null, 'org_100000razorpay');

        $testData = $this->testData[__FUNCTION__];

        if ($featureName === null)
        {
            $testData['request']['url'] = '/features/10000000000000/' . $featureName;
        }

        if ($shouldSync === true)
        {
            $testData['request']['content']['should_sync'] = 1;
        }

        $this->startTest($testData);
    }

    /**
     * Simulates merchant behavior based on the
     * params received
     *
     * @param string $action
     * @param string $addToMode
     * @param string $featureName
     * @param bool   $expectBadRequestException
     * @param bool   $shouldSync
     */
    private function updateFeatureAsMerchant(
        string $action,
        string $addToMode,
        string $featureName = 'noflashcheckout',
        bool $expectBadRequestException = false,
        bool $shouldSync = true)
    {
        $authMethod = 'proxyAuth' . studly_case($addToMode);

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

    /**
     * Performs a GET request based on the mode received and verifies the
     * presence of the features received as arguments
     *
     * @param string $mode
     * @param array  $featureNames
     */
    private function verifyFeaturePresence(
        string $mode,
        array $featureNames = ['dummy'])
    {
        $authMethod = 'appAuth' . studly_case($mode);

        $this->ba->$authMethod();

        $response = $this->startTest();

        $assignedFeatures = array_map(function ($feature)
            {
                return $feature["name"];
            }, $response["assigned_features"]);

        $assignedFeaturesInResponse = array_intersect($assignedFeatures, $featureNames);

        // Check if all the featureNames requested, are present in the assignedFeatures array
        $this->assertTrue(count($assignedFeaturesInResponse) === count($featureNames));
    }

    /**
     * Performs a GET request based on the mode received and verifies the
     * absence of the features received as arguments
     *
     * @param string $mode
     * @param array  $featureNames
     */
    private function verifyFeatureAbsence(
        string $mode,
        array $featureNames = ['dummy'])
    {
        $authMethod = 'appAuth' . studly_case($mode);

        $this->ba->$authMethod();

        $response = $this->startTest();

        $assignedFeatures = array_map(function ($feature)
            {
                return $feature["name"];
            }, $response["assigned_features"]);

        $assignedFeaturesInResponse = array_intersect($assignedFeatures, $featureNames);

        $this->assertEquals(0, count($assignedFeaturesInResponse));
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
        $this->addFeatures( Mode::LIVE, true);

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
        $this->addFeatures(Mode::LIVE, true, ['subscriptions']);

        $this->verifyFeaturePresence(Mode::TEST, ['subscriptions']);

        $this->verifyFeaturePresence(Mode::LIVE, ['subscriptions']);
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
     * Post a request for product activation
     */
    public function testpostOnboardingResponses()
    {
        $this->createMerchantDetailsOnLive();

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
    }

    /**
     * @param string $url
     *
     * @return UploadedFile
     */
    protected function createUploadedFile(string $file): UploadedFile
    {
        $this->assertFileExists($file);

        $mimeType = 'application/pdf';
        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            filesize($file),
            null,
            true
        );

        return $uploadedFile;
    }

    /**
     * Enable a notifyFeature on Live mode
     */
    public function testFeatureEnabledEmailNotificationOnLive()
    {
        Mail::fake();

        $this->addFeatures(Mode::LIVE, false, ['dummy', 'marketplace']);

        Mail::assertSent(FeatureEnabledEmail::class, function ($mail)
        {
            $feature       = 'Route';
            $this->assertEquals($feature, $mail->viewData['feature']);

            $documentation = 'route';
            $this->assertEquals($documentation, $mail->viewData['documentation']);

            return true;
        });
    }

    /**
     * Enable a notifyFeature on Test mode
     */
    public function testFeatureEnabledEmailNotificationOnTest()
    {
        Mail::fake();

        $this->addFeatures(Mode::TEST, false, ['dummy', 'marketplace']);

        Mail::assertNotSent(FeatureEnabledEmail::class);
    }

    /**
     * Enable a notifyFeature on Test mode with shouldSync flag
     */
    public function testFeatureEnabledEmailNotificationOnTestWithSync()
    {
        Mail::fake();

        $this->addFeatures(Mode::TEST, true, ['dummy', 'marketplace']);

        Mail::assertSent(FeatureEnabledEmail::class, function ($mail)
        {
            $feature       = 'Route';
            $this->assertEquals($feature, $mail->viewData['feature']);

            $documentation = 'route';
            $this->assertEquals($documentation, $mail->viewData['documentation']);

            return true;
        });
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
    public function testUpdateMerchantUnEditableFeatures()
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
    public function testAddMerchantUnEditableFeatures()
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
        $this->createMerchantDetailsOnLive();

        $this->createMarketplaceOnboardingResponse();

        $this->ba->adminAuth('test', null, 'org_100000razorpay');

        $this->updateMarketplaceOnboardingResponse();

//        $this->ba->proxyAuthLive();
        $id = '10000000001017';
        $this->ba->proxyAuth('rzp_live_' . $id);

        $this->startTest();
    }

//    public function testResendOnboardingResponses()
//    {
//        $this->createMerchantDetailsOnLive();
//
//        $this->createMarketplaceOnboardingResponse();
//
//        $this->createMarketplaceOnboardingResponse(true);
//    }

    public function testGetAllOnboardingResponsesByStatus()
    {
        $this->createMerchantDetailsOnLive();

        $this->createMarketplaceOnboardingResponse();

        $this->addFeatures('live', true, [Constants::MARKETPLACE], '10000000001017');

        $this->ba->adminAuth('live', null, 'org_100000razorpay');

        $this->updateMarketplaceOnboardingResponseStatus('approved');

        $this->verifyMarketplaceOnboardingResponseApproval();
    }

    public function testUpdateOnboardingRequestStatus()
    {
        $this->createMarketplaceOnboardingResponse();

        $this->ba->adminAuth('live', null, 'org_100000razorpay');

        $this->updateMarketplaceOnboardingResponseStatus('pending');

        $this->updateMarketplaceOnboardingResponseStatus('rejected');

        $this->addFeature(Mode::LIVE, false);

        $this->updateMarketplaceOnboardingResponseStatus('approved');
    }

    public function testApproveOnboardingRequestWithoutEnablingFeature()
    {
        $this->createMarketplaceOnboardingResponse();

        $this->ba->adminAuth('live', null, 'org_100000razorpay');

        $this->updateMarketplaceOnboardingResponseStatus('pending');

        $this->updateMarketplaceOnboardingResponseStatus('rejected');

        $this->addFeature(Mode::LIVE, false);

        $this->updateMarketplaceOnboardingResponseStatus('approved');

    }

    public function createMarketplaceOnboardingResponse(bool $expectError = false)
    {
        $id = '10000000001017';

        $this->ba->proxyAuth('rzp_live_' . $id);

        $testData = $this->testData[__FUNCTION__];

        if ($expectError === true)
        {
            $testData['response'] = [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => PublicErrorDescription::BAD_REQUEST_CANNOT_RESEND_FEATURE_ONBOARDING_RESPONSES
                    ],
                ],
                'status_code' => 400,
            ];

            $testData['exception'] = [
                'class' => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_CANNOT_RESEND_FEATURE_ONBOARDING_RESPONSES,
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

    public function updateMarketplaceOnboardingResponse()
    {
        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response);
    }

    public function verifyMarketplaceOnboardingResponseApproval()
    {
        $this->startTest();
    }

    public function updateMarketplaceOnboardingResponseStatus(string $status)
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['status'] = $status;

        $request = $testData['request'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response);
    }

    private function createMerchantDetailsOnLive()
    {
        $id = '10000000001017';

        $attributes = ['id' => $id, 'org_id' => Org::RZP_ORG];

        $detailsAttributes = ['merchant_id' => $id];

        $merchant = $this->fixtures->on('live')->create('merchant', $attributes);

        $this->fixtures->on('live')->create('merchant_detail:sane', $detailsAttributes);

        $this->ba->proxyAuth('rzp_live_' . $id);
    }

}
