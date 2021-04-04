<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Models\Batch;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;

class HitachiBulkTerminalTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/HitachiBulkTerminalTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testBulkTerminalCreationValidateFile()
    {
        $this->ba->proxyAuth();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBulkTerminalCreation()
    {
        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $batch['processed_count']);
        $this->assertEquals(2, $batch['success_count']);
        $this->assertEquals(1, $batch['failure_count']);

        $count = $batch['success_count'];

        $terminals = $this->getEntities('terminal', ['count' => $count], true);

        $this->assertEquals($terminals['items'][0][Terminal\Entity::MERCHANT_ID], $entries[1][Batch\Header::HITACHI_MERCHANT_ID]);
        $this->assertEquals($terminals['items'][0][Terminal\Entity::CATEGORY], $entries[1][Batch\Header::HITACHI_MCC]);
        $this->assertEquals($terminals['items'][0][Terminal\Entity::GATEWAY_TERMINAL_ID], $entries[1][Batch\Header::HITACHI_TID]);
        $this->assertEquals($terminals['items'][0][Terminal\Entity::GATEWAY_MERCHANT_ID], $entries[1][Batch\Header::HITACHI_MID]);

        $this->assertEquals($terminals['items'][1][Terminal\Entity::MERCHANT_ID], $entries[0][Batch\Header::HITACHI_MERCHANT_ID]);
        $this->assertEquals($terminals['items'][1][Terminal\Entity::CATEGORY], $entries[0][Batch\Header::HITACHI_MCC]);
        $this->assertEquals($terminals['items'][1][Terminal\Entity::GATEWAY_TERMINAL_ID], $entries[0][Batch\Header::HITACHI_TID]);
        $this->assertEquals($terminals['items'][1][Terminal\Entity::GATEWAY_MERCHANT_ID], $entries[0][Batch\Header::HITACHI_MID]);

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                Batch\Header::HITACHI_RID          => '1111',
                Batch\Header::HITACHI_MERCHANT_ID  => '10NodalAccount',
                Batch\Header::HITACHI_SUB_IDS      => '10000000000000',
                Batch\Header::HITACHI_MID          => '1252836',
                Batch\Header::HITACHI_TID          => '12313',
                Batch\Header::HITACHI_PART_NAME    => 'partname',
                Batch\Header::HITACHI_ME_NAME      => 'mename',
                Batch\Header::HITACHI_ZIPCODE      => 'zipcode',
                Batch\Header::HITACHI_LOCATION     => 'location',
                Batch\Header::HITACHI_CITY         => 'city',
                Batch\Header::HITACHI_STATE        => 'state',
                Batch\Header::HITACHI_COUNTRY      => 'country',
                Batch\Header::HITACHI_MCC          => '3323',
                Batch\Header::HITACHI_TERM_STATUS  => 'termstatus',
                Batch\Header::HITACHI_ME_STATUS    => 'mestatus',
                Batch\Header::HITACHI_ZIPCODE      => 'zipcode',
                Batch\Header::HITACHI_SWIPER_ID    => 'swiperid',
                Batch\Header::HITACHI_SPONSOR_BANK => 'sponsorbank',
                Batch\Header::HITACHI_CURRENCY     => 'INR',
            ],
            [
                Batch\Header::HITACHI_RID          => '2222',
                Batch\Header::HITACHI_MERCHANT_ID  => '100000Razorpay',
                Batch\Header::HITACHI_SUB_IDS      => '',
                Batch\Header::HITACHI_MID          => '2222836',
                Batch\Header::HITACHI_TID          => '1233313',
                Batch\Header::HITACHI_PART_NAME    => 'partname',
                Batch\Header::HITACHI_ME_NAME      => 'mename',
                Batch\Header::HITACHI_ZIPCODE      => 'zipcode',
                Batch\Header::HITACHI_LOCATION     => 'location',
                Batch\Header::HITACHI_CITY         => 'city',
                Batch\Header::HITACHI_STATE        => 'state',
                Batch\Header::HITACHI_COUNTRY      => 'country',
                Batch\Header::HITACHI_MCC          => '1323',
                Batch\Header::HITACHI_TERM_STATUS  => 'termstatus',
                Batch\Header::HITACHI_ME_STATUS    => 'mestatus',
                Batch\Header::HITACHI_ZIPCODE      => 'zipcode',
                Batch\Header::HITACHI_SWIPER_ID    => 'swiperid',
                Batch\Header::HITACHI_SPONSOR_BANK => 'sponsorbank',
                Batch\Header::HITACHI_CURRENCY     => 'USD',
            ],
            // Should Fail
            [
                Batch\Header::HITACHI_RID          => '233',
                Batch\Header::HITACHI_MERCHANT_ID  => '100000Razorpay',
                Batch\Header::HITACHI_SUB_IDS      => '100DemoAccount,100AtomAccount,10000000000000',
                Batch\Header::HITACHI_MID          => '1252836',
                Batch\Header::HITACHI_TID          => '12313',
                Batch\Header::HITACHI_PART_NAME    => 'partname',
                Batch\Header::HITACHI_ME_NAME      => 'mename',
                Batch\Header::HITACHI_ZIPCODE      => 'zipcode',
                Batch\Header::HITACHI_CITY         => 'city',
                Batch\Header::HITACHI_STATE        => 'state',
                Batch\Header::HITACHI_COUNTRY      => 'country',
                Batch\Header::HITACHI_TERM_STATUS  => 'termstatus',
                Batch\Header::HITACHI_ME_STATUS    => 'mestatus',
                Batch\Header::HITACHI_ZIPCODE      => 'zipcode',
                Batch\Header::HITACHI_SWIPER_ID    => 'swiperid',
                Batch\Header::HITACHI_SPONSOR_BANK => 'sponsorbank',
                Batch\Header::HITACHI_CURRENCY     => 'hitachicurrency',
            ],
        ];
    }
}
