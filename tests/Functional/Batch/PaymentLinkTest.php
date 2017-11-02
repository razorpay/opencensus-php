<?php

namespace RZP\Tests\Functional\Batch;

use Mail;
use Illuminate\Support\Facades\Queue;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Entity;
use RZP\Jobs\Batch as BatchJob;
use RZP\Mail\Batch\PaymentLink as BatchPaymentLinkFileMail;

class PaymentLinkTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/PaymentLinkTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateBatchOfPaymentLinkType1()
    {
        Queue::fake();

        $entries = $this->getDefaultPaymentLinkFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Just asserting that job is being pushed on creation of batch entity
        // for payment link type.

        Queue::assertPushed(BatchJob::class);
    }

    public function testCreateBatchOfPaymentLinkType2()
    {
        Mail::fake();

        $entries = $this->getDefaultPaymentLinkFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $entities = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $entities['success_count']);
        $this->assertEquals(1, $entities['failure_count']);

        // Processing should have happened immediately in tests as
        // queue are sync basically.

        $this->assertInputFileExistsForBatch($response[Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Entity::ID]);

        Mail::assertSent(BatchPaymentLinkFileMail::class);

        // TODO:
        // - Open and verify output file contents with expectations
    }

    /**
     * File's header is invalid
     */
    public function testCreateBatchOfPaymentLinkTypeWithInvalidFile1()
    {
        $entries = $this->getDefaultPaymentLinkFileEntries();

        // Remove a required header

        foreach ($entries as & $entry)
        {
            unset($entry[Header::AMOUNT]);
        }

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        //  Test again by adding one extra header in input

        $entries = $this->getDefaultPaymentLinkFileEntries();

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
        $entries = $this->getDefaultPaymentLinkFileEntries();

        $entries[1][Header::AMOUNT] = 0;

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    protected function getDefaultPaymentLinkFileEntries()
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
                Header::PARTIAL_PAYMENT  => null,
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
                Header::PARTIAL_PAYMENT  => 0,
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
}
