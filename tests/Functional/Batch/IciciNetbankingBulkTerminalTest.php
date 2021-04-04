<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Models\Batch;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;

class IciciNetbankingBulkTerminalTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/IciciNetbankingBulkTerminalTestData.php';

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

        $this->assertEquals($terminals['items'][0][Terminal\Entity::MERCHANT_ID], $entries[1][Batch\Header::ICIC_NB_MERCHANT_ID]);
        $this->assertEquals($terminals['items'][0][Terminal\Entity::NETWORK_CATEGORY], $entries[1][Batch\Header::ICIC_NB_SECTOR]);
        $this->assertEquals($terminals['items'][0][Terminal\Entity::GATEWAY_MERCHANT_ID], $entries[1][Batch\Header::ICIC_NB_GATEWAY_MID]);
        $this->assertEquals($terminals['items'][0][Terminal\Entity::GATEWAY_MERCHANT_ID2], $entries[1][Batch\Header::ICIC_NB_GATEWAY_MID2]);

        $this->assertEquals($terminals['items'][1][Terminal\Entity::MERCHANT_ID], $entries[0][Batch\Header::ICIC_NB_MERCHANT_ID]);
        $this->assertEquals($terminals['items'][1][Terminal\Entity::NETWORK_CATEGORY], $entries[0][Batch\Header::ICIC_NB_SECTOR]);
        $this->assertEquals($terminals['items'][1][Terminal\Entity::GATEWAY_MERCHANT_ID], $entries[0][Batch\Header::ICIC_NB_GATEWAY_MID]);
        $this->assertEquals($terminals['items'][1][Terminal\Entity::GATEWAY_MERCHANT_ID2], $entries[0][Batch\Header::ICIC_NB_GATEWAY_MID2]);

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                Batch\Header::ICIC_NB_MERCHANT_ID  => '10NodalAccount',
                Batch\Header::ICIC_NB_SUB_IDS      => '10000000000000',
                Batch\Header::ICIC_NB_GATEWAY_MID  => '1233231',
                Batch\Header::ICIC_NB_GATEWAY_MID2 => '23443',
                Batch\Header::ICIC_NB_SECTOR       => 'ecommerce',
            ],
            [
                Batch\Header::ICIC_NB_MERCHANT_ID  => '100000Razorpay',
                Batch\Header::ICIC_NB_SUB_IDS      => null,
                Batch\Header::ICIC_NB_GATEWAY_MID  => '1233291',
                Batch\Header::ICIC_NB_GATEWAY_MID2 => '13443',
                Batch\Header::ICIC_NB_SECTOR       => 'ecommerce',
            ],
            // Should Fail
            [
                Batch\Header::ICIC_NB_MERCHANT_ID  => '10NodalAccount',
                Batch\Header::ICIC_NB_SUB_IDS      => '10000000000000',
                Batch\Header::ICIC_NB_GATEWAY_MID  => '10000000000000',
                Batch\Header::ICIC_NB_SECTOR       => 'ecommerce',
            ],
        ];
    }
}
