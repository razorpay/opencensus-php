<?php

namespace Tests\Functional\Merchant;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

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
        $this->ba->appAuth();
        $this->startTest();
    }

    public function testGetFeatureListForMerchant()
    {
        $this->testAddFeatureToMerchant();
        $this->startTest();
    }

    public function testGetAllFeatures()
    {
        $this->startTest();
    }

    public function testDummyFeatureRouteWithoutAccess()
    {
        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testDummyFeatureRouteWithAccess()
    {
        $this->testAddFeatureToMerchant();
        $this->ba->privateAuth();
        $this->startTest();
    }
}