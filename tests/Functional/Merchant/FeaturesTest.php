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
        $features = $this->fixtures->merchant->addFeatures(['dummy']);

        $request = [
            'url'       => '/features/'.$features->first()->getId(),
            'method'    => 'delete'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            "id"            => (string)$features->first()->getId(),
            "name"          => 'dummy',
            "entity_id"     => '10000000000000',
            "entity_type"   => 'RZP\\Models\\Merchant\\Entity'
        ];

        $this->assertArraySelectiveEquals($resultData, $content);
    }

    public function testMigrateMerchantFeature()
    {
        $merch1 = $this->fixtures->create('merchant', ['id' => '10000000000001',
                    'features' => 'dummy']);
        $merch2 = $this->fixtures->create('merchant', ['id' => '10000000000002',
                    'features' => 'dummy']);
        $merch3 = $this->fixtures->create('merchant', ['id' => '10000000000003',
                    'features' => 'dummy']);

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

        $this->fixtures->merchant->editFeatures('dummy');
        $this->fixtures->merchant->addFeatures(['dummy']);

        $this->ba->privateAuth();

        $this->startTest();
    }
}
