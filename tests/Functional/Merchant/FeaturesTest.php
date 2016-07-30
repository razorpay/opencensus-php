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
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testResetFeatureForMerchant()
    {
        $this->fixtures->merchant->editFeatures("cardsaving,tokens");

        $this->ba->appAuth();

        $this->startTest();

        $merchant = $this->getEntityById('merchant', '10000000000000', true);

        $this->assertEquals($merchant['features'], []);
    }

    public function testAddInvalidFeatureToMerchant()
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