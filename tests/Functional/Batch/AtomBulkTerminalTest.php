<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Models\Batch;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;

class AtomBulkTerminalTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/AtomBulkTerminalTestData.php';

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

        $this->assertEquals($terminals['items'][0][Terminal\Entity::MERCHANT_ID], $entries[1][Batch\Header::ATOM_MERCHANT_ID]);
        $this->assertEquals($terminals['items'][0][Terminal\Entity::NETWORK_CATEGORY], $entries[1][Batch\Header::ATOM_CATEGORY]);
        $this->assertEquals($terminals['items'][0][Terminal\Entity::TYPE], []);
        $this->assertEquals($terminals['items'][0][Terminal\Entity::GATEWAY_ACCESS_CODE], $entries[1][Batch\Header::ATOM_ACCESS_CODE]);
        $this->assertEquals($terminals['items'][0][Terminal\Entity::GATEWAY_MERCHANT_ID], $entries[1][Batch\Header::ATOM_GATEWAY_MERCHANT_ID]);

        $this->assertEquals($terminals['items'][1][Terminal\Entity::MERCHANT_ID], $entries[0][Batch\Header::ATOM_MERCHANT_ID]);
        $this->assertEquals($terminals['items'][1][Terminal\Entity::NETWORK_CATEGORY], $entries[0][Batch\Header::ATOM_CATEGORY]);
        $this->assertEquals($terminals['items'][1][Terminal\Entity::TYPE][0], Terminal\Type::NON_RECURRING);
        $this->assertEquals($terminals['items'][1][Terminal\Entity::GATEWAY_ACCESS_CODE], $entries[0][Batch\Header::ATOM_ACCESS_CODE]);
        $this->assertEquals($terminals['items'][1][Terminal\Entity::GATEWAY_MERCHANT_ID], $entries[0][Batch\Header::ATOM_GATEWAY_MERCHANT_ID]);

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                Batch\Header::ATOM_MERCHANT_ID          => '10NodalAccount',
                Batch\Header::ATOM_GATEWAY_MERCHANT_ID  => '123',
                Batch\Header::ATOM_CATEGORY             => 'ecommerce',
                Batch\Header::ATOM_TERMINAL_PASSWORD    => 'abc123',
                Batch\Header::ATOM_TERMINAL_PASSWORD2   => 'abc321',
                Batch\Header::ATOM_ACCESS_CODE          => '12345678',
                Batch\Header::ATOM_SECURE_SECRET        => 's12345678',
                Batch\Header::ATOM_SECURE_SECRET2       => 's12345678',
                Batch\Header::ATOM_NON_RECURRING        => '1',
            ],
            [
                Batch\Header::ATOM_MERCHANT_ID          => '100000Razorpay',
                Batch\Header::ATOM_GATEWAY_MERCHANT_ID  => '321',
                Batch\Header::ATOM_CATEGORY             => 'ecommerce',
                Batch\Header::ATOM_TERMINAL_PASSWORD    => 'abc123',
                Batch\Header::ATOM_TERMINAL_PASSWORD2   => 'abc321',
                Batch\Header::ATOM_ACCESS_CODE          => '12345678',
                Batch\Header::ATOM_SECURE_SECRET        => 's12345678',
                Batch\Header::ATOM_SECURE_SECRET2       => 's12345678',
                Batch\Header::ATOM_NON_RECURRING        => '0',
            ],
            // Should Fail
            [
                Batch\Header::ATOM_MERCHANT_ID          => '100000Razorpay',
                Batch\Header::ATOM_GATEWAY_MERCHANT_ID  => '321',
                Batch\Header::ATOM_CATEGORY             => 'ecommerce',
            ],
        ];
    }
}
