<?php

namespace RZP\Tests\Functional\Batch;

use Mail;
use Mockery;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

use RZP\Models\Batch\Header;
use RZP\Models\Batch\Entity;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Batch\AuthLink as BatchAuthFileMail;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class AuthLinkTest extends TestCase
{
    use BatchTestTrait;

    // 10-02-2020 Tuesday
    const FIXED_WORKING_DAY_AFTER_WORKING_DAY_TIME = 1581385905;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/AuthLinkTestData.php';

        parent::setUp();

        $fixedTime = (new Carbon())->timestamp(self::FIXED_WORKING_DAY_AFTER_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->ba->proxyAuth();
    }

    public function testCreateBatchOfAuthLinks()
    {
        Queue::fake();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();
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

        // changed from successCount 2, failure count 2
        // to successCount 3 and failure count 1 since we support emandate
        // authlink amount to be non-zero
        $this->assertEquals(3, $entity['success_count']);

        $this->assertEquals(1, $entity['failure_count']);

        // Processing should have happened immediately in tests as
        // queue are sync basically.

        $this->assertInputFileExistsForBatch($response[Entity::ID]);

        $this->assertOutputFileExistsForBatch($response[Entity::ID]);

        Mail::assertSent(BatchAuthFileMail::class);
    }

    public function testCreateBatchOfNachAuthLinks()
    {
        $this->fixtures->create('terminal:nach');

        $this->mockHyperVerge(function ()
        {
            return [
                'outputImage' => base64_encode('dummy'),
                'uid'         => 'XXXXXXX'
            ];
        });

        $entries = $this->getDefaultNachFileEntry();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        $entity = $this->getDbLastEntity('batch');

        $this->assertEquals(1, $entity['success_count']);

        $paperMandate = $this->getDbLastEntity('paper_mandate')->toArray();

        $this->assertEquals(100000, $paperMandate['amount']);
        $this->assertEquals('ref_1', $paperMandate['reference_1']);
        $this->assertEquals('ref_2', $paperMandate['reference_2']);
        $this->assertTrue(empty($paperMandate['generated_file_id']) === false);
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


    public function testFetchAuthLinkForAuthLinkSupervisorRole()
    {
        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '100AgentUserId'], 'auth_link_supervisor');

        $this->ba->proxyAuth('rzp_test_10000000000000', '100AgentUserId');

        $this->startTest();
    }

    public function testFetchAuthLinkForAuthLinkAgentRole()
    {
        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '100AgentUserId'], 'auth_link_agent');

        $this->ba->proxyAuth('rzp_test_10000000000000', '100AgentUserId');

        $this->startTest();
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

    protected function getDefaultNachFileEntry()
    {
        return [
            [
                Header::AUTH_LINK_CUSTOMER_NAME    => 'test',
                Header::AUTH_LINK_CUSTOMER_EMAIL   => 'test@test.test',
                Header::AUTH_LINK_CUSTOMER_PHONE   => '9999998888',
                Header::AUTH_LINK_AMOUNT_IN_PAISE  => 0,
                Header::AUTH_LINK_CURRENCY         => "INR",
                Header::AUTH_LINK_METHOD           => 'nach',
                Header::AUTH_LINK_TOKEN_EXPIRE_BY  => '20-10-2020',
                Header::AUTH_LINK_MAX_AMOUNT       => "100000",
                Header::AUTH_LINK_EXPIRE_BY        => '20-10-2020',
                Header::AUTH_LINK_AUTH_TYPE        => 'physical',
                Header::AUTH_LINK_BANK             => "HDFC",
                Header::AUTH_LINK_NAME_ON_ACCOUNT  => "Test",
                Header::AUTH_LINK_IFSC             => "HDFC0001233",
                Header::AUTH_LINK_ACCOUNT_NUMBER   => "1233100023891",
                Header::AUTH_LINK_ACCOUNT_TYPE     => "savings",
                Header::AUTH_LINK_RECEIPT          => '#1',
                Header::AUTH_LINK_DESCRIPTION      => 'test auth link',
                Header::AUTH_LINK_NACH_REFERENCE1  => 'ref_1',
                Header::AUTH_LINK_NACH_REFERENCE2  => 'ref_2',
                Header::AUTH_LINK_NACH_CREATE_FORM => '1'
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

    protected function mockHyperVerge($callable = null)
    {
        $hyperVerge = Mockery::mock('RZP\Services\HyperVerge', [$this->app]);

        $callable = $callable ?: function ()
        {
            return [];
        };

        $hyperVerge->shouldReceive('generateNACH', 'extractNACHWithOutputImage')
                   ->andReturnUsing($callable);

        $this->app->instance('hyperVerge', $hyperVerge);
    }
}
