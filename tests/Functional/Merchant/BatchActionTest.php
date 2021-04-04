<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class BatchActionTest extends OAuthTestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BatchActionTestData.php';

        parent::setUp();
    }

    public function testUpdateFieldsAction()
    {
        $this->fixtures->create('merchant_detail',["merchant_id" => '10000000000000']);

        $this->ba->batchAppAuth();

        $this->startTest();

        $merchant_detail1 = $this->getDbEntityById('merchant_detail', '10000000000000');

        $this->assertEquals('suresh', $merchant_detail1['business_name']);
        $this->assertEquals('local suresh address', $merchant_detail1['business_registered_address']);

        $merchant_detail2 = $this->getDbEntityById('merchant_detail', '100000Razorpay');

        $this->assertEquals('mukesh', $merchant_detail2['business_name']);
        $this->assertEquals('local mukesh address', $merchant_detail2['business_registered_address']);
    }


    public function testMerchantSuspendBatch()
    {
        $merchant = $this->getEntityById('merchant', '10000000000000',true);

        $this->ba->batchAppAuth();

        $this->startTest();

        $merchant = $this->getEntityById('merchant', $merchant['id'], true);

        $this->assertNotNull($merchant['suspended_at']);
    }

    public function testMerchantAlreadySuspendMerchantBatch()
    {
        $merchant = $this->getEntityById('merchant', '10000000000000',true);

        $this->fixtures->base->editEntity('merchant', $merchant['id'], [ 'suspended_at' => '123456789' ]);

        $this->ba->batchAppAuth();

        $this->startTest();

        $merchant = $this->getEntityById('merchant', $merchant['id'], true);

        $this->assertNotNull($merchant['suspended_at']);
    }

    public function testMerchantUnsuspendBatch()
    {
        $merchant = $this->getEntityById('merchant', '10000000000000',true);

        $this->fixtures->base->editEntity('merchant', $merchant['id'], [ 'suspended_at' => '123456789' ]);

        $this->ba->batchAppAuth();

        $this->startTest();

        $merchant = $this->getEntityById('merchant', $merchant['id'], true);

        $this->assertNull($merchant['suspended_at']);
    }

    public function testMerchantAlreadyUnuspendMerchantBatch()
    {
        $merchant = $this->getEntityById('merchant', '10000000000000',true);

        $this->fixtures->base->editEntity('merchant', $merchant['id'], ['suspended_at' => null]);

        $this->ba->batchAppAuth();

        $this->startTest();

        $merchant = $this->getEntityById('merchant', $merchant['id'], true);

        $this->assertNull($merchant['suspended_at']);
    }


    public function testUpdateFieldsInvalidAction()
    {
        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testUpdateFieldsInvalidEntity()
    {
        $this->ba->batchAppAuth();

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
