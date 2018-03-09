<?php

namespace RZP\Tests\Functional\Settlement;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Merchant\Account;
use RZP\Models\Feature\Constants;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Holidays;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Settlement\Entity as SettlementEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class SettlementTest extends TestCase
{
    use SettlementTrait;
    use PaymentTrait;
    use HeimdallTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/SettlementTestData.php';

        parent::setUp();

        $this->ba->publicAuth();
    }

    public function tearDown()
    {
        parent::tearDown();

        Carbon::setTestNow();
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
            '/settlements/h2hreconcile/kotak',
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
        $payments = $this->createPaymentEntities(1);

        $channel = Channel::AXIS;

        // Generate settlements for above transactions
        $content = $this->initiateSettlements($channel);

        $this->assertEquals(0, $content[$channel]['txnCount']);

        $content = $this->getEntities('file_store', [], true);
        $this->assertSame($content['count'], 0);
    }


    // Random settlement holiday - Test for live mode
    public function testSettlementOnHolidayInLiveMode()
    {
        //@TODO
        $this->markTestSkipped("Fix test as soon as possible.");

        $this->ba->publicLiveAuth();

        $days = $this->getDaysForSettlementHolidayTests();

        $createdAt = Carbon::parse($days['payment_created_at'], Timezone::IST)->timestamp;
        $capturedAt = Carbon::parse($days['payment_created_at'], Timezone::IST)->timestamp + 10;

        $payments = $this->fixtures->times(2)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt]);

        $setDate = Carbon::parse($days['payment_settlment_holiday'],Timezone::IST);

        Carbon::setTestNow($setDate);

        // Generate settlements for above transactions
        $content = $this->initiateSettlements(Channel::AXIS);

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

        $createdAt = Carbon::parse($days['payment_created_at'], Timezone::IST)->timestamp;
        $capturedAt = Carbon::parse($days['payment_created_at'], Timezone::IST)->timestamp + 10;

        $payments = $this->fixtures->times(2)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt]);

        $txn = $this->getEntities('transaction',[],true);

        $setDate = Carbon::parse($days['payment_settlement_on'], Timezone::IST);
        Carbon::setTestNow($setDate);

        $channel = Channel::AXIS;
        // Generate settlements for above transactions
        $content = $this->initiateSettlements($channel);

        $this->assertEquals(2, $content[$channel]['txnCount']);

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

        $payments = $this->fixtures->times(2)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt]);

        $setDate = Carbon::parse($days['payment_settlement_on'], Timezone::IST);
        Carbon::setTestNow($setDate);

        $channel = Channel::AXIS;
        // Generate settlements for above transactions
        $content = $this->initiateSettlements($channel);

        $this->assertEquals(2, $content[$channel]['txnCount']);

        Carbon::setTestNow();
    }

    // Random settlement non holiday - Test for test mode
    public function testSettlementOnNonHolidayInTestMode()
    {
        $days = $this->getDaysForSettlementNonHolidayTests();

        $createdAt = Carbon::parse($days['payment_created_at'], Timezone::IST)->timestamp;
        $capturedAt = Carbon::parse($days['payment_created_at'], Timezone::IST)->timestamp + 10;

        $payments = $this->fixtures->times(2)->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt + 10]);

        $setDate = Carbon::parse($days['payment_settlement_on'],Timezone::IST);
        Carbon::setTestNow($setDate);

        $channel = Channel::AXIS;
        // Generate settlements for above transactions
        $content = $this->initiateSettlements($channel);

        $this->assertEquals(2, $content[$channel]['txnCount']);

        Carbon::setTestNow();
    }

    public function testSettlementWithPayout()
    {
        // Create payments and refunds with timestamps two days back
        $payments = $this->createPaymentEntities(2);

        $createdAt = Carbon::today(Timezone::IST)->subDays(10)->timestamp + 5;

        $payout = $this->fixtures->create(
            'payout',
            [
                'amount'     => '1000',
                'currency'   => 'INR',
                'created_at' => $createdAt,
                'updated_at' => $createdAt + 10
            ]);

        $channel = Channel::AXIS;
        // Generate settlements for above transactions
        $content = $this->initiateSettlements($channel);

        // (5 payments txn + 1 payout txn)
        $this->assertEquals(3, $content[$channel]['txnCount']);
    }

    public function testMerchantSettlementForCreditTransaction()
    {
        $this->ba->appAuth();

        $this->fixtures->create('credits',
            [
                'type'        => 'fee',
                'value'       => 50000,
           ]);

        $this->fixtures->merchant->editFeeCredits('50000', Account::TEST_ACCOUNT);

        $payments = $this->createPaymentEntities();

        foreach ($payments as $payment)
        {
            $attrs = [
                'payment' => $payment,
                'amount'  => '100'
            ];

            $refund = $this->fixtures->create('refund:from_payment', $attrs);

            $refunds[] = $refund;
        }

        $input = array('count' => 10);
        $txns = $this->getEntities('transaction', $input, true);

        $content = $this->initiateSettlements(Channel::AXIS);

        $setl = $this->getLastEntity('settlement', true);

        $setlAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($setlAttempt['source'], $setl['id']);


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

    public function testMerchantSettlementV2YesBank()
    {

        $this->ba->adminAuth();

        $channel = Channel::YESBANK;

        $this->initiateAndverifySettlementEntitiesForChannel($channel);
    }

    public function testMerchantSettlementV2Axis()
    {

        $this->ba->adminAuth();

        $channel = Channel::AXIS;

        $this->initiateAndverifySettlementEntitiesForChannel($channel);
    }

    public function testMerchantSettlementV2Icici()
    {

        $this->ba->adminAuth();

        $channel = Channel::ICICI;

        $this->initiateAndverifySettlementEntitiesForChannel($channel);
    }

    public function testMerchantSettlementV2Hdfc()
    {
        $this->ba->adminAuth();

        $channel = Channel::HDFC;

        $this->initiateAndverifySettlementEntitiesForChannel($channel);
    }

    public function testMerchantSettlementV2Kotak()
    {

        $this->ba->adminAuth();

        $channel = Channel::KOTAK;

        $this->initiateAndverifySettlementEntitiesForChannel($channel);
    }

    public function testMerchantSettlementV2DspSpecific()
    {
        $channel = Channel::AXIS;

        $this->ba->adminAuth();

        $this->fixtures->merchant->createAccount('7thBRSDflu7NHL');

        $dt = Carbon::create(2017, 12, 12, 16, 0, 0, Timezone::IST)
                    ->subDays(5);

        $payments = $this->createPaymentEntities(2, '7thBRSDflu7NHL', $dt);

        foreach ($payments as $payment)
        {
            $attrs = ['payment' => $payment, 'amount'  => '100'];

            $refund = $this->fixtures->create('refund:from_payment', $attrs);
            $refunds[] = $refund;
        }

        $dt = Carbon::create(2017, 12, 12, 16, 0, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(0, $setlResponse[$channel]['count']);

        $dt = Carbon::create(2017, 12, 12, 11, 0, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);
        $this->assertEquals(4, $setlResponse[$channel]['txnCount']);

        Carbon::setTestNow();
    }

    public function testSeparateSettlement1()
    {
        Carbon::setTestNow(Carbon::now(Timezone::IST));

        $channel = Channel::ICICI;

        $this->fixtures->merchant->addFeatures([Constants::DAILY_SETTLEMENT]);

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $today = Carbon::today(Timezone::IST);

        // Create payment with captured at as 26th Jan
        $entities = $this->createPaymentAndRefundEntities(1, $today);

        $txns = $this->getEntities('transaction', [], true);

        $settledAt1 = $today->addHours(10);

        $tomorrow = Carbon::tomorrow(Timezone::IST);

        // Create payment with captured at as 27th Jan
        $entities = $this->createPaymentAndRefundEntities(1, $tomorrow);

        $settledAt2 = $tomorrow->addHours(10);

        // Mark payments eligible for settlement
        $paymentTxns = $this->getEntities('transaction', ['type' => 'payment'], true);

        $this->fixtures->transaction->edit($paymentTxns['items'][0]['id'],
            ['settled_at' => $settledAt1->getTimestamp()]);

        $this->fixtures->transaction->edit($paymentTxns['items'][1]['id'],
            ['settled_at' => $settledAt2->getTimestamp()]);

        // Mark refunds eligible for settlement
        $refundTxns = $this->getEntities('transaction', ['type' => 'refund'], true);

        $this->fixtures->transaction->edit($refundTxns['items'][0]['id'],
            ['settled_at' => 1, 'created_at' => $today->getTimestamp() + 100]);

        $this->fixtures->transaction->edit($refundTxns['items'][1]['id'],
            ['settled_at' => 1, 'created_at' => $tomorrow->getTimestamp() + 100]);

        // Normal settlement route must not settle to Airtel
        $content = $this->initiateSettlements($channel);

        $this->assertEquals(0, $content[$channel]['count']);
        $this->assertEquals(0, $content[$channel]['txnCount']);

        // Set time to day after tomorrow
        $tomorrow = Carbon::tomorrow(Timezone::IST);

        Carbon::setTestNow($tomorrow);

        $content = $this->initiateDailySettlements();

        $txn = $this->getEntityById('transaction', $paymentTxns['items'][0]['id'], true);
        $this->assertEquals($tomorrow->addDay()->getTimestamp(), $txn['settled_at']);

        $this->assertEquals(2, $content[$channel]['count']);
        $this->assertEquals(4, $content[$channel]['txnCount']);

        $ftas = ($this->getEntities('fund_transfer_attempt', [], true))['items'];

        $this->assertEquals($settledAt1->getTimestamp(), $ftas[0]['initiate_at']);
        $this->assertEquals($settledAt2->getTimestamp(), $ftas[1]['initiate_at']);
    }

    /**
     * Tests the case when settlement entity gets created,
     * but the transaction creation for it fails because
     * the merchant's balance was less than the amount to be settled.
     * This test then adjusts the balance, and
     * verifies that retry of the settlement creates the transaction.
     */
    public function testSettlementRetryWhenNoTransaction()
    {
        $channel = Channel::ICICI;

        $this->ba->adminAuth();

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $this->createPaymentEntities(1);

        // Setting the balance to a value less than expected settlement
        // for above entitties, so that balance update fails because of
        // going negavative. So transaction creation for settlement
        // will fail.
        $this->fixtures->balance->edit('10000000000000', ['balance' => 100]);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertEquals(0, $setlResponse[$channel]['count']);
        $this->assertEquals(0, $setlResponse[$channel]['txnCount']);

        $setl = $this->getLastEntity('settlement', true);

        $this->assertNull($setl);
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

        $setlResponse = $this->initiateSettlements(Channel::AXIS);

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

        $this->initiateSettlements(Channel::AXIS);

        $txns = $this->getEntities('transaction', ['count' => 2]);

        foreach ($txns['items'] as $txn)
        {
            $this->assertEquals($txn['settled'], false);
        }

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
                'gateway'     => 'first_data',
                'destination' => 'kotak'
            ]
        ];

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotEquals(null, $content);

        $adj = $this->getLastEntity('adjustment', true);

        $expected = [
            'amount'        => 49500,
            'channel'       => 'icici',
            'merchant_id'   => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expected, $adj);

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
                'amount'        => 1076,
                'channel'       => 'axis',
                'destination'   => 'kotak'
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotEquals(null, $content);

        $adj = $this->getLastEntity('adjustment', true);

        $expected = [
            'amount'        => 1076,
            'channel'       => 'axis',
            'merchant_id'   => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expected, $adj);
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

        $channel = Channel::AXIS;

        // Generate settlements
        $content = $this->initiateSettlements($channel);

        // Assert linked account settlement
        $lastSetl = $this->getLastEntity('settlement', true);
        $this->assertEquals($transfer['to_id'], $lastSetl['merchant_id']);
        $this->assertEquals(5000, $lastSetl['amount']);

        // (1 payment txn + 1 transfer txn + 1 transfer payment txn)
        $this->assertEquals(3, $content[$channel]['txnCount']);

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
                'expand'    => [
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
                'tax'           => 0,
                'utr'           => null,
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $transferResponse = $content['items'][0];

        $this->assertArraySelectiveEquals($response, $transferResponse);
    }

    public function testSettlementAccountTransferOnHold()
    {
        $channel = Channel::AXIS;
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
        $content = $this->initiateSettlements($channel);

        // 1 payment txn + 1 transfer txn
        $this->assertEquals(2, $content[$channel]['txnCount']);

        // The txn for the transfer payment to the merchant should not settled
        $trfPayment = $this->getEntities('payment', ['transfer_id' => $transfer->getId()], true)['items'][0];
        $txn = $this->getEntityById('transaction', $trfPayment['transaction_id'], true);
        $this->assertEquals('payment', $txn['type']);
        $this->assertEquals($transfer->toArrayAdmin()['recipient'], 'acc_' . $txn['merchant_id']);
        $this->assertFalse($txn['settled']);
    }

    public function testSettlementAccountTransferOnHoldUntil()
    {
        $channel = Channel::AXIS;
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
        $content = $this->initiateSettlements($channel);

        // 1 payment txn + 1 transfer txn
        $this->assertEquals(2, $content[$channel]['txnCount']);

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
        $content = $this->initiateSettlements($channel, time() + $threeDaysInSeconds);

        // 1 transfer-payment txn
        $this->assertEquals(1, $content[$channel]['txnCount']);

        //
        // After settling the transfer, the linked account balance should have
        // gone back to 250000
        //
        $this->assertEquals(250000, $account->balance->reload()->getBalance());
    }

    public function testSettlementForReversalOfDirectTransfer()
    {
        $channel = Channel::AXIS;

        $this->createPaymentEntities(2);

        // Get a timestamp of 2 days ago
        $createdAt = Carbon::today(Timezone::IST)->subDays(5)->getTimestamp() + 5;

        // Create a linked account
        $account = $this->fixtures->create('merchant:marketplace_account', ['balance' => 250000]);

        // Create 2 direct transfers to the linked account
        $transfer = $this->fixtures->times(2)->create(
            'transfer:to_account',
            [
                'account'     => $account,
                'source_id'   => $account->getId(),
                'source_type' => 'merchant',
                'amount'      => 1000,
                'currency'    => 'INR',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt + 10
            ]);

        // Create one reversal, same day
        $this->fixtures->create(
            'reversal',
            [
                'entity_type' => 'transfer',
                'entity_id'   => $transfer[1]->getId(),
                'amount'      => 500,
                'created_at'  => $createdAt + 10,
                'updated_at'  => $createdAt + 20
            ]);

        // Initiate immediate settlement, Reversal should not be settled
        $content = $this->initiateSettlements($channel, Carbon::tomorrow(Timezone::IST)->getTimestamp());

        //
        // Total 7. Following transactions should have settled:
        // 2 payment txns
        // 2 transfers
        // 2 transfer payment (linked account)
        // 1 reversal refund  (linked account)
        //
        $this->assertEquals(7, $content[$channel]['txnCount']);

        $lastSetl = $this->getLastEntity('settlement', true);

        // Assert linked account settlement
        $this->assertEquals($transfer[1]['to_id'], $lastSetl['merchant_id']);

        //
        // transfer payment 1 -> credit 1000 + transfer payment 2 -> credit 1000
        // reversal refund -> debit 500
        // total => 1500
        //
        $this->assertEquals(1500, $lastSetl['amount']);

        // Set time to 3 working days from now and initiate settlements
        $settlementAfterT3 = Carbon::createFromTimestamp($createdAt, Timezone::IST)->addDays(3);
        $nextWorkingDay = Holidays::getNthWorkingDayFrom($settlementAfterT3, 3);
        Carbon::setTestNow($nextWorkingDay->setTime(8, 0));

        $content = $this->initiateSettlements($channel);
        $this->assertEquals(1, $content[Channel::AXIS]['txnCount']);

        // Assert master account settlement
        $lastSetl = $this->getLastEntity('settlement', true);
        $this->assertEquals($transfer[1]['merchant_id'], $lastSetl['merchant_id']);

        // Reversal to be settled => 500
        $this->assertEquals(500, $lastSetl['amount']);
    }

    public function testSettlementForReversalOfPaymentTransfer()
    {
        $channel = Channel::AXIS;

        $createdAt = Carbon::today(Timezone::IST)->getTimestamp() + 5;

        $payment = $this->fixtures->create(
            'payment:captured',
            [
                'captured_at' => $createdAt + 10,
                'method'      => 'card',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt + 10
            ]
        );

        // Create a linked account
        $account = $this->fixtures->create('merchant:marketplace_account', ['balance' => 250000]);

        $transfer = $this->fixtures->create(
            'transfer:to_account',
            [
                'account'     => $account,
                'source_id'   => $payment->getId(),
                'source_type' => 'payment',
                'amount'      => 5000,
                'currency'    => 'INR',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt + 10
            ]);

        // Create one reversal, 5 days later.
        $time = Carbon::today(Timezone::IST)->addDays(5);
        Carbon::setTestNow($time);
        $this->fixtures->create(
            'reversal',
            [
                'entity_type' => 'transfer',
                'entity_id'   => $transfer->getId(),
                'amount'      => 500,
                'created_at'  => $time->getTimestamp(),
                'updated_at'  => $time->getTimestamp(),
            ]);

        Carbon::setTestNow();

        // Initiate immediate settlement, none should settle on the same day
        $content = $this->initiateSettlements($channel);
        $this->assertEquals(0, $content[$channel]['txnCount']);

        // Set time to 3 working days from now and initiate settlements
        $settlementAfterT3 = Carbon::today(Timezone::IST);
        $nextWorkingDay = Holidays::getNthWorkingDayFrom($settlementAfterT3, 3);
        Carbon::setTestNow($nextWorkingDay->setTime(8, 0));

        //
        // Try settlement after 3 days:
        // Total expected 4 =>
        // 1 payment, 1 transfer
        // 1 transfer payment (linked account)
        // 1 reversal refund (linked account)
        //
        $content = $this->initiateSettlements($channel);
        $this->assertEquals(4, $content[$channel]['txnCount']);

        Carbon::setTestNow($nextWorkingDay->addDays(1));
        $content = $this->initiateSettlements($channel);
        $this->assertEquals(1, $content[$channel]['txnCount']);
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

        $channel = Channel::AXIS;
        // Generate settlements
        $content = $this->initiateSettlements($channel);

        // Expected 2: 1 payment txn and 1 dispute txn
        $this->assertEquals(2, $content[$channel]['txnCount']);
    }

    public function testAdjustmentCreationAgainstSettlement()
    {
        // Create payments and refunds with timestamps two days back
        $prEntities = $this->createPaymentAndRefundEntities();

        // Generate settlements for above transactions
        $setlFile = $this->initiateSettlements(Channel::AXIS);

        $setl = $this->getLastEntity('settlement', true);

        $setlId = $setl['id'];

        $adjustmentData =[
            'merchant_id'   => '10000000000000',
            'amount'        => 100,
            'currency'      => 'INR',
            'description'   => 'random desc',
            'settlement_id' => $setlId
        ];

        $request = [
            'method'    => 'POST',
            'url'       => '/adjustments',
            'content'   => $adjustmentData
        ];

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->setAdminForInternalAuth();
        $this->ba->addAdminAuthHeaders('org_'.$this->org->id, $this->authToken);

        $content = $this->makeRequestAndGetContent($request);

        $this->ba->addAdminAuthHeaders(null, null);

        $data = $this->getLastEntity('adjustment', true);

        $this->assertArraySelectiveEquals($content, $data);
    }

    protected function initiateAndVerifySettlementEntitiesForChannel(string $channel)
    {
        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $payments = $this->createPaymentEntities(2);

        $this->createRefundFromPayments($payments);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertSettlementEntitiesCreation($setlResponse, $channel);
    }

    protected function assertSettlementEntitiesCreation(array $setlResponse, string $channel)
    {
        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);
        $this->assertEquals(4, $setlResponse[$channel]['txnCount']);

        $setl = $this->getLastEntity('settlement', true);

        $this->assertTestResponse($setl, 'fetchAndMatchSettlement');
        $this->assertEquals($channel, $setl[SettlementEntity::CHANNEL]);

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

        // Validate fund_transfer_attempt entity
        $bta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertTestResponse($bta, 'matchSettlementAttempt');
        $this->assertEquals($setl['id'], $bta['source']);
        $this->assertEquals($channel, $bta[Attempt\Entity::CHANNEL]);
    }

    protected function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }

    protected function setAdminForInternalAuth()
    {
        $this->org = $this->fixtures->create('org');

        $this->authToken = $this->getAuthTokenForOrg($this->org);
    }
}
