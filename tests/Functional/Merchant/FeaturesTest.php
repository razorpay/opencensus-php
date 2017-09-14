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
     * Add an opt-in feature and an opt-out feature to the test database
     * Get the features from the live database
     * Verify - Any feature added to test, should not be synced to live
     */
    public function testAddFeatureToTestVerifyAbsenceInLive()
    {
        $optInFeature  = FeatureConstants::DUMMY;

        $optOutFeature = FeatureConstants::NOFLASHCHECKOUT;

        $this->addFeatureToMode($optInFeature, Mode::TEST);

        $this->addFeatureToMode($optOutFeature, Mode::TEST);

        $this->ba->appAuthLive();

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->makeRequestAndGetContent($request);

        $assignedFeatures = $response['assigned_features'];

        $this->assertEquals(count($assignedFeatures), 0);
    }

    /**
     * Add an opt-in feature to live [syncs to test]
     * Add an opt-in feature to live [does not sync to test] and then to test
     * Delete the opt-in feature from the test database
     * Delete the opt-out feature from the test database
     * Get the features from the live database
     * Verify - Any feature deleted from test should not be deleted from live
     */
    public function testDeleteFeatureFromTestAndVerifyPresenceInLive()
    {
        $optInFeature  = FeatureConstants::DUMMY;

        $optOutFeature = FeatureConstants::NOFLASHCHECKOUT;

        $this->addFeatureToMode($optInFeature, Mode::LIVE);

        $this->addFeatureToMode($optOutFeature, Mode::LIVE);

        $this->addFeatureToMode($optOutFeature, Mode::TEST);

        $this->deleteFeatureFromMode($optInFeature, Mode::TEST);

        $this->deleteFeatureFromMode($optOutFeature, Mode::TEST);

        $this->ba->appAuthLive();

        $this->startTest();
    }

    /**
     * Add a feature to the live database
     * Get the features from the test database
     * Verify - The feature added to live should be added to test as well
     */
    public function testAddOptOutFeatureToLiveVerifyAbsenceInTest()
    {
        $optInFeature = FeatureConstants::NOFLASHCHECKOUT;

        $this->addFeatureToMode($optInFeature, 'live');

        $this->ba->appAuthTest();

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->makeRequestAndGetContent($request);

        $assignedFeatures = $response['assigned_features'];

        $this->assertEquals(count($assignedFeatures), 0);
    }

    /**
     * Add a feature to the live database
     * Get the features from the test database
     * Verify - The feature added to live should be added to test as well
     */
    public function testDeleteOptInFeatureFromLiveVerifyPresenceInTest()
    {
        $optInFeature = FeatureConstants::DUMMY;

        // This will also add it to test
        $this->addFeatureToMode($optInFeature, 'live');

        $this->deleteFeatureFromMode($optInFeature, 'live');

        $this->ba->appAuthTest();

        $this->startTest();
    }

    /**
     * Add a feature to the live database
     * Get the features from the test database
     * Verify - The feature added to live should be added to test as well
     */
    public function testDeleteOptOutFeatureFromLiveVerifyAbsenceInTest()
    {
        $optOutFeature = FeatureConstants::NOFLASHCHECKOUT;

        $this->addFeatureToMode($optOutFeature, 'test');

        $this->addFeatureToMode($optOutFeature, 'live');

        $this->deleteFeatureFromMode($optOutFeature, 'live');

        $this->ba->appAuthTest();

        $request = $this->testData[__FUNCTION__]['request'];

        $response = $this->makeRequestAndGetContent($request);

        $assignedFeatures = $response['assigned_features'];

        $this->assertEquals(count($assignedFeatures), 0);
    }

    /**
     * Add a feature to the database which is linked to the mode passed as parameter
     * Get the features from the second database
     * Verify -
     *  If added to live - The feature should be added to test as well
     *  If added to test - The feature should not be added to live
     *
     * @param $addToMode
     */
    private function addFeatureToMode(string $featureName, string $addToMode)
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

        $this->makeRequestAndGetContent($request);
    }

    /**
     * Delete a feature from the database linked to mode received
     * Verify - The feature deleted from database1 should not be deleted from database2
     *
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
}
