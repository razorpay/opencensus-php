<?php

namespace RZP\Tests\Functional\Batch;

use Illuminate\Support\Facades\Queue;

use RZP\Models\Batch;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\TestCase;

class RecurringChargeTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/RecurringChargeTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

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

        Queue::assertPushed(BatchJob::class);
    }

    public function testCreateBatchOfRecurringChargeTypeStatus()
    {
        $entries = $this->getDefaultVirtualAccountFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $entities = $this->getLastEntity('batch', true);
        $this->assertEquals(1, $entities['success_count']);

        // Processing should have happened immediately in tests as
        // queue are sync basically.

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);
    }

    protected function getDefaultVirtualAccountFileEntries()
    {
        return [
            [
                Batch\Header::RECURRING_CHARGE_AMOUNT      => 100,
                Batch\Header::RECURRING_CHARGE_CURRENCY    => 'INR',
                Batch\Header::RECURRING_CHARGE_EMAIL       => 'test@test.test',
                Batch\Header::RECURRING_CHARGE_CONTACT     => '9999996666',
                Batch\Header::RECURRING_CHARGE_DESCRIPTION => 'random description',
                Batch\Header::RECURRING_CHARGE_CUSTOMER_ID => 'cust_100000customer',
                Batch\Header::RECURRING_CHARGE_TOKEN       => $this->token,
            ],
        ];
    }
}
