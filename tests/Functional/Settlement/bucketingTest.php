<?php

namespace RZP\Tests\Functional\Settlement;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Preferences;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Schedule\ScheduleTrait;

class BucketingTest extends TestCase
{
    use PaymentTrait;
    use ScheduleTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->publicAuth();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    public function testAdjustmentBucketing()
    {
        $timestamp = Carbon::create(2019, 9, 19, 9, 30, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->ba->cronAuth();

        $this->mockSettlementServiceRamp(false);

        $request = [
            'url'     => '/nodal/transfer',
            'method'  => 'POST',
            'content' => [
                'amount'        => 1076,
                'channel'       => 'yesbank',
                'destination'   => 'axis2'
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $txn = $this->getLastEntity('transaction', true);

        $bucket = $this->getLastEntity('settlement_bucket', true);

        $this->assertEquals(1568865600, $txn['settled_at']);

        $this->assertEquals(1568867400, $bucket['bucket_timestamp']);
    }

    public function testMerchantReleaseFundsBucketing()
    {
        $timestamp = Carbon::create(2019, 9, 19, 9, 30, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->ba->adminAuth();

        $this->mockSettlementServiceRamp(false);

        $request = [
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
            'content' => [
                'action' => 'hold_funds',
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $request = [
            'url'     => '/merchants/10000000000000/action',
            'method'  => 'PUT',
            'content' => [
                'action' => 'release_funds',
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $bucket = $this->getLastEntity('settlement_bucket', true);

        $this->assertEquals(10000000000000, $bucket['merchant_id']);

        $this->assertEquals(1568867400, $bucket['bucket_timestamp']);
    }

    public function testPaymentSettlementBucketing()
    {
        $this->setTestTime();

        $this->mockSettlementServiceRamp(false);

        $this->createPaymentAndAssert(10000000000000, 1569195000, 1569195000);
    }

    public function testPaymentSettlementBucketingNewService()
    {
        $this->setTestTime();

        $this->mockSettlementServiceRamp(true);

        $this->createPayment(10000000000000, true);

        $bucket = $this->getLastEntity('settlement_bucket', true);

       $this->assertNull($bucket);
    }

    public function testEarlySettlement9AMBucket()
    {
        $timestamp = Carbon::create(2019, 9, 19, 8, 30, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->initializeEarlySettlementMerchant('10000000000000');

        $this->mockSettlementServiceRamp(false);

        $this->createPaymentAndAssert(10000000000000, 1568863800, 1568863800);
    }

    public function testEarlySettlement9AMNextDayBucket()
    {
        $timestamp = Carbon::create(2019, 9, 19, 17, 1, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->initializeEarlySettlementMerchant('10000000000000');

        $this->mockSettlementServiceRamp(false);

        $this->createPaymentAndAssert(10000000000000, 1568896200, 1568950200);
    }

    public function testEarlySettlement5PMBucket()
    {
        $timestamp = Carbon::create(2019, 9, 19, 10, 1, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->initializeEarlySettlementMerchant('10000000000000');

        $this->mockSettlementServiceRamp(false);

        $this->createPaymentAndAssert(10000000000000, 1568871000, 1568892600);
    }

    public function testEarlySettlement3PMBucket()
    {
        $timestamp = Carbon::create(2019, 9, 19, 13, 1, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->initializeEarlySettlementMerchant('10000000000000', true);

        $this->mockSettlementServiceRamp(false);

        $this->createPaymentAndAssert(10000000000000, 1568881800, 1568885400);
    }

    public function testOnly1PMSettlementBucket()
    {
        $timestamp = Carbon::create(2019, 9, 19, 10, 1, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->initializeMutualFundMerchants(Preferences::MID_PAISABAZAAR, 10000000000000);

        $this->mockSettlementServiceRamp(false);

        $this->createPaymentAndAssert(10000000000000, 1568871000, 1568881800);

        $timestamp = Carbon::create(2019, 9, 19, 14, 1, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->createPaymentAndAssert(10000000000000, 1568885400, 1568968200);
    }

    public function testOnlyTwoSettlementBucket()
    {
        // 1PM bucket
        $timestamp = Carbon::create(2019, 9, 19, 10, 1, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->initializeMutualFundMerchants(Preferences::MID_WEALTHAPP, 10000000000000);

        $this->mockSettlementServiceRamp(false);

        $this->createPaymentAndAssert(10000000000000, 1568871000, 1568878200);

        // 2PM bucket
        $timestamp = Carbon::create(2019, 9, 19, 13, 1, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->createPaymentAndAssert(10000000000000, 1568881800, 1568881800);

        // next day 1 PM
        $timestamp = Carbon::create(2019, 9, 19, 14, 1, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->createPaymentAndAssert(10000000000000, 1568885400, 1568964600);
    }

    public function testKarvySettlementBucket()
    {
        $timestamp = Carbon::create(2019, 9, 19, 10, 1, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->initializeMutualFundMerchants(Preferences::MID_KARVY, 10000000000000);

        $this->mockSettlementServiceRamp(false);

        $this->createPaymentAndAssert(10000000000000, 1568871000, 1568878200);

        $timestamp = Carbon::create(2019, 9, 19, 13, 0, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->createPaymentAndAssert(10000000000000, 1568878200, 1568878200);

        $timestamp = Carbon::create(2019, 9, 19, 14, 30, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->createPaymentAndAssert(10000000000000, 1568885400, 1568885400);

        $timestamp = Carbon::create(2019, 9, 19, 15, 1, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->createPaymentAndAssert(10000000000000, 1568889000, 1568964600);
    }

    public function testETMoneySettlementBucket()
    {
        $timestamp = Carbon::create(2019, 9, 19, 10, 1, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->initializeMutualFundMerchants(Preferences::MID_ET_MONEY, 10000000000000);

        $this->mockSettlementServiceRamp(false);

        $this->createPaymentAndAssert(10000000000000, 1568871000, 1568874600);

        $timestamp = Carbon::create(2019, 9, 19, 12, 0, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->createPaymentAndAssert(10000000000000, 1568874600, 1568874600);

        $timestamp = Carbon::create(2019, 9, 19, 13, 30, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->createPaymentAndAssert(10000000000000, 1568881800, 1568881800);

        $timestamp = Carbon::create(2019, 9, 19, 15, 1, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->createPaymentAndAssert(10000000000000, 1568889000, 1568961000);
    }

    public function testNoSettlementOnSaturday()
    {
        $timestamp = Carbon::create(2019, 9, 21, 10, 1, 0, Timezone::IST);

        $this->setTestTime($timestamp);

        $this->initializeMutualFundMerchants(Preferences::MID_WEALTHY, 10000000000000);

        $this->mockSettlementServiceRamp(false);

        $this->createPaymentAndAssert(10000000000000, 1569043800, 1569209400);
    }

    protected function createPaymentAndAssert($mid, $expectedSettledAt, $bucketTimestamp)
    {
        $this->createPayment($mid, true);

        $txn = $this->getLastEntity('transaction', true);

        $bucket = $this->getLastEntity('settlement_bucket', true);

        $this->assertEquals($expectedSettledAt, $txn['settled_at']);

        $this->assertEquals($bucketTimestamp, $bucket['bucket_timestamp']);
    }

    protected function initializeEarlySettlementMerchant($mid, $withThreePmSchedule = false)
    {
        $parentId = 'ZmReTNPu1KFKBn';

        $this->fixtures->merchant->createAccount($parentId);

        $this->fixtures->edit('merchant', $mid, ['parent_id' => $parentId]);

        $features = [
            Constants::ES_AUTOMATIC
        ];

        if ($withThreePmSchedule === true)
        {
            $features[] =  Constants::ES_AUTOMATIC_THREE_PM;
        }

        $this->fixtures->merchant->addFeatures($features, $mid);

        $input = [
            'name'        => 'Hourly Early Settlement',
            'period'      => 'hourly',
            'interval'    => 1,
            'hour'        => 0,
            'delay'       => 0,
        ];

        $this->createAndAssignSettlementSchedule($input, $mid);
    }

    protected function initializeMutualFundMerchants($parentId, $mid)
    {
        $this->fixtures->merchant->createAccount($parentId);

        $this->fixtures->edit('merchant', $mid, ['parent_id' => $parentId]);

        $input = [
            'name'        => 'Hourly Early Settlement',
            'period'      => 'hourly',
            'interval'    => 1,
            'hour'        => 0,
            'delay'       => 0,
        ];

        $this->createAndAssignSettlementSchedule($input, $mid);
    }

    protected function createTerminal()
    {
        try
        {
            $this->sharedTerminal = $this->fixtures->create('terminal:shared_first_data_terminal');

            $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        }
        catch (\Throwable $e)
        {
            // ignoring error
        }
    }

    protected function createPayment($mid = 10000000000000, bool $capture = true)
    {
        $this->createTerminal();

        $payment = $this->defaultAuthPayment();

        $this->gateway = 'hdfc';

        $this->ba->privateAuth();

        if ($capture === true)
        {
            $this->capturePayment($payment['id'], $payment['amount']);
        }

        $payment = $this->getLastEntity('payment', true);

        if ($capture === true)
        {
            $this->assertEquals('captured', $payment['status']);
        }
        else
        {
            $this->assertEquals('authorized', $payment['status']);
        }

        return $payment;
    }

    protected function createAndAssignSettlementSchedule($input, $merchantId)
    {
        $this->ba->adminAuth();

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

    protected function setTestTime(Carbon $timestamp = null)
    {
        if ($timestamp === null)
        {
            // friday and next working saturday
            $timestamp = Carbon::create(2019, 9, 19, 1, 0, 0, Timezone::IST);
        }

        Carbon::setTestNow($timestamp);
    }

    protected function mockSettlementServiceRamp(bool $status)
    {
        if ($status === true)
        {
            $this->fixtures->feature->create([
                'entity_type' => 'merchant',
                'entity_id' => '10000000000000',
                'name' => Constants::NEW_SETTLEMENT_SERVICE]);
        }
    }
}
