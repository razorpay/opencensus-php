<?php

namespace RZP\Tests\Functional\Batch;


use RZP\Constants\Entity;
use RZP\Models\Batch;
use RZP\Tests\Functional\TestCase;

class MpanBatchTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/MpanBatchTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }
    public function testMpanCreationBatch()
    {
        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $batch['processed_count']);

        $this->assertEquals(2, $batch['success_count']);

        $this->assertEquals(0, $batch['failure_count']);
    }

    public function testMpanCreationBatchEmptyCell()
    {
        $entries = $this->getDefaultFileEntries();

        $entries[0][Batch\Header::MPAN_RUPAY_PAN] = null;

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $batch['processed_count']);

        $this->assertEquals(2, $batch['success_count']);

        $this->assertEquals(0, $batch['failure_count']);

        $mpans = $this->getDbEntities(Entity::MPAN);

        $this->assertEquals(5, $mpans->count());

    }

    public function testMpanCreationInvalidMpan()
    {
        $entries = $this->getDefaultFileEntries();

        $entries[0][Batch\Header::MPAN_RUPAY_PAN] = '6123456'; // expecting 16 digits

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $batch['processed_count']);

        $this->assertEquals(1, $batch['success_count']);

        $this->assertEquals(1, $batch['failure_count']);

        $mpans = $this->getDbEntities(Entity::MPAN);

        $this->assertEquals(3, $mpans->count());
    }

    public function testMpanCreationExistingMpan()
    {
        $entries = $this->getDefaultFileEntries();

        $entries[0][Batch\Header::MPAN_MASTERCARD_PAN] =  $entries[1][Batch\Header::MPAN_MASTERCARD_PAN];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $batch['processed_count']);

        $this->assertEquals(1, $batch['success_count']);

        $this->assertEquals(1, $batch['failure_count']);

        $mpans = $this->getDbEntities(Entity::MPAN);

        $this->assertEquals(3, $mpans->count());
    }


    protected function getDefaultFileEntries()
    {
        return [
            [
                // we are not consuming this value. so leaving them blank
                Batch\Header::MPAN_SERIAL_NUMBER           => '',
                Batch\Header::MPAN_ADDED_ON                => '',
                Batch\Header::MPAN_VISA_PAN                => '4604901005005799',
                Batch\Header::MPAN_MASTERCARD_PAN          => '5122600005005789',
                Batch\Header::MPAN_RUPAY_PAN               => '6100020005005792',
            ],
            [
                Batch\Header::MPAN_SERIAL_NUMBER           => '',
                Batch\Header::MPAN_ADDED_ON                => '',
                Batch\Header::MPAN_VISA_PAN                => '4604901005005823',
                Batch\Header::MPAN_MASTERCARD_PAN          => '5122600005005813',
                Batch\Header::MPAN_RUPAY_PAN               => '6100020005005826',
            ],
        ];
    }
}
