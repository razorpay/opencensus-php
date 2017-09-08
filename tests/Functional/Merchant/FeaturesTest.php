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
}
