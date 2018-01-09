<?php

namespace RZP\Tests\Functional\Batch;

use Illuminate\Support\Facades\Queue;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Batch;
use RZP\Jobs\Batch as BatchJob;

class PayoutTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/PayoutTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures(['payout']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');
    }

    public function testCreateBatchOfPayoutTypeQueued()
    {
        Queue::fake();

        $entries = $this->getDefaultPayoutFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        Queue::assertPushed(BatchJob::class);
    }

    public function testCreateBatchOfPayoutTypeStatus()
    {
        $entries = $this->getDefaultPayoutFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $entities = $this->getLastEntity('batch', true);
        $this->assertEquals(3, $entities['success_count']);
        $this->assertEquals(3, $entities['failure_count']);

        // Processing should have happened immediately in tests as
        // queue are sync basically.

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);
    }

    protected function getDefaultPayoutFileEntries()
    {
        return [
            [
                Batch\Header::PAYOUT_CUSTOMER_NAME       => 'test customer',
                Batch\Header::PAYOUT_CUSTOMER_CONTACT    => '9999999999',
                Batch\Header::PAYOUT_CUSTOMER_EMAIL      => 'hi@bi.com',
                Batch\Header::PAYOUT_BANK_ACCOUNT_NUMBER => '10101010101010',
                Batch\Header::PAYOUT_BANK_IFSC           => 'RAZR0000001',
                Batch\Header::PAYOUT_METHOD              => 'fund_transfer',
                Batch\Header::PAYOUT_AMOUNT              => '1000',
                Batch\Header::PAYOUT_CURRENCY            => 'INR',
                Batch\Header::PAYOUT_NOTES               => '{"a": "b"}',
            ],
            [
                Batch\Header::PAYOUT_CUSTOMER_NAME       => 'test customer',
                Batch\Header::PAYOUT_CUSTOMER_CONTACT    => '9999999999',
                Batch\Header::PAYOUT_CUSTOMER_EMAIL      => 'hi@bi.com',
                Batch\Header::PAYOUT_BANK_ACCOUNT_NUMBER => '10101010101010',
                Batch\Header::PAYOUT_BANK_IFSC           => 'RAZR0000001',
                Batch\Header::PAYOUT_METHOD              => 'fund_transfer',
                Batch\Header::PAYOUT_AMOUNT              => '1000',
                Batch\Header::PAYOUT_CURRENCY            => 'INR',
                Batch\Header::PAYOUT_NOTES               => '',
            ],
            [
                Batch\Header::PAYOUT_CUSTOMER_NAME       => 'test customer',
                Batch\Header::PAYOUT_CUSTOMER_CONTACT    => '9999999999',
                Batch\Header::PAYOUT_CUSTOMER_EMAIL      => 'hi@bi.com',
                Batch\Header::PAYOUT_BANK_ACCOUNT_NUMBER => '10101010101010',
                Batch\Header::PAYOUT_BANK_IFSC           => 'RAZR0000001',
                Batch\Header::PAYOUT_METHOD              => 'fund_transfer',
                Batch\Header::PAYOUT_AMOUNT              => '1000',
                Batch\Header::PAYOUT_CURRENCY            => 'INR',
                Batch\Header::PAYOUT_NOTES               => null,
            ],
            // Following should fail
            [
                Batch\Header::PAYOUT_CUSTOMER_NAME       => 'test~customer',
                Batch\Header::PAYOUT_CUSTOMER_CONTACT    => '9999999999',
                Batch\Header::PAYOUT_CUSTOMER_EMAIL      => 'hi@bi.com',
                Batch\Header::PAYOUT_BANK_ACCOUNT_NUMBER => '10101010101010',
                Batch\Header::PAYOUT_BANK_IFSC           => 'RAZR0000001',
                Batch\Header::PAYOUT_METHOD              => 'fund_transfer',
                Batch\Header::PAYOUT_AMOUNT              => '100',
                Batch\Header::PAYOUT_CURRENCY            => 'INR',
                Batch\Header::PAYOUT_NOTES               => '{"a": "b"}',
            ],
            [
                Batch\Header::PAYOUT_CUSTOMER_NAME       => 'test customer',
                Batch\Header::PAYOUT_CUSTOMER_CONTACT    => '9999999999',
                Batch\Header::PAYOUT_CUSTOMER_EMAIL      => 'hi@bi.com',
                Batch\Header::PAYOUT_BANK_ACCOUNT_NUMBER => '10101010101010',
                Batch\Header::PAYOUT_BANK_IFSC           => null,
                Batch\Header::PAYOUT_METHOD              => 'fund_transfer',
                Batch\Header::PAYOUT_AMOUNT              => '100',
                Batch\Header::PAYOUT_CURRENCY            => 'INR',
                Batch\Header::PAYOUT_NOTES               => '',
            ],
            [
                Batch\Header::PAYOUT_CUSTOMER_NAME       => 'test customer',
                Batch\Header::PAYOUT_CUSTOMER_CONTACT    => '9999999999',
                Batch\Header::PAYOUT_CUSTOMER_EMAIL      => 'hi@bi.com',
                Batch\Header::PAYOUT_BANK_ACCOUNT_NUMBER => '10101010101010',
                Batch\Header::PAYOUT_BANK_IFSC           => 'RAZR0000001',
                Batch\Header::PAYOUT_METHOD              => null,
                Batch\Header::PAYOUT_AMOUNT              => '100',
                Batch\Header::PAYOUT_CURRENCY            => 'INR',
                Batch\Header::PAYOUT_NOTES               => null,
            ],
        ];
    }
}
