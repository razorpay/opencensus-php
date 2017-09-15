<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Constants\Mode;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class FeaturesTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/FeaturesTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testAddFeatureToMerchant()
    {
        $this->startTest();
    }

    public function testAddInvalidFeatureToMerchant()
    {
        $this->startTest();
    }

    public function testAddDuplicateFeatureToMerchant()
    {
        $this->testAddFeatureToMerchant();

        $this->startTest();
    }

    public function testDeleteFeatureFromMerchant()
    {
        $this->ba->adminAuth('test', null, 'org_100000razorpay');

        $features = $this->fixtures->merchant->addFeatures(['dummy']);

        $request = [
            'url'       => '/features/10000000000000/dummy',
            'method'    => 'delete',
            'server' => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-User-Email'     => 'user@rzp.dev',
            ],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            "id"            => (string) $features->first()->getId(),
            "deleted"       => true,
        ];

        $this->assertArraySelectiveEquals($resultData, $content);
    }

    public function testDeleteNonExistentFeatureFromMerchant()
    {
        $this->ba->adminAuth('test', null, 'org_100000razorpay');

        $this->startTest();
    }

    public function testMultiAssignFeature()
    {
        $merch1 = $this->fixtures->create('merchant', ['id' => '10000000000001']);
        $merch2 = $this->fixtures->create('merchant', ['id' => '10000000000002']);
        $merch3 = $this->fixtures->create('merchant', ['id' => '10000000000003']);

        $this->startTest();
    }

    public function testMultiRemoveFeature()
    {
        $merch1 = $this->fixtures->create('feature', ['entity_id' => '10000000000001',
                    'name' => 'dummy']);
        $merch2 = $this->fixtures->create('feature', ['entity_id' => '10000000000002',
                    'name' => 'dummy']);
        $merch3 = $this->fixtures->create('feature', ['entity_id' => '10000000000003',
                    'name' => 'dummy']);

        $this->startTest();
    }

    public function testGetFeatureListForMerchant()
    {
        $this->testAddFeatureToMerchant();

        $this->startTest();
    }

    public function testDummyFeatureRouteWithoutAccess()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testDummyFeatureRouteWithAccess()
    {
        $this->fixtures->merchant->addFeatures(['dummy']);

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
        $featureName  = FeatureConstants::DUMMY;

        $this->addFeature($featureName, Mode::TEST);

        $this->verifyFeatureAbsence(Mode::LIVE);
    }

    /**
     * Add a feature to live
     * Get the features from the test database
     * Verify - Any feature added to live should not be added to test
     */
    public function testAddFeatureToLiveVerifyAbsenceInTest()
    {
        $featureName  = FeatureConstants::DUMMY;

        $this->addFeature($featureName, Mode::LIVE);

        $this->verifyFeatureAbsence(Mode::TEST);
    }

    /**
     * Add a feature to the test database and sync it to live
     * Get the features from the live database
     * Verify - Any feature added to test with the should_sync flag, should be synced to live
     */
    public function testAddFeatureToTestSyncedToLive()
    {
        $featureName  = FeatureConstants::DUMMY;

        $this->addFeature($featureName, Mode::TEST, true);

        $this->verifyFeaturePresence(Mode::LIVE);
    }

    /**
     * Add a feature to the test database
     * Add a feature to the live database and sync it to test
     * Get the features from the live database
     * Verify - Any feature added to test with the should_sync flag, should be synced to live
     */
    public function testAddFeatureToTestAddFeatureToLiveSyncedToTest()
    {
        $featureName  = FeatureConstants::DUMMY;

        $this->addFeature($featureName, Mode::TEST);

        $this->addFeature($featureName, Mode::LIVE, true);

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
        $featureName  = FeatureConstants::DUMMY;

        // This step also tests for testAddFeatureToLiveAndSyncToTest
        $this->addFeature($featureName, Mode::LIVE, true);

        $this->deleteFeatureFromMode($featureName, Mode::TEST);

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
        $featureName  = FeatureConstants::DUMMY;

        // This step also tests for testAddFeatureToLiveAndSyncToTest
        $this->addFeature($featureName, Mode::LIVE, true);

        $this->deleteFeatureFromMode($featureName, Mode::LIVE);

        $this->verifyFeaturePresence(Mode::TEST);
    }

    /**
     * Adds a feature to the database which is linked to the mode passed as parameter.
     *
     * @param string $featureName
     * @param string $addToMode
     * @param bool   $shouldSync
     */
    private function addFeature(string $featureName, string $addToMode, bool $shouldSync = false)
    {
        $authMethod = 'appAuth' . studly_case($addToMode);

        $this->ba->$authMethod();

        $request = [
            'url'     => '/features',
            'method'  => 'post',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
            'content' => [
                'names'       => [$featureName],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ];

        if ($shouldSync === true)
        {
            $request['content']['should_sync'] = 1;
        }

        $this->makeRequestAndGetContent($request);
    }

    /**
     * Deletes a feature from the database linked to mode received
     *
     * @param string $deleteFromMode
     * @param string $deleteFromMode
     */
    private function deleteFeatureFromMode(string $featureName, string $deleteFromMode)
    {
        $this->ba->adminAuth($deleteFromMode, null, 'org_100000razorpay');

        $request = [
            'url'       => "/features/10000000000000/$featureName",
            'method'    => 'delete',
            'server' => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-User-Email'     => 'user@rzp.dev',
            ],
            'content'   => [
                'names'             => [$featureName],
                'entity_type'       => 'merchant',
                'entity_id'         => '10000000000000'
            ]
        ];

        $this->makeRequestAndGetContent($request);
    }

    /**
     * Performs a GET request based on the mode received and verifies the
     * absence of the dummy feature
     *
     * @param string $feature
     * @param string $mode
     */
    private function verifyFeatureAbsence($mode)
    {
        $authMethod = 'appAuth' . studly_case($mode);

        $this->ba->$authMethod();

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->makeRequestAndGetContent($request);

        $assignedFeatures = $response['assigned_features'];

        $this->assertEquals(0, count($assignedFeatures));
    }

    /**
     * Performs a GET request based on the mode received and verifies the
     * presence of the dummy feature
     *
     * @param string $mode
     */
    private function verifyFeaturePresence($mode)
    {
        $authMethod = 'appAuth' . studly_case($mode);

        $this->ba->$authMethod();

        $this->startTest();
    }
}
