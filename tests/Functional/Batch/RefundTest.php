<?php

namespace RZP\Tests\Functional\Batch;

use Mail;
use Illuminate\Support\Facades\Queue;

use RZP\Models\FileStore;
use RZP\Models\Batch\Header;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Batch\Refund as BatchRefundFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class RefundTest extends TestCase
{
    use BatchTestTrait;
    use DbEntityFetchTrait;

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

        $response = $this->startTest();

        // This attribute(derived) is only exposed in admin auth at the moment
        $this->assertArrayNotHasKey('processed_percentage', $response);
        $this->assertArrayNotHasKey('processed_count', $response);

        $batch = $this->getDbLastEntity('batch');

        $this->assertEquals(0, $batch->getProcessedCount());

        Queue::assertNotPushed(BatchJob::class);
    }

    public function testRefundBatchWithAdminAuth()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testRefundBatchWithSharedMerchantProxyAuth()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth('rzp_test_100000Razorpay');

        $this->startTest();
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

        // Adding an extra entry to test an entry with no notes
        $payment2 = $this->defaultAuthPayment();
        $entries[] = [
            Header::PAYMENT_ID => $payment2['id'],
            Header::AMOUNT     => 200,
        ];

        $batch = $this->fixtures->create('batch:refund', $entries);

        $this->capturePayment($entries[0][Header::PAYMENT_ID], 50000);
        $this->capturePayment($entries[1][Header::PAYMENT_ID], 50000);

        $this->ba->appAuth();

        $this->startTest();

        $batch = $this->getDbLastEntity('batch');

        $this->assertEquals(2, $batch->getProcessedCount());

        // Assert that the processed file exist

        $file = FileStore\Entity::where(FileStore\Entity::TYPE, FileStore\Type::BATCH_OUTPUT)
                                ->first();

        $this->assertNotNull($file);

        $this->assertEquals('batch/download/' . $batch->getFileKeyWithExt(), $file->getLocation());
        $this->assertEquals('batch/download/' . $batch->getFileKey(), $file->getName());

        // Validate notes
        $refunds = $this->getEntities('refund', [], true);

        $expectedRefundsWithNotes = [
            [
                'payment_id' => $entries[1][Header::PAYMENT_ID],
                'amount'     => 200,
                'notes'      => [
                    'key_1' => null,
                    'key_2' => null
                ],
            ],
            [
                'payment_id' => $entries[0][Header::PAYMENT_ID],
                'amount'     => 4000,
                'notes'      => [
                    'key_1' => 'Notes Value 1',
                    'key_2' => 'Notes Value 2'
                ],
            ]
        ];

        $this->assertArraySelectiveEquals($expectedRefundsWithNotes, $refunds['items']);

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
        $entries = $this->getDefaultRefundFileEntries(false);

        $batch = $this->fixtures->create('batch:refund', $entries);

        $payment = $this->capturePayment($entries[0]['Payment Id'], 50000);

        $refund = $this->refundPayment($entries[0]['Payment Id'], 4000);

        $this->fixtures->base->editEntity('refund', $refund['id'], ['batch_id' => $batch['id']]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEmpty($refund['notes']);

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

        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['request']['url'] = "/batches/$publicBatchId/process";

        $this->startTest();

        $batch = $this->getDbLastEntity('batch');

        $this->assertEquals(3, $batch->getAttempts());
        $this->assertEquals('processed', $batch->getStatus());
        $this->assertEquals(1, $batch->getProcessedCount());
        $this->assertEquals(100, $batch->getProcessedPercentage());
    }

    public function testProcessRefundWithThreeAttemptSuccess()
    {
        $entries = $this->getDefaultRefundFileEntries();

        $batch = $this->fixtures->create('batch:refund_with_three_attempt', $entries);

        $payment = $this->capturePayment($entries[0]['Payment Id'], 50000);

        $publicBatchId = $batch->getPublicId();

        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['request']['url'] = "/batches/$publicBatchId/process";

        $this->startTest();

        $batch = $this->getDbLastEntity('batch');

        $this->assertEquals(3, $batch->getAttempts());
        $this->assertEquals('processed', $batch->getStatus());
        $this->assertEquals(1, $batch->getProcessedCount());
        $this->assertEquals(100, $batch->getProcessedPercentage());
    }

    protected function getDefaultRefundFileEntries(bool $withNotes = true)
    {
        $payment = $this->defaultAuthPayment();

        $entries = [
            [
                Header::PAYMENT_ID => $payment['id'],
                Header::AMOUNT     => 4000,
            ],
        ];

        if ($withNotes === true)
        {
            $entries[0] += [
                'notes[key_1]'     => 'Notes Value 1',
                'notes[key_2]'     => 'Notes Value 2',
            ];
        }

        return $entries;
    }
}
