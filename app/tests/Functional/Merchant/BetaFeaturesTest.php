<?php

namespace Tests\Functional\Merchant;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class BetaFeaturesTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/BetaFeaturesTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testAddBetaFeatureToMerchant()
    {
        $this->startTest();
    }

    public function testGetBetaFeatureListForMerchant()
    {
        $this->testAddBetaFeatureToMerchant();
        $this->startTest();
    }

    public function testGetAllFeatures()
    {
        $this->startTest();
    }

    public function testDummyBetaFeatureRouteWithoutAccess()
    {
        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testDummyBetaFeatureRouteWithAccess()
    {
        $this->testAddBetaFeatureToMerchant();
        $this->ba->privateAuth();
        $this->startTest();
    }
}