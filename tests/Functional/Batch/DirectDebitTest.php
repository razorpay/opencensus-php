<?php
namespace RZP\Tests\Functional\Batch;

use Illuminate\Support\Facades\Queue;

use Cache;
use RZP\Models\Batch;
use RZP\Models\FileStore;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\Fixtures\Entity\Feature;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Batch\Header;

class DirectDebitTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/DirectDebitTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures(['direct_debit']);

        $this->fixtures->create('terminal:shared_sharp_terminal');
    }

    public function testCreateDirectDebitBatchQueued()
    {
        Queue::fake();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Just asserting that job is being pushed on creation of batch entity
        // for payment link type.
        Queue::assertPushedOn('payment_batch', BatchJob::class);

        Queue::assertPushed(BatchJob::class);
    }

    public function testCreateDirectDebitBatchStatus()
    {
        $entries = $this->getDefaultFileEntries();
        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $batch = $this->getLastEntity('batch', true);
        $this->assertEquals(2, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);
        $this->assertEquals(10000, $batch['processed_amount']);

        // Processing should have happened immediately in tests as
        // queue are sync basically.

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);

        // Input file is to be deleted
        $inputFile = $this->getFileForBatchOfType($response[Batch\Entity::ID], FileStore\Type::BATCH_INPUT);
        $this->assertNotNull($inputFile['deleted_at']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals('random receipt', $order['receipt']);
        $this->assertEquals('INR', $order['currency']);
        $this->assertEquals($order['notes']['notes_1'], 'random notes');
        $this->assertEquals($order['notes']['notes_2'], 123);
        $this->assertEquals($order['notes']['notes_3'], true);
        $this->assertEquals($order['notes']['notes_4'], null);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('INR', $payment['currency']);
        $this->assertEquals(9900, $payment['amount']);
        $this->assertEquals('random description', $payment['description']);
        $this->assertEquals($batch['id'], 'batch_'.$payment['batch_id']);
        $this->assertEquals('captured', $payment['status']);
    }

    public function testCreateDirectDebitBatchValidateFile()
    {
        $this->setUpConsumeTokenCacheMock();

        $this->ba->directAuth();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }


    protected function setUpConsumeTokenCacheMock()
    {
        $store = Cache::store();

        // TODO remove this after session migration
        Cache::shouldReceive('driver')
            ->andReturnUsing(function() use ($store)
            {
                return $store;
            });

        Cache::shouldReceive('pull')
                ->once()
                ->with('ott')
                ->andReturnUsing(function()
                {
                   return [
                        'merchantId' => '10000000000000',
                        'mode' => 'test',
                    ];
                })
                ->shouldReceive('store')
                ->withAnyArgs()
                ->andReturn($store);
    }

    public function getDefaultFileEntries()
    {
        return [
            [
                Header::DIRECT_DEBIT_EMAIL           => 'test@razorpay.com',
                Header::DIRECT_DEBIT_CONTACT         => 9876543210,
                Header::DIRECT_DEBIT_CARD_NUMBER     => '4111111111111111',
                Header::DIRECT_DEBIT_EXPIRY_MONTH    => '12',
                Header::DIRECT_DEBIT_EXPIRY_YEAR     => '25',
                Header::DIRECT_DEBIT_CARDHOLDER_NAME => 'John Doe 2',
                Header::DIRECT_DEBIT_AMOUNT          => 100,
                Header::DIRECT_DEBIT_CURRENCY        => 'INR',
                Header::DIRECT_DEBIT_RECEIPT         => 'random receipt',
                Header::DIRECT_DEBIT_DESCRIPTION     => 'random description',
                'notes[notes_1]'                     => null,
                'notes[notes_2]'                     => null,
                'notes[notes_3]'                     => null,
                'notes[notes_4]'                     => null,
            ],
            [
                Header::DIRECT_DEBIT_EMAIL           => 'test@razorpay.com',
                Header::DIRECT_DEBIT_CONTACT         => 9876543210,
                Header::DIRECT_DEBIT_CARD_NUMBER     => '4111111111111111',
                Header::DIRECT_DEBIT_EXPIRY_MONTH    => '12',
                Header::DIRECT_DEBIT_EXPIRY_YEAR     => '25',
                Header::DIRECT_DEBIT_CARDHOLDER_NAME => 'John Doe',
                Header::DIRECT_DEBIT_AMOUNT          => 9900,
                Header::DIRECT_DEBIT_CURRENCY        => 'INR',
                Header::DIRECT_DEBIT_RECEIPT         => 'random receipt',
                Header::DIRECT_DEBIT_DESCRIPTION     => 'random description',
                'notes[notes_1]'                     => 'random notes',
                'notes[notes_2]'                     => 123,
                'notes[notes_3]'                     => true,
                'notes[notes_4]'                     => null,
            ],
        ];
    }
}
