<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Constants;
use RZP\Models\Batch;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;

class UpiMindgateBulkTerminalTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/UpiMindgateBulkTerminalTestData.php';

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

    public function testBulkTerminalCreationValidateVpaRequired()
    {
        $this->ba->proxyAuth();

        $entries = $this->getDefaultFileEntries();

        $entries[0][Batch\Header::UPI_MINDGATE_VPA ] = '';

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

        $this->assertEquals($terminals['items'][1][Terminal\Entity::NETBANKING], false);

        $this->assertEquals($terminals['items'][1][Terminal\Entity::UPI], true);

        $this->assertEquals($terminals['items'][1][Terminal\Entity::GATEWAY], Constants\Entity::UPI_MINDGATE);

        $this->assertEquals($terminals['items'][1][Terminal\Entity::MERCHANT_ID],
                            $entries[0][Batch\Header::UPI_MINDGATE_MERCHANT_ID]);

        $this->assertEquals($terminals['items'][1][Terminal\Entity::GATEWAY_MERCHANT_ID],
                            $entries[0][Batch\Header::UPI_MINDGATE_GATEWAY_MERCHANT_ID]);

        $this->assertEquals($terminals['items'][1][Terminal\Entity::GATEWAY_MERCHANT_ID2],
                            $entries[0][Batch\Header::UPI_MINDGATE_VPA]);

        $this->assertEquals($terminals['items'][1][Terminal\Entity::TYPE], ['non_recurring']);

        $this->assertEquals($terminals['items'][0][Terminal\Entity::NETBANKING], false);

        $this->assertEquals($terminals['items'][0][Terminal\Entity::UPI], true);

        $this->assertEquals($terminals['items'][0][Terminal\Entity::GATEWAY], Constants\Entity::UPI_MINDGATE);

        $this->assertEquals($terminals['items'][0][Terminal\Entity::MERCHANT_ID],
                            $entries[1][Batch\Header::UPI_MINDGATE_MERCHANT_ID]);

        $this->assertEquals($terminals['items'][0][Terminal\Entity::GATEWAY_MERCHANT_ID],
                            $entries[1][Batch\Header::UPI_MINDGATE_GATEWAY_MERCHANT_ID]);

        $this->assertEquals($terminals['items'][0][Terminal\Entity::GATEWAY_MERCHANT_ID2],
                            $entries[1][Batch\Header::UPI_MINDGATE_VPA]);

        $this->assertEquals($terminals['items'][0][Terminal\Entity::TYPE], ['non_recurring', 'pay']);

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);

        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                Batch\Header::UPI_MINDGATE_MERCHANT_ID           => '10NodalAccount',
                Batch\Header::UPI_MINDGATE_GATEWAY_MERCHANT_ID   => 'HDFC000011670815',
                Batch\Header::UPI_MINDGATE_VPA                   => 'abc.razorpay@hdfcbank',
                Batch\Header::UPI_MINDGATE_TERMINAL_PASSWORD     => 'terminalPassword',
                Batch\Header::UPI_MINDGATE_COLLECT               => '',
                Batch\Header::UPI_MINDGATE_PAY                   => null,
            ],
            [
                Batch\Header::UPI_MINDGATE_MERCHANT_ID           => '100000Razorpay',
                Batch\Header::UPI_MINDGATE_GATEWAY_MERCHANT_ID   => 'HDFC000011670816',
                Batch\Header::UPI_MINDGATE_VPA                   => 'xyz.razorpay@hdfcbank',
                Batch\Header::UPI_MINDGATE_TERMINAL_PASSWORD     => 'terminalPassword',
                Batch\Header::UPI_MINDGATE_COLLECT               =>  '0',
                Batch\Header::UPI_MINDGATE_PAY                   =>  1,
            ],
            // this will fail, as one merchant should not have two terminals of same gateway, mcc, vpa and currency
            [
                Batch\Header::UPI_MINDGATE_MERCHANT_ID          => '10NodalAccount',
                Batch\Header::UPI_MINDGATE_GATEWAY_MERCHANT_ID  => 'HDFC000011670817',
                Batch\Header::UPI_MINDGATE_VPA                  => 'abc.razorpay@hdfcbank',
                Batch\Header::UPI_MINDGATE_TERMINAL_PASSWORD    => 'terminalPassword',
            ],
        ];
    }
}
