<?php

namespace RZP\Tests\Functional\Settlement;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

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

                $this->assertEquals(2300, $txn->fee);
                $this->assertEquals(7700, $txn->credit);
                $this->assertEquals(0, $txn->debit);
                $this->assertEquals(300, $txn->service_tax);
            }

            $this->assertEquals(15400, $merchant->balance->getBalance());

            $merchantPayments[] = $payments;
        }

        $attrs = ['payment' => $merchantPayments[0][0]];

        $refund = $this->fixtures->create('refund:from_payment', $attrs);

        $this->assertEquals(10000, $refund->transaction->debit);

        $this->assertEquals(5400, $merchants[0]->balance->reload()->getBalance());
        $this->assertEquals(5400, $merchants[0]->balance->reload()->getBalance());
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

            $response = $this->sendRequest($request);

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

    // Random settlement holiday - Test for live mode
    public function testSettlementOnHolidayInLiveMode()
    {

        $this->ba->publicLiveAuth();

        $days = $this->getDaysForSettlementHolidayTests();

        $createdAt = Carbon::parse($days['payment_created_at'], 'Asia/Kolkata')->timestamp;
        $capturedAt = Carbon::parse($days['payment_created_at'], 'Asia/Kolkata')->timestamp + 10;

        $payments = $this->fixtures->times(5)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt + 10]);


        $setDate = Carbon::parse($days['payment_settlement_on'],'Asia/Kolkata');

        Carbon::setTestNow($setDate);

        // Generate settlements for above transactions
        $content = $this->initiateSettlements();

        $this->assertEquals('Today is a holiday! Happy holidays :)', $content['message']);

        // Reset test params
        Carbon::setTestNow();
        $this->ba->publicAuth();
    }

    // Random settlement non holiday - Test for live mode
    public function testSettlementOnNonHolidayInLiveMode()
    {
        $this->ba->publicLiveAuth();

        $days = $this->getDaysForSettlementNonHolidayTests();

        $createdAt = Carbon::parse($days['payment_created_at'], 'Asia/Kolkata')->timestamp;
        $capturedAt = Carbon::parse($days['payment_created_at'], 'Asia/Kolkata')->timestamp + 10;

        $payments = $this->fixtures->times(5)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt + 10]);

        $txn = $this->getEntities('transaction',[],true);

        $setDate = Carbon::parse($days['payment_settlement_on'],'Asia/Kolkata');
        Carbon::setTestNow($setDate);

        // Generate settlements for above transactions
        $content = $this->initiateSettlements();

        $this->assertEquals(5, $content['kotak']['transaction_count']);

        // Reset test params
        Carbon::setTestNow();
        $this->ba->publicAuth();
    }

    // Random settlement holiday - Test for test mode
    public function testSettlementOnHolidayInTestMode()
    {
        $days = $this->getDaysForSettlementHolidayTests();

        $createdAt = Carbon::parse($days['payment_created_at'], 'Asia/Kolkata')->timestamp;
        $capturedAt = Carbon::parse($days['payment_created_at'], 'Asia/Kolkata')->timestamp + 10;

        $payments = $this->fixtures->times(5)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt + 10]);

        $setDate = Carbon::parse($days['payment_settlement_on'],'Asia/Kolkata');
        Carbon::setTestNow($setDate);

        // Generate settlements for above transactions
        $content = $this->initiateSettlements();

        $this->assertEquals(5, $content['kotak']['transaction_count']);

        Carbon::setTestNow();
    }

    // Random settlement non holiday - Test for test mode
    public function testSettlementOnNonHolidayInTestMode()
    {
        $days = $this->getDaysForSettlementNonHolidayTests();

        $createdAt = Carbon::parse($days['payment_created_at'], 'Asia/Kolkata')->timestamp;
        $capturedAt = Carbon::parse($days['payment_created_at'], 'Asia/Kolkata')->timestamp + 10;

        $payments = $this->fixtures->times(5)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt + 10]);

        $setDate = Carbon::parse($days['payment_settlement_on'],'Asia/Kolkata');
        Carbon::setTestNow($setDate);

        // Generate settlements for above transactions
        $content = $this->initiateSettlements();

        $this->assertEquals(5, $content['kotak']['transaction_count']);

        Carbon::setTestNow();
    }

    public function testMerchantSettlement()
    {
        $this->ba->appAuth();

        // $this->fixtures->merchant->createBankAccount();

        $payments = $this->createPaymentEntities();

        foreach ($payments as $payment)
        {
            $attrs = ['payment' => $payments[0],
                      'amount'  => '100'];
            $refund = $this->fixtures->create('refund:from_payment', $attrs);
            $refunds[] = $refund;
        }

        $input = array('count' => 10);
        $txns = $this->getEntities('transaction', $input, true);

        $request = array(
            'url' => '/settlements/initiate/kotak',
            'method' => 'POST'
        );

        $content = $this->makeRequestAndGetContent($request);

        $setl = $this->getLastEntity('settlement', true);

        // $request = array('url' => '/settlements/details', 'method' => 'post');
        // $content = $this->makeRequestAndGetContent($request);
        // sd($content);

        $content = $this->getEntities('settlement_details', ['settlement_id' => $setl['id']], true);

        $this->assertArrayHasKey('entity', $content);
        $this->assertSame('collection', $content['entity']);
        $this->assertSame($content['count'], 4);

        $totalAmount = 0;

        foreach ($content['items'] as $details)
        {
            if ($details['type'] == 'debit')
            {
                $totalAmount -= $details['amount'];
            }
            else
            {
                $totalAmount += $details['amount'];
            }
        }

        $this->assertSame($totalAmount, $setl['amount']);
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
