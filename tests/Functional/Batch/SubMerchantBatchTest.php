<?php

namespace RZP\Tests\Functional\Batch;

use Mail;
use Illuminate\Support\Facades\Queue;

use RZP\Models\Batch\Header;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\TestCase;

class SubMerchantBatchTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/SubMerchantBatchTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateSubMerchantBatchAggregator()
    {
        $this->fixtures->merchant->addFeatures('aggregator');

        Queue::fake();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        Queue::assertNotPushed(BatchJob::class);
    }

    public function testCreateSubMerchantBatchPartner()
    {
        $this->fixtures->merchant->markPartner();

        Queue::fake();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        Queue::assertPushed(BatchJob::class);
    }

    public function testCreateSubMerchantBatchInvalidHeaders()
    {
        $this->fixtures->merchant->addFeatures('aggregator');

        $entries = $this->getDefaultFileEntries();

        foreach ($entries as & $entry)
        {
            unset($entry[Header::WEBSITE_URL]);
        }

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    protected function getDefaultFileEntries(): array
    {
        return $this->testData['defaultEntries'];
    }
}
