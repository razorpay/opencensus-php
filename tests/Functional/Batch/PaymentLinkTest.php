<?php

namespace RZP\Tests\Functional\Batch;

use Mail;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;

use RZP\Constants\Mode;
use RZP\Models\Invoice;
use RZP\Models\Settings;
use RZP\Models\Batch\Type;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Entity;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;
use RZP\Mail\Batch\PaymentLink as BatchPaymentLinkFileMail;

class PaymentLinkTest extends TestCase
{
    use TestsMetrics;
    use BatchTestTrait;
    use CreatesInvoice;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/PaymentLinkTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateBatchOfPaymentLinkType1()
    {
        Queue::fake();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Just asserting that job is being pushed on creation of batch entity
        // for payment link type.

        Queue::assertPushed(BatchJob::class);
    }

    public function testCreateBatchOfPaymentLinkType2()
    {
        Mail::fake();

        $metrics = $this->createMetricsMock();

        $metrics->expects($this->at(5))
                ->method('count')
                ->with(
                    'invoice_created_total',
                    1,
                    [
                        'type'             => 'link',
                        'has_batch'        => 1,
                        'has_subscription' => 0,
                    ]);

        $metrics->expects($this->at(8))
                ->method('count')
                ->with(
                    'invoice_created_total',
                    1,
                    [
                        'type'             => 'link',
                        'has_batch'        => 1,
                        'has_subscription' => 0,
                    ]);

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $entity = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $entity['success_count']);
        $this->assertEquals(1, $entity['failure_count']);

        // Processing should have happened immediately in tests as
        // queue are sync basically.

        $this->assertInputFileExistsForBatch($response[Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Entity::ID]);

        Mail::assertSent(BatchPaymentLinkFileMail::class);

