<?php

namespace RZP\Tests\Functional\Batch;

use Mail;
use Illuminate\Support\Facades\Queue;

use RZP\Models\Batch\Header;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Batch\Refund as BatchRefundFileMail;
use RZP\Models\FileStore;
use RZP\Jobs\Batch as BatchJob;

class RefundTest extends TestCase
{
    use BatchTestTrait;

    protected $payment = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/RefundTestData.php';

        parent::setUp();
    }

    public function testUploadRefundFile()
    {
        Queue::fake();

        $entries = $this->getDefaultRefundFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();

        Queue::assertNotPushed(BatchJob::class);
    }

    public function testUploadRefundFileException()
    {
        $entries = $this->getDefaultRefundFileEntries();

        // Put improper format data
        $entries[0]['Amount'] = '';

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetRefundFiles()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund', $entries);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetRefundFileWithId()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund', $entries);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/batches/' .$batch->getPublicId();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testProcessRefundFile()
    {
        Mail::fake();

        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund', $entries);

        $payment = $this->capturePayment($entries[0]['Payment Id'], 50000);

        $this->ba->appAuth();

        $this->startTest();

        // Assert that the processed file exist

        $file = FileStore\Entity::where(FileStore\Entity::TYPE, FileStore\Type::BATCH_OUTPUT)
                                ->first();

        $this->assertNotNull($file);

        $this->assertEquals('batch/download/' . $batch->getFileKeyWithExt(), $file->getLocation());
        $this->assertEquals('batch/download/' . $batch->getFileKey(), $file->getName());

        Mail::assertSent(BatchRefundFileMail::class);
    }

    public function testProcessRefundFileWithInvalidFile()
    {
        $this->markTestSkipped();

        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund', $entries);

        $payment = $this->capturePayment($entries[0]['Payment Id'], 50000);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testProcessRefundFileWithRefundedBatch()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund', $entries);

        $payment = $this->capturePayment($entries[0]['Payment Id'], 50000);

        $refund = $this->refundPayment($entries[0]['Payment Id'], 4000);

        $this->fixtures->base->editEntity('refund', $refund['id'], ['batch_id' => $batch['id']]);

        $refund = $this->getLastEntity('refund', true);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testProcessRefundFileWithInvalidPaymentId()
    {
        $this->markTestSkipped();

        $entries = $this->getDefaultRefundFileEntries();

        $entries[] = [
                Header::PAYMENT_ID => 'pay_xyz',
                Header::AMOUNT     => 4000
            ];

        $batch = $this->fixtures->create('batch:refund', $entries);

        $payment = $this->capturePayment($entries[0]['Payment Id'], 50000);

        $request = $this->testData[__FUNCTION__]['request'];

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testProcessRefundWithOneAttempt()
    {
        $this->markTestSkipped();

        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund_with_one_attempt', $entries);

        $this->ba->appAuth();

        $this->testData[__FUNCTION__]['request']['url'] = "/batches/$batch->getPublicId()/process";

        $this->startTest();
    }

    public function testProcessRefundWithTwoAttempt()
    {
        $this->markTestSkipped();

        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund_with_two_attempt', $entries);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testProcessRefundWithThreeAttempt()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund_with_three_attempt', $entries);

        $publicBatchId = $batch->getPublicId();

        $this->ba->appAuth();

        $this->testData[__FUNCTION__]['request']['url'] = "/batches/$publicBatchId/process";

        $this->startTest();

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals($batch['attempts'], 3);
        $this->assertEquals($batch['status'], 'processed');
    }

    public function testProcessRefundWithThreeAttemptSuccess()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund_with_three_attempt', $entries);

        $payment = $this->capturePayment($entries[0]['Payment Id'], 50000);

        $publicBatchId = $batch->getPublicId();

        $this->ba->appAuth();

        $this->testData[__FUNCTION__]['request']['url'] = "/batches/$publicBatchId/process";

        $this->startTest();

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals($batch['attempts'], 3);
        $this->assertEquals($batch['status'], 'processed');
    }

    protected function getDefaultRefundFileEntries()
    {
        $payment = $this->defaultAuthPayment();

        $entries = [
            [
                Header::PAYMENT_ID => $payment['id'],
                Header::AMOUNT     => 4000
            ]
        ];

        return $entries;
    }
}
