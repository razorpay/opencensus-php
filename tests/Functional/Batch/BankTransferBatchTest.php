<?php

namespace RZP\Tests\Functional\Batch;

use Illuminate\Support\Facades\Queue;

use RZP\Models\Batch;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\TestCase;

class BankTransferBatchTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BankTransferBatchTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->markTestSkipped('Bank transfer insertions are temporarily disabled');
    }

    public function testCreateBatchOfBankTransferTypeQueued()
    {
        Queue::fake();

        $entries = $this->getDefaultBankTransferFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        Queue::assertPushed(BatchJob::class);
    }

    public function testCreateBatchOfBankTransferTypeStatus()
    {
        $skipReason = 'Some validations does not work properly in queue CLI flow.';

        $this->markTestSkipped($skipReason);

        $entries = $this->getDefaultBankTransferFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->adminAuth();

        $response = $this->startTest();

        $this->ba->proxyAuth();

        // Gets last entity (Post queue processing) and asserts attributes
        $entities = $this->getLastEntity('batch', true);
        $this->assertEquals(1, $entities['success_count']);

        // Processing should have happened immediately in tests as
        // queue are sync basically.

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);

        // Not able to test this right now, since we've got checks for queues
        // TODO: Test if payment actually got created
        //
        // // Created bank transfer is an unexpected one, since we never created VA
        // $bankTransfer =  $this->getLastEntity('bank_transfer', true);
        // $this->assertEquals(false, $bankTransfer['expected']);
        // $this->assertEquals(5000000, $bankTransfer['amount']);
        // $this->assertNotNull($bankTransfer['payment_id']);

        // // Payment is not captured, since it was an unexpected one
        // $payment =  $this->getLastEntity('payment', true);
        // $this->assertEquals('bank_transfer', $payment['method']);
        // $this->assertEquals('authorized', $payment['status']);
        // $this->assertEquals(5000000, $payment['amount']);
        // $this->assertEquals($bankTransfer['payment_id'], $payment['id']);

        // // Customer bank account created
        // $bankAccount = $this->getLastEntity('bank_account', true);
        // $this->assertEquals('HDFC0000001', $bankAccount['ifsc']);
        // $this->assertEquals('9876543210123456789', $bankAccount['account_number']);
        // $this->assertEquals('Name of account holder', $bankAccount['name']);
    }

    protected function getDefaultBankTransferFileEntries()
    {
        return [
            [
                Batch\Header::PROVIDER       => 'dashboard',
                Batch\Header::PAYER_NAME     => 'Name of account holder',
                Batch\Header::PAYER_ACCOUNT  => '9876543210123456789',
                Batch\Header::PAYER_IFSC     => 'HDFC0000001',
                Batch\Header::PAYEE_ACCOUNT  => 'RZRP0001',
                Batch\Header::PAYEE_IFSC     => 'RAZR0000001',
                Batch\Header::MODE           => 'neft',
                Batch\Header::UTR            => 'HDFC111111111111',
                Batch\Header::TIME           => '148415544000',
                Batch\Header::AMOUNT         => 50000,
                Batch\Header::DESCRIPTION    => 'NEFT payment of 50,000 rupees',
            ],
        ];
    }
}
