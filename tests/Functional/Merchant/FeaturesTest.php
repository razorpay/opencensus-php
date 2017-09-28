<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;
use Illuminate\Http\UploadedFile;

use RZP\Constants\Mode;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
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
     * Adds dummy feature to the database which is linked to the mode passed as parameter.
     *
     */
    private function addFeatures(
        string $addToMode,
        bool $shouldSync = false,
        array $featureNames = ['dummy'])
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

        $this->startTest($testData);
    }

    /**
     * Deletes a feature from the database linked to mode received
     *
     * @param string $deleteFromMode
     * @param bool   $shouldSync
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
     * Performs a GET request based on the mode received and verifies the
     * presence of the dummy feature
     *
     * @param string $mode
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
     * absence of the dummy feature
     *
     * @param string $mode
     * @param array $features
     */
    private function verifyFeatureAbsence(
        string $mode,
        array $featureNames = ['dummy'])
    {
        $authMethod = 'appAuth' . studly_case($mode);

        $this->ba->$authMethod();

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->makeRequestAndGetContent($request);

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
     * Get the features from the live database
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
     * Get the features from the test database
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
     * Get the features from the live database
     * Verify - Any feature added to test with the should_sync flag, should be synced to live
     */
    public function testAddFeatureToTestSyncedToLive()
    {
        $this->addFeatures(Mode::TEST,true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);
    }

    /**
     * Add a feature to the live database and sync it to test
     * Get the features from the test database
     * Verify - Any feature added to live with the should_sync flag, should be synced to test
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
     * Get the features from the live database
     * Verify - Any feature added to live with the should_sync flag, should not
     * fail even if it is already present in test
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
     * Get the features from the test database
     * Verify - Any feature added to test with the should_sync flag, should not
     * fail even if it is already present in live
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
     * Get the features from the test database
     * Verify - Any feature added to live with the should_sync flag, should not
     * fail even if it is already present in live. It should add it to test
     */
    public function testAddFeatureToLiveAddFeatureToLiveSyncedToTest()
    {
        $this->addFeatures(Mode::LIVE);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);
    }

    /**
     * Add a feature to the live database
     * Add a feature to the live database and sync it to test
     * Get the features from the test database
     * Verify - Any feature added to live with the should_sync flag, should not
     * fail even if it is already present in live. It should add it to test
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
     * Get the features from the live database
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
     * Get the features from the test database
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
     * Get the features from the live as well as test
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
     * Get the features from the test as well as live
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
     * Get the features from the test as well as live
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
     * Get the features from the live as well as test
     * Verify - Deleting the feature from test with sync, should not
     * fail even if the feature does not exist on test. Feature should be
     * deleted from live
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
     *
     * @param string $addToMode
     * @param bool   $shouldSync
     */
    public function testAddFeatureNonEditableByMerchantOnLive()
    {
        $this->addFeatureNonEditableByMerchantOnLive(Mode::LIVE, true);
    }

    /**
     * Adds subscriptions feature to the test database
     * Since the route is accessed by an admin, it should
     * allow even when should sync is sent as 1.
     *
     * @param string $addToMode
     * @param bool   $shouldSync
     */
    public function testAddFeatureNonEditableByMerchantOnTest()
    {
        $this->addFeatureNonEditableByMerchantOnLive(Mode::TEST, true);
    }

    protected function addFeatureNonEditableByMerchantOnLive(
        string $addToMode,
        bool $shouldSync = false)
    {
        $authMethod = 'appAuth' . studly_case($addToMode);

        $this->ba->$authMethod();

        $testData = $this->testData[__FUNCTION__];

        if ($shouldSync === true)
        {
            $testData['request']['content']['should_sync'] = 1;
        }

        $this->startTest($testData);
    }

    public function testGetOnboardingQuestions()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testpostOnboardingResponses()
    {
        $this->ba->proxyAuth();

        //$url = "storage/files/" . Constants::ONBOARDING .  "/" . Constants::VENDOR_AGREEMENT . ".pdf";

        //$uploadedFile = $this->createUploadedFile($url);

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        //$request['content'][Constants::VENDOR_AGREEMENT] = $uploadedFile;

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response);

        $testData = $this->testData['getOnboardingResponses'];

        $request = $testData['request'];

        $expectedResponse = $testData['response']['content'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    protected function createUploadedFile(string $url): UploadedFile
    {
        $mime = 'application/pdf';

        return new UploadedFile(
            $url,
            'file',
            $mime,
            filesize($url),
            null,
            true);
    }

    public function testFeatureEnabledEmailNotificationOnLive()
    {
        Mail::fake();

        $this->addNotifyFeatures(Mode::LIVE, false);

        Mail::assertSent(FeatureEnabledEmail::class, function ($mail)
        {
            $feature       = 'Route';
            $this->assertEquals($feature, $mail->viewData['feature']);

            $documentation = 'route';
            $this->assertEquals($documentation, $mail->viewData['documentation']);

            return true;
        });
    }

    public function testFeatureEnabledEmailNotificationOnTest()
    {
        Mail::fake();

        $this->addNotifyFeatures(Mode::TEST, false);

        Mail::assertNotSent(FeatureEnabledEmail::class);
    }

    public function testFeatureEnabledEmailNotificationOnTestWithSync()
    {
        Mail::fake();

        $this->addNotifyFeatures(Mode::TEST, true);

        Mail::assertSent(FeatureEnabledEmail::class, function ($mail)
        {
            $feature       = 'Route';
            $this->assertEquals($feature, $mail->viewData['feature']);

            $documentation = 'route';
            $this->assertEquals($documentation, $mail->viewData['documentation']);

            return true;
        });
    }

    public function testFeatureEnabledEmailNonNotify()
    {
        Mail::fake();

        $this->addFeatures(Mode::LIVE, true);

        Mail::assertNotSent(FeatureEnabledEmail::class);
    }

    protected function addNotifyFeatures(string $mode, bool $shouldSync)
    {
        $authMethod = 'appAuth' . studly_case($mode);

        $this->ba->$authMethod();

        $testData = $this->testData[__FUNCTION__];

        if ($shouldSync === true)
        {
            $testData['request']['content']['should_sync'] = 1;
        }

        $this->startTest($testData);
    }
}
