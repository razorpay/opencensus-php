<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Models\Batch;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;

class ZestMoneyBulkTerminalTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/ZestMoneyBulkTerminalTestData.php';

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

        $this->assertEquals($terminals['items'][0][Terminal\Entity::MERCHANT_ID], $entries[1][Batch\Header::ZESTMONEY_MERCHANT_ID]);
        $this->assertEquals($terminals['items'][0][Terminal\Entity::CATEGORY], $entries[1][Batch\Header::ZESTMONEY_CATEGORY]);
        $this->assertEquals($terminals['items'][0][Terminal\Entity::GATEWAY_MERCHANT_ID], $entries[1][Batch\Header::ZESTMONEY_GATEWAY_MERCHANT_ID]);
        $this->assertEquals($terminals['items'][0][Terminal\Entity::GATEWAY_MERCHANT_ID2], $entries[1][Batch\Header::ZESTMONEY_GATEWAY_MERCHANT_ID2]);

        $this->assertEquals($terminals['items'][1][Terminal\Entity::MERCHANT_ID], $entries[0][Batch\Header::ZESTMONEY_MERCHANT_ID]);
        $this->assertEquals($terminals['items'][1][Terminal\Entity::CATEGORY], $entries[0][Batch\Header::ZESTMONEY_CATEGORY]);
        $this->assertEquals($terminals['items'][1][Terminal\Entity::GATEWAY_MERCHANT_ID], $entries[0][Batch\Header::ZESTMONEY_GATEWAY_MERCHANT_ID]);
        $this->assertEquals($terminals['items'][1][Terminal\Entity::GATEWAY_MERCHANT_ID2], $entries[0][Batch\Header::ZESTMONEY_GATEWAY_MERCHANT_ID2]);

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                Batch\Header::ZESTMONEY_MERCHANT_ID          => '10NodalAccount',
                Batch\Header::ZESTMONEY_GATEWAY_MERCHANT_ID  => '123',
                Batch\Header::ZESTMONEY_GATEWAY_MERCHANT_ID2 => '123',
                Batch\Header::ZESTMONEY_CATEGORY             => '1234',
            ],
            [
                Batch\Header::ZESTMONEY_MERCHANT_ID          => '100000Razorpay',
                Batch\Header::ZESTMONEY_GATEWAY_MERCHANT_ID  => '321',
                Batch\Header::ZESTMONEY_GATEWAY_MERCHANT_ID2 => '321',
                Batch\Header::ZESTMONEY_CATEGORY             => '1234',

            ],
            // Should Fail
            [
                Batch\Header::ZESTMONEY_MERCHANT_ID          => '100000Razorpay',
                Batch\Header::ZESTMONEY_GATEWAY_MERCHANT_ID2 => '321',
                Batch\Header::ZESTMONEY_CATEGORY             => '1234',
            ],
        ];
    }
}
