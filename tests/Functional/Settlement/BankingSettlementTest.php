<?php

namespace RZP\Tests\Functional\Settlement;

use Illuminate\Support\Facades\DB;
use Mail;
use Config;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use RZP\Models\Feature\Constants;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Traits\MocksSplitz;
use Illuminate\Database\Eloquent\Factory;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Services\Mock\UfhService as MockUfhService;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Schedule\ScheduleTrait;
use RZP\Models\Admin;

class BankingSettlementTest extends TestCase
{
    use PartnerTrait;
    use MocksSplitz;
    use SettlementTrait;
    use PaymentTrait;
    use HeimdallTrait;
    use ScheduleTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    const STANDARD_PRICING_PLAN_ID = '1A0Fkd38fGZPVC';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BankingSettlementTestData.php';

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

        // working wednesday 12 july 2023
        $todaydate = Carbon::createFromDate(2023, 7, 12,Timezone::IST);
        Carbon::setTestNow($todaydate->copy());

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

        Carbon::setTestNow($todaydate->copy()->addDay(1));

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

        // working wednesday 12 july 2023
        $todaydate = Carbon::createFromDate(2023, 7, 12,Timezone::IST);
        Carbon::setTestNow($todaydate->copy());

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

        Carbon::setTestNow($todaydate->copy()->addDay());

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testCustomGefuFileCreationAfterHoliday()
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

        // working saturday
        $todaydate = Carbon::createFromDate(2023, 7, 15,Timezone::IST);
        Carbon::setTestNow($todaydate->copy());

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

        Carbon::setTestNow($todaydate->copy()->addDay());

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow();

        // file generation on monday
        Carbon::setTestNow($todaydate->copy()->addDay(2));

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testCustomGefuFileWithDsUpiTransactionsCreation()
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

        // working wednesday 12 july 2023
        $todaydate = Carbon::createFromDate(2023, 7, 12,Timezone::IST);

        Carbon::setTestNow($todaydate->copy());

        $this->mockRazorxTreatment();

        $this->mockAllSplitzTreatment();

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
                $createdAt = Carbon::today(Timezone::IST)->setTime(23, 30, 0)->getTimestamp() + 5;
                $capturedAt = Carbon::today(Timezone::IST)->setTime(23, 35, 0)->getTimestamp() + 10;

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

        Carbon::setTestNow($todaydate->copy()->addDay());

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

        // working wednesday 12 july 2023
        $todaydate = Carbon::createFromDate(2023, 7, 12,Timezone::IST);
        Carbon::setTestNow($todaydate->copy());

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

        Carbon::setTestNow($todaydate->copy()->addDay());

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

        // working wednesday 12 july 2023
        $todaydate = Carbon::createFromDate(2023, 7, 12,Timezone::IST);
        Carbon::setTestNow($todaydate->copy());

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

        Carbon::setTestNow($todaydate->copy()->addDay());

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow($todaydate->copy());

        $capturedAt = Carbon::tomorrow(Timezone::IST)->setTime(16, 0, 0)->getTimestamp() + 10;

        $this->fixtures->edit('payment', $payId, [
            'status' => 'captured',
            'captured_at' => $capturedAt,
        ]);

        Carbon::setTestNow($todaydate->copy()->addDay(2));

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

        // working wednesday 12 july 2023
        $todaydate = Carbon::createFromDate(2023, 7, 12,Timezone::IST);
        Carbon::setTestNow($todaydate->copy());

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

        Carbon::setTestNow($todaydate->copy()->addDay());

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow($todaydate->copy());

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

