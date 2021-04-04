<?php

namespace RZP\Tests\Functional\Batch;

use Mockery;
use RZP\Constants\Entity;
use RZP\Models\Batch;
use RZP\Tests\Functional\TestCase;

class MpanBatchTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/MpanBatchTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    /**
     * tests if batch processing is done in api service and not via batch service
     * BatchMicroService's mock isCompletelyMigratedBatchType will return false, so api flow would be tested 
     */
    public function testMpanCreationBatch()
    {
        $entries = $this->getDefaultFileEntries();

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

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

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $batch['processed_count']);

        $this->assertEquals(1, $batch['success_count']);

        $this->assertEquals(1, $batch['failure_count']);

        $mpans = $this->getDbEntities(Entity::MPAN);
         
        $this->assertEquals(3, $mpans->count()); // only one row will get processed
    }

    public function testMpanCreationInvalidMpan()
    {
        $entries = $this->getDefaultFileEntries();

        $entries[0][Batch\Header::MPAN_RUPAY_PAN] = '6123456'; // expecting 16 digits

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

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

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $batch['processed_count']);

        $this->assertEquals(1, $batch['success_count']);

        $this->assertEquals(1, $batch['failure_count']);

        $mpans = $this->getDbEntities(Entity::MPAN);

        $this->assertEquals(3, $mpans->count());
    }

    // tests the mpans batch completely migrated to batch service,
    // response should be as returned by batch mock and batch file should have encrypted values
    public function testMpanCreationBatchMigrated()
    {
        $batch = Mockery::mock('RZP\Services\Mock\BatchMicroService')->makePartial();

        $this->app->instance('batchService', $batch);

        $encryptedData = $this->testData[__FUNCTION__]['encrypted_data'];

        $batch->shouldReceive('isCompletelyMigratedBatchType')
            ->andReturnUsing(function (string $type)
            {
                return true;
            });

        $batch->shouldReceive('forwardToBatchServiceRequest')
            ->andReturnUsing(function (array $input, $merchant, $ufhFile) use ($encryptedData)
            {
                $rows = $this->parseCsvFile($ufhFile->getFullFilePath());

                // assert that sensitive headers(mpans) are actually encrypted 
                $this->assertArraySelectiveEquals($rows, $encryptedData);

                return [
                    'id'               => 'Ev6Ob5J8kaMV6o',
                    'created_at'       => 1590521524,
                    'updated_at'       => 1590521524,
                    'entity_id'        => '100000Razorpay',
                    'name'             =>  null,
                    'batch_type_id'    => 'mpan',
                    'type'             => 'mpan',
                    'is_scheduled'     => false,
                    'upload_count'     => 0,
                    'total_count'      => 3,
                    'failure_count'    => 0,
                    'success_count'    => 0,
                    'amount'           => 0,
                    'attempts'         => 0,
                    'status'           => 'created',
                    'processed_amount' => 0
                ];
            });

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();
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
