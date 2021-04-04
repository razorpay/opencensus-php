<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail;

class EntityUpdateActionTest extends TestCase
{
    use BatchTestTrait;


    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/EntityUpdateActionTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testEntityUpdateActionBatchCreate()
    {
        $entries = $this->getDefaultFileEntries(2);

        //its response is returned from mock service so it doesn't depend on upload file
        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    protected function getDefaultFileEntries(int $count)
    {
        $records = [
            [
                Detail\Entity::BUSINESS_REGISTERED_ADDRESS => 'address1',
                Detail\Entity::BUSINESS_REGISTERED_STATE   => 'state1',
                Detail\Entity::BUSINESS_NAME               => 'acharya_group',
                Detail\Entity::ID                          => '10000000000000',
            ],
            [
                Detail\Entity::BUSINESS_REGISTERED_ADDRESS => 'address2',
                Detail\Entity::BUSINESS_REGISTERED_STATE   => 'state2',
                Detail\Entity::BUSINESS_NAME               => 'yadav_group',
                Detail\Entity::ID                          => '10000000000001',
            ],
            [
                Detail\Entity::BUSINESS_REGISTERED_ADDRESS => 'address3',
                Detail\Entity::BUSINESS_REGISTERED_STATE   => 'state3',
                Detail\Entity::BUSINESS_NAME               => 'teli_group',
                Detail\Entity::ID                          => '10000000000002',
            ],

        ];

        return array_slice($records, 0, $count);
    }

    public function testEntityUpdateActionBatchCreateInvalidAction()
    {
        $entries = $this->getDefaultFileEntries(1);

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testEntityUpdateActionBatchCreateInvalidEntity()
    {
        $entries = $this->getDefaultFileEntries(1);

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

}
