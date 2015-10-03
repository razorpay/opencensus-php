<?php

namespace Tests\Functional\Settlement;

use Carbon\Carbon;
use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class SettlementTest extends TestCase
{
    use RequestResponseFlowTrait;
    use SettlementTrait;

    public function setUp()
    {
        parent::setUp();

        $this->ba->publicAuth();
    }

    public function testSettlement()
    {
        $pricing = $this->fixtures->create('pricing:standard_plan');

        $merchants = $this->fixtures->times(3)->create('merchant:with_balance_terminals_standard_pricing');

        $merchantPayments = [];

        $i = 0;

        foreach ($merchants as $merchant)
        {
            $payments = $this->fixtures->times(2)->create(
                'payment:captured',
                ['merchant_id' => $merchant->getId(),
                 'amount' => '10000']);

            foreach ($payments as $payment)
            {
                $txn = $payment->transaction;

                $this->assertEquals(2280, $txn->fee);
                $this->assertEquals(7720, $txn->credit);
                $this->assertEquals(0, $txn->debit);
            }

            $this->assertEquals(15440, $merchant->balance->getBalance());

            $merchantPayments[] = $payments;
        }

        $attrs = ['payment' => $merchantPayments[0][0]];

        $refund = $this->fixtures->create('refund:from_payment', $attrs);

        $this->assertEquals(10000, $refund->transaction->debit);

        $this->assertEquals(5440, $merchants[0]->balance->reload()->getBalance());
        $this->assertEquals(5440, $merchants[0]->balance->reload()->getBalance());
    }

    /**
     * Tests the different settlement routes which are expecting to read
     * a file. Even if there is no file to read, they should exit
     * gracefully with no fuss!
     */
    public function testSetlRoutesReadingFileWithNoFile()
    {
        $this->deleteSetlFiles();

        $urls = [
            '/settlements/reconcile/generate',
            '/settlements/reconcile',
            '/settlements/return',
        ];

        $this->ba->appAuth();

        // Verify by hitting each route that in case no file
        // present, it returns without issues.
        foreach ($urls as $url)
        {
            $request = ['url' => $url];

            $response = $this->makeRequest($request);

            $this->assertResponseStatus(200);
        }
    }

    public function testHoldFundsDuringSettlement()
    {
        $this->fixtures->merchant->holdFunds('10000000000000');

        // Create payments and refunds with timestamps two days back
        $payments = $this->createPaymentEntities();

        // Generate settlements for above transactions
        $content = $this->initiateSettlements();

        $this->assertEquals(0, $content['kotak']['transaction_count']);
    }

    public function createPaymentEntities()
    {
        $prEntities = array();

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(10)->timestamp + 5;
        $capturedAt = Carbon::today('Asia/Kolkata')->subDays(10)->timestamp + 10;

        $payments = $this->fixtures->times(5)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt + 10]);

        return $payments;
    }

    protected function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }
}