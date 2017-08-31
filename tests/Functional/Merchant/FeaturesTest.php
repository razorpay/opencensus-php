<?php

namespace RZP\Tests\Functional\Merchant;

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
            "name"          => 'dummy',
            "entity_id"     => '10000000000000',
            "entity_type"   => 'merchant'
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
     * Add a feature to the test database
     * Get the features from the live database
     * Verify - The feature added to test should not be added to live
     */
    public function testAddFeatureToTestAndVerifyLive()
    {
        $this->addFeatureToMode1AndVerifyMode2('test');
    }
    /**
     * Add a feature to the live database
     * Get the features from the test database
     * Verify - The feature added to live should be added to test as well
     */
    public function testAddFeatureToLiveAndVerifyTest()
    {
        $this->addFeatureToMode1AndVerifyMode2('live');
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
    private function addFeatureToMode1AndVerifyMode2($addToMode)
    {
        $featureName = "dummy";

        $method = 'appAuth' . studly_case($addToMode);

        $this->ba->$method();

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

        $content = $this->makeRequestAndGetContent($request);

        if ($addToMode === 'test')
        {
            $this->ba->appAuthLive();
        }
        else
        {
            $this->ba->appAuthTest();
        }

        $request          = [
            'url'    => '/features/10000000000000',
            'method' => 'get',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ];

        $content          = $this->makeRequestAndGetContent($request);

        $assignedFeatures = array_map(
            function ($feature)
            {
                return $feature["name"];
            },
            $content["assigned_features"]
        );

        if ($addToMode === 'test')
        {
            // Check if not in array
            $this->assertFalse(in_array($featureName, $assignedFeatures));
        }
        else
        {
            // Check if in array
            $this->assertTrue(in_array($featureName, $assignedFeatures));
        }
        $this->ba->appAuth();
    }
    /**
     * Add a feature to live [adds to both, test and live]
     * Delete a feature from the test database
     * Get the features from the live database
     * Verify - The feature deleted from test should not be deleted from live
     */
    public function testDeleteFeatureFromTestAndVerifyLive()
    {
        $this->deleteFeatureFromMode1AndVerifyMode2('test');
    }
    /**
     * Add a feature to live [adds to both, test and live]
     * Delete a feature from the live database
     * Get the features from the test database
     * Verify - The feature deleted from live should not be deleted from test
     */
    public function testDeleteFeatureFromLiveAndVerifyTest()
    {
        $this->deleteFeatureFromMode1AndVerifyMode2('live');
    }
    /**
     * Add a feature to live [adds to both, test and live]
     * Delete a feature from the database linked to mode1
     * Get the features from the database linked to mode2
     * Verify - The feature deleted from database1 should not be deleted from database2
     *
     * @param string $deleteFromMode
     */
    private function deleteFeatureFromMode1AndVerifyMode2($deleteFromMode)
    {
        $featureName = "dummy";
        $this->ba->appAuthLive();
        $request = [
            'url'       => '/features',
            'method'    => 'post',
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
        $content = $this->makeRequestAndGetContent($request);
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
        $content = $this->makeRequestAndGetContent($request);
        if ($deleteFromMode === 'test')
        {
            $this->ba->appAuthLive();
        }
        else
        {
            $this->ba->appAuthTest();
        }
        $request = [
            'url'       => '/features/10000000000000',
            'method'    => 'get',
            'server' => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-User-Email'     => 'user@rzp.dev',
            ],
        ];
        $content = $this->makeRequestAndGetContent($request);
        $assignedFeatures = array_map (
            function($feature)
            {
                return $feature["name"];
            },
            $content["assigned_features"]
        );
        // Check if in array
        $this->assertTrue(in_array($featureName, $assignedFeatures));
        $this->ba->appAuth();
    }
}
