<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Constants\Mode;
use RZP\Models\Settings;
use RZP\Models\Batch\Header;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;

class BatchServiceTest extends TestCase
{
    use TestsMetrics;
    use BatchTestTrait;
    use CreatesInvoice;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/BatchServiceTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function mockRazorX(string $functionName, string $featureName, string $variant)
    {
        $testData = &$this->testData[$functionName];

        $uniqueLocalId = RazorXClient::getLocalUniqueId('10000000000000', $featureName, Mode::TEST);

        $testData['request']['cookies'] = [RazorXClient::RAZORX_COOKIE_KEY => '{"' . $uniqueLocalId . '":"' . $variant . '"}'];
    }


    public function testBatchServiceIsDown()
    {
        $this->fixtures->create(
            'batch',
            [
                'id'          => 'C7e2YqUIpZ2KwZ',
                'type'        => 'payment_link',
                'total_count' => 4,
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    // Merge the result from batch service and api
    public function testBatchServiceGetAllPaymentLinks()
    {
        $batch = $this->fixtures->create(
            'batch',
            [
                'id'          => '00000000000002',
                'type'        => 'payment_link',
                'total_count' => 4,
            ]);

        Settings\Accessor::for($batch, Settings\Module::BATCH)
                         ->upsert([
                                      'sms_notify'   => 0,
                                      'email_notify' => 0,
                                  ])->save();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBatchServiceDownloadBatch()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBatchCreateToNewBatchService()
    {
        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->mockRazorX(__FUNCTION__,  'batch_service_payment_link_migration', "on");

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBatchRawAPIGetAllBatches()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBatchRawAPIUpdateSettings()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBatchAdminFetchNoResult()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('merchant',['id' => 'CWIYz6Yfu8tqZv']);

        $this->startTest();
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                Header::INVOICE_NUMBER   => '#1',
                Header::CUSTOMER_NAME    => 'test',
                Header::CUSTOMER_EMAIL   => 'test@test.test',
                Header::CUSTOMER_CONTACT => '9999998888',
                Header::AMOUNT           => 100,
                Header::DESCRIPTION      => 'test payment link',
                Header::EXPIRE_BY        => null,
                Header::PARTIAL_PAYMENT  => 'YES',
                'notes[key1]'            => 'Notes Value 1',
                'notes[key2]'            => 'Notes Value 2',
            ],
        ];
    }
}