        // TODO:
        // - Open and verify output file contents with expectations
    }

    /**
     * Tests pl batch with new header values (includes Amount (In Paise))
     */
    public function testCreateBatchOfPaymentLinkTypeWithNewHeaderValues()
    {
        $rows = $this->testData[__FUNCTION__ . 'FileRows'];

        $this->createAndPutExcelFileInRequest($rows, __FUNCTION__);

        $response = $this->startTest();

        // Asserts batch entity's attributes
        $entity = $this->getLastEntity('batch', true);

        $this->assertEquals(1, $entity['success_count']);
        $this->assertEquals(0, $entity['failure_count']);

        // Asserts files existence
        $this->assertInputFileExistsForBatch($response[Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Entity::ID]);

        // Assert invoice entity's attributes
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals('#1', $invoice['receipt']);
        $this->assertEquals(500, $invoice['amount']);
    }

    /**
     * File's header is invalid
     */
    public function testCreateBatchOfPaymentLinkTypeWithInvalidFile1()
    {
        $entries = $this->getDefaultFileEntries();

        // Remove a required header

        foreach ($entries as & $entry)
        {
            unset($entry[Header::AMOUNT]);
        }

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        //  Test again by adding one extra header in input

        $entries = $this->getDefaultFileEntries();

        foreach ($entries as & $entry)
        {
            $entry['Extra Header'] = 'not needed value';
        }

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    /**
     * File row count is not in allowed limits
     */
    public function testCreateBatchOfPaymentLinkTypeWithInvalidFile2()
    {
        $entries = [];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    /**
     * Few of the file rows has validation errors
     */
    public function testCreateBatchOfPaymentLinkTypeWithInvalidFile3()
    {
        $entries = $this->getDefaultFileEntries();

        $entries[1][Header::AMOUNT] = 0;

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testProcessPaymentLinkBatchById()
    {
        $this->fixtures
             ->batch
             ->create(
                [
                    Entity::ID   => '00000000000001',
                    Entity::TYPE => Type::PAYMENT_LINK,
                ]);

        $this->ba->adminAuth();

        Queue::fake();

        $this->startTest();

        Queue::assertPushed(BatchJob::class, function($job)
        {
            $this->assertEquals(Mode::TEST, $job->getMode());

            $this->assertEquals('00000000000001', $job->getId());

            $this->assertArraySelectiveEquals(
                [
                    Invoice\Entity::SMS_NOTIFY   => '1',
                    Invoice\Entity::EMAIL_NOTIFY => '0',
                    Invoice\Entity::DRAFT        => '0',
                ],
                $job->getParams());

            return true;
        });
    }

    public function testBatchFileValidation()
    {
        $entries = $this->getDefaultFileEntries();

        $entries = array_merge($entries, $this->getFileEntriesWithBooleanPartialPaymentValues());

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        $files = $this->getEntities('file_store', [], true);

        $validatedFile = $files['items'][0];

        $inputFile = $files['items'][1];

        $this->assertNull($inputFile['entity_type']);
        $this->assertNull($inputFile['entity_id']);
        $this->assertEquals('batch_input', $inputFile['type']);

        $this->assertNull($validatedFile['entity_type']);
        $this->assertNull($validatedFile['entity_id']);
        $this->assertEquals('batch_validated', $validatedFile['type']);

        $this->assertEquals($validatedFile['id'], $response['file_id']);

        // The validated file is supposed to be inside batch/validated folder
        $this->assertEquals(storage_path('files/filestore/') . $validatedFile['location'], $response['signed_url']);
        $this->assertTrue(str_contains($validatedFile['location'], 'batch/validated'));
        $this->assertTrue(str_contains($response['signed_url'], 'batch/validated'));
    }

    public function testBatchCreateForUploadedFile()
    {
        $this->prepareStateFromValidateApi();

        $response = $this->startTest();

        // Check invoices
        $invoices = $this->getEntities('invoice', [], true);
        $this->assertEquals(2, $invoices['count']);

        // Check files
        $files = $this->getEntities('file_store', [], true);
        $this->assertEquals(3, $files['count']);

        // Check output file
        $outputFile = $files['items'][0];
        $this->assertEquals('batch_output', $outputFile['type']);
        $this->assertEquals($response['id'], $outputFile['entity_type'] . '_' . $outputFile['entity_id']);
        $this->assertEquals('batch/download/' . Entity::stripDefaultSign($response['id']), $outputFile['name']);

        // Check validated file
        $validatedFile = $files['items'][1];
        $this->assertEquals('batch_validated', $validatedFile['type']);
        $this->assertEquals($response['id'], $validatedFile['entity_type'] . '_' . $validatedFile['entity_id']);
        $this->assertTrue(str_contains($validatedFile['location'], 'batch/validated'));

        // Check input file
        $inputFile = $files['items'][2];
        $this->assertEquals('batch_input', $inputFile['type']);
        $this->assertNull($inputFile['entity_type']);
        $this->assertNull($inputFile['entity_id']);
        $this->assertTrue(str_contains($inputFile['location'], 'batch/upload'));
    }

    public function testCreateBatchWithHumanReadableExpireBy()
    {
        $rows = $this->testData[__FUNCTION__ . 'FileRows'];

        $this->createAndPutExcelFileInRequest($rows, __FUNCTION__);

        $response = $this->startTest();

        // Asserts batch entity's attributes
        $entity = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $entity['success_count']);
        $this->assertEquals(0, $entity['failure_count']);

        // Asserts files existence
        $this->assertInputFileExistsForBatch($response[Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Entity::ID]);

        // Assert invoice entity's attributes
        $invoices = $this->getEntities('invoice', [], true)['items'];
        $this->assertCount(3, $invoices);

        // Against each invoice's receipt from test rows assert expected epoch values
        foreach ($invoices as $invoice)
        {
            $receipt          = $invoice['receipt'];
            $expireBy         = $invoice['expire_by'];
            $expectedExpireBy = Carbon::now(Timezone::IST)->addDays((int) $receipt)->getTimestamp();

            $this->assertEquals($expectedExpireBy, $expireBy, '', 10);
        }
    }

    /**
     * Helper method to accompany testBatchCreateForUploadedFile() test.
     * It creates an state(db, file wise) which would have been there if
     * /validate api was called prior to /create api call. We could have triggered
     * /validated api call first followed by /create api call in same tests
     * but we are avoiding doing multiple api calls in same test.
     */
    protected function prepareStateFromValidateApi()
    {
        // Creating input and validated file fixtures
        $attributeInputFile = [

            'type'          => 'batch_input',
            'entity_type'   => null,
            'name'          => 'batch/upload/10000000000001',
            'location'      => 'batch/upload/10000000000001.xlsx',
        ];

        $attributeValidatedFile = [

            'type'          => 'batch_validated',
            'entity_type'   => null,
            'name'          => 'batch/validated/10000000000002',
            'location'      => 'batch/validated/10000000000002.xlsx',
        ];

        $inputFile = $this->fixtures->create('file_store', $attributeInputFile);

        $validatedFile = $this->fixtures->create('file_store', $attributeValidatedFile);

        // Move validated test file copy to validated folder
        copy('tests/Functional/Batch/files/validated.xlsx',
            'storage/files/filestore/batch/validated/10000000000002.xlsx');

        $trace    = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name     = $testDataKey ?? $trace[1]['function'];

        $this->testData[$name]['request']['content']['file_id'] = $validatedFile->getPublicId();
    }

    public function testPaymentLinkStatsOfBatch()
    {
        $this->fixtures->create(
            'batch',
            [
                'id'          => '00000000000001',
                'type'        => 'payment_link',
                'total_count' => 6,
            ]);

        $attributes = $this->testData[__FUNCTION__ . 'InputData']['attributes'];

        foreach ($attributes as $attribute)
        {
            $this->createInvoice($attribute['invoiceAttributes'], $attribute['orderAttributes']);
        }

        $this->startTest();
    }

    public function testGetStatsOfInvalidType()
    {
        $this->fixtures->create(
            'batch',
            [
                'id'   => '00000000000001',
                'type' => 'linked_account'
            ]);

        $this->startTest();
    }

    public function testFetchBatchesOfPaymentLinkTypeWithConfig()
    {
        $batch1 = $this->fixtures->create(
            'batch',
            [
                'id'          => '00000000000001',
                'type'        => 'payment_link',
                'total_count' => 4,
            ]);

        Settings\Accessor::for($batch1, Settings\Module::BATCH)
                         ->upsert([
                            'sms_notify'    => 1,
                            'email_notify'  => 0,
                         ])->save();

        $batch2 = $this->fixtures->create(
            'batch',
            [
                'id'          => '00000000000002',
                'type'        => 'payment_link',
                'total_count' => 4,
            ]);

        Settings\Accessor::for($batch2, Settings\Module::BATCH)
                        ->upsert([
                            'sms_notify'    => 0,
                            'email_notify'  => 0,
                        ])->save();

        $this->ba->proxyAuth();

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
            ],
            // Following one should fail
            [
                Header::INVOICE_NUMBER   => '#1',
                Header::CUSTOMER_NAME    => 'test 2',
                Header::CUSTOMER_EMAIL   => 'test-2@test.test',
                Header::CUSTOMER_CONTACT => '9999997777',
                Header::AMOUNT           => 100,
                Header::DESCRIPTION      => 'test payment link - 2',
                Header::EXPIRE_BY        => null,
                Header::PARTIAL_PAYMENT  => 'NO',
            ],
            [
                Header::INVOICE_NUMBER   => '#3',
                Header::CUSTOMER_NAME    => 'test 3',
                Header::CUSTOMER_EMAIL   => 'test-3@test.test',
                Header::CUSTOMER_CONTACT => '9999996666',
                Header::AMOUNT           => 100,
                Header::DESCRIPTION      => 'test payment link - 3',
                Header::EXPIRE_BY        => null,
                Header::PARTIAL_PAYMENT  => null,
            ],
        ];
    }

    protected function getFileEntriesWithBooleanPartialPaymentValues()
    {
        return [
            [
                Header::INVOICE_NUMBER   => '#10',
                Header::CUSTOMER_NAME    => 'test 3',
                Header::CUSTOMER_EMAIL   => 'test-3@test.test',
                Header::CUSTOMER_CONTACT => '9999996666',
                Header::AMOUNT           => 100,
                Header::DESCRIPTION      => 'test payment link - 3',
                Header::EXPIRE_BY        => null,
                Header::PARTIAL_PAYMENT  => '1',
            ],
            [
                Header::INVOICE_NUMBER   => '#20',
                Header::CUSTOMER_NAME    => 'test 3',
                Header::CUSTOMER_EMAIL   => 'test-3@test.test',
                Header::CUSTOMER_CONTACT => '9999996666',
                Header::AMOUNT           => 100,
                Header::DESCRIPTION      => 'test payment link - 3',
                Header::EXPIRE_BY        => null,
                Header::PARTIAL_PAYMENT  => '0',
            ],
        ];
    }
}
