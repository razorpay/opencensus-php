<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Models\Batch;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;


class SubmerchantBulkAssignTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/SubmerchantBulkAssignTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testBulkAssignValidateFile()
    {
        $this->ba->proxyAuth();

        $entries = $this->getDefaultFileEntries();

        $this->testData[__FUNCTION__]['response']
            ['content']['parsed_entries'][0][Batch\Header::TERMINAL_ID] = $entries[0][Batch\Header::TERMINAL_ID];

        $this->testData[__FUNCTION__]['response']
            ['content']['parsed_entries'][1][Batch\Header::TERMINAL_ID] = $entries[1][Batch\Header::TERMINAL_ID];

        $this->testData[__FUNCTION__]['response']
            ['content']['parsed_entries'][2][Batch\Header::TERMINAL_ID] = $entries[1][Batch\Header::TERMINAL_ID];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBulkSubmerchantAssign()
    {
        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(4, $batch['processed_count']);
        $this->assertEquals(3, $batch['success_count']);
        $this->assertEquals(1, $batch['failure_count']);

        $count = $batch['success_count'];

        $terminal1 = $this->getEntityById('terminal', $entries[0][Batch\Header::TERMINAL_ID], true);
        $terminal2 = $this->getEntityById('terminal', $entries[1][Batch\Header::TERMINAL_ID], true);

        $this->assertArrayHasKey('sub_merchants', $terminal1);
        $this->assertEquals($terminal1['sub_merchants'][0]['id'], '100000Razorpay');

        $this->assertArrayHasKey('sub_merchants', $terminal2);
        $this->assertEquals($terminal2['sub_merchants'][0]['id'], '10000000000000');
        $this->assertEquals($terminal2['sub_merchants'][1]['id'], '10NodalAccount');

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);
    }

    public function testBulkSubmerchantAssignViaBatchService()
    {
        $this->ba->batchAppAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        for ($index = 0; $index < 2; $index++)
        {
            $terminal = $this->fixtures->create('terminal');
            $merchant = $this->fixtures->create('merchant');

            $this->testData[__FUNCTION__]['request']['content'][$index]['terminal_id']    = $terminal['id'];
            $this->testData[__FUNCTION__]['request']['content'][$index]['submerchant_id'] = $merchant['id'];
        }

        $terminal = $this->fixtures->create('terminal');
        $merchant = $this->fixtures->create('merchant');

        $this->testData[__FUNCTION__]['request']['content'][2]['terminal_id']    = $terminal['id'];
        $this->testData[__FUNCTION__]['request']['content'][2]['submerchant_id'] = $merchant['id'];

        $this->testData[__FUNCTION__]['request']['content'][3]['terminal_id']    = $terminal['id'];
        $this->testData[__FUNCTION__]['request']['content'][3]['submerchant_id'] = $merchant['id'];

        $this->startTest();
    }

    public function testBulkSubmerchantAssignViaBatchServiceWithoutBatchId()
    {
        $this->ba->batchAppAuth();

        for ($index = 0; $index < 2; $index++)
        {
            $terminal = $this->fixtures->create('terminal');
            $merchant = $this->fixtures->create('merchant');

            $this->testData[__FUNCTION__]['request']['content'][$index]['terminal_id']    = $terminal['id'];
            $this->testData[__FUNCTION__]['request']['content'][$index]['submerchant_id'] = $merchant['id'];
        }

        $this->startTest();
    }

    public function testBulkSubmerchantAssignViaBatchServiceForExcessCount()
    {
        $this->ba->batchAppAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        for ($index = 0; $index < 16; $index++)
        {
            $terminal = $this->fixtures->create('terminal');
            $merchant = $this->fixtures->create('merchant');

            $this->testData[__FUNCTION__]['request']['content'][$index]['terminal_id']    = $terminal['id'];
            $this->testData[__FUNCTION__]['request']['content'][$index]['submerchant_id'] = $merchant['id'];
        }

        $this->startTest();
    }

    protected function getDefaultFileEntries()
    {
        $terminal1 = $this->fixtures->create('terminal:bharat_qr_terminal');

        $terminal2 = $this->fixtures->create('terminal:bharat_qr_terminal');

        return [
            [
                Batch\Header::SUBMERCHANT_ID   => '100000Razorpay',
                Batch\Header::TERMINAL_ID      => $terminal1['id'],
            ],
            [
                Batch\Header::SUBMERCHANT_ID   => '10NodalAccount',
                Batch\Header::TERMINAL_ID      => $terminal2['id'],
            ],
            [
                Batch\Header::SUBMERCHANT_ID   => '10000000000000',
                Batch\Header::TERMINAL_ID      => $terminal2['id'],
            ],
            // Should Fail because terminalID doesn't exists
            [
                Batch\Header::SUBMERCHANT_ID   => '100DemoAccount',
                Batch\Header::TERMINAL_ID      => '22nP3sEf2tQco1',
            ],
        ];
    }
}
