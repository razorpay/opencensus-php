<?php

namespace RZP\Tests\Functional\Batch;

use Illuminate\Support\Facades\Queue;

use RZP\Models\Batch;
use RZP\Models\Settings;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\TestCase;

class RecurringChargeTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/RecurringChargeTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->mockCardVault();

        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $paymentRequest = $this->getDefaultRecurringPaymentArray();

        $this->doAuthPayment($paymentRequest);

        $payment = $this->getLastEntity('payment', true);

        $this->token = $payment['token_id'];

        $this->ba->proxyAuth();
    }

    public function testCreateBatchOfRecurringChargeTypeQueued()
    {
        Queue::fake();
        $entries = $this->getDefaultVirtualAccountFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testCreateBatchOfRecurringChargeTypeStatus()
    {
        $entries = $this->getDefaultVirtualAccountFileEntries();
        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $entities = $this->getLastEntity('batch', true);
        $this->assertEquals(2, $entities['success_count']);

        // Processing should have happened immediately in tests as
        // queue are sync basically.

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);

        $batch = $this->getLastEntity('batch', true);
        $this->assertEquals('processed', $batch['status']);
        $this->assertEquals(200, $batch['processed_amount']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals('random receipt', $order['receipt']);
        $this->assertEquals('INR', $order['currency']);
        $this->assertEquals($order['notes']['notes_1'], 'random notes');
        $this->assertEquals($order['notes']['notes_2'], 123);
        $this->assertEquals($order['notes']['notes_3'], true);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('INR', $payment['currency']);
        $this->assertEquals('cust_100000customer', $payment['customer_id']);
        $this->assertEquals(100, $payment['amount']);
        $this->assertEquals('random description', $payment['description']);
        $this->assertEquals($batch['id'], 'batch_'.$payment['batch_id']);
    }

    public function testCreateBatchOfRecurringChargeWithRupeeAmount()
    {
        $merchant = $this->getDbEntityById('merchant','10000000000000');

        Settings\Accessor::for($merchant, Settings\Module::BATCH)
                         ->upsert('recurring_charge', ['amount_as_rupee' => '1'])->save();

        $entries = $this->getBatchFileEntriesWithRupee();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        $entities = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $entities['success_count']);

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);

        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals('processed', $batch['status']);

        $this->assertEquals(200, $batch['processed_amount']);

        $order = $this->getLastEntity('order', true);

        $this->assertEquals('random receipt', $order['receipt']);

        $this->assertEquals('INR', $order['currency']);

        $this->assertEquals($order['notes']['notes_1'], 'random notes');

        $this->assertEquals($order['notes']['notes_2'], 123);

        $this->assertEquals($order['notes']['notes_3'], true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('INR', $payment['currency']);

        $this->assertEquals('cust_100000customer', $payment['customer_id']);

        $this->assertEquals(10000, $payment['amount']);

        $this->assertEquals('random description', $payment['description']);

        $this->assertEquals($batch['id'], 'batch_'.$payment['batch_id']);
    }

    public function testCreateBatchOfRecurringChargeWithoutCustomerId()
    {
        $entries = $this->getBatchFileEntriesWithoutCustomerId();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $entities = $this->getLastEntity('batch', true);
        $this->assertEquals(2, $entities['success_count']);

        // Processing should have happened immediately in tests as
        // queue are sync basically.

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);

        $batch = $this->getLastEntity('batch', true);
        $this->assertEquals('processed', $batch['status']);
        $this->assertEquals(200, $batch['processed_amount']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals('random receipt', $order['receipt']);
        $this->assertEquals('INR', $order['currency']);
        $this->assertEquals($order['notes']['notes_1'], 'random notes');
        $this->assertEquals($order['notes']['notes_2'], 123);
        $this->assertEquals($order['notes']['notes_3'], true);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('INR', $payment['currency']);
        $this->assertEquals('cust_100000customer', $payment['customer_id']);
        $this->assertEquals(100, $payment['amount']);
        $this->assertEquals('random description', $payment['description']);
        $this->assertEquals($batch['id'], 'batch_'.$payment['batch_id']);
    }

    protected function getDefaultVirtualAccountFileEntries()
    {
        return [
            [
                Batch\Header::RECURRING_CHARGE_TOKEN       => $this->token,
                Batch\Header::RECURRING_CHARGE_CUSTOMER_ID => 'cust_100000customer',
                Batch\Header::RECURRING_CHARGE_AMOUNT      => 100,
                Batch\Header::RECURRING_CHARGE_CURRENCY    => 'INR',
                Batch\Header::RECURRING_CHARGE_RECEIPT     => '',
                Batch\Header::RECURRING_CHARGE_DESCRIPTION => null,
                'notes[notes_1]'                           => null,
                'notes[notes_2]'                           => null,
                'notes[notes_3]'                           => null,
                'notes[notes_4]'                           => null,
                'notes[notes_5]'                           => null,

            ],
            [
                Batch\Header::RECURRING_CHARGE_TOKEN       => $this->token,
                Batch\Header::RECURRING_CHARGE_CUSTOMER_ID => 'cust_100000customer',
                Batch\Header::RECURRING_CHARGE_AMOUNT      => 100,
                Batch\Header::RECURRING_CHARGE_CURRENCY    => 'INR',
                Batch\Header::RECURRING_CHARGE_RECEIPT     => 'random receipt',
                Batch\Header::RECURRING_CHARGE_DESCRIPTION => 'random description',
                'notes[notes_1]'                           => 'random notes',
                'notes[notes_2]'                           =>  123,
                'notes[notes_3]'                           =>  true,
                'notes[notes_4]'                           =>  '',
                'notes[notes_5]'                           =>  null

            ],
        ];
    }

    protected function getBatchFileEntriesWithRupee()
    {
        return [
            [
                Batch\Header::RECURRING_CHARGE_TOKEN       => $this->token,
                Batch\Header::RECURRING_CHARGE_CUSTOMER_ID => 'cust_100000customer',
                Batch\Header::RECURRING_CHARGE_AMOUNT      => 100,
                Batch\Header::RECURRING_CHARGE_CURRENCY    => 'INR',
                Batch\Header::RECURRING_CHARGE_RECEIPT     => '',
                Batch\Header::RECURRING_CHARGE_DESCRIPTION => null,
                'notes[notes_1]'                           => null,
                'notes[notes_2]'                           => null,
                'notes[notes_3]'                           => null,
                'notes[notes_4]'                           => null,
                'notes[notes_5]'                           => null,
            ],
            [
                Batch\Header::RECURRING_CHARGE_TOKEN       => $this->token,
                Batch\Header::RECURRING_CHARGE_CUSTOMER_ID => 'cust_100000customer',
                Batch\Header::RECURRING_CHARGE_AMOUNT      => 100,
                Batch\Header::RECURRING_CHARGE_CURRENCY    => 'INR',
                Batch\Header::RECURRING_CHARGE_RECEIPT     => 'random receipt',
                Batch\Header::RECURRING_CHARGE_DESCRIPTION => 'random description',
                'notes[notes_1]'                           => 'random notes',
                'notes[notes_2]'                           =>  123,
                'notes[notes_3]'                           =>  true,
                'notes[notes_4]'                           =>  '',
                'notes[notes_5]'                           =>  null
            ],
        ];
    }

    protected function getBatchFileEntriesWithoutCustomerId()
    {
        return [
            [
                Batch\Header::RECURRING_CHARGE_TOKEN       => $this->token,
                Batch\Header::RECURRING_CHARGE_CUSTOMER_ID => '',
                Batch\Header::RECURRING_CHARGE_AMOUNT      => 100,
                Batch\Header::RECURRING_CHARGE_CURRENCY    => 'inr',
                Batch\Header::RECURRING_CHARGE_RECEIPT     => '',
                Batch\Header::RECURRING_CHARGE_DESCRIPTION => null,
                'notes[notes_1]'                           => null,
                'notes[notes_2]'                           => null,
                'notes[notes_3]'                           => null,
                'notes[notes_4]'                           => null,
                'notes[notes_5]'                           => null,

            ],
            [
                Batch\Header::RECURRING_CHARGE_TOKEN       => $this->token,
                Batch\Header::RECURRING_CHARGE_CUSTOMER_ID => '',
                Batch\Header::RECURRING_CHARGE_AMOUNT      => 100,
                Batch\Header::RECURRING_CHARGE_CURRENCY    => 'INR',
                Batch\Header::RECURRING_CHARGE_RECEIPT     => 'random receipt',
                Batch\Header::RECURRING_CHARGE_DESCRIPTION => 'random description',
                'notes[notes_1]'                           => 'random notes',
                'notes[notes_2]'                           =>  123,
                'notes[notes_3]'                           =>  true,
                'notes[notes_4]'                           =>  '',
                'notes[notes_5]'                           =>  null

            ],
        ];
    }
}
