<?php

namespace RZP\Tests\Functional\Batch;

use Mail;
use Illuminate\Support\Facades\Queue;

use RZP\Models\Batch\Header;
use RZP\Models\Batch\Entity;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Batch\AuthLink as BatchAuthFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class AuthLinkTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/AuthLinkTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateBatchOfAuthLinks()
    {
        Queue::fake();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        Queue::assertPushed(BatchJob::class);
    }

    public function testBatchFileValidation()
    {
        $entries = $this->getDefaultFileEntries();

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

    public function testCheckAuthLinkBatchStatus()
    {
        Mail::fake();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $entity = $this->getDbLastEntity('batch');

        $this->assertEquals(2, $entity['success_count']);

        $this->assertEquals(2, $entity['failure_count']);

        // Processing should have happened immediately in tests as
        // queue are sync basically.

        $this->assertInputFileExistsForBatch($response[Entity::ID]);

        $this->assertOutputFileExistsForBatch($response[Entity::ID]);

        Mail::assertSent(BatchAuthFileMail::class);
    }

    public function testValidateBatchWithInvalidHeaders()
    {
        Queue::fake();

        $entries = $this->getWrongHeaderEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testCheckAuthLinkBatchWithBlankSpaceInput()
    {
        Mail::fake();

        $entries = $this->getFileEntriesWithBlankSpaces();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $entity = $this->getDbLastEntity('batch');

        $this->assertEquals(1, $entity['success_count']);

        $this->assertEquals(0, $entity['failure_count']);

        // Processing should have happened immediately in tests as
        // queue are sync basically.

        $this->assertInputFileExistsForBatch($response[Entity::ID]);

        $this->assertOutputFileExistsForBatch($response[Entity::ID]);

        Mail::assertSent(BatchAuthFileMail::class);
    }

    public function testCheckAuthLinkBatchWitIntegerDateInputForExcel()
    {
        // TODO: Debug and unskip
        $this->markTestSkipped('intermittent failures, need to debug');

        Mail::fake();

        $entries = $this->getFileEntriesForExcelwithIntegerDate();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $entity = $this->getDbLastEntity('batch');

        $this->assertEquals(1, $entity['success_count']);

        $this->assertEquals(0, $entity['failure_count']);

        // Processing should have happened immediately in tests as
        // queue are sync basically.

        $this->assertInputFileExistsForBatch($response[Entity::ID]);

        $this->assertOutputFileExistsForBatch($response[Entity::ID]);

        Mail::assertSent(BatchAuthFileMail::class);

        $subr = $this->getDbLastEntity('subscription_registration');

        $expireAt = $subr->getExpireAt();

        self::assertEquals(date('d/m/Y', $expireAt) , '20/03/2021');

        $invoice = $this->getDbLastEntity('invoice');

        $expireBy = $invoice->getExpireBy();

        self::assertEquals(date('d/m/Y', $expireBy) , '20/10/2020');
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                Header::AUTH_LINK_CUSTOMER_NAME   => 'test',
                Header::AUTH_LINK_CUSTOMER_EMAIL  => 'test@test.test',
                Header::AUTH_LINK_CUSTOMER_PHONE  => '9999998888',
                Header::AUTH_LINK_AMOUNT_IN_PAISE => 0,
                Header::AUTH_LINK_CURRENCY        => "INR",
                Header::AUTH_LINK_METHOD          => 'emandate',
                Header::AUTH_LINK_TOKEN_EXPIRE_BY => '20-10-2020',
                Header::AUTH_LINK_MAX_AMOUNT      => "100000",
                Header::AUTH_LINK_EXPIRE_BY       => '20-10-2020',
                Header::AUTH_LINK_AUTH_TYPE       => 'netbanking',
                Header::AUTH_LINK_BANK            => "HDFC",
                Header::AUTH_LINK_NAME_ON_ACCOUNT => "Test",
                Header::AUTH_LINK_IFSC            => "HDFC0001233",
                Header::AUTH_LINK_ACCOUNT_NUMBER  => "1233100023891",
                Header::AUTH_LINK_ACCOUNT_TYPE    => "savings",
                Header::AUTH_LINK_RECEIPT         => '#1',
                Header::AUTH_LINK_DESCRIPTION     => 'test auth link',

            ],

            //will fail
            [
                Header::AUTH_LINK_CUSTOMER_NAME   => 'test',
                Header::AUTH_LINK_CUSTOMER_EMAIL  => 'test@test.test',
                Header::AUTH_LINK_CUSTOMER_PHONE  => '9999998888',
                Header::AUTH_LINK_AMOUNT_IN_PAISE => 1000,
                Header::AUTH_LINK_CURRENCY        => "INR",
                Header::AUTH_LINK_METHOD          => 'emandate',
                Header::AUTH_LINK_TOKEN_EXPIRE_BY => null,
                Header::AUTH_LINK_MAX_AMOUNT      => "100000",
                Header::AUTH_LINK_EXPIRE_BY       => null,
                Header::AUTH_LINK_AUTH_TYPE       => null,
                Header::AUTH_LINK_BANK            => "HDFC",
                Header::AUTH_LINK_NAME_ON_ACCOUNT => "Test",
                Header::AUTH_LINK_IFSC            => "HDFC0001233",
                Header::AUTH_LINK_ACCOUNT_NUMBER  => "1233100023891",
                Header::AUTH_LINK_ACCOUNT_TYPE    => "savings",
                Header::AUTH_LINK_RECEIPT         => '#2',
                Header::AUTH_LINK_DESCRIPTION     => 'test auth link',
            ],
            [
                Header::AUTH_LINK_CUSTOMER_NAME   => 'test',
                Header::AUTH_LINK_CUSTOMER_EMAIL  => 'test@test.test',
                Header::AUTH_LINK_CUSTOMER_PHONE  => '9999998888',
                Header::AUTH_LINK_AMOUNT_IN_PAISE => 1000,
                Header::AUTH_LINK_CURRENCY        => "INR",
                Header::AUTH_LINK_METHOD          => 'card',
                Header::AUTH_LINK_TOKEN_EXPIRE_BY => null,
                Header::AUTH_LINK_MAX_AMOUNT      => "100000",
                Header::AUTH_LINK_EXPIRE_BY       => null,
                Header::AUTH_LINK_AUTH_TYPE       => null,
                Header::AUTH_LINK_BANK            => null,
                Header::AUTH_LINK_NAME_ON_ACCOUNT => null,
                Header::AUTH_LINK_IFSC            => null,
                Header::AUTH_LINK_ACCOUNT_NUMBER  => null,
                Header::AUTH_LINK_ACCOUNT_TYPE    => null,
                Header::AUTH_LINK_RECEIPT         => '#3',
                Header::AUTH_LINK_DESCRIPTION     => 'test auth link',
            ],

            //will fail
            [
                Header::AUTH_LINK_CUSTOMER_NAME   => 'test',
                Header::AUTH_LINK_CUSTOMER_EMAIL  => 'test@test.test',
                Header::AUTH_LINK_CUSTOMER_PHONE  => '9999998888',
                Header::AUTH_LINK_AMOUNT_IN_PAISE => 0,
                Header::AUTH_LINK_CURRENCY        => "INR",
                Header::AUTH_LINK_METHOD          => 'card',
                Header::AUTH_LINK_TOKEN_EXPIRE_BY => null,
                Header::AUTH_LINK_MAX_AMOUNT      => "100000",
                Header::AUTH_LINK_EXPIRE_BY       => null,
                Header::AUTH_LINK_AUTH_TYPE       => null,
                Header::AUTH_LINK_BANK            => "HDFC",
                Header::AUTH_LINK_NAME_ON_ACCOUNT => "Test",
                Header::AUTH_LINK_IFSC            => "HDFC0001233",
                Header::AUTH_LINK_ACCOUNT_NUMBER  => "1233100023891",
                Header::AUTH_LINK_ACCOUNT_TYPE    => "savings",
                Header::AUTH_LINK_RECEIPT         => '#4',
                Header::AUTH_LINK_DESCRIPTION     => 'test auth link',
            ],
        ];
    }

    protected function getWrongHeaderEntries()
    {
        return [
            [
                Header::AUTH_LINK_CUSTOMER_NAME   => 'test',
                Header::AUTH_LINK_CUSTOMER_EMAIL  => 'test@test.test',
                Header::AUTH_LINK_CUSTOMER_PHONE  => '9999998888',
                Header::AUTH_LINK_AMOUNT_IN_PAISE => 0,
                Header::AUTH_LINK_CURRENCY        => "INR",
                Header::AUTH_LINK_METHOD          => 'emandate',
                Header::AUTH_LINK_TOKEN_EXPIRE_BY => '20-10-2018',
                Header::AUTH_LINK_MAX_AMOUNT      => "100000",
                Header::AUTH_LINK_EXPIRE_BY       => '20-10-2018',
                Header::AUTH_LINK_AUTH_TYPE       => 'netbanking',
                Header::AUTH_LINK_BANK            => "HDFC",
                Header::AUTH_LINK_NAME_ON_ACCOUNT => "Test",
                Header::AUTH_LINK_IFSC            => "HDFC0001233",
                Header::AUTH_LINK_ACCOUNT_NUMBER  => "1233100023891",
                Header::AUTH_LINK_ACCOUNT_TYPE    => "savings",
                Header::AUTH_LINK_RECEIPT         => '#1',
                Header::AUTH_LINK_DESCRIPTION     => 'test auth link',
                Header::PAYEE_ACCOUNT             => 'sbi',

            ],
        ];
    }

    protected function getFileEntriesWithBlankSpaces()
    {
        return [
            // Blank spaces in values so that it will be trimmed and processed correctly
            [
                Header::AUTH_LINK_CUSTOMER_NAME   => 'test',
                Header::AUTH_LINK_CUSTOMER_EMAIL  => 'test@test.test',
                Header::AUTH_LINK_CUSTOMER_PHONE  => '9999998888',
                Header::AUTH_LINK_AMOUNT_IN_PAISE => 0,
                Header::AUTH_LINK_CURRENCY        => "INR",
                Header::AUTH_LINK_METHOD          => 'emandate ',
                Header::AUTH_LINK_TOKEN_EXPIRE_BY => '20-10-2020',
                Header::AUTH_LINK_MAX_AMOUNT      => "100000",
                Header::AUTH_LINK_EXPIRE_BY       => '20-10-2020',
                Header::AUTH_LINK_AUTH_TYPE       => ' netbanking',
                Header::AUTH_LINK_BANK            => "hdfc ",
                Header::AUTH_LINK_NAME_ON_ACCOUNT => "Test",
                Header::AUTH_LINK_IFSC            => "hdfc0001233",
                Header::AUTH_LINK_ACCOUNT_NUMBER  => "1233100023891",
                Header::AUTH_LINK_ACCOUNT_TYPE    => "savings ",
                Header::AUTH_LINK_RECEIPT         => '#1',
                Header::AUTH_LINK_DESCRIPTION     => 'test auth link',

            ],
        ];
    }

    protected function getFileEntriesForExcelwithIntegerDate()
    {
        return [
            // Blank spaces in values so that it will be trimmed and processed correctly
            [
                Header::AUTH_LINK_CUSTOMER_NAME   => 'test',
                Header::AUTH_LINK_CUSTOMER_EMAIL  => 'test@test.test',
                Header::AUTH_LINK_CUSTOMER_PHONE  => '9999998888',
                Header::AUTH_LINK_AMOUNT_IN_PAISE => 0,
                Header::AUTH_LINK_CURRENCY        => "INR",
                Header::AUTH_LINK_METHOD          => 'emandate',
                Header::AUTH_LINK_TOKEN_EXPIRE_BY => 44275,
                Header::AUTH_LINK_MAX_AMOUNT      => "100000",
                Header::AUTH_LINK_EXPIRE_BY       => '20-10-2020',
                Header::AUTH_LINK_AUTH_TYPE       => ' netbanking',
                Header::AUTH_LINK_BANK            => "hdfc ",
                Header::AUTH_LINK_NAME_ON_ACCOUNT => "Test",
                Header::AUTH_LINK_IFSC            => "hdfc0001233",
                Header::AUTH_LINK_ACCOUNT_NUMBER  => "1233100023891",
                Header::AUTH_LINK_ACCOUNT_TYPE    => "savings ",
                Header::AUTH_LINK_RECEIPT         => '#1',
                Header::AUTH_LINK_DESCRIPTION     => 'test auth link',

            ],
        ];
    }

}
