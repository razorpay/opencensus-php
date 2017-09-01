<?php

namespace RZP\Tests\Functional\Settlement;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;

use RZP\Mail\Settlement\AxisSettlement as AxisSettlementMail;
use RZP\Mail\Settlement\IciciSettlement as IciciSettlementMail;
use RZP\Mail\Settlement\KotakSettlement as KotakSettlementMail;
use RZP\Mail\Settlement\KotakPayout as KotakPayoutMail;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Merchant\Account;
use RZP\Models\Settlement\Entity as SettlementEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class SettlementTest extends TestCase
{
    use SettlementTrait;
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/SettlementTestData.php';

        parent::setUp();

        $this->ba->publicAuth();
    }

    public function testSettlement()
    {
        $this->fixtures->create('pricing:standard_plan');

        $merchants = $this->fixtures->times(3)->create('merchant:with_balance_terminals_standard_pricing');

        $merchantPayments = [];

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

                $this->assertEquals(2000, $txn->fee);
                $this->assertEquals(8000, $txn->credit);
                $this->assertEquals(0, $txn->debit);
                $this->assertEquals(0, $txn->tax);
                //$this->assertEquals(0, $txn->service_tax);
            }

            $this->assertEquals(16000, $merchant->balance->getBalance());

            $merchantPayments[] = $payments;
        }

        $attrs = ['payment' => $merchantPayments[0][0]];

        $refund = $this->fixtures->create('refund:from_payment', $attrs);

        $this->assertEquals(10000, $refund->transaction->debit);

        $this->assertEquals(6000, $merchants[0]->balance->reload()->getBalance());
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
        ];

        $this->ba->appAuth();

        // Verify by hitting each route that in case no file
        // present, it returns without issues.
        foreach ($urls as $url)
        {
            $request = ['url' => $url];

            $response = $this->sendRequest($request);
            $response->assertStatus(200);
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

        $content = $this->getEntities('file_store', [], true);
        $this->assertSame($content['count'], 0);
    }

    protected function createPaymentEntities(int $count = 5)
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(50)->timestamp + 5;
        $capturedAt = Carbon::today(Timezone::IST)->subDays(50)->timestamp + 10;

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

        $createdAt = Carbon::parse($days['payment_created_at'], Timezone::IST)->timestamp;
        $capturedAt = Carbon::parse($days['payment_created_at'], Timezone::IST)->timestamp + 10;

        $payments = $this->fixtures->times(5)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt]);

        $setDate = Carbon::parse($days['payment_settlment_holiday'],Timezone::IST);

        Carbon::setTestNow($setDate);

        // Generate settlements for above transactions
        $content = $this->initiateSettlements();

        $this->assertEquals('Today is a holiday! Happy holidays :)', $content['message']);

        $content = $this->getEntities('file_store', [], true);
        $this->assertSame($content['count'], 0);

        // Reset test params
        Carbon::setTestNow();
        $this->ba->publicAuth();
    }

    // Random settlement non holiday - Test for live mode
    public function testSettlementOnNonHolidayInLiveMode()
    {
        $this->ba->publicLiveAuth();

        $days = $this->getDaysForSettlementNonHolidayTests();

        $createdAt = Carbon::parse($days['payment_created_at'], Timezone::IST)->timestamp;
        $capturedAt = Carbon::parse($days['payment_created_at'], Timezone::IST)->timestamp + 10;

        $payments = $this->fixtures->times(5)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt]);

        $txn = $this->getEntities('transaction',[],true);

        $setDate = Carbon::parse($days['payment_settlement_on'], Timezone::IST);
        Carbon::setTestNow($setDate);

        // Generate settlements for above transactions
        $content = $this->initiateSettlements();

        $this->assertEquals(5, $content['kotak']['transaction_count']);

        // Validate 2 files were created
        $content = $this->getEntities('file_store', [], true);
        $this->assertSame($content['count'], 2);

        // Reset test params
        Carbon::setTestNow();
        $this->ba->publicAuth();
    }

    // Random settlement holiday - Test for test mode
    public function testSettlementOnHolidayInTestMode()
    {
        $days = $this->getDaysForSettlementHolidayTests();

        $createdAt = Carbon::parse($days['payment_created_at'], Timezone::IST)->timestamp;
        $capturedAt = Carbon::parse($days['payment_created_at'], Timezone::IST)->timestamp + 10;

        $payments = $this->fixtures->times(5)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt]);

        $setDate = Carbon::parse($days['payment_settlement_on'], Timezone::IST);
        Carbon::setTestNow($setDate);

        // Generate settlements for above transactions
        $content = $this->initiateSettlements();

        $this->assertEquals(5, $content['kotak']['transaction_count']);

        // Validate 2 files were created
        $content = $this->getEntities('file_store', [], true);
        $this->assertSame($content['count'], 2);

        Carbon::setTestNow();
    }

    // Random settlement non holiday - Test for test mode
    public function testSettlementOnNonHolidayInTestMode()
    {
        $days = $this->getDaysForSettlementNonHolidayTests();

        $createdAt = Carbon::parse($days['payment_created_at'], Timezone::IST)->timestamp;
        $capturedAt = Carbon::parse($days['payment_created_at'], Timezone::IST)->timestamp + 10;

        $payments = $this->fixtures->times(5)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt + 10]);

        $setDate = Carbon::parse($days['payment_settlement_on'],Timezone::IST);
        Carbon::setTestNow($setDate);

        // Generate settlements for above transactions
        $content = $this->initiateSettlements();

        $this->assertEquals(5, $content['kotak']['transaction_count']);

        // Validate 2 files were created
        $content = $this->getEntities('file_store', [], true);
        $this->assertSame($content['count'], 2);

        Carbon::setTestNow();
    }

    public function testSettlementWithPayout()
    {
        // Create payments and refunds with timestamps two days back
        $payments = $this->createPaymentEntities();

        $createdAt = Carbon::today(Timezone::IST)->subDays(10)->timestamp + 5;

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

        $this->fixtures->create('credits', [
                       'type'        => 'fee',
                       'value'       => 50000,
                   ]);

         $this->fixtures->create('credits', [
                       'type'        => 'fee',
                       'value'       => 50000,
                       'merchant_id' => '10NodalAccount',
                   ]);

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

        $batchFundTransfer = $this->getLastEntity('batch_fund_transfer', true);
        $setlAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($setlAttempt['batch_fund_transfer_id'], $batchFundTransfer['id']);
        $this->assertEquals($setlAttempt['source'], $setl['id']);

        $request = [
            'url' => '/settlements/file/generate',
            'method' => 'post',
            'content' => ['batch_fund_transfer_id' => $batchFundTransfer['id']]
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
        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year'  => $dt->year,
            'month' => $dt->month,
            'day'   => $dt->day
        ];

        $settlementReport = $this->fetchReport('settlement', $input);
        assert(count($settlementReport) === 1);
    }

    public function testMerchantSettlementV2()
    {
        Mail::fake();

        $this->ba->adminAuth();

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
            'url' => '/settlements/initiate/kotak',
            'method' => 'POST'
        ];

        $setlResponse = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($setlResponse['kotak']);
        $this->assertNotNull($setlResponse['kotak']['settlement_text_file']);
        $this->assertNotNull($setlResponse['kotak']['settlement_excel_file']);

        $setl = $this->getLastEntity('settlement', true);
        $this->assertTestResponse($setl, 'fetchAndMatchSettlement');

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
        $this->assertNotNull($batchFundTransfer['txt_file_id']);
        $this->assertNotNull($batchFundTransfer['excel_file_id']);

        // Validate association of settlement with batch
        $this->assertEquals($batchFundTransfer['id'], $setl['batch_fund_transfer_id']);

        // Validate fund_transfer_attempt entity
        $bta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertTestResponse($bta, 'matchSettlementAttempt');
        $this->assertEquals($batchFundTransfer['id'], $bta['batch_fund_transfer_id']);
        $this->assertEquals($setl['id'], $bta['source']);

        $content = $this->getEntities('file_store', [], true);
        $this->assertSame($content['count'], 2);

        Mail::assertSent(KotakSettlementMail::class);
    }

    public function testSettlementForMultipleMerchants()
    {
        $this->ba->appAuth();

        $merchants = $this->fixtures->times(2)->create('merchant');

        $firstMerchant = $merchants[0]->getId();
        $secondMerchant = $merchants[1]->getId();

        $amount = 10000;

        foreach ($merchants as $merchant)
        {
            $merchantId = $merchant->getId();

            $balance = $this->fixtures->create('balance', ['id' => $merchantId]);

            $this->fixtures->create('terminal', ['merchant_id' => $merchantId]);

            $this->fixtures->create(
                'bank_account',
                ['entity_id' => $merchantId, 'beneficiary_name' => random_alpha_string(10)]);

            $createdAt = Carbon::today(Timezone::IST)->subDays(50)->timestamp + 5;
            $capturedAt = Carbon::today(Timezone::IST)->subDays(50)->timestamp + 10;

            $payments = $this->fixtures->times(2)->create(
                'payment:captured',
                [
                    'captured_at' => $capturedAt,
                    'method'      => 'card',
                    'merchant_id' => $merchantId,
                    'amount'      => $amount,
                    'created_at'  => $createdAt,
                    'updated_at'  => $createdAt + 10
                ]
            );

            $amount = $amount * 2;
        }

        $request = [
            'url' => '/settlements/initiate/kotak',
            'method' => 'POST'
        ];

        $setlResponse = $this->makeRequestAndGetContent($request);

        $this->assertTestResponse($setlResponse);

        // Verifiy settlement amounts
        $firstSettlements = $this->getEntities('settlement', ['merchant_id' => $firstMerchant], true);
        $this->assertEquals(1, $firstSettlements['count']);
        $this->assertEquals(19600, $firstSettlements['items'][0]['amount']);

        $secondSettlements = $this->getEntities('settlement', ['merchant_id' => $secondMerchant], true);
        $this->assertEquals(1, $secondSettlements['count']);
        $this->assertEquals(39200, $secondSettlements['items'][0]['amount']);
    }

    public function testSettlementIgnoredTxns()
    {
        $this->ba->appAuth();

        $createdAt = Carbon::today(Timezone::IST)->subDays(10)->timestamp + 5;
        $capturedAt = Carbon::today(Timezone::IST)->subDays(10)->timestamp + 10;

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
            'url' => '/settlements/initiate/kotak',
            'method' => 'POST'
        );

        $this->makeRequestAndGetContent($request);

        $txns = $this->getEntities('transaction', ['count' => 2]);

        foreach ($txns['items'] as $txn)
        {
            $this->assertEquals($txn['settled'], false);
        }

        $content = $this->getEntities('file_store', [], true);
        $this->assertSame($content['count'], 0);

    }

    public function testSettlementFileGeneration()
    {
        $this->ba->appAuth();

        $payments = $this->createPaymentEntities();

        foreach ($payments as $payment)
        {
            $attrs = ['payment' => $payments[0],
                      'amount'  => '100'];
            $refund = $this->fixtures->create('refund:from_payment', $attrs);
            $refunds[] = $refund;
        }

        // Generate settlements for above transactions
        $request = array(
            'url' => '/settlements/initiate/kotak',
            'method' => 'POST'
        );

        $this->makeRequestAndGetContent($request);

        $batch = $this->getLastEntity('batch_fund_transfer', true);

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

        $content = $this->getEntities('file_store', [], true);
        $this->assertSame($content['count'], 4);
    }

    public function testNodalTransferWithGateway()
    {
        Mail::fake();

        $this->ba->appAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_first_data_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->doAuthAndCapturePayment();

        $p1 = $this->getLastEntity('payment', true);

        $testTime = Carbon::tomorrow(Timezone::IST)->addHours(5);

        Carbon::setTestNow($testTime);

        $request = [
            'url'     => '/nodal/transfer',
            'method'  => 'POST',
            'content' => [
                'gateway' => 'first_data',
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotEquals(null, $content['file']);

        Mail::assertSent(IciciSettlementMail::class);

        Carbon::setTestNow();
    }

    public function testNodalTransferWithAmount()
    {
        Mail::fake();

        $this->ba->appAuth();

        $request = [
            'url'     => '/nodal/transfer',
            'method'  => 'POST',
            'content' => [
                'amount'  => 1076,
                'channel' => 'axis',]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotEquals(null, $content['file']);

        Mail::assertSent(AxisSettlementMail::class);
    }

    public function testSettlementWithAccountTransfer()
    {
        $payment = $this->createPaymentEntities(1);

        $createdAt = Carbon::today(Timezone::IST)->subDays(10)->timestamp + 5;

        $transfer = $this->fixtures->create(
            'transfer:to_account',
            [
                'source_id'   => $payment->getId(),
                'source_type' => 'payment',
                'amount'      => 5000,
                'currency'    => 'INR',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt + 10
            ]);

        // Check if the recipient_settlement_id for the transfer entity created is null
        $defaultSettlementId1 = $transfer->getRecipientSettlementId();

        $this->assertEquals($defaultSettlementId1, null);

        // Generate settlements
        $content = $this->initiateSettlements();

        $lastSetl = $this->getLastEntity('settlement', true);

        // Assert linked account settlement
        $this->assertEquals($transfer['to_id'], $lastSetl['merchant_id']);
        $this->assertEquals(5000, $lastSetl['amount']);

        // (1 payment txn + 1 transfer txn + 1 transfer payment txn)
        $this->assertEquals(3, $content['kotak']['transaction_count']);

        // Reload the entities so that the cached values are not returned
        $transfer->reload();

        $updatedSettlementId1 = $transfer->getRecipientSettlementId();

        $this->assertNotEquals($updatedSettlementId1, null);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->privateAuth();

        // The response should not contain details of the Settlement entity
        $request = [
            'url'     => '/transfers',
            'method'  => 'GET'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $transferResponse = $content['items'][0];

        $this->assertArrayNotHasKey('recipient_settlement', $transferResponse);

        // The response should contain details of the Settlement entity,
        // since expand[]=recipient_settlement flag is being passed.
        $request = [
            'url'     => '/transfers',
            'method'  => 'GET',
            'content' => [
                'expand'    =>  [
                    'recipient_settlement'
                ]
            ]
        ];

        $response = [
            'recipient_settlement'  => [
                'entity'        => 'settlement',
                'amount'        => 5000,
                'status'        => 'created',
                'fees'          => 0,
                'service_tax'   => 0,
                'utr'           => null,
                'settled_on'    => null
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $transferResponse = $content['items'][0];

        $this->assertArraySelectiveEquals($response, $transferResponse);
    }

    public function testSettlementAccountTransferOnHold()
    {
        $payment = $this->createPaymentEntities(1);

        $createdAt = Carbon::today(Timezone::IST)->subDays(10)->timestamp + 5;

        $transfer = $this->fixtures->create(
            'transfer:to_account',
            [
                'source_id'   => $payment->getId(),
                'source_type' => 'payment',
                'amount'      => 5000,
                'currency'    => 'INR',
                'on_hold'     => '1',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt + 10
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

    public function testSettlementAccountTransferOnHoldUntil()
    {
        $payment = $this->createPaymentEntities(1);

        $createdAt = Carbon::today(Timezone::IST)->subDays(10)->timestamp + 5;

        $account = $this->fixtures->create('merchant:marketplace_account', ['balance' => 250000]);

        $transfer = $this->fixtures->create(
            'transfer:to_account',
            [
                'account'       => $account,
                'source_id'     => $payment->getId(),
                'source_type'   => 'payment',
                'amount'        => 5000,
                'currency'      => 'INR',
                'on_hold'       => '1',
                'on_hold_until' => Carbon::today(Timezone::IST)->timestamp - 600,
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt + 10
            ]);

        // Generate settlements
        $content = $this->initiateSettlements();

        // 1 payment txn + 1 transfer txn
        $this->assertEquals(2, $content['kotak']['transaction_count']);

        // Marketplace linked account balance is credited with 5000 after transfer
        $this->assertEquals(255000, $account->balance->reload()->getBalance());

        //
        // Run the payment on_hold update cron:
        // This should allow the txn to be picked up for settlement
        //
        $cronResult = $this->runPaymentOnHoldUpdateCron();

        $this->assertEquals(1, $cronResult['summary']['total_count']);

        // Run the next settlement in 3 days to workaround the T+3 schedule
        $threeDaysInSeconds = 259200;

        // Generate settlements
        $content = $this->initiateSettlements('kotak', time() + $threeDaysInSeconds);

        // 1 transfer-payment txn
        $this->assertEquals(1, $content['kotak']['transaction_count']);

        //
        // After settling the transfer, the linked account balance should have
        // gone back to 250000
        //
        $this->assertEquals(250000, $account->balance->reload()->getBalance());
    }

    public function testSettlementForTransferReversal()
    {
        $payment = $this->createPaymentEntities(1);

        $createdAt = Carbon::today(Timezone::IST)->subDays(10)->timestamp + 5;

        $account = $this->fixtures->create('merchant:marketplace_account', ['balance' => 250000]);

        $transfer = $this->fixtures->times(2)->create(
            'transfer:to_account',
            [
                'account'     => $account,
                'source_id'   => $payment->getId(),
                'source_type' => 'payment',
                'amount'      => 1000,
                'currency'    => 'INR',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt + 10
            ]);

        $reversal = $this->fixtures->create(
            'reversal',
            [
                'entity_type'   => 'transfer',
                'entity_id'     => $transfer[1]->getId(),
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

    public function testSettlementWithDispute()
    {
        // Create payment
        $payment = $this->createPaymentEntities(1);

        $createdAt = Carbon::today(Timezone::IST)->subDays(10)->timestamp + 5;

        // Create dispute on the payment (fixture internally creates a transaction)
        $this->fixtures->create(
            'dispute',
            [
                'payment_id'      => $payment->getId(),
                'amount'          => 5000,
                'deduct_at_onset' => 1,
                'created_at'      => $createdAt,
                'updated_at'      => $createdAt + 100
            ]);

        // Generate settlements
        $content = $this->initiateSettlements();

        // Expected 2: 1 payment txn and 1 dispute txn
        $this->assertEquals(2, $content['kotak']['transaction_count']);
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
