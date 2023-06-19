<?php

namespace RZP\Tests\Functional\Settlement;

use Illuminate\Support\Facades\DB;
use Mail;
use Config;
use Carbon\Carbon;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailsEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Services\RazorXClient;
use Razorpay\OAuth\Application;
use RZP\Models\Merchant\Account;
use RZP\Models\Feature\Constants;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Traits\MocksSplitz;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\Partner as Partner;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Services\Mock\UfhService as MockUfhService;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Holidays;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Merchant\Preferences;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Settlement\Entity as SettlementEntity;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Schedule\ScheduleTrait;
use RZP\Models\Transaction\Processor\SettlementTransfer;
use RZP\Models\Admin;

class SettlementTest extends TestCase
{
    use PartnerTrait;
    use MocksSplitz;
    use SettlementTrait;
    use PaymentTrait;
    use HeimdallTrait;
    use ScheduleTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    const STANDARD_PRICING_PLAN_ID  = '1A0Fkd38fGZPVC';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/SettlementTestData.php';

        parent::setUp();

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $this->ba->publicAuth();
    }

    protected function tearDown(): void
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

    public function testHoldFundsDuringSettlement()
    {
        $this->fixtures->merchant->holdFunds('10000000000000');

        $now = Carbon::create(2018, 8, 14, 10, 0, 0);

        Carbon::setTestNow($now);

        // Create payments and refunds with timestamps two days back
        $this->createPaymentEntities(1);

        $channel = Channel::AXIS;

        // Generate settlements for above transactions
        $content = $this->initiateSettlements($channel);

        $this->assertEquals(0, $content[$channel]['txnCount']);

        $content = $this->getEntities('file_store', [], true);
        $this->assertSame($content['count'], 0);
    }

    // Random settlement holiday - Test for live mode
    public function testSettlementOnHolidayNon247Channel()
    {
        $this->markTestSkipped('test mode overrides holiday check');

        $this->createPaymentEntities(2);

        $now = Carbon::create(2018, 8, 15, 10, 0, 0);

        Carbon::setTestNow($now);

        // Generate settlements for above transactions
        $content = $this->initiateSettlements(Channel::AXIS);

        $this->assertEquals('Today is a holiday! Happy holidays :)', $content['message']);
    }

    // Random settlement non holiday - Test for live mode
    public function testSettlementOnNonHolidayNon247Channel()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0);

        Carbon::setTestNow($now);

        $this->createPaymentEntities(2);

        $channel = Channel::AXIS;
        // Generate settlements for above transactions
        $content = $this->initiateSettlements($channel);

        $this->assertEquals(2, $content[$channel]['txnCount']);
    }

    public function testSettlementWithPayout()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0);

        Carbon::setTestNow($now);

        // Create payments and refunds with timestamps two days back
        $this->createPaymentEntities(2);

        $createdAt = Carbon::today(Timezone::IST)->subDays(10)->timestamp + 5;

        $this->fixtures->create(
            'payout',
            [
                'amount'            =>      '1000',
                'currency'          =>      'INR',
                'created_at'        =>      $createdAt,
                'updated_at'        =>      $createdAt + 10,
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        $channel = Channel::AXIS;
        // Generate settlements for above transactions
        $content = $this->initiateSettlements($channel);

        // (5 payments txn + 1 payout txn)
        $this->assertEquals(3, $content[$channel]['txnCount']);
    }

    public function testBankingPayoutSettlementExcluded()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0);

        Carbon::setTestNow($now);

        // Create payments and refunds with timestamps two days back
        $this->createPaymentEntities(2);

        $this->setUpMerchantForBusinessBanking(false, 5000);
        $this->createPayout();

        $channel = Channel::AXIS;
        // Generate settlements for above transactions
        $content = $this->initiateSettlements($channel);

        // (2 payments txns)
        $this->assertEquals(2, $content[$channel]['txnCount']);
    }

    public function testMerchantSettlementForCreditTransaction()
    {

        $this->fixtures->create('credits',
            [
                'type'        => 'fee',
                'value'       => 50000,
           ]);

        $this->fixtures->merchant->editFeeCredits('50000', Account::TEST_ACCOUNT);

        $now = Carbon::create(2018, 8, 14, 10, 0, 0);

        Carbon::setTestNow($now);

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
        $this->assertSame($content['count'], 7);

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

    public function testMerchantSettlementV2YesBankOnHoliday()
    {
        $now = Carbon::create(2018, 8, 15, 10, 0, 0);

        Carbon::setTestNow($now);

        $channel = Channel::YESBANK;

        $this->initiateAndverifySettlementEntitiesForChannel($channel);
    }

    public function testMerchantSettlementV2YesBankOutsideBankWorkHours()
    {
        $channel = Channel::YESBANK;

        $now = Carbon::create(2018, 8, 14, 20, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->initiateAndverifySettlementEntitiesForChannel($channel);
    }

    public function testMerchantSettlementV2Axis()
    {
        $channel = Channel::AXIS;

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->initiateAndverifySettlementEntitiesForChannel($channel);
    }

    public function testMerchantSettlementV2Icici()
    {
        $channel = Channel::ICICI;

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->initiateAndverifySettlementEntitiesForChannel($channel);
    }

    public function testMerchantSettlementV2Hdfc()
    {
        $channel = Channel::HDFC;

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->initiateAndverifySettlementEntitiesForChannel($channel);
    }


    public function testMerchantSettlementTransfer()
    {
        $channel = Channel::AXIS;

        $this->ba->adminAuth();

        $this->fixtures->create('balance_config',
            [
                'id'                            => '100yz000yz00yz',
                'balance_id'                    => '10000000000000',
                'type'                          => 'primary',
                'negative_transaction_flows'    => ['transfer'],
                'negative_limit_auto'           => 500000,
                'negative_limit_manual'         => 500000

            ]
        );

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

        $this->fixtures->merchant->editBalance(1000);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $this->fixtures->merchant->activate('10000000000002');

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $dt = Carbon::create(2018, 8, 14, 6, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $attrs = [
            'captured_at' => $dt->getTimestamp() + 1,
            'method' => 'card',
            'created_at' => $dt->getTimestamp(),
            'amount' => 2000,
        ];

        $payment = $this->fixtures->create('payment:captured', $attrs);

        $transfers[0] = [
            'account'  => 'acc_' . $account->getId(),
            'amount'   => 2000,
            'currency' => 'INR',
        ];

       $this->transferPayment('pay_'.$payment['id'], $transfers);

       $this->initiateSettlements($channel, null, true, [$account->getId()]);

       $transfer = $this->getLastEntity('transfer');

       $transferIds = $transfer['id'];

       $transferResponse = $this->getTransfer($transferIds);

       $this->assertNotNull($transferResponse['recipient_settlement_id']);

    }

    public function testMerchantEarlySettlement()
    {
        $channel = Channel::AXIS;

        $this->ba->adminAuth();

        $this->fixtures->merchant->addFeatures([Constants::ES_AUTOMATIC]);

        $dt = Carbon::create(2018, 8, 14, 6, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $attrs = [
            'captured_at' => $dt->getTimestamp() + 1,
            'method'      => 'card',
            'created_at'  => $dt->getTimestamp(),
            'amount'       => 2000,
        ];

        $payment = $this->fixtures->create('payment:captured', $attrs);

        $txn = $this->getLastTransaction(true);

        $this->fixtures->edit('transaction', $txn['id'], ['settled_at' => $dt->addHour()->getTimestamp()]);

        $txn = $this->getLastTransaction(true);

        $now = Carbon::create(
            2018, 8, 14, 8, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);

        $this->assertEquals(0, $setlResponse[$channel]['count']);

        $dt = Carbon::create(2018, 8, 14, 9, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);

        Carbon::setTestNow();

        // Part 2

        $dt = Carbon::create(2018, 8, 14, 12, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $attrs = [
            'captured_at' => $dt->getTimestamp() + 1,
            'method'      => 'card',
            'created_at'  => $dt->getTimestamp(),
            'amount'       => 2000,
        ];

        $payment = $this->fixtures->create('payment:captured', $attrs);

        $txn = $this->getLastTransaction(true);

        $this->fixtures->edit('transaction', $txn['id'], ['settled_at' => $dt->addHour()->getTimestamp()]);

        $txn = $this->getLastTransaction(true);

        $now = Carbon::create(
            2018, 8, 14, 16, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(0, $setlResponse[$channel]['count']);

        $dt = Carbon::create(2018, 8, 14, 17, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);

        Carbon::setTestNow();
    }

    public function testDelayedEarlySettlement()
    {
        $channel = Channel::AXIS;

        $this->ba->adminAuth();

        $this->fixtures->merchant->addFeatures([Constants::ES_AUTOMATIC]);

        $dt = Carbon::create(2018, 8, 13, 8, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $attrs = [
            'captured_at' => $dt->getTimestamp() + 1,
            'method'      => 'card',
            'created_at'  => $dt->getTimestamp(),
            'amount'       => 2000,
        ];

        $payment = $this->fixtures->create('payment:captured', $attrs);

        $txn = $this->getLastTransaction(true);

        $this->fixtures->edit('transaction', $txn['id'], ['settled_at' => $dt->addHour()->getTimestamp()]);

        $txn = $this->getLastTransaction(true);

        $now = Carbon::create(
            2018, 8, 14, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);

        Carbon::setTestNow();
    }

    public function testMerchantSettlementV2DspWithinTime()
    {
        $channel = Channel::AXIS;

        $this->ba->adminAuth();

        $dt = Carbon::create(2017, 12, 12, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->merchant->createAccount('7thBRSDflu7NHL');

        $input = [
            'name'        => 'Every 1 hour',
            'period'      => 'hourly',
            'interval'    => 1,
            'delay'       => 0,
        ];

        $this->createAndAssignSettlementSchedule($input,'7thBRSDflu7NHL');

        //assert dsp settlement creation within time range
        $payments = $this->createPaymentEntities(2, '7thBRSDflu7NHL', $dt);

        foreach ($payments as $payment)
        {
            $attrs = ['payment' => $payment, 'amount'  => '100'];

            $refund = $this->fixtures->create('refund:from_payment', $attrs);
            $refunds[] = $refund;
        }

        $dt = Carbon::create(2017, 12, 12, 11, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);
        $this->assertEquals(4, $setlResponse[$channel]['txnCount']);
    }

    /**
     * Tests the case for MF merchants that shouldn't get settlements
     */
    public function testMfMerchantSettlementSkip()
    {
        $channel = Channel::AXIS;

        $this->ba->adminAuth();

        $skipMfIds = Preferences::NO_SETTLEMENT_MIDS;

        foreach ($skipMfIds as $mid)
        {
            $this->fixtures->merchant->createAccount($mid);

            $dt = Carbon::create(2017, 12, 12, 16, 0, 0, Timezone::IST)
                        ->subDays(5);

            $this->createPaymentEntities(2, $mid, $dt);

            $dt = Carbon::create(2017, 12, 12, 16, 13, 0, Timezone::IST);

            Carbon::setTestNow($dt);

            $setlResponse = $this->initiateSettlements($channel);

            $this->assertNotNull($setlResponse[$channel]);
            $this->assertEquals(0, $setlResponse[$channel]['count']);
        }
    }

    /**
     * Tests the case for settlement amount greater than max block that shouldn't create settlements
     */
    public function testMerchantSettlementSkipMaxBlock()
    {
        $channel = Channel::AXIS2;

        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $merchantId = '10000000000000';

        $this->fixtures->merchant->edit(
            $merchantId,
            [
                'channel'      => $channel,
                'suspended_at' => null,
                'activated'    => true
            ]);

        $this->createPaymentEntities(10, $merchantId, $now, 6000000000);

        // setting the time stamp later so as to consider the previous dated transactions into the settlements
        $now = Carbon::create(2018, 8, 20, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->initiateSettlements($channel, null, true, [$merchantId]);

        $settlement = $this->getLastEntity('settlement', true);

        $this->assertNull($settlement);
    }

    public function testMutualFundMarketplaceSettlementSchedule()
    {
        $channel = Channel::AXIS;

        $this->ba->adminAuth();

        $parentId = Preferences::MID_PAISABAZAAR;

        $subMid = '8lv4idBRY4C9c1';

        $this->fixtures->merchant->createAccount($parentId);

        $this->fixtures->merchant->createAccount($subMid);

        $this->fixtures->edit('merchant', $subMid, ['parent_id' => $parentId]);

        // 7th December 2017 9 am
        $paymentTime = Carbon::create(2017, 12, 7, 9, 00, 0, Timezone::IST);

        $payments = $this->createPaymentEntities(1, $subMid, $paymentTime);

        $txn = $this->getLastEntity('transaction', true);

        $this->fixtures->edit(
            'transaction',
            $txn['id'],
            [
                'settled_at' => $paymentTime->getTimestamp()
            ]);

        // 7th December 2017 10 am
        $now = $paymentTime->copy()->addHour();
        Carbon::setTestNow($now);

        // This is outside the mutual fund settlement window, so settlement is skipped
        $setlResponse = $this->initiateSettlements($channel);
        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(0, $setlResponse[$channel]['count']);

        // 1.30pm 7th December 2017
        $dt = Carbon::create(2017, 12, 7, 13, 30, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        // This is inside the mutual fund settlement window, so settlement is initiated
        $setlResponse = $this->initiateSettlements($channel);
        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);
        $this->assertEquals(1, $setlResponse[$channel]['txnCount']);

        // 2.30pm 12th December 2017
        $dt = Carbon::create(2017, 12, 12, 14, 30, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $payment = $this->createPaymentEntities(1, $subMid, $paymentTime);

        $txnId = $payment->getTransactionId();

        $txn = $this->fixtures->edit('transaction', $txnId, [
            'settled_at' => $paymentTime->getTimestamp()
        ]);

        // This is outside the mutual fund settlement window, but settlement is
        // initiated anyway, as the payment was due to be settled a while ago
        $setlResponse = $this->initiateSettlements($channel);
        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);
        $this->assertEquals(1, $setlResponse[$channel]['txnCount']);

        Carbon::setTestNow();
    }

    public function testDailySettlement()
    {
        Carbon::setTestNow(Carbon::now(Timezone::IST));

        $channel = Channel::ICICI;

        $this->fixtures->merchant->addFeatures([Constants::DAILY_SETTLEMENT]);

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $this->fixtures->merchant->activate();

        $today = Carbon::create(2018, 1, 26, 0, 0, 0, Timezone::IST);

        // Create payment with captured at as 26th Jan
        $entities = $this->createPaymentAndRefundEntities(1, $today);

        $txns = $this->getEntities('transaction', [], true);

        $settledAt1 = $today->addHours(10);

        $tomorrow = Carbon::create(2018, 1, 27, 0, 0, 0, Timezone::IST);

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
        $tomorrow = Carbon::create(2018, 1, 28, 0, 0, 0, Timezone::IST);

        Carbon::setTestNow($tomorrow);

        $content = $this->initiateDailySettlements();

        $txn0 = $this->getEntityById('transaction', $paymentTxns['items'][0]['id'], true);
        $this->assertNotNull($txn0['settled_at']);
        $txn1 = $this->getEntityById('transaction', $paymentTxns['items'][1]['id'], true);
        $this->assertNotNull($txn1['settled_at']);

        $this->assertEquals(1, $content['enqueued']);
        $this->assertEquals(1, $content['total_merchants']);

        //
        // Have to be fetched separately since they're created at the
        // same time, and IDs are random, so order can't be predicted
        //
        $fta0 = $this->getEntities('fund_transfer_attempt', ['source_id' => $txn0['settlement_id']], true)['items'][0];
        $fta1 = $this->getEntities('fund_transfer_attempt', ['source_id' => $txn1['settlement_id']], true)['items'][0];

        $this->assertEquals($settledAt1->getTimestamp(), $fta0['initiate_at']);
        $this->assertEquals($settledAt2->getTimestamp(), $fta1['initiate_at']);

        $content = $this->getEntities('settlement_destination', ['settlement_id' => substr($txn0['settlement_id'],strpos($txn0['settlement_id'], '_')+1)],true);

        $this->assertEquals($fta0['id'], 'fta_' . $content['items'][0]['destination_id']);

        $content = $this->getEntities('settlement_destination', ['settlement_id' => substr($txn1['settlement_id'],strpos($txn1['settlement_id'], '_')+1)],true);

        $this->assertEquals($fta1['id'], 'fta_' . $content['items'][0]['destination_id']);
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

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

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

    protected function allowAdminToAccessMerchant(string $merchantId)
    {
        $merchant = Merchant\Entity::find($merchantId);

        $admin    = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);

        $this->ba->adminProxyAuth($merchantId, 'rzp_test_'.$merchantId);

        return $merchant;
    }

    public function createConfigForPartnerApp($appId, $submerchantId = null, $attributes = [])
    {
        if ($submerchantId === null)
        {
            $attributes[PartnerConfig\Entity::ENTITY_TYPE] = PartnerConfig\Constants::APPLICATION;
            $attributes[PartnerConfig\Entity::ENTITY_ID]   = $appId;

            $attributes[PartnerConfig\Entity::ORIGIN_TYPE] = null;
            $attributes[PartnerConfig\Entity::ORIGIN_ID]   = null;
        }
        else
        {
            $attributes[PartnerConfig\Entity::ENTITY_TYPE] = PartnerConfig\Constants::MERCHANT;
            $attributes[PartnerConfig\Entity::ENTITY_ID]   = $submerchantId;

            $attributes[PartnerConfig\Entity::ORIGIN_TYPE] = PartnerConfig\Constants::APPLICATION;
            $attributes[PartnerConfig\Entity::ORIGIN_ID]   = $appId;
        }

        $defaultAttributes = $this->getDefaultPartnerConfigAttributes();

        $attributes        = array_merge($defaultAttributes, $attributes);

        return $this->fixtures->create('partner_config', $attributes);
    }

    public function testGetConfigForOrgBadRequest()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreateConfigForOrgBadRequest()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    protected function getDefaultPartnerConfigAttributes()
    {
        return [
            'commissions_enabled' => 1,
            'default_plan_id'     => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];
    }

    public function createDefaultSubmerchantPricingPlan($planId = Partner\Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN)
    {
        $this->fixtures->create('pricing:two_percent_pricing_plan', [
            'plan_id' => $planId,
            'type'    => 'pricing',
        ]);
    }

    public function createNonPurePlatFormMerchantAndSubMerchant()
    {
        $this->fixtures->merchant->createAccount(Partner\Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);
        $this->fixtures->merchant->createAccount(Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID);

        $this->fixtures->merchant->edit(
            Partner\Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::RESELLER,
            ]
        );

        $this->createOAuthApplication(
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
                'type'        => Application\Type::PARTNER,
                'id'          => Partner\Constants::DEFAULT_NON_PLATFORM_APP_ID,
            ]
        );

        $this->fixtures->create(
            'merchant_access_map',
            [
                'merchant_id'     => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'entity_id'       => Partner\Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'entity_type'     => 'application',
                'entity_owner_id' => Partner\Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            ]
        );
    }

    public function createTransactionForMerchants(array $merchantIds, $amount)
    {
        foreach ($merchantIds as $merchantId)
        {
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
    }

    public function createPlatformMerchantsAndSubmerchants()
    {


        $this->createPurePlatFormMerchantAndSubMerchant();
        $this->createNonPurePlatFormMerchantAndSubMerchant();

        $this->allowAdminToAccessMerchant(\RZP\Tests\Functional\Partner\Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $partnerBankAccount = $this->getDbEntity(
            'bank_account',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
                'entity_id'   => Partner\Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            ]);

        $this->fixtures->merchant->createAccount(Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'merchant_id'     => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
                'entity_id'       => Partner\Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'entity_type'     => 'application',
                'entity_owner_id' => Partner\Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            ]
        );

        return $partnerBankAccount;
    }

    protected function mockRazorxTreatment(string $returnValue = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn($returnValue);
    }

    /**
     * Asserts that the settlement and fta is associated with correct bank account when -
     * 1. Partner config is defined only for an application
     */
    public function testSettleToPartnerWithDefaultConfig()
    {
        $partnerBankAccount = $this->createPlatformMerchantsAndSubmerchants();

        $this->assertNotNull($partnerBankAccount);

        $merchantCore = (new MerchantCore());

        $this->createConfigForPartnerApp(
            Partner\Constants::DEFAULT_NON_PLATFORM_APP_ID,
            null,
            [PartnerConfig\Entity::SETTLE_TO_PARTNER => true]);

        $merchantIds = [
            Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
            Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
        ];

        $results = $merchantCore->getPartnerBankAccountIdsForSubmerchants($merchantIds);

        $expectedResult = [
            Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID   => $partnerBankAccount->getId(),
            Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2 => $partnerBankAccount->getId(),
        ];

        $this->assertEquals($expectedResult, $results);

        $amount = 10000;

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $firstMerchant = Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID;

        $secondMerchant = Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2;

        $this->createTransactionForMerchants($merchantIds, $amount);

        $setlResponse = $this->initiateSettlements(Channel::AXIS);

        $this->assertTestResponse($setlResponse);

        // Verifiy settlement amounts
        $firstSettlements = $this->getEntities('settlement', ['merchant_id' => $firstMerchant], true);

        $this->assertEquals(1, $firstSettlements['count']);
        $this->assertEquals(19600, $firstSettlements['items'][0]['amount']);

        $firstSubMerchantBankAccount = $this->getDbEntity(
            'bank_account',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'entity_id'   => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
            ]);

        // Verify bank account associated with the settlements
        $firstSettlementBankAccount = $firstSettlements['items'][0]['bank_account_id'];

        // Bank account associated with settlement should be partner BA and not submerchant BA.
        $this->assertEquals($partnerBankAccount->getId(), $firstSettlementBankAccount);
        $this->assertNotEquals($firstSubMerchantBankAccount->getId(), $firstSettlementBankAccount);

        $secondSettlements = $this->getEntities('settlement', ['merchant_id' => $secondMerchant], true);

        $this->assertEquals(1, $secondSettlements['count']);
        $this->assertEquals(39200, $secondSettlements['items'][0]['amount']);

        $secondSubMerchantBankAccount = $this->getDbEntity(
            'bank_account',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
                'entity_id'   => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
            ]);

        $secondSettlementBankAccount = $secondSettlements['items'][0]['bank_account_id'];

        // Bank account associated with settlement should be partner BA and not submerchant BA.
        $this->assertEquals($partnerBankAccount->getId(), $secondSettlementBankAccount);
        $this->assertNotEquals($secondSubMerchantBankAccount->getId(), $secondSettlementBankAccount);

        // Verify bank account associated with the ftas
        $firstFta = $this->getDbEntity(
            'fund_transfer_attempt',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID
            ]);

        // Bank account associated with fta should be partner BA and not submerchant BA.
        $this->assertEquals($partnerBankAccount->getId(), $firstFta->bankAccount->getId());
        $this->assertNotEquals($firstSubMerchantBankAccount->getId(), $firstFta->bankAccount->getId());

        $secondFta = $this->getDbEntity(
            'fund_transfer_attempt',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2
            ]);

        // Bank account associated with fta should be partner BA and not submerchant BA.
        $this->assertEquals($partnerBankAccount->getId(), $secondFta->bankAccount->getId());
        $this->assertNotEquals($secondSubMerchantBankAccount->getId(), $secondFta->bankAccount->getId());
    }

    /**
     * Asserts that the settlement and fta is associated with correct bank account when -
     * 1. Partner config is defined for an application and merchant bank account does not exists.
     */
    public function testSettleToPartnerWhenNoMerchantBA()
    {
        $partnerBankAccount = $this->createPlatformMerchantsAndSubmerchants();

        $this->assertNotNull($partnerBankAccount);

        $merchantCore = (new MerchantCore());

        $this->createConfigForPartnerApp(
            Partner\Constants::DEFAULT_NON_PLATFORM_APP_ID,
            null,
            [PartnerConfig\Entity::SETTLE_TO_PARTNER => true]);

        $merchantIds = [
            Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
            Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
        ];

        $results = $merchantCore->getPartnerBankAccountIdsForSubmerchants($merchantIds);

        $expectedResult = [
            Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID   => $partnerBankAccount->getId(),
            Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2 => $partnerBankAccount->getId(),
        ];

        $this->assertEquals($expectedResult, $results);

        $amount = 10000;

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $firstMerchant = Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID;

        $secondMerchant = Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2;

        $this->fixtures->merchant->edit($firstMerchant, ['channel' => Channel::AXIS2]);

        $this->fixtures->merchant->edit($secondMerchant, ['channel' => Channel::AXIS2]);

        $this->createTransactionForMerchants($merchantIds, $amount);

        $firstSubMerchantBankAccount = $this->getDbEntity(
            'bank_account',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'entity_id'   => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
            ]);

        $deletedTimestamp = Carbon::today(Timezone::IST)->subDays(50)->timestamp + 5;

        //Deleting bank account for first submerchant.
        $this->fixtures->edit('bank_account',
            $firstSubMerchantBankAccount->getId(),
            [
                'deleted_at' => $deletedTimestamp
            ]);

        $secondSubMerchantBankAccount = $this->getDbEntity(
            'bank_account',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
                'entity_id'   => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
            ]);

        //Deleting bank account for second submerchant.
        $this->fixtures->edit('bank_account',
            $secondSubMerchantBankAccount->getId(),
            [
                'deleted_at' => $deletedTimestamp
            ]);

        $setlResponse = $this->initiateSettlements(Channel::AXIS2);

        $this->assertTestResponse($setlResponse);

        // Verifiy settlement amounts
        $firstSettlements = $this->getEntities('settlement', ['merchant_id' => $firstMerchant], true);

        $this->assertEquals(1, $firstSettlements['count']);
        $this->assertEquals(19600, $firstSettlements['items'][0]['amount']);

        $firstSubMerchantBankAccount = $this->getDbEntity(
            'bank_account',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'entity_id'   => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
            ]);

        // Verify bank account associated with the settlements
        $firstSettlementBankAccount = $firstSettlements['items'][0]['bank_account_id'];

        // Bank account associated with settlement should be partner BA and submerchant BA should not exist.
        $this->assertEquals($partnerBankAccount->getId(), $firstSettlementBankAccount);
        $this->assertEmpty($firstSubMerchantBankAccount);

        $secondSettlements = $this->getEntities('settlement', ['merchant_id' => $secondMerchant], true);

        $this->assertEquals(1, $secondSettlements['count']);
        $this->assertEquals(39200, $secondSettlements['items'][0]['amount']);

        $secondSubMerchantBankAccount = $this->getDbEntity(
            'bank_account',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
                'entity_id'   => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
            ]);

        $secondSettlementBankAccount = $secondSettlements['items'][0]['bank_account_id'];

        // Bank account associated with settlement should be partner BA and submerchant BA should not exist.
        $this->assertEquals($partnerBankAccount->getId(), $secondSettlementBankAccount);
        $this->assertEmpty($secondSubMerchantBankAccount);

        // Verify bank account associated with the ftas
        $firstFta = $this->getDbEntity(
            'fund_transfer_attempt',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID
            ]);

        // Bank account associated with fta should be partner BA and not submerchant BA.
        $this->assertEquals($partnerBankAccount->getId(), $firstFta->bankAccount->getId());

        $secondFta = $this->getDbEntity(
            'fund_transfer_attempt',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2
            ]);

        // Bank account associated with fta should be partner BA and not submerchant BA.
        $this->assertEquals($partnerBankAccount->getId(), $secondFta->bankAccount->getId());
    }

    /**
     * Asserts that the settlement and fta is associated with correct bank account when -
     * 1. Partner configs are defined for both - application and submerchant
     * 2. Partner configs are defined for both - application and submerchant and the submerchant config has
     * settle_to_flag set to false
     */
    public function testSettleToPartnerWithOverriddenConfig()
    {
        $partnerBankAccount = $this->createPlatformMerchantsAndSubmerchants();

        $this->assertNotNull($partnerBankAccount);

        $merchantCore = (new MerchantCore());

        // Overridden config with settle to partner as true
        $this->createConfigForPartnerApp(
            Partner\Constants::DEFAULT_NON_PLATFORM_APP_ID,
            Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
            [PartnerConfig\Entity::SETTLE_TO_PARTNER => true]);

        // Overridden config with settle to partner as false
        $this->createConfigForPartnerApp(
            Partner\Constants::DEFAULT_NON_PLATFORM_APP_ID,
            Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
            [PartnerConfig\Entity::SETTLE_TO_PARTNER => false]);

        $merchantIds = [
            Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
            Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
        ];

        $results = $merchantCore->getPartnerBankAccountIdsForSubmerchants($merchantIds);

        $expectedResult = [
            Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID => $partnerBankAccount->getId(),
        ];

        $this->assertEquals($expectedResult, $results);

        $amount = 10000;

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $firstMerchant = Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID;

        $secondMerchant = Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2;

        $this->assertEquals($expectedResult, $results);

        $this->createTransactionForMerchants($merchantIds, $amount);

        $setlResponse = $this->initiateSettlements(Channel::AXIS);

        $this->assertTestResponse($setlResponse);

        // Verifiy settlement amounts
        $firstSettlements = $this->getEntities('settlement', ['merchant_id' => $firstMerchant], true);

        $this->assertEquals(1, $firstSettlements['count']);
        $this->assertEquals(19600, $firstSettlements['items'][0]['amount']);

        $firstSubMerchantBankAccount = $this->getDbEntity(
            'bank_account',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'entity_id'   => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
            ]);

        // Verify bank account associated with the settlements
        $firstSettlementBankAccount = $firstSettlements['items'][0]['bank_account_id'];

        // Bank account associated with settlement should be partner BA and not submerchant BA.
        $this->assertEquals($partnerBankAccount->getId(), $firstSettlementBankAccount);
        $this->assertNotEquals($firstSubMerchantBankAccount->getId(), $firstSettlementBankAccount);

        $secondSettlements = $this->getEntities('settlement', ['merchant_id' => $secondMerchant], true);

        $this->assertEquals(1, $secondSettlements['count']);
        $this->assertEquals(39200, $secondSettlements['items'][0]['amount']);

        $secondSubMerchantBankAccount = $this->getDbEntity(
            'bank_account',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
                'entity_id'   => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2,
            ]);

        $secondSettlementBankAccount = $secondSettlements['items'][0]['bank_account_id'];

        // Bank account associated with settlement should be submerchant BA and not partner BA
        // when partner config is overridden.
        $this->assertEquals($secondSubMerchantBankAccount->getId(), $secondSettlementBankAccount);
        $this->assertNotEquals($partnerBankAccount->getId(), $secondSettlementBankAccount);

        // Verify bank account associated with the ftas
        $firstFta = $this->getDbEntity(
            'fund_transfer_attempt',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID
            ]);

        // Bank account associated with fta should be partner BA and not submerchant BA.
        $this->assertEquals($partnerBankAccount->getId(), $firstFta->bankAccount->getId());
        $this->assertNotEquals($firstSubMerchantBankAccount->getId(), $firstFta->bankAccount->getId());

        $secondFta = $this->getDbEntity(
            'fund_transfer_attempt',
            [
                'merchant_id' => Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID_2
            ]);

        // Bank account associated with fta should be submerchant BA and not partner BA
        // when partner config is overridden.
        $this->assertEquals($secondSubMerchantBankAccount->getId(), $secondFta->bankAccount->getId());
        $this->assertNotEquals($partnerBankAccount->getId(), $secondFta->bankAccount->getId());
    }

    public function testSettlementForMultipleMerchants()
    {
        $merchants = $this->fixtures->times(2)->create('merchant');

        $firstMerchant = $merchants[0]->getId();
        $secondMerchant = $merchants[1]->getId();

        $amount = 10000;

        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        foreach ($merchants as $merchant)
        {
            $merchantId = $merchant->getId();

            $balance = $this->fixtures->create('balance', ['id' => $merchantId, 'merchant_id' => $merchantId]);

            $this->fixtures->create('terminal', ['merchant_id' => $merchantId]);

            $this->fixtures->create(
                'bank_account',
                ['entity_id' => $merchantId, 'beneficiary_name' => random_string_special_chars(10)]);

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
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

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

        $this->fixtures->create(
            'refund:from_payment',
            [
                'payment' => $payment,
                'amount'  => '1000',
            ]
        );

        // Payment for 10 rupees, followed by full refund.
        // Net amount to be settled is -23 paise, so will be ignored.

        $this->initiateSettlements(Channel::AXIS);

        $txns = $this->getDbEntities('transaction');

        foreach ($txns as $txn)
        {
            $this->assertEquals($txn['settled'], false);
        }
    }

    public function testNodalTransferWithGateway()
    {
        $this->ba->cronAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_first_data_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->doAuthAndCapturePayment();

        $this->getLastEntity('payment', true);

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
        $this->ba->cronAuth();

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

    public function testNodalTransferAdmin()
    {
        $this->ba->adminAuth();

        $request = [
            'url'     => '/nodal/transfer/admin',
            'method'  => 'POST',
            'content' => [
                'amount'        => 1076,
                'channel'       => 'axis',
                'destination'   => 'icici'
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

    public function testSettlementAccountTransferOnHold()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

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
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

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
        $this->markTestSkipped('failed on 15th Aug 18');

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
                'source_id'   => '10000000000000',
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

        $lastSetlAcct = $this->getLastEntity('settlement', true);

        // Assert linked account settlement
        $this->assertEquals($transfer[1]['to_id'], $lastSetlAcct['merchant_id']);

        //
        // transfer payment 1 -> credit 1000 + transfer payment 2 -> credit 1000
        // reversal refund -> debit 500
        // total => 1500
        //
        $this->assertEquals(1500, $lastSetlAcct['amount']);

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

        // Test if the transfers can be fetched with recipient_settlement_id
        $request = [
            'url'     => '/transfers?recipient_settlement_id=' . $lastSetlAcct['id'],
            'method'  => 'GET',
            'content' => []
        ];

        $response = [
            [
                'entity'                  => 'transfer',
                'amount'                  => 1000,
                'recipient_settlement_id' => $lastSetlAcct['id'],
            ],
            [
                'entity'                  => 'transfer',
                'amount'                  => 1000,
                'recipient_settlement_id' => $lastSetlAcct['id'],
            ],
        ];

        $this->fixtures->merchant->addFeatures(['marketplace']);
        $this->ba->privateAuth();
        $content = $this->makeRequestAndGetContent($request);

        $transferResponse = $content['items'];

        $this->assertArraySelectiveEquals($response, $transferResponse);
    }

    public function testSettlementForReversalOfPaymentTransfer()
    {
        $this->markTestSkipped();

        $channel = Channel::AXIS;

        //  Thursday, 8 March 2018 00:00:05 GMT+05:30
        $createdAt = 1520447405;

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
        $time = Carbon::createFromTimestamp(1520447400, Timezone::IST)->addDays(5);
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

        // Initiate immediate settlement, none should settle on the same day
        $content = $this->initiateSettlements($channel);
        $this->assertEquals(0, $content[$channel]['txnCount']);

        // Set time to 3 working days from now and initiate settlements
        $settlementAfterT3 = Carbon::createFromTimestamp(1520447400, Timezone::IST);
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
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

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
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        // Create payments and refunds with timestamps two days back
        $this->createPaymentAndRefundEntities();

        // Generate settlements for above transactions
        $this->initiateSettlements(Channel::AXIS);

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
        $setlTxn = $this->getEntities('transaction', ['entity_id' => $setl['id']], true);
        $setlTxn = $setlTxn['items'][0];
        $this->assertEquals('settlement', $setlTxn[TransactionEntity::TYPE]);
        $this->assertEquals($setl['id'], $setlTxn[TransactionEntity::ENTITY_ID]);
        $this->assertNull($setlTxn[TransactionEntity::RECONCILED_AT]);
        $this->assertNotNull($setlTxn[TransactionEntity::SETTLED_AT]);

        // Validate settlement details entity
        $content = $this->getEntities('settlement_details', ['settlement_id' => $setl['id']], true);

        $this->assertArrayHasKey('entity', $content);
        $this->assertSame('collection', $content['entity']);
        $this->assertSame($content['count'], 6);

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

        $this->validationSettlementDestination($setl['id'], Entity::FUND_TRANSFER_ATTEMPT, $bta['id']);
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

    protected function createAndAssignSettlementSchedule($input, $merchantId)
    {
        $schedule = $this->createSchedule($input);

        $request = [
            'method'  => 'POST',
            'url'     => '/merchants/'. $merchantId. '/schedules',
            'content' => [
                'schedule_id' => $schedule['id']
            ],
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        return $schedule;
    }

    protected function createAndAssignSettlementTransferSchedule($input, $merchantId)
    {
        $schedule = $this->createSchedule($input);

        $request = [
            'method'  => 'POST',
            'url'     => '/merchants/'. $merchantId. '/schedules',
            'content' => [
                'schedule_id' => $schedule['id'],
                'method'      => 'settlement_transfer',
                'type'        => 'Settlement',
            ],
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        return $schedule;
    }

    public function testMerchantSettlementV2DspDelayedSettlement()
    {
        $channel = Channel::AXIS;

        $this->ba->adminAuth();

        $dt = Carbon::create(2017, 12, 12, 14, 0, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->merchant->createAccount('7thBRSDflu7NHL');

        $input = [
            'name'        => 'Every 1 hour',
            'period'      => 'hourly',
            'interval'    => 1,
            'delay'       => 0,
        ];

        $this->createAndAssignSettlementSchedule($input,'7thBRSDflu7NHL');

        //assert dsp settlement creation within time range
        $payments = $this->createPaymentEntities(2, '7thBRSDflu7NHL', $dt);

        foreach ($payments as $payment)
        {
            $attrs = ['payment' => $payment, 'amount'  => '100'];

            $refund = $this->fixtures->create('refund:from_payment', $attrs);
            $refunds[] = $refund;
        }

        $dt = Carbon::create(2017, 12, 12, 15, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->createPaymentEntities(2, '7thBRSDflu7NHL', $dt);

        $dt = Carbon::create(2017, 12, 13, 10, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);
        $this->assertEquals(6, $setlResponse[$channel]['txnCount']);
    }

    public function testMerchantSettlementV2DspInBlockPeriod()
    {
        $channel = Channel::AXIS;

        $this->ba->adminAuth();

        $dt = Carbon::create(2017, 12, 12, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->merchant->createAccount('7thBRSDflu7NHL');

        $input = [
            'name'        => 'Every 1 hour',
            'period'      => 'hourly',
            'interval'    => 1,
            'delay'       => 0,
        ];

        $this->createAndAssignSettlementSchedule($input,'7thBRSDflu7NHL');

        //assert dsp settlement creation within time range
        $payments = $this->createPaymentEntities(2, '7thBRSDflu7NHL', $dt);

        foreach ($payments as $payment)
        {
            $attrs = ['payment' => $payment, 'amount'  => '100'];

            $refund = $this->fixtures->create('refund:from_payment', $attrs);
            $refunds[] = $refund;
        }

        $dt = Carbon::create(2017, 12, 12, 17, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(0, $setlResponse[$channel]['count']);
        $this->assertEquals(0, $setlResponse[$channel]['txnCount']);
    }

    public function testMerchantSettlementV2KarvySettlement()
    {
        $channel = Channel::AXIS;

        $this->ba->adminAuth();

        $dt = Carbon::create(2017, 12, 12, 12, 59, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->merchant->createAccount('AmReTNPu1KFKBn');

        $this->fixtures->merchant->createAccount('AwLHhFePbpsfSZ');

        $this->fixtures->edit('merchant', 'AwLHhFePbpsfSZ',['parent_id' => 'AmReTNPu1KFKBn']);

        $input = [
            'name'        => 'Every 1 hour',
            'period'      => 'hourly',
            'interval'    => 1,
            'delay'       => 0,
        ];

        $this->createAndAssignSettlementSchedule($input,'AwLHhFePbpsfSZ');

        $this->createPaymentEntities(2, 'AwLHhFePbpsfSZ', $dt);

        $dt = Carbon::create(2017, 12, 12, 13, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);
        $this->assertEquals(2, $setlResponse[$channel]['txnCount']);

        $dt = Carbon::create(2017, 12, 12, 13, 59, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->createPaymentEntities(2, 'AwLHhFePbpsfSZ', $dt);

        $dt = Carbon::create(2017, 12, 12, 14, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(0, $setlResponse[$channel]['count']);
        $this->assertEquals(0, $setlResponse[$channel]['txnCount']);

        $dt = Carbon::create(2017, 12, 12, 14, 59, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->createPaymentEntities(2, 'AwLHhFePbpsfSZ', $dt);

        $dt = Carbon::create(2017, 12, 12, 15, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);
        $this->assertEquals(4, $setlResponse[$channel]['txnCount']);
    }

    public function testMerchantSettlementV2BlockKarvyOutsideTime()
    {
        $channel = Channel::AXIS;

        $this->ba->adminAuth();

        $dt = Carbon::create(2017, 12, 12, 15, 30, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->merchant->createAccount('AmReTNPu1KFKBn');

        $this->fixtures->merchant->createAccount('AwLHhFePbpsfUU');

        $this->fixtures->edit('merchant', 'AwLHhFePbpsfUU', ['parent_id' => 'AmReTNPu1KFKBn']);

        $input = [
            'name' => 'Every 1 hour',
            'period' => 'hourly',
            'interval' => 1,
            'delay' => 0,
        ];

        $this->createAndAssignSettlementSchedule($input, 'AwLHhFePbpsfUU');

        $payments = $this->createPaymentEntities(2, 'AwLHhFePbpsfUU', $dt);

        foreach ($payments as $payment) {
            $attrs = ['payment' => $payment, 'amount' => '100'];

            $refund = $this->fixtures->create('refund:from_payment', $attrs);
            $refunds[] = $refund;
        }

        $dt = Carbon::create(2017, 12, 12, 16, 10, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(0, $setlResponse[$channel]['count']);
        $this->assertEquals(0, $setlResponse[$channel]['txnCount']);
    }

    public function testMerchantSettlementV2DelayedKarvySettlement()
    {
        $channel = Channel::AXIS;

        $this->ba->adminAuth();

        $dt = Carbon::create(2017, 12, 12, 12, 59, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->merchant->createAccount('AmReTNPu1KFKBn');

        $this->fixtures->merchant->createAccount('AwLHhFePbpsfSZ');

        $this->fixtures->edit('merchant', 'AwLHhFePbpsfSZ',['parent_id' => 'AmReTNPu1KFKBn']);

        $input = [
            'name'        => 'Every 1 hour',
            'period'      => 'hourly',
            'interval'    => 1,
            'delay'       => 0,
        ];

        $this->createAndAssignSettlementSchedule($input,'AwLHhFePbpsfSZ');

        $this->createPaymentEntities(2, 'AwLHhFePbpsfSZ', $dt);

        $dt = Carbon::create(2017, 12, 12, 16, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);
        $this->assertEquals(2, $setlResponse[$channel]['txnCount']);

        $dt = Carbon::create(2017, 12, 13, 14, 30, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->createPaymentEntities(2, 'AwLHhFePbpsfSZ', $dt);

        $dt = Carbon::create(2017, 12, 14, 10, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);
        $this->assertEquals(2, $setlResponse[$channel]['txnCount']);

        $setl = $this->getLastEntity('settlement', true);

        $bta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->validationSettlementDestination($setl['id'], Entity::FUND_TRANSFER_ATTEMPT, $bta['id']);
    }


    public function testMerchantOnEarlySettlementThreePm()
    {
        $channel = Channel::AXIS;

        $this->ba->adminAuth();

        $dt = Carbon::create(2018, 12, 6, 8, 50, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->merchant->createAccount('ZmReTNPu1KFKBn');

        $this->fixtures->merchant->createAccount('BwLHhFePbpsfSZ');

        $this->fixtures->edit('merchant', 'BwLHhFePbpsfSZ',['parent_id' => 'ZmReTNPu1KFKBn']);

        $this->fixtures->merchant->addFeatures([Constants::ES_AUTOMATIC, Constants::ES_AUTOMATIC_THREE_PM], 'BwLHhFePbpsfSZ');

        $input = [
            'name'        => 'Hourly Early Settlement',
            'period'      => 'hourly',
            'interval'    => 1,
            'hour'        => 0,
            'delay'       => 0,
        ];

        $this->createAndAssignSettlementSchedule($input,'BwLHhFePbpsfSZ');

        $this->createPaymentEntities(2, 'BwLHhFePbpsfSZ', $dt);

        $dt = Carbon::create(2018, 12, 6, 9, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);
        $this->assertEquals(2, $setlResponse[$channel]['txnCount']);

        $dt = Carbon::create(2018, 12, 6, 14, 59, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->createPaymentEntities(2, 'BwLHhFePbpsfSZ', $dt);

        $dt = Carbon::create(2018, 12, 6, 15, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);
        $this->assertEquals(2, $setlResponse[$channel]['txnCount']);

        $dt = Carbon::create(2018, 12, 6, 16, 59, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->createPaymentEntities(2, 'BwLHhFePbpsfSZ', $dt);

        $dt = Carbon::create(2018, 12, 6, 17, 1, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $setlResponse = $this->initiateSettlements($channel);

        $this->assertNotNull($setlResponse[$channel]);
        $this->assertEquals(1, $setlResponse[$channel]['count']);
        $this->assertEquals(2, $setlResponse[$channel]['txnCount']);

        $setl = $this->getLastEntity('settlement', true);

        $bta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->validationSettlementDestination($setl['id'], Entity::FUND_TRANSFER_ATTEMPT, $bta['id']);
    }

    protected function validationSettlementDestination(string $settlementId, string $destinationType, string $destinationId)
    {
        $destinationPrefix = ($destinationType === Entity::FUND_TRANSFER_ATTEMPT) ? 'fta_' : 'stf_';

        $content = $this->getLastEntity('settlement_destination', true);

        $this->assertEquals($settlementId, 'setl_' . $content['settlement_id']);

        $this->assertEquals($destinationType, $content['destination_type']);

        $this->assertEquals($destinationId, $destinationPrefix . $content['destination_id']);
    }

    public function testSettlementTransferWithScheduleAssign()
    {
        $this->ba->adminAuth();

        $dt = Carbon::create(2019, 12, 6, 9, 0, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->merchant->createAccount('ZmReTNPu1KFKBn');

        $input = [
            'name'        => 'Hourly Early Settlement',
            'period'      => 'hourly',
            'interval'    => 1,
            'hour'        => 0,
            'delay'       => 4,
        ];

        $this->createAndAssignSettlementTransferSchedule($input,'ZmReTNPu1KFKBn');

        $settlementTransfer= $this->fixtures->create('settlement_transfer');

        $settlementTransfer = new SettlementTransfer($settlementTransfer);

        $returnTIme = $settlementTransfer->getSettledAtTimestampForSettlementTransfer('ZmReTNPu1KFKBn');

        $this->assertEquals(1575617400, $returnTIme);
    }

    public function testSettlementTransferWithoutScheduleAssign()
    {
        $this->ba->adminAuth();

        $dt = Carbon::create(2019, 12, 6, 9, 0, 0, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->merchant->createAccount('ZmReTNPu1KFKBn');

        $settlementTransfer= $this->fixtures->create('settlement_transfer');

        $settlementTransfer = new SettlementTransfer($settlementTransfer);

        $returnTIme = $settlementTransfer->getSettledAtTimestampForSettlementTransfer('ZmReTNPu1KFKBn');

        $this->assertEquals(1575628200, $returnTIme);
    }

    public function testSettlementCreateFromNewService()
    {
        $content = $this->testData['testSettlementCreateFromNewService'];

        $result = $this->createSettlementEntry($content);

        $settlement = $this->getLastEntity('settlement', true);

        $settlementDetails = $this->getSettlementDetails($settlement['id']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals('txn_'. $settlement['transaction_id'], $transaction['id']);

        $this->assertEquals($settlement['id'], $transaction['entity_id']);

        $this->assertArraySelectiveEquals($this->testData['testSettlementCreateFromNewServiceSettlementDetails'],
                                            $settlementDetails['items']);

        $this->assertEquals(1, $settlement['is_new_service']);

        $this->assertEquals('setl_'. $content['settlement_id'], $settlement['id']);
    }

    public function testGetGlobalConfigFetchWithPartnerBankAccount()
    {
        $partnerBankAccount = $this->createPlatformMerchantsAndSubmerchants();

        $this->assertNotNull($partnerBankAccount);

        $this->createConfigForPartnerApp(
            Partner\Constants::DEFAULT_NON_PLATFORM_APP_ID,
            null,
            [PartnerConfig\Entity::SETTLE_TO_PARTNER => true]);

        $this->ba->settlementsAuth();

        $this->fixtures->edit('merchant', Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID, [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,

        ]);

        $result = $this->getGlobalConfig(Partner\Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID);

        $this->assertEquals($partnerBankAccount->getId(), $result['partner_bank_account']);
        $this->assertEquals(true, $result['active']);
        $this->assertNull($result['parent']);
    }

    public function testGetGlobalConfigFetchWithParent()
    {


        $this->createPurePlatFormMerchantAndSubMerchant();

        $this->fixtures->create('feature', [
            'name'        => 'aggregate_settlement',
            'entity_id'   => Partner\Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->edit('merchant', Partner\Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID, [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
        ]);

        $this->ba->settlementsAuth();

        $result = $this->getGlobalConfig(Partner\Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID);

        $this->assertEquals(Partner\Constants::DEFAULT_PLATFORM_MERCHANT_ID, $result['parent']);
        $this->assertEquals(true, $result['active']);
        $this->assertNull($result['partner_bank_account']);
    }

    public function testSettleToOrgAttributePositive()
    {
        $this->ba->settlementsAuth();

        // set feature flag for org
        $this->fixtures->create('feature', [
            'name' => Constants::ORG_SETTLE_TO_BANK,
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
        ]);

        $result = $this->getGlobalConfig('100000Razorpay');

        $this->assertEquals(true, $result["settle_to_org"]);
    }

    public function testSettleToOrgAttributeNegative()
    {
        $this->ba->settlementsAuth();

        // set feature flag on both merchant and org
        $this->fixtures->create('feature', [
            'name' => Constants::ORG_SETTLE_TO_BANK,
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name' => Constants::CANCEL_SETTLE_TO_BANK,
            'entity_id' => '100000Razorpay',
            'entity_type' => 'merchant',
        ]);

        $result = $this->getGlobalConfig('100000Razorpay');

        $this->assertEquals(false, $result["settle_to_org"]);
    }


    public function testSettleToOrgAttributeNegative2()
    {
        $this->ba->settlementsAuth();

        // set feature flag on both merchant and org
        $this->fixtures->create('feature', [
            'name' => Constants::ORG_SETTLE_TO_BANK,
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name' => Constants::OLD_CUSTOM_SETTL_FLOW,
            'entity_id' => '100000Razorpay',
            'entity_type' => 'merchant',
        ]);

        $result = $this->getGlobalConfig('100000Razorpay');

        $this->assertEquals(false, $result["settle_to_org"]);
    }




    public function testSettlementAdminDashboardAction()
    {
        $this->ba->adminAuth();

        $request = [
            'url'     => '/settlements/migration/migrate_to_payout',
            'method'  => 'POST',
            'content' => ['merchant_id' => ['merchant_12345']],
        ];

       $response =  $this->makeRequestAndGetContent($request);

       $this->assertEquals(1,$response['count']);
       $this->assertEquals(200, $response['status_code']);
    }

    public function testGetSettlementFromStatusWithTimeInterval()
    {
        $content = $this->testData['testSettlementCreateFromNewService'];

        $result = $this->createSettlementEntry($content);

        $settlement = $this->getLastEntity('settlement', true);

        $from = Carbon::now()->subDays(1)->getTimestamp();

        $to = Carbon::now()->timestamp;

        $request = [
            'url'     => '/settlements?status=processed&from=' . $from . '&to=' . $to,
            'method'  => 'GET',
            'content' => []
        ];

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotEmpty($content);

        $this->assertArraySelectiveEquals($content['items'][0], $settlement);
    }

    public function testGetSettlementForProcessedStatusWithoutTimeInterval()
    {
        $content = $this->testData['testSettlementCreateFromNewService'];

        $result = $this->createSettlementEntry($content);

        $settlement = $this->getLastEntity('settlement', true);

        $request = [
            'url'     => '/settlements',
            'method'  => 'GET',
            'content' => [
                'status' => 'processed'
            ]
        ];

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotEmpty($content);

        $this->assertArraySelectiveEquals($content['items'][0], $settlement);
    }

    public function testGetSettlementForFailedStatusWithoutTimeInterval()
    {
        $content = $this->testData['testSettlementCreateFromNewService'];

        $result = $this->createSettlementEntry($content);

        $settlement = $this->getLastEntity('settlement', true);

        $request = [
            'url'     => '/settlements',
            'method'  => 'GET',
            'content' => [
                'status' => 'failed'
            ]
        ];

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotEmpty($content);

        $this->assertEquals(sizeof($content['items']), 0);
    }

    public function testGetSettlementForProcessedStatusBeforeDefaultIntervalWithoutTimeInterval()
    {
        $content = $this->testData['testSettlementCreateFromNewService'];

        $result = $this->createSettlementEntry($content);

        $settlement = $this->getLastEntity('settlement', true);

        $settlementId = $settlement['id'];

        $settlementEntity = $this->getDbEntityById('settlement', $settlementId);

        $fiftyDaysAgoTimestamp = Carbon::now(Timezone::IST)->subDays(50)->startOfDay()->getTimestamp();

        $settlementEntity->setCreatedAt($fiftyDaysAgoTimestamp);

        $settlementEntity->saveOrFail();

        $settlement = $this->getLastEntity('settlement', true);

        $request = [
            'url'     => '/settlements',
            'method'  => 'GET',
            'content' => [
                'status' => 'processed'
            ]
        ];

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotEmpty($content);

        $this->assertEquals(sizeof($content['items']), 0);
    }

    public function testGetSettlementFromUtr()
    {
        $content = $this->testData['testSettlementCreateFromNewService'];

        $result = $this->createSettlementEntry($content);

        $settlement = $this->getLastEntity('settlement', true);

        $settlementId = $settlement['id'];

        $settlementEntity = $this->getDbEntityById('settlement', $settlementId);

        $settlementEntity->setUtr('UTR1234');

        $settlementEntity->saveOrFail();

        $settlement = $this->getLastEntity('settlement', true);

        $request = [
            'url'     => '/settlements?utr=UTR1234',
            'method'  => 'GET',
            'content' => []
        ];

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotEmpty($content);

        $this->assertArraySelectiveEquals($content['items'][0], $settlement);
    }

    public function testGetSettlementDetails()
    {
        $content = $this->testData['testSettlementCreateFromNewService'];

        $result = $this->createSettlementEntry($content);

        $settlement = $this->getLastEntity('settlement', true);

        $settlementId = $settlement['id'];

        $settlementDetails = $this->getEntities('settlement_details', ['settlement_id' => $settlement['id']], true);

        $request = [
            'url'     => '/settlements/' . $settlementId . '/details',
            'method'  => 'GET',
            'content' => [],
        ];

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertNotEmpty($content);

        $this->assertArrayHasKey('has_aggregated_fee_tax', $content);
    }

    public function testSettlementServiceMigration()
    {
        $this->ba->adminAuth();

        $this->fixtures->merchant->createAccount('setlMerchant12');

        $input = [
            'name'        => 'Every 1 hour',
            'period'      => 'hourly',
            'interval'    => 1,
            'delay'       => 0,
        ];

        $this->createAndAssignSettlementSchedule($input,'setlMerchant12');

        $request = [
            'url'     => '/settlements/service/migration/admin',
            'method'  => 'POST',
            'content' => [
                'merchant_ids'             => ['setlMerchant12'],
                'migrate_bank_account'    => '0',
                'migrate_merchant_config' => '1',
                'via'                     => 'fts',
            ],
        ];

        $response =  $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $response['total']);

        $this->assertEquals(0, $response['failed_count']);

        $this->fixtures->create('feature', [
            'name'          => Constants::NEW_SETTLEMENT_SERVICE,
            'entity_id'     => 'setlMerchant12',
            'entity_type'   => 'merchant',
        ]);

        $request = [
            'url'     => '/settlements/service/migration/admin',
            'method'  => 'POST',
            'content' => [
                'merchant_ids'             => ['setlMerchant12'],
                'migrate_bank_account'    => '1',
                'migrate_merchant_config' => '0',
                'via'                     => 'fts',
            ],
        ];

        $response =  $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $response['total']);

        $this->assertEquals(0, $response['failed_count']);
    }

    public function testLedgerReconCron()
    {
        $this->ba->cronAuth();

        $createdAt = Carbon::today(Timezone::IST)->subDays(10)->timestamp + 5;
        $capturedAt = Carbon::today(Timezone::IST)->subDays(10)->timestamp + 10;

        $this->fixtures->times(2)->create(
            'payment:captured',
            [
                'captured_at' => $capturedAt,
                'method'      => 'card',
                'merchant_id' => '10000000000000',
                'amount'      => 200,
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt + 10
            ]
        );

        $request = [
            'url'     => '/settlements/ledger_inconsistency/debug/cron',
            'method'  => 'POST',
            'content' => [
                "merchant_ids" => ["10000000000000"],
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, $response['enqueued_mid_count']);
    }

    public function testGefuFileCreation()
    {
        $this->app['config']->set('applications.ufh.mock', true);

        $merchants = $this->fixtures->times(5)->create('merchant');

        $org = $this->fixtures->create('org',[
        'id' => 'IUXvshap3Hbzos',
        'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->create('feature', [
            'name' => 'org_pool_settlement',
            'entity_id' => 'IUXvshap3Hbzos',
            'entity_type' => 'org',
        ]);

        $poolAcc = random_alphanum_string(14);

        $channel = Channel::AXIS;

        foreach ($merchants as $merchant) {

            $merchantId = $merchant->getId();

            $terminal = $this->fixtures->create(
                'terminal',
                [
                    'id' => random_alphanum_string(14),
                    'merchant_id' => $merchantId,
                    'gateway' => 'hdfc',
                    'gateway_merchant_id' => '250000002',
                    'gateway_secure_secret' => "1231424",
                    'gateway_terminal_id' => '250000004',
                    'card' => 1,
                    'emi'  => 1,
                    'mode' => 2,
                    'type'    => [
                        'direct_settlement_with_refund' => '1'
                    ],
                ]);


            $this->fixtures->edit('merchant', $merchant->getId(), [
                'org_id' => $org['id'],
                'channel' => $channel,
                'activated' => true ,
                'suspended_at' => null
            ]);

            $this->fixtures->create('balance', ['id' => $merchantId, 'merchant_id' => $merchantId, 'balance' => 5000]);

            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'account_number'       => $poolAcc, // constant id for all merchant's pool account
                    'beneficiary_name' => random_string_special_chars(10) ,
                    'merchant_id' =>$merchantId,
                    'type'  => 'merchant'
                ]);

            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'beneficiary_name' => random_string_special_chars(10) ,
                    'merchant_id' =>$merchantId,
                    'type'  => 'org_settlement'
                ]);

            $createdAt = Carbon::today(Timezone::IST)->subDays(50)->timestamp + 5;
            $capturedAt = Carbon::today(Timezone::IST)->subDays(50)->timestamp + 10;

            $this->fixtures->times(2)->create(
                'payment:captured',
                [
                    'captured_at' => $capturedAt,
                    'method'      => 'card',
                    'merchant_id' => $merchantId,
                    'amount'      => 100000,
                    'created_at'  => $createdAt,
                    'updated_at'  => $createdAt + 10
                ]

            );
        }

        $this->initiateSettlements(Channel::AXIS);

        Carbon::setTestNow(Carbon::tomorrow(Timezone::IST));

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testCustomGefuFileCreation()
    {
        $this->app['config']->set('applications.ufh.mock', true);

        $merchants = $this->fixtures->times(5)->create('merchant');

        $org = $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzos',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->create('feature', [
            'name' => 'org_pool_settlement',
            'entity_id' => 'IUXvshap3Hbzos',
            'entity_type' => 'org',
        ]);

        $poolAcc = random_alphanum_string(14);

        $channel = Channel::AXIS;

        $this->mockRazorxTreatment();

        $lastCutoffTime = Carbon::yesterday(Timezone::IST)->setTime(20, 0, 0)->getTimestamp() ;

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CARD_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $lastCutoffTime]);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::UPI_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $lastCutoffTime]);

        foreach ($merchants as $merchant) {

            $merchantId = $merchant->getId();

            $terminal = $this->fixtures->create(
                'terminal',
                [
                    'id' => random_alphanum_string(14),
                    'merchant_id' => $merchantId,
                    'gateway' => 'hdfc',
                    'gateway_merchant_id' => '250000002',
                    'gateway_secure_secret' => "1231424",
                    'gateway_terminal_id' => '250000004',
                    'card' => 1,
                    'emi'  => 1,
                    'mode' => 2,
                    'type'    => [
                        'direct_settlement_with_refund' => '1'
                    ],
                ]);


            $this->fixtures->edit('merchant', $merchant->getId(), [
                'org_id' => $org['id'],
                'channel' => $channel,
                'activated' => true ,
                'suspended_at' => null
            ]);

            $this->fixtures->create('balance', ['id' => $merchantId, 'merchant_id' => $merchantId, 'balance' => 5000]);

            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'account_number'       => $poolAcc, // constant id for all merchant's pool account
                    'beneficiary_name' => random_string_special_chars(10) ,
                    'merchant_id' =>$merchantId,
                    'type'  => 'merchant'
                ]);

            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'beneficiary_name' => random_string_special_chars(10) ,
                    'merchant_id' =>$merchantId,
                    'type'  => 'org_settlement'
                ]);

            $createdAt = Carbon::today(Timezone::IST)->subDays(50)->timestamp + 5;
            $capturedAt = Carbon::today(Timezone::IST)->subDays(50)->timestamp + 10;

            $this->fixtures->times(2)->create(
                'payment:captured',
                [
                    'captured_at' => $capturedAt,
                    'method'      => 'card',
                    'merchant_id' => $merchantId,
                    'amount'      => 100000,
                    'created_at'  => $createdAt,
                    'updated_at'  => $createdAt + 10
                ]

            );

            $createdAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 5;
            $capturedAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 10;

            $this->fixtures->times(2)->create(
                'payment',
                [
                    'captured_at' => $capturedAt,
                    'method'      => 'card',
                    'merchant_id' => $merchantId,
                    'amount'      => 100000,
                    'mdr'         => 400,
                    'fee'         => 100,
                    'created_at'  => $createdAt,
                    'updated_at'  => $createdAt + 10,
                    'settled_by' => 'bank'
                ]

            );
            $this->fixtures->times(2)->create(
                'payment',
                [
                    'captured_at' => $capturedAt,
                    'method'      => 'upi',
                    'merchant_id' => $merchantId,
                    'amount'      => 100000,
                    'mdr'         => 0,
                    'fee'         => 100,
                    'created_at'  => $createdAt,
                    'updated_at'  => $createdAt + 10,
                    'settled_by' => 'bank'
                ]

            );
        }

        $this->initiateSettlements(Channel::AXIS);

        Carbon::setTestNow(Carbon::tomorrow(Timezone::IST));

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testCustomGefuFileWithNonDsAndDsTransactionsCreation()
    {
        $this->app['config']->set('applications.ufh.mock', true);

        $merchants = $this->fixtures->times(6)->create('merchant');

        $org = $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzos',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->create('feature', [
            'name' => 'org_pool_settlement',
            'entity_id' => 'IUXvshap3Hbzos',
            'entity_type' => 'org',
        ]);

        $poolAcc = random_alphanum_string(14);

        $channel = Channel::AXIS;

        $this->mockRazorxTreatment();

        $lastCutoffTime = Carbon::yesterday(Timezone::IST)->setTime(20, 0, 0)->getTimestamp() ;

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CARD_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $lastCutoffTime]);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::UPI_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $lastCutoffTime]);


        $cnt = 0;
        foreach ($merchants as $merchant) {

            $merchantId = $merchant->getId();

            $terminal = $this->fixtures->create(
                'terminal',
                [
                    'id' => random_alphanum_string(14),
                    'merchant_id' => $merchantId,
                    'gateway' => 'hdfc',
                    'gateway_merchant_id' => '250000002',
                    'gateway_secure_secret' => "1231424",
                    'gateway_terminal_id' => '250000004',
                    'card' => 1,
                    'emi'  => 1,
                    'mode' => 2,
                    'type'    => [
                        'direct_settlement_with_refund' => '1'
                    ],
                ]);


            $this->fixtures->edit('merchant', $merchant->getId(), [
                'org_id' => $org['id'],
                'channel' => $channel,
                'activated' => true ,
                'suspended_at' => null
            ]);

            $this->fixtures->create('balance', ['id' => $merchantId, 'merchant_id' => $merchantId, 'balance' => 5000]);

            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'account_number'       => $poolAcc, // constant id for all merchant's pool account
                    'beneficiary_name' => random_string_special_chars(10) ,
                    'merchant_id' =>$merchantId,
                    'type'  => 'merchant'
                ]);

            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'beneficiary_name' => random_string_special_chars(10) ,
                    'merchant_id' =>$merchantId,
                    'type'  => 'org_settlement'
                ]);

            if($cnt%3 != 0)
            {
                $createdAt = Carbon::today(Timezone::IST)->subDays(50)->timestamp + 5;
                $capturedAt = Carbon::today(Timezone::IST)->subDays(50)->timestamp + 10;

                $this->fixtures->times(2)->create(
                    'payment:captured',
                    [
                        'captured_at' => $capturedAt,
                        'method'      => 'card',
                        'merchant_id' => $merchantId,
                        'amount'      => 100000,
                        'created_at'  => $createdAt,
                        'updated_at'  => $createdAt + 10
                    ]

                );
            }

            if($cnt%3 != 1)
            {
                $createdAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 5;
                $capturedAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 10;

                $this->fixtures->times(2)->create(
                    'payment',
                    [
                        'captured_at' => $capturedAt,
                        'method'      => 'card',
                        'merchant_id' => $merchantId,
                        'amount'      => 100000,
                        'mdr'         => 400,
                        'fee'         => 100,
                        'created_at'  => $createdAt,
                        'updated_at'  => $createdAt + 10,
                        'settled_by' => 'bank'
                    ]

                );
                $this->fixtures->times(2)->create(
                    'payment',
                    [
                        'captured_at' => $capturedAt,
                        'method'      => 'upi',
                        'merchant_id' => $merchantId,
                        'amount'      => 100000,
                        'mdr'         => 0,
                        'fee'         => 100,
                        'created_at'  => $createdAt,
                        'updated_at'  => $createdAt + 10,
                        'settled_by' => 'bank'
                    ]

                );
            }

            $cnt = $cnt + 1;

        }

        $this->initiateSettlements(Channel::AXIS);

        Carbon::setTestNow(Carbon::tomorrow(Timezone::IST));

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testCustomGefuFileCreationWithMultipleDsTransactionsScenario()
    {
        $this->app['config']->set('applications.ufh.mock', true);

        $merchants = $this->fixtures->times(5)->create('merchant');

        $org = $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzos',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->create('feature', [
            'name' => 'org_pool_settlement',
            'entity_id' => 'IUXvshap3Hbzos',
            'entity_type' => 'org',
        ]);

        $poolAcc = random_alphanum_string(14);

        $channel = Channel::AXIS;

        $this->mockRazorxTreatment();

        $cnt = 0;
        $payId = null;

        $lastCutoffTime = Carbon::yesterday(Timezone::IST)->setTime(20, 0, 0)->getTimestamp() ;

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CARD_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $lastCutoffTime]);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::UPI_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $lastCutoffTime]);

        foreach ($merchants as $merchant) {

            $merchantId = $merchant->getId();

            $terminal = $this->fixtures->create(
                'terminal',
                [
                    'id' => random_alphanum_string(14),
                    'merchant_id' => $merchantId,
                    'gateway' => 'hdfc',
                    'gateway_merchant_id' => '250000002',
                    'gateway_secure_secret' => "1231424",
                    'gateway_terminal_id' => '250000004',
                    'card' => 1,
                    'emi'  => 1,
                    'mode' => 2,
                    'type'    => [
                        'direct_settlement_with_refund' => '1'
                    ],
                ]);


            $this->fixtures->edit('merchant', $merchant->getId(), [
                'org_id' => $org['id'],
                'channel' => $channel,
                'activated' => true ,
                'suspended_at' => null
            ]);

            $this->fixtures->create('balance', ['id' => $merchantId, 'merchant_id' => $merchantId, 'balance' => 5000]);

            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'account_number'       => $poolAcc, // constant id for all merchant's pool account
                    'beneficiary_name' => random_string_special_chars(10) ,
                    'merchant_id' =>$merchantId,
                    'type'  => 'merchant'
                ]);

            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'beneficiary_name' => random_string_special_chars(10) ,
                    'merchant_id' =>$merchantId,
                    'type'  => 'org_settlement'
                ]);

            if($cnt%5 == 0)
            {
                $createdAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 5;
                $capturedAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 10;

                $this->createPaymentForMerchantAt(100000*($cnt+1),$merchantId,$createdAt,'captured','card',$capturedAt);

            }
            else if ($cnt%5 == 1)
            {
                $createdAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 5;

                $this->createPaymentForMerchantAt(100000*($cnt+1),$merchantId,$createdAt,'failed');

            }
            else if($cnt%5 == 2)
            {
                $createdAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 5;

                $this->createPaymentForMerchantAt(100000*($cnt+1),$merchantId,$createdAt,'authorized');
            }
            else if($cnt%5 == 3)
            {
                $createdAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 5;

                $capturedAt = Carbon::today(Timezone::IST)->setTime(16, 0, 0)->getTimestamp() + 10;

                $id = $this->createPaymentForMerchantAt(100000*($cnt+1),$merchantId,$createdAt,'authorized');

                $this->fixtures->edit('payment', $id, [
                    'status' => 'captured',
                    'captured_at' => $capturedAt,
                ]);
            }
            else
            {
                $createdAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 5;

                $payId = $this->createPaymentForMerchantAt(100000*($cnt+1),$merchantId,$createdAt,'authorized');
            }

            $cnt = $cnt + 1;

        }

        $this->initiateSettlements(Channel::AXIS);

        Carbon::setTestNow(Carbon::tomorrow(Timezone::IST));

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow();

        $capturedAt = Carbon::tomorrow(Timezone::IST)->setTime(16, 0, 0)->getTimestamp() + 10;

        $this->fixtures->edit('payment', $payId, [
            'status' => 'captured',
            'captured_at' => $capturedAt,
        ]);

        Carbon::setTestNow(Carbon::now(Timezone::IST)->addDay(2));

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testCustomGefuFileCreationWithTiDbDelay()
    {
        $this->app['config']->set('applications.ufh.mock', true);

        $merchants = $this->fixtures->times(2)->create('merchant');

        $org = $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzos',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->create('feature', [
            'name' => 'org_pool_settlement',
            'entity_id' => 'IUXvshap3Hbzos',
            'entity_type' => 'org',
        ]);

        $poolAcc = random_alphanum_string(14);

        $channel = Channel::AXIS;

        $this->mockRazorxTreatment();

        $lastCutoffTime = Carbon::yesterday(Timezone::IST)->setTime(20, 0, 0)->getTimestamp() ;

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CARD_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $lastCutoffTime]);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::UPI_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $lastCutoffTime]);

        $cnt = 0;

        foreach ($merchants as $merchant) {

            $merchantId = $merchant->getId();

            $terminal = $this->fixtures->create(
                'terminal',
                [
                    'id' => random_alphanum_string(14),
                    'merchant_id' => $merchantId,
                    'gateway' => 'hdfc',
                    'gateway_merchant_id' => '250000002',
                    'gateway_secure_secret' => "1231424",
                    'gateway_terminal_id' => '250000004',
                    'card' => 1,
                    'emi'  => 1,
                    'mode' => 2,
                    'type'    => [
                        'direct_settlement_with_refund' => '1'
                    ],
                ]);


            $this->fixtures->edit('merchant', $merchant->getId(), [
                'org_id' => $org['id'],
                'channel' => $channel,
                'activated' => true ,
                'suspended_at' => null
            ]);

            $this->fixtures->create('balance', ['id' => $merchantId, 'merchant_id' => $merchantId, 'balance' => 5000]);

            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'account_number'       => $poolAcc, // constant id for all merchant's pool account
                    'beneficiary_name' => random_string_special_chars(10) ,
                    'merchant_id' =>$merchantId,
                    'type'  => 'merchant'
                ]);

            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'beneficiary_name' => random_string_special_chars(10) ,
                    'merchant_id' =>$merchantId,
                    'type'  => 'org_settlement'
                ]);


            $createdAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 5;
            $capturedAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 10;

            $this->createPaymentForMerchantAt(100000*($cnt+1),$merchantId,$createdAt,'captured','card',$capturedAt);

            $createdAt = Carbon::today(Timezone::IST)->setTime(15, 0, 0)->getTimestamp() + 5;
            $capturedAt = Carbon::today(Timezone::IST)->setTime(15, 0, 0)->getTimestamp() + 10;

            $this->createPaymentForMerchantAt(100000*($cnt+1),$merchantId,$createdAt,'captured','card',$capturedAt);

            $cnt = $cnt + 1;

        }

        $this->initiateSettlements(Channel::AXIS);

        Carbon::setTestNow(Carbon::tomorrow(Timezone::IST));

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow();

        foreach ($merchants as $merchant) {
            $merchantId = $merchant->getId();

            // these txns are before last cutoff time, so shouln't come in gifu file
            $createdAt = Carbon::today(Timezone::IST)->setTime(14, 0, 0)->getTimestamp() + 5;
            $capturedAt = Carbon::today(Timezone::IST)->setTime(14, 0, 0)->getTimestamp() + 10;

            $this->createPaymentForMerchantAt(700000,$merchantId,$createdAt,'captured','card',$capturedAt);

            // these txns happened in last batch but didn't come in db due to delay,
            // so these txns are after last cutoff time so shouln't come in current batch gifu file
            $createdAt = Carbon::today(Timezone::IST)->setTime(16, 0, 0)->getTimestamp() + 5;
            $capturedAt = Carbon::today(Timezone::IST)->setTime(16, 0, 0)->getTimestamp() + 10;

            $this->createPaymentForMerchantAt(300000,$merchantId,$createdAt,'captured','card',$capturedAt);

            $createdAt = Carbon::tomorrow(Timezone::IST)->setTime(17, 0, 0)->getTimestamp() + 5;
            $capturedAt = Carbon::tomorrow(Timezone::IST)->setTime(17, 0, 0)->getTimestamp() + 10;

            $this->createPaymentForMerchantAt(200000,$merchantId,$createdAt,'captured','card',$capturedAt);
        }

        Carbon::setTestNow(Carbon::now(Timezone::IST)->addDay(2));

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    protected function createPaymentForMerchantAt($amount,$merchantId,$createdAt,$status='captured',$method='card',$capturedAt = null)
    {
        switch ($status)
        {
            case 'authorized' :
                $paymentId =  $this->fixtures->create('payment', [
                    'status' => 'authorized',
                    'method'      => $method,
                    'merchant_id' => $merchantId,
                    'amount'      => $amount,
                    'created_at'  => $createdAt,
                    'updated_at'  => $createdAt + 10,
                    'settled_by' => 'bank'
                ])->getId();
                break;

            case 'failed':
                $paymentId =  $this->fixtures->create('payment', [
                    'status' => 'failed',
                    'method'      => $method,
                    'merchant_id' => $merchantId,
                    'amount'      => $amount,
                    'created_at'  => $createdAt,
                    'updated_at'  => $createdAt + 10,
                    'settled_by' => 'bank'
                ])->getId();
                break;

            default :
                $paymentId =  $this->fixtures->create('payment', [
                    'status' => 'captured',
                    'captured_at' => $capturedAt,
                    'method'      => $method,
                    'merchant_id' => $merchantId,
                    'amount'      => $amount,
                    'created_at'  => $createdAt,
                    'updated_at'  => $createdAt + 10,
                    'settled_by' => 'bank'
                ])->getId();
        }

        return $paymentId;
    }

    public function testGefuFileCreationWithoutPoolAccount()
    {
        $this->markTestSkipped('The flakiness in the testcase needs to be fixed. Skipping as its impacting dev-productivity.');

        $this->app['config']->set('applications.ufh.mock', true);

        $merchants = $this->fixtures->times(5)->create('merchant');

        $org = $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzos',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->create('feature', [
            'name' => 'org_pool_settlement',
            'entity_id' => 'IUXvshap3Hbzos',
            'entity_type' => 'org',
        ]);

        $channel = Channel::AXIS;

        foreach ($merchants as $merchant) {

            $merchantId = $merchant->getId();

            $terminal = $this->fixtures->create(
                'terminal',
                [
                    'id' => random_alphanum_string(14),
                    'merchant_id' => $merchantId,
                    'gateway' => 'hdfc',
                    'gateway_merchant_id' => '250000002',
                    'gateway_secure_secret' => "1231424",
                    'gateway_terminal_id'   => '250000003',
                    'card' => 1,
                    'emi'  => 1,
                    'mode' => 2,
                    'type'    => [
                        'direct_settlement_with_refund' => '1'
                    ],
                ]);


            $this->fixtures->edit('merchant', $merchant->getId(), [
                'org_id' => $org['id'],
                'channel' => $channel,
                'activated' => true ,
                'suspended_at' => null
            ]);

            $this->fixtures->create('balance', ['id' => $merchantId, 'merchant_id' => $merchantId]);

            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'account_number'       => random_integer(14),
                    'beneficiary_name' => random_string_special_chars(10) ,
                    'merchant_id' =>$merchantId,
                    'type'  => 'merchant'
                ]);

            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'account_number'  => random_integer(14),
                    'beneficiary_name' => random_string_special_chars(10) ,
                    'merchant_id' =>$merchantId,
                    'type'      => 'org_settlement'
                ]);

            $createdAt = Carbon::today(Timezone::IST)->subDays(50)->timestamp + 5;
            $capturedAt = Carbon::today(Timezone::IST)->subDays(50)->timestamp + 10;

            $this->fixtures->times(2)->create(
                'payment:captured',
                [
                    'captured_at' => $capturedAt,
                    'method'      => 'card',
                    'merchant_id' => $merchantId,
                    'amount'      => 10000,
                    'created_at'  => $createdAt,
                    'updated_at'  => $createdAt + 10
                ]

            );
        }

        $this->initiateSettlements(Channel::AXIS);

        Carbon::setTestNow(Carbon::tomorrow(Timezone::IST));

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testGetMerchantConfigForSettlementForMerchantWithBusinessTypeProprietorshipWithCompanyPan()
    {
        $this->ba->settlementsAuth();

        $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzot',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->merchant->createMerchantWithDetails(
            'IUXvshap3Hbzot',
            '110000Razorpay',
            [
                MerchantEntity::NAME            => 'Test IIR MID 1254',
                MerchantEntity::ACTIVATED       => 1,
                MerchantEntity::LIVE            => 1,
                MerchantEntity::ACTIVATED_AT    => Carbon::now()->timestamp,
                MerchantEntity::WEBSITE         => 'www.testIIRMid1235.com',
                MerchantEntity::CATEGORY2       => 'category2_1'
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => 'activated',
                MerchantDetailsEntity::BUSINESS_TYPE            => '1',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 2',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
                MerchantDetailsEntity::PROMOTER_PAN             => 'AJDDOC1234',
                MerchantDetailsEntity::COMPANY_PAN              => 'COMPANYPAN'
            ]);

        $result = $this->getGlobalConfig('110000Razorpay');

        $this->assertEquals("COMPANYPAN", $result["pan_details"]);
    }

    public function testGetPartnerCommissionConfigForResellerPartnerActivated()
    {
        $this->ba->settlementsAuth();

        $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzot',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->merchant->createMerchantWithDetails(
            'IUXvshap3Hbzot',
            '110000Razorpay',
            [
                MerchantEntity::NAME            => 'Test IIR MID 1254',
                MerchantEntity::WEBSITE         => 'www.testIIRMid1235.com',
                MerchantEntity::PARTNER_TYPE    => 'reseller',
                MerchantEntity::ACTIVATED       => 0,
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => null,
                MerchantDetailsEntity::BUSINESS_TYPE            => '1',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 2',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
                MerchantDetailsEntity::PROMOTER_PAN             => 'AJDDOC1234',
            ]);

        $this->fixtures->create('partner_activation',[
            'merchant_id'       => '110000Razorpay',
            'hold_funds'        => false,
            'activation_status' => 'activated'
        ]);

        $this->mockAllSplitzTreatment();

        $result = $this->getGlobalConfig('110000Razorpay');

        $expectedPartnerCommissionsConfig = [
            'hold_status'          => false,
            'hold_reason'          => '',
            'enabled'              => true,
        ];

        $this->assertArraySelectiveEquals($expectedPartnerCommissionsConfig, $result["partner_commissions_config"]);
    }

    public function testGetPartnerCommissionConfigForMalaysianResellerPartnerActivated()
    {
        $this->ba->settlementsAuth();

        $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzot',
            'display_name' => 'Curlec Org'
        ]);

        $this->fixtures->merchant->createMerchantWithDetails(
            'IUXvshap3Hbzot',
            '11000000Curlec',
            [
                MerchantEntity::NAME            => 'Test IIR MID 1254',
                MerchantEntity::WEBSITE         => 'www.testIIRMid1235.com',
                MerchantEntity::PARTNER_TYPE    => 'reseller',
                MerchantEntity::COUNTRY_CODE    => 'MY',
                MerchantEntity::ACTIVATED       => 1,
                MerchantEntity::LIVE            => 1,
                MerchantEntity::ACTIVATED_AT    => Carbon::now()->timestamp,
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => 'activated',
                MerchantDetailsEntity::BUSINESS_TYPE            => '1',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 2',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
                MerchantDetailsEntity::PROMOTER_PAN             => 'AJDDOC1234',
            ]);


        $this->fixtures->create('partner_activation',[
            'merchant_id'       => '11000000Curlec',
            'hold_funds'        => false,
            'activation_status' => 'activated'
        ]);

        $result = $this->getGlobalConfig('11000000Curlec');

        $expectedPartnerCommissionsConfig = [
            'hold_status'          => false,
            'hold_reason'          => '',
            'enabled'              => false,
        ];

        $this->assertArraySelectiveEquals($expectedPartnerCommissionsConfig, $result["partner_commissions_config"]);
    }

    public function testGetPartnerCommissionConfigForMalaysianMerchantResellerPartnerNotActivated()
    {
        $this->ba->settlementsAuth();

        $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzot',
            'display_name' => 'Curlec Org'
        ]);

        $this->fixtures->merchant->createMerchantWithDetails(
            'IUXvshap3Hbzot',
            '11000000Curlec',
            [
                MerchantEntity::NAME            => 'Test IIR MID 1254',
                MerchantEntity::WEBSITE         => 'www.testIIRMid1235.com',
                MerchantEntity::PARTNER_TYPE    => 'reseller',
                MerchantEntity::COUNTRY_CODE    => 'MY',
                MerchantEntity::ACTIVATED       => 0,
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => null,
                MerchantDetailsEntity::BUSINESS_TYPE            => '1',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 2',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
                MerchantDetailsEntity::PROMOTER_PAN             => 'AJDDOC1234',
            ]);


        $this->fixtures->create('partner_activation',[
            'merchant_id'       => '11000000Curlec',
            'hold_funds'        => false,
            'activation_status' => 'activated'
        ]);

        $result = $this->getGlobalConfig('11000000Curlec');

        $expectedPartnerCommissionsConfig = [
            'hold_status'          => false,
            'hold_reason'          => '',
            'enabled'              => false,
        ];

        $this->assertArraySelectiveEquals($expectedPartnerCommissionsConfig, $result["partner_commissions_config"]);
    }

    public function testGetPartnerCommissionConfigForResellerMerchantActivated()
    {
        $this->ba->settlementsAuth();

        $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzot',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->merchant->createMerchantWithDetails(
            'IUXvshap3Hbzot',
            '110000Razorpay',
            [
                MerchantEntity::NAME            => 'Test IIR MID 1254',
                MerchantEntity::WEBSITE         => 'www.testIIRMid1235.com',
                MerchantEntity::PARTNER_TYPE    => 'reseller',
                MerchantEntity::ACTIVATED       => 1,
                MerchantEntity::LIVE            => 1,
                MerchantEntity::ACTIVATED_AT    => Carbon::now()->timestamp,
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => 'activated',
                MerchantDetailsEntity::BUSINESS_TYPE            => '1',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 2',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
                MerchantDetailsEntity::PROMOTER_PAN             => 'AJDDOC1234',
            ]);

        $this->fixtures->create('partner_activation',[
            'merchant_id'       => '110000Razorpay',
            'hold_funds'        => false,
            'activation_status' => 'activated'
        ]);

        $this->mockAllSplitzTreatment();

        $result = $this->getGlobalConfig('110000Razorpay');

        $expectedPartnerCommissionsConfig = [
            'hold_status'          => false,
            'hold_reason'          => '',
            'enabled'              => false,
        ];

        $this->assertArraySelectiveEquals($expectedPartnerCommissionsConfig, $result["partner_commissions_config"]);
    }

    public function testGetPartnerCommissionConfigForNonResellerMerchantActivated()
    {
        $this->ba->settlementsAuth();

        $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzot',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->merchant->createMerchantWithDetails(
            'IUXvshap3Hbzot',
            '110000Razorpay',
            [
                MerchantEntity::NAME               => 'Test IIR MID 1254',
                MerchantEntity::WEBSITE            => 'www.testIIRMid1235.com',
                MerchantEntity::PARTNER_TYPE       => 'aggregator',
                MerchantEntity::ACTIVATED          => 1,
                MerchantEntity::LIVE               => 1,
                MerchantEntity::ACTIVATED_AT       => Carbon::now()->timestamp,
                MerchantEntity::HOLD_FUNDS         => false,
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => 'activated',
                MerchantDetailsEntity::BUSINESS_TYPE            => '1',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 2',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
                MerchantDetailsEntity::PROMOTER_PAN             => 'AJDDOC1234',
            ]);

        $this->fixtures->create('partner_activation',[
            'merchant_id'       => '110000Razorpay',
            'hold_funds'        => false,
            'activation_status' => 'activated'
        ]);

        $this->mockAllSplitzTreatment();

        $result = $this->getGlobalConfig('110000Razorpay');

        $expectedPartnerCommissionsConfig = [
            'hold_status'          => false,
            'hold_reason'          => '',
            'enabled'              => false,
        ];

        $this->assertArraySelectiveEquals($expectedPartnerCommissionsConfig, $result["partner_commissions_config"]);
    }

    public function testGetPartnerCommissionConfigForNonPartnerMerchantActivated()
    {
        $this->ba->settlementsAuth();

        $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzot',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->merchant->createMerchantWithDetails(
            'IUXvshap3Hbzot',
            '110000Razorpay',
            [
                MerchantEntity::NAME               => 'Test IIR MID 1254',
                MerchantEntity::WEBSITE            => 'www.testIIRMid1235.com',
                MerchantEntity::ACTIVATED          => 1,
                MerchantEntity::LIVE               => 1,
                MerchantEntity::ACTIVATED_AT       => Carbon::now()->timestamp,
                MerchantEntity::HOLD_FUNDS         => false,
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => 'activated',
                MerchantDetailsEntity::BUSINESS_TYPE            => '1',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 2',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
                MerchantDetailsEntity::PROMOTER_PAN             => 'AJDDOC1234',
            ]);

        $this->fixtures->create('partner_activation',[
            'merchant_id'       => '110000Razorpay',
            'hold_funds'        => false,
            'activation_status' => 'activated'
        ]);

        $this->mockAllSplitzTreatment();

        $result = $this->getGlobalConfig('110000Razorpay');

        $expectedPartnerCommissionsConfig = [
            'hold_status'          => false,
            'hold_reason'          => '',
            'enabled'              => false,
        ];

        $this->assertArraySelectiveEquals($expectedPartnerCommissionsConfig, $result["partner_commissions_config"]);
    }

    public function testGetPartnerCommissionConfigForNonActivatedResellerPartnerAndMerchant()
    {
        $this->ba->settlementsAuth();

        $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzot',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->merchant->createMerchantWithDetails(
            'IUXvshap3Hbzot',
            '110000Razorpay',
            [
                MerchantEntity::NAME               => 'Test IIR MID 1254',
                MerchantEntity::WEBSITE            => 'www.testIIRMid1235.com',
                MerchantEntity::PARTNER_TYPE       => 'reseller',
                MerchantEntity::HOLD_FUNDS         => true,
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => null,
                MerchantDetailsEntity::BUSINESS_TYPE            => '1',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 2',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
                MerchantDetailsEntity::PROMOTER_PAN             => 'AJDDOC1234',
            ]);

        $this->fixtures->create('partner_activation',[
            'merchant_id'       => '110000Razorpay',
            'hold_funds'        => true,
            'activation_status' => null
        ]);

        $this->mockAllSplitzTreatment();

        $result = $this->getGlobalConfig('110000Razorpay');

        $expectedPartnerCommissionsConfig = [
            'hold_status'          => true,
            'hold_reason'          => 'Partner funds are on hold',
            'enabled'              => true,
        ];

        $this->assertArraySelectiveEquals($expectedPartnerCommissionsConfig, $result["partner_commissions_config"]);
    }

    public function testGetMerchantConfigForSettlementForMerchantWithBusinessTypeProprietorshipWithPromoterPan()
    {
        $this->ba->settlementsAuth();

        $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzot',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->merchant->createMerchantWithDetails(
            'IUXvshap3Hbzot',
            '110000Razorpay',
            [
                MerchantEntity::NAME            => 'Test IIR MID 1254',
                MerchantEntity::ACTIVATED       => 1,
                MerchantEntity::LIVE            => 1,
                MerchantEntity::ACTIVATED_AT    => Carbon::now()->timestamp,
                MerchantEntity::WEBSITE         => 'www.testIIRMid1235.com',
                MerchantEntity::CATEGORY2       => 'category2_1'
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => 'activated',
                MerchantDetailsEntity::BUSINESS_TYPE            => '1',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 2',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
                MerchantDetailsEntity::PROMOTER_PAN             => 'PROMOTERPAN',
            ]);

        $result = $this->getGlobalConfig('110000Razorpay');

        $this->assertEquals("PROMOTERPAN", $result["pan_details"]);
    }

    public function testGetMerchantConfigForSettlementForMerchantWithBusinessTypeProprietorshipWithNoPan()
    {
        $this->ba->settlementsAuth();

        $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzot',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->merchant->createMerchantWithDetails(
            'IUXvshap3Hbzot',
            '110000Razorpay',
            [
                MerchantEntity::NAME            => 'Test IIR MID 1254',
                MerchantEntity::ACTIVATED       => 1,
                MerchantEntity::LIVE            => 1,
                MerchantEntity::ACTIVATED_AT    => Carbon::now()->timestamp,
                MerchantEntity::WEBSITE         => 'www.testIIRMid1235.com',
                MerchantEntity::CATEGORY2       => 'category2_1'
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => 'activated',
                MerchantDetailsEntity::BUSINESS_TYPE            => '1',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 2',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
            ]);

        $result = $this->getGlobalConfig('110000Razorpay');

        $this->assertNull($result["pan_details"]);
    }

    public function testGetMerchantConfigForSettlementForMerchantWithBusinessTypePrivateWithNoPan()
    {
        $this->ba->settlementsAuth();

        $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzot',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->merchant->createMerchantWithDetails(
            'IUXvshap3Hbzot',
            '110000Razorpay',
            [
                MerchantEntity::NAME            => 'Test IIR MID 1254',
                MerchantEntity::ACTIVATED       => 1,
                MerchantEntity::LIVE            => 1,
                MerchantEntity::ACTIVATED_AT    => Carbon::now()->timestamp,
                MerchantEntity::WEBSITE         => 'www.testIIRMid1235.com',
                MerchantEntity::CATEGORY2       => 'category2_1'
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => 'activated',
                MerchantDetailsEntity::BUSINESS_TYPE            => '4',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 2',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
            ]);

        $result = $this->getGlobalConfig('110000Razorpay');

        $this->assertNull($result["pan_details"]);
    }

    public function testGetMerchantConfigForSettlementForMerchantWithBusinessTypePrivateWithCompanyPan()
    {
        $this->ba->settlementsAuth();

        $this->fixtures->create('org',[
            'id' => 'IUXvshap3Hbzot',
            'display_name' => 'HDFC CollectNow Bank'
        ]);

        $this->fixtures->merchant->createMerchantWithDetails(
            'IUXvshap3Hbzot',
            '110000Razorpay',
            [
                MerchantEntity::NAME            => 'Test IIR MID 1254',
                MerchantEntity::ACTIVATED       => 1,
                MerchantEntity::LIVE            => 1,
                MerchantEntity::ACTIVATED_AT    => Carbon::now()->timestamp,
                MerchantEntity::WEBSITE         => 'www.testIIRMid1235.com',
                MerchantEntity::CATEGORY2       => 'category2_1'
            ],
            [
                MerchantDetailsEntity::ACTIVATION_STATUS        => 'activated',
                MerchantDetailsEntity::BUSINESS_TYPE            => '4',
                MerchantDetailsEntity::BUSINESS_CATEGORY        => 'biz category 2',
                MerchantDetailsEntity::BUSINESS_SUBCATEGORY     => 'biz subcategory',
                MerchantDetailsEntity::COMPANY_PAN              => 'COMPANYPAN',
            ]);

        $result = $this->getGlobalConfig('110000Razorpay');

        $this->assertEquals("COMPANYPAN",$result["pan_details"]);
    }
}
