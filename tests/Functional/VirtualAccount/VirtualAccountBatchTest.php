<?php

namespace RZP\Tests\Functional\VirtualAccount;

use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class VirtualAccountBatchTest extends TestCase
{
    use TestsWebhookEvents;
    use VirtualAccountTrait;
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/VirtualAccountBatchTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->create('terminal:vpa_shared_terminal_icici');
    }

    public function testValidateBulkEditVirtualAccount()
    {
        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures(['va_edit_bulk']);

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testValidateBulkEditVirtualAccountFeatureMissing()
    {
        $this->ba->proxyAuth();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testValidateBulkEditVirtualAccountWrongData()
    {
        $this->fixtures->merchant->addFeatures(['va_edit_bulk']);

        $this->ba->proxyAuth();

        $entries = $this->getFileEntriesWithWrongData();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                'Virtual Account Id' => 'va_testName561234',
                'Expire By' => '10-10-2121 15:30',
            ],
        ];
    }

    protected function getFileEntriesWithWrongData()
    {
        return [
            [
                'Virtual Account Id' => 'va_testName561234',
                'Expire By' => '10-18-1999 15:30',
            ],
        ];
    }
}
