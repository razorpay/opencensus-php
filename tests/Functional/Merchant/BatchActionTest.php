<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class BatchActionTest extends OAuthTestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BatchActionTestData.php';

        parent::setUp();
    }

    public function testUpdateFieldsAction()
    {
        $this->fixtures->create('merchant_detail',["merchant_id" => '10000000000000']);

        $this->ba->proxyAuth();

        $this->startTest();

        $merchant_detail1 = $this->getDbEntityById('merchant_detail', '10000000000000');

        $this->assertEquals('suresh', $merchant_detail1['business_name']);
        $this->assertEquals('local suresh address', $merchant_detail1['business_registered_address']);

        $merchant_detail2 = $this->getDbEntityById('merchant_detail', '100000Razorpay');

        $this->assertEquals('mukesh', $merchant_detail2['business_name']);
        $this->assertEquals('local mukesh address', $merchant_detail2['business_registered_address']);
    }

    public function testUpdateFieldsInvalidAction()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdateFieldsInvalidEntity()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetBatchActions()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetBatchActionEntities()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }
}