        Carbon::setTestNow($todaydate->copy()->addDay(2));

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testCustomGefuFileWithNonDsAndDsAndExcludingPosTransactions()
    {

        $this->app['config']->set('applications.ufh.mock', true);

        $merchants = $this->fixtures->times(3)->create('merchant');

        $org = $this->fixtures->create('org', [
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

        // working wednesday 12 july 2023
        $todaydate = Carbon::createFromDate(2023, 7, 12, Timezone::IST);
        Carbon::setTestNow($todaydate->copy());

        $this->mockRazorxTreatment();

        $lastCutoffTime = Carbon::yesterday(Timezone::IST)->setTime(20, 0, 0)->getTimestamp();

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
                    'emi' => 1,
                    'mode' => 2,
                    'type' => [
                        'direct_settlement_with_refund' => '1'
                    ],
                ]);


            $this->fixtures->edit('merchant', $merchant->getId(), [
                'org_id' => $org['id'],
                'channel' => $channel,
                'activated' => true,
                'suspended_at' => null
            ]);

            $this->fixtures->create('balance', ['id' => $merchantId, 'merchant_id' => $merchantId, 'balance' => 5000]);

            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'account_number' => $poolAcc, // constant id for all merchant's pool account
                    'beneficiary_name' => random_string_special_chars(10),
                    'merchant_id' => $merchantId,
                    'type' => 'merchant'
                ]);

            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'beneficiary_name' => random_string_special_chars(10),
                    'merchant_id' => $merchantId,
                    'type' => 'org_settlement'
                ]);

            $createdAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 5;
            $capturedAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 10;

            $this->fixtures->create(
                'payment:captured',
                [
                    'captured_at' => $capturedAt,
                    'method' => 'card',
                    'merchant_id' => $merchantId,
                    'gateway' => 'upi_mindgate',
                    'amount' => 1000,
                    'fee' => 0,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt + 10,
                    'settled_by' => 'bank',
                    'receiver_type' => 'bank_account'
                ]
            );

            $this->fixtures->create(
                'payment:captured',
                [
                    'captured_at' => $capturedAt,
                    'method' => 'card',
                    'merchant_id' => $merchantId,
                    'amount' => 10000,
                    'fee' => 0,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt + 10,
                    'settled_by' => 'bank',
                    'receiver_type' => 'bank_account'
                ]
            );

            $this->fixtures->create(
                'payment:captured',
                [
                    'captured_at' => $capturedAt,
                    'method' => 'card',
                    'gateway' => 'upi_mindgate',
                    'merchant_id' => $merchantId,
                    'amount' => 100000,
                    'fee' => 0,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt + 10,
                    'settled_by' => 'bank',
                ]
            );

            $createdAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 5;
            $capturedAt = Carbon::today(Timezone::IST)->setTime(13, 0, 0)->getTimestamp() + 10;

            $this->fixtures->create(
                'payment',
                [
                    'captured_at' => $capturedAt,
                    'method' => 'upi',
                    'merchant_id' => $merchantId,
                    'gateway' => 'hdfc_ezetap',
                    'amount' => 1000000,
                    'mdr' => 0,
                    'fee' => 0,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt + 10,
                    'settled_by' => 'bank',
                    'receiver_type' => 'pos'
                ]

            );
            $this->fixtures->create(
                'payment',
                [
                    'captured_at' => $capturedAt,
                    'method' => 'upi',
                    'merchant_id' => $merchantId,
                    'gateway' => 'hdfc_ezetap',
                    'amount' => 10000000,
                    'mdr' => 0,
                    'fee' => 0,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt + 10,
                    'settled_by' => 'bank',
                ]

            );
            $this->fixtures->create(
                'payment',
                [
                    'captured_at' => $capturedAt,
                    'method' => 'upi',
                    'merchant_id' => $merchantId,
                    'amount' => 100000000,
                    'mdr' => 0,
                    'fee' => 0,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt + 10,
                    'settled_by' => 'bank',
                    'receiver_type' => 'pos'
                ]
            );

            $this->fixtures->create(
                'payment',
                [
                    'captured_at' => $capturedAt,
                    'method' => 'upi',
                    'merchant_id' => $merchantId,
                    'amount' => 1000000000,
                    'mdr' => 0,
                    'fee' => 0,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt + 10,
                    'settled_by' => 'bank',
                ]
            );
        }


        $this->initiateSettlements(Channel::AXIS);

        Carbon::setTestNow($todaydate->copy()->addDay());

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

        // working wednesday 12 july 2023
        $todaydate = Carbon::createFromDate(2023, 7, 12,Timezone::IST);
        Carbon::setTestNow($todaydate->copy());

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

        Carbon::setTestNow($todaydate->copy()->addDay());

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow();
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

    public function testGefuFileCreationWithGatewayTerminalIdPrefix190()
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

        // working wednesday 12 july 2023
        $todaydate = Carbon::createFromDate(2023, 7, 12,Timezone::IST);

        Carbon::setTestNow($todaydate->copy());

        $this->mockRazorxTreatment();

        $this->mockAllSplitzTreatment();

        $lastCutoffTime = Carbon::yesterday(Timezone::IST)->setTime(20, 0, 0)->getTimestamp() ;

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CARD_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $lastCutoffTime]);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::UPI_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $lastCutoffTime]);

        $cont = 0;

        foreach ($merchants as $merchant) {

            $merchantId = $merchant->getId();

            if($cont%2 == 0)
            {
                $terminal = $this->fixtures->create(
                    'terminal',
                    [
                        'id' => random_alphanum_string(14),
                        'merchant_id' => $merchantId,
                        'gateway' => 'hdfc',
                        'gateway_merchant_id' => '250000002',
                        'gateway_secure_secret' => "1231424",
                        'gateway_terminal_id' => '190000004',
                        'card' => 1,
                        'emi'  => 1,
                        'mode' => 2,
                        'type'    => [
                            'direct_settlement_with_refund' => '1'
                        ],
                    ]);
            }
            else
            {
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
            }

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

            $createdAt = Carbon::today(Timezone::IST)->setTime(23, 30, 0)->getTimestamp() + 5;
            $capturedAt = Carbon::today(Timezone::IST)->setTime(23, 35, 0)->getTimestamp() + 10;

            $amount = ($cont % 2 == 1) ? 10000 : 15000;

            $this->fixtures->times(2)->create(
                'payment',
                [
                    'captured_at' => $capturedAt,
                    'method'      => 'card',
                    'merchant_id' => $merchantId,
                    'amount'      => $amount,
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
                    'amount'      => $amount,
                    'mdr'         => 0,
                    'fee'         => 100,
                    'created_at'  => $createdAt,
                    'updated_at'  => $createdAt + 10,
                    'settled_by' => 'bank'
                ]

            );

            $cont++;
        }

        $this->initiateSettlements(Channel::AXIS);

        Carbon::setTestNow($todaydate->copy()->addDay());

        $this->ba->cronAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testTerminalValidationWithUPIAndCardChecks()
    {
        $this->app['config']->set('applications.ufh.mock', true);
        $merchants = $this->fixtures->times(6)->create('merchant');
        $org = $this->fixtures->create('org', [
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
        // working wednesday 12 july 2023
        $todaydate = Carbon::createFromDate(2023, 7, 12, Timezone::IST);
        Carbon::setTestNow($todaydate->copy());
        $this->mockRazorxTreatment();
        $this->mockAllSplitzTreatment();
        $lastCutoffTime = Carbon::yesterday(Timezone::IST)->setTime(20, 0, 0)->getTimestamp();
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CARD_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $lastCutoffTime]);
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::UPI_DS_PAYMENTS_LAST_BATCH_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $lastCutoffTime]);
        $cont = 0;
        foreach ($merchants as $merchant) {
            $merchantId = $merchant->getId();
            if ($cont % 2 == 0) {
                $terminal = $this->fixtures->create(
                    'terminal',
                    [
                        'id' => random_alphanum_string(14),
                        'merchant_id' => $merchantId,
                        'gateway' => 'hdfc',
                        'gateway_merchant_id' => '250000002',
                        'gateway_secure_secret' => "1231424",
                        'gateway_terminal_id' => '190000004',
                        'card' => 1,
                        'emi' => 0,
                        'mode' => 2,
                        'type' => [
                            'direct_settlement_with_refund' => '1'
                        ],
                    ]);
            } else {
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
                        'emi' => 1,
                        'mode' => 2,
                        'type' => [
                            'direct_settlement_with_refund' => '1'
                        ],
                    ]);
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
                        'emi' => 0,
                        'mode' => 2,
                        'type' => [
                            'direct_settlement_with_refund' => '1'
                        ],
                    ]);
            }
            $this->fixtures->edit('merchant', $merchant->getId(), [
                'org_id' => $org['id'],
                'channel' => $channel,
                'activated' => true,
                'suspended_at' => null
            ]);
            $this->fixtures->create('balance', ['id' => $merchantId, 'merchant_id' => $merchantId, 'balance' => 5000]);
            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'account_number' => $poolAcc, // constant id for all merchant's pool account
                    'beneficiary_name' => random_string_special_chars(10),
                    'merchant_id' => $merchantId,
                    'type' => 'merchant'
                ]);
            $this->fixtures->create(
                'bank_account',
                [
                    'entity_id' => $merchantId,
                    'beneficiary_name' => random_string_special_chars(10),
                    'merchant_id' => $merchantId,
                    'type' => 'org_settlement'
                ]);
            $createdAt = Carbon::today(Timezone::IST)->setTime(23, 30, 0)->getTimestamp() + 5;
            $capturedAt = Carbon::today(Timezone::IST)->setTime(23, 35, 0)->getTimestamp() + 10;
            $amount = ($cont % 2 == 1) ? 10000 : 15000;
            $this->fixtures->times(2)->create(
                'payment',
                [
                    'captured_at' => $capturedAt,
                    'method' => 'card',
                    'merchant_id' => $merchantId,
                    'amount' => $amount,
                    'mdr' => 400,
                    'fee' => 100,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt + 10,
                    'settled_by' => 'bank',
                    'terminal_id' => $terminal->getId()
                ]
            );
            $this->fixtures->times(2)->create(
                'payment',
                [
                    'captured_at' => $capturedAt,
                    'method' => 'emi',
                    'merchant_id' => $merchantId,
                    'amount' => $amount,
                    'mdr' => 0,
                    'fee' => 100,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt + 10,
                    'settled_by' => 'bank',
                    'terminal_id' => $terminal->getId()
                ]
            );
            // Assert that card is 1 and emi is 0
            $this->assertEquals(1, $terminal->card, "Card should be 1");
            $this->assertEquals(0, $terminal->emi, "EMI should be 0");
            $cont++;
        }
        $this->initiateSettlements(Channel::AXIS);
        Carbon::setTestNow($todaydate->copy()->addDay());
        $this->ba->cronAuth();
        $this->startTest();
        Carbon::setTestNow();
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
}
