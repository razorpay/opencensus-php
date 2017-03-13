<?php

namespace RZP\Tests\Functional\Settlement;

use Carbon\Carbon;

use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Merchant\Account;
use RZP\Models\Settlement\Entity as SettlementEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;

class SettlementTest extends TestCase
{
    use RequestResponseFlowTrait;
    use SettlementTrait;
    use EntityActionTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/SettlementTestData.php';

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
                [
                    'merchant_id' => $merchant->getId(),
                    'amount' => '10000',
                ]
            );

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

    protected function createPaymentEntities(int $count = 5)
    {
        $createdAt = Carbon::today('Asia/Kolkata')->subDays(50)->timestamp + 5;
        $capturedAt = Carbon::today('Asia/Kolkata')->subDays(50)->timestamp + 10;

        $payments = $this->fixtures->times($count)->create(
            'payment:captured',
            [
                'captured_at' => $capturedAt,
                'method'      => 'card',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt + 10
            ]
        );

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

    public function testSettlementWithPayout()
    {
        // Create payments and refunds with timestamps two days back
        $payments = $this->createPaymentEntities();

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(10)->timestamp + 5;

        $payout = $this->fixtures->create(
            'payout',
            [
                'amount'     => '1000',
                'currency'   => 'INR',
                'created_at' => $createdAt,
                'updated_at' => $createdAt + 10
            ]);

        // Generate settlements for above transactions
        $content = $this->initiateSettlements();

        // (5 payments txn + 1 payout txn)
        $this->assertEquals(6, $content['kotak']['transaction_count']);
    }

    public function testMerchantSettlement()
    {
        $this->ba->appAuth();

        // $this->fixtures->merchant->createBankAccount();
        $this->fixtures->merchant->editFeeCredits('50000', Account::TEST_ACCOUNT);
        $this->fixtures->merchant->editCreditsforNodalAccount('50000', 'fee');

        $payments = $this->createPaymentEntities();

        foreach ($payments as $payment)
        {
            $attrs = [
                'payment' => $payments[0],
                'amount'  => '100'
            ];

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

        $batchSetl = $this->getLastEntity('batch_fund_transfer', true);

        $this->assertEquals($setl['batch_fund_transfer_id'], $batchSetl['id']);

        $request = [
            'url' => '/settlements/file/generate',
            'method' => 'post',
            'content' => ['batch_fund_transfer_id' => $batchSetl['id']]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $content = $this->getEntities('settlement_details', ['settlement_id' => $setl['id']], true);

        $this->assertArrayHasKey('entity', $content);
        $this->assertSame('collection', $content['entity']);
        $this->assertSame($content['count'], 5);

        $totalAmount = 0;
        $totalFeeCredits = 0;

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

            if (($details['type'] === 'credit') and
                ($details['component'] === 'fee_credits'))
            {
                $totalFeeCredits += $details['amount'];
            }
        }

        $totalTxnFeeCredits = 0;

        foreach ($txns['items'] as $txn)
        {
            $totalTxnFeeCredits += $txn['fee_credits'];
        }

        $this->assertEquals($totalTxnFeeCredits, $totalFeeCredits);

        $this->assertSame($totalAmount, $setl['amount']);

        // check settlement report
        $dt = Carbon::today('Asia/Kolkata');

        $input = array(
            'year' => $dt->year,
            'month' => $dt->month,
            'day' => $dt->day);

        $settlementReport = $this->fetchReport('settlement', $input);
        assert(count($settlementReport) === 1);
    }

    public function testMerchantSettlementV2()
    {
        $this->ba->adminAuth();

        $schedule = $this->createAndAssignSchedule();

        $this->ba->appAuth();

        $payments = $this->createPaymentEntities();

        foreach ($payments as $payment)
        {
            $attrs = ['payment' => $payments[0],
                      'amount'  => '100'];
            $refund = $this->fixtures->create('refund:from_payment', $attrs);
            $refunds[] = $refund;
        }

        $input = ['count' => 10];
        $txns = $this->getEntities('transaction', $input, true);

        $request = [
            'url' => '/settlements/initiate2/kotak',
            'method' => 'POST'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $setl = $this->getLastEntity('settlement', true);
        $this->assertTestResponse($setl, 'fetchAndMatchSettlementForV2');

        // Validate settlement txn entity
        $setlTxn = $this->getLastEntity('transaction', true);
        $this->assertEquals('settlement', $setlTxn['type']);
        $this->assertEquals($setl['id'], $setlTxn['entity_id']);
        $this->assertNull($setlTxn['reconciled_at']);

        // Validate settlement details entity
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

        // Validate batch settlement entity
        $batchFundTransfer = $this->getLastEntity('batch_fund_transfer', true);
        $this->assertNotNull($batchFundTransfer['urls']['kotak_settlement_txt']);
        $this->assertNotNull($batchFundTransfer['urls']['kotak_settlement_excel']);
        $this->assertTestResponse($batchFundTransfer, 'fetchAndMatchBatchDataSettlement');
        $this->assertGreaterThanOrEqual($batchFundTransfer['initiated_at'], time());
        $this->assertNull($batchFundTransfer['reconciled_at']);

        // Validate fund_transfer_attempt entity
        $bta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertTestResponse($bta, 'matchSettlementAttempt');
        $this->assertEquals($batchFundTransfer['id'], $bta['batch_transfer_id']);
        $this->assertEquals(SettlementEntity::verifyIdAndStripSign($setl['id']), $bta['source_id']);
    }

    public function testSettlementIgnoredTxns()
    {
        $this->ba->appAuth();

        $schedule = $this->createAndAssignSchedule();

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(10)->timestamp + 5;
        $capturedAt = Carbon::today('Asia/Kolkata')->subDays(10)->timestamp + 10;

        $payment = $this->fixtures->create(
            'payment:captured',
            [
                'amount'      => '1000',
                'captured_at' => $capturedAt,
                'method'      => 'card',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt + 10
            ]
        );

        $refund = $this->fixtures->create(
            'refund:from_payment',
            [
                'payment' => $payment,
                'amount'  => '1000',
            ]
        );

        // Payment for 10 rupees, followed by full refund.
        // Net amount to be settled is -23 paise, so will be ignored.

        $request = array(
            'url' => '/settlements/initiate2/kotak',
            'method' => 'POST'
        );

        $this->makeRequestAndGetContent($request);

        $txns = $this->getEntities('transaction', ['count' => 2]);

        foreach ($txns['items'] as $txn)
        {
            $this->assertEquals($txn['settled'], false);
        }
    }

    public function testSettlementFileGeneration()
    {
        $this->testMerchantSettlementV2();

        $setl = $this->getLastEntity('settlement', true);

        $request = array(
            'url' => '/settlements/file/generate',
            'method' => 'POST',
            'content' => [
                'batch_fund_transfer_id' => $setl['batch_fund_transfer_id']
            ]
        );

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotEquals($content, null);
    }

    public function testSettlementFileGenerationV1()
    {
        $this->ba->adminAuth();

        $schedule = $this->createAndAssignSchedule();

        $this->ba->appAuth();

        // Create payments for old date
        $createdAt = 1481500800; // 12th Dec 2016
        $capturedAt = $createdAt + 10;

        $this->fixtures->times(2)->create(
            'payment:captured',
            [
                'captured_at' => $capturedAt,
                'method'      => 'card',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt + 10
            ]
        );

        // Generate settlements for above transactions
        $request = array(
            'url' => '/settlements/initiate2/kotak',
            'method' => 'POST'
        );

        $this->makeRequestAndGetContent($request);

        // Modify created_at of batch so that the old settlement file generation can kick in
        $batch = $this->getLastEntity('batch_fund_transfer', true);

        $this->fixtures->edit('batch_fund_transfer', $batch['id'], ['created_at' => $capturedAt + 50]);

        // Generate settlement-file generation
        $request = array(
            'url' => '/settlements/file/generate',
            'method' => 'POST',
            'content' => [
                'batch_fund_transfer_id' => $batch['id']
            ]
        );

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotEquals($content, null);
    }

    public function testIciciNodalTransfer()
    {
        $this->ba->appAuth();

        $request = [
            'url'     => '/nodal/transfer/icici',
            'method'  => 'POST',
            'content' => [
                'amount' => 1076
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotEquals(null, $content['file']);
    }

    public function testSettlementWithAccountTransfer()
    {
        $payment = $this->createPaymentEntities(1);

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(10)->timestamp + 5;

        $transfer = $this->fixtures->create(
            'transfer:to_account',
            [
                'source_id'  => $payment->getId(),
                'source_type'=> 'payment',
                'amount'     => 5000,
                'currency'   => 'INR',
                'created_at' => $createdAt,
                'updated_at' => $createdAt + 10
            ]);

        // Generate settlements
        $content = $this->initiateSettlements();

        $lastSetl = $this->getLastEntity('settlement', true);

        // Assert linked account settlement
        $this->assertEquals($transfer['to_id'], $lastSetl['merchant_id']);
        $this->assertEquals(5000, $lastSetl['amount']);

        // (1 payment txn + 1 transfer txn + 1 transfer payment txn)
        $this->assertEquals(3, $content['kotak']['transaction_count']);
    }

    public function testSettlementAccountTransferOnHold()
    {
        $payment = $this->createPaymentEntities(1);

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(10)->timestamp + 5;

        $transfer = $this->fixtures->create(
            'transfer:to_account',
            [
                'source_id'  => $payment->getId(),
                'source_type'=> 'payment',
                'amount'     => 5000,
                'currency'   => 'INR',
                'on_hold'    => '1',
                'created_at' => $createdAt,
                'updated_at' => $createdAt + 10
            ]);

        // Generate settlements
        $content = $this->initiateSettlements();

        // 1 payment txn + 1 transfer txn
        $this->assertEquals(2, $content['kotak']['transaction_count']);

        // The txn for the transfer payment to the merchant should not settled
        $trfPayment = $this->getEntities('payment', ['transfer_id' => $transfer->getId()], true)['items'][0];
        $txn = $this->getEntityById('transaction', $trfPayment['transaction_id'], true);
        $this->assertEquals('payment', $txn['type']);
        $this->assertEquals($transfer->toArrayAdmin()['recipient'], 'acc_' . $txn['merchant_id']);
        $this->assertFalse($txn['settled']);
    }

    public function testSettletmentForTransferReversal()
    {
        $payment = $this->createPaymentEntities(1);

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(10)->timestamp + 5;

        $account = $this->fixtures->create('merchant:marketplace_account', ['balance' => 250000]);

        $transfer = $this->fixtures->times(2)->create(
            'transfer:to_account',
            [
                'account'    => $account,
                'source_id'  => $payment->getId(),
                'source_type'=> 'payment',
                'amount'     => 1000,
                'currency'   => 'INR',
                'created_at' => $createdAt,
                'updated_at' => $createdAt + 10
            ]);

        $reversal = $this->fixtures->create(
            'reversal',
            [
                'transfer_id'   => $transfer[1]->getId(),
                'amount'        => 90,
                'created_at'    => $createdAt + 10,
                'updated_at'    => $createdAt + 20
            ]);

        $content = $this->initiateSettlements();

        $lastSetl = $this->getLastEntity('settlement', true);

        // Assert linked account settlement
        $this->assertEquals($transfer[1]['to_id'], $lastSetl['merchant_id']);

        //
        // transfer 1 -> credit 1000 + transfer 2 -> credit 1000
        // reverse transfer 1 -> debit 1000
        // total => 1000
        //
        $this->assertEquals(1000, $lastSetl['amount']);

        //
        // (1 payment txn +
        //  2 transfer txn + 2 transfer payment txn +
        //  1 transfer payment refund txn + 1 reversal txn)
        //
        $this->assertEquals(7, $content['kotak']['transaction_count']);
    }

    protected function createAndAssignSchedule()
    {
        $request = array(
            'url' => '/merchants/'. Account::TEST_ACCOUNT . '/schedules',
            'method' => 'POST',
            'content' => array(
                'name'        => 'Basic T3',
                'type'        => 'settlement',
                'period'      => 'daily',
                'interval'    => 1,
                'delay'       => 3,
                'next_run'    => 1451586600,
            )
        );

        $response = $this->makeRequestAndGetContent($request);

        return $response;
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
