<?php

namespace Functional\Payout;

use Mail;
use Queue;
use Mockery;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Models\Payout;
use RZP\Models\Schedule;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Models\FeeRecovery;
use Rzp\Models\FundTransfer;
use RZP\Services\Mock\Mozart;
use RZP\Models\Payout\Status;
use RZP\Models\Merchant\Balance;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Mode as EnvMode;
use RZP\Models\Settlement\Channel;
Use RZP\Models\FundTransfer\Attempt;
use RZP\Mail\Payout\AutoRejectedPayout;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\BankingAccount\Gateway\Axis;
use RZP\Models\Merchant\Balance\FreePayout;
use RZP\Services\Mock\BankingAccountService;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\BankingAccount\Gateway\Fields;
use RZP\Models\BankingAccount\Core as BACore;
use RZP\Models\BankingAccountStatement as BAS;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Jobs\BankingAccountStatementSourceLinking;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Jobs\ConnectedBankingAccountGatewayBalanceUpdate;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;

class AxisCaPayoutTest extends TestCase
{
    use PayoutTrait;
    use TestsMetrics;
    use AttemptTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    private $ownerRoleUser;

    protected $merchant;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/AxisCaPayoutTestData.php';

        parent::setUp();

        $this->fixtures->on('test')->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->on('test')->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 0, 'direct', 'axis');

        $this->fixtures->create('banking_account_statement_details',[
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $this->bankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626905',
            Details\Entity::CHANNEL        => Details\Channel::AXIS,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $this->merchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    protected function setupScheduleAndScheduleTaskForMerchant()
    {
        $createScheduleRequest = [
            'method'  => 'POST',
            'url'     => '/schedules',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content'   => [
                'type'      => 'fee_recovery',
                'name'      => 'Basic T+7',
                'period'    => 'daily',
                'interval'  => 7,
            ],
        ];

        $this->ba->adminAuth();

        $schedule = $this->makeRequestAndGetContent($createScheduleRequest);

        $scheduleTaskInput = [
            'type'          => 'fee_recovery',
            'schedule_id'   => $schedule['id'],
        ];

        $scheduleTask = (new Schedule\Task\Core)->create($this->merchant, $this->bankingBalance , $scheduleTaskInput);

        $scheduleTask->saveOrFail();

        $scheduleTask = $this->getDbLastEntity('schedule_task')->toArray();

        $pastTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $this->fixtures->edit('schedule_task', $scheduleTask['id'], [
            'next_run_at'  => $pastTimeStamp
        ]);

        $this->fixtures->edit('balance', $this->bankingBalance->getId(), [
            'created_at'   => $pastTimeStamp
        ]);
    }

    protected function setUpMerchantForBusinessBankingLive(
        bool $skipFeatureAddition = false,
        int $balance = 0,
        string $balanceType = AccountType::SHARED,
        $channel = Channel::YESBANK)
    {
        // Activate merchant with business_banking flag set to true.
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['business_banking' => 1]);
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);
        $this->fixtures->on('live')->merchant->activate();

        // Creates banking balance
        $bankingBalance = $this->fixtures->on('live')->merchant->createBalanceOfBankingType(
            $balance, '10000000000000',$balanceType, $channel);

        // Creates virtual account, its bank account receiver on new banking balance.
        $virtualAccount = $this->fixtures->on('live')->create('virtual_account');
        $bankAccount    = $this->fixtures->on('live')->create(
            'bank_account',
            [
                'id'             => '1000000lcustba',
                'type'           => 'virtual_account',
                'entity_id'      => $virtualAccount->getId(),
                'account_number' => '2224440041626905',
                'ifsc_code'      => 'RAZRB000000',
            ]);

        $virtualAccount->bankAccount()->associate($bankAccount);
        $virtualAccount->balance()->associate($bankingBalance);
        $virtualAccount->save();

        $defaultFreePayoutsCount = $this->getDefaultFreePayoutsCount($bankingBalance);

        $this->fixtures->on('live')->create('counter', [
            'account_type'          => $balanceType,
            'balance_id'            => $bankingBalance->getId(),
            'free_payouts_consumed' => $defaultFreePayoutsCount,
        ]);

        // Updates banking balance's account number after bank account creation.
        $bankingBalance->setAccountNumber($virtualAccount->bankAccount->getAccountNumber());
        $bankingBalance->save();

        // Enables required features on merchant
        if ($skipFeatureAddition === false)
        {
            $this->fixtures->on('live')->merchant->addFeatures(['virtual_accounts', 'payout']);
        }

        $this->setupRedisConfigKeysForTerminalSelection();

        // Sets instance member variable to be re-usable in other test methods for assertions.
        $this->bankingBalance = $bankingBalance;
        $this->virtualAccount = $virtualAccount;
        $this->bankAccount    = $bankAccount;
    }

    protected function liveSetUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/AxisCaPayoutTestData.php';

        $this->fixtures->on('live')->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->on('live')->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBankingLive(true, 10000000, 'direct', 'axis');

        $pricings = $this->getDbEntities('pricing');

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_BANKING_PLAN_ID]);

        // Merchant needs to be activated to make live requests
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        // Create merchant user mapping
        $this->fixtures->on('live')->create('banking_account_statement_details',[
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $this->bankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626905',
            Details\Entity::CHANNEL        => Details\Channel::AXIS,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    protected function mockMozartResponseForFetchingBalanceFromAxisGateway($amount, $exception = null): void
    {
        $mozartServiceMock = $this->getMockBuilder(Mozart::class)
                                  ->setConstructorArgs([$this->app])
                                  ->setMethods(['sendMozartRequest'])
                                  ->getMock();

        $mozartServiceMock->method('sendMozartRequest')
                          ->willReturn([
                                           Axis\Fields::DATA => [

                                               Axis\Fields::ACCOUNT_BALANCE_AMOUNT => $amount
                                           ]
                                       ]);

        if ($exception !== null)
        {
            $mozartServiceMock->method('sendMozartRequest')
                              ->willThrowException($exception);
        }

        $this->app->instance('mozart', $mozartServiceMock);
    }

    protected function setupAxisDispatchGatewayBalanceUpdateForMerchants()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT => 1]);

        $request = [
            'method'  => 'put',
            'url'     => '/banking_accounts/gateway/axis/balance',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ];

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function testDispatchGatewayBalanceUpdateJob()
    {
        Queue::fake();

        $this->setupAxisDispatchGatewayBalanceUpdateForMerchants();

        Queue::assertPushed(ConnectedBankingAccountGatewayBalanceUpdate::class, 1);
    }

    public function testProcessGatewayBalanceUpdate()
    {
        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                       ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $this->mockMozartResponseForFetchingBalanceFromAxisGateway(500);

        $response = $this->setupAxisDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(50000, $basDetailsAfterCronRuns->getGatewayBalance());

        $this->assertNotNull($basDetailsAfterCronRuns->getBalanceLastFetchedAt());

        $this->assertNotNull($basDetailsAfterCronRuns->getGatewayBalanceChangeAt());
    }

    public function testProcessGatewayBalanceUpdateInDeleteMode()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE => True]);

        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                       ['account_number' => 2224440041626905]);

        $this->assertNull($basDetailsBeforeCronRuns->getGatewayBalance());
        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());
        $this->assertNull($basDetailsBeforeCronRuns->getGatewayBalanceChangeAt());

        $this->mockMozartResponseForFetchingBalanceFromAxisGateway(500);

        $response = $this->setupAxisDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals($basDetailsBeforeCronRuns->toArray(), $basDetailsAfterCronRuns->toArray());
    }

    public function testGatewayBalanceFetchWithGatewayFailure()
    {
        $exception = new GatewayErrorException("GATEWAY_ERROR_UNKNOWN_ERROR",
                                               "Failure",
                                               "(No error description was mapped for this error code)");
        $mozartError = [
            "description"               => "",
            "gateway_error_code"        => "Failure",
            "gateway_error_description" => "(No error description was mapped for this error code)",
            "gateway_status_code"       => 200,
            "internal_error_code"       => "GATEWAY_ERROR_UNKNOWN_ERROR",
        ];

        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                       ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $this->mockMozartResponseForFetchingBalanceFromAxisGateway(null, $exception);

        $response = $this->setupAxisDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsAfterCronRuns->getBalanceLastFetchedAt());
    }

    public function testBalanceFetch()
    {
        $this->setMockRazorxTreatment(['gateway_balance_fetch_v2' => 'on']);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        $response = $this->setupAxisDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetails */
        $basDetails = $this->getDbEntity('banking_account_statement_details',
                                         ['account_number' => 2224440041626905]);

        $this->assertArrayHasKey(BACore::MADE_PAYOUT_RULE, $response);
        $this->assertArrayHasKey(BACore::BALANCE_CHANGE_RULE, $response);
        $this->assertArrayHasKey(BACore::MANDATORY_UPDATE_RULE, $response);

        $this->assertEmpty($response[BACore::MADE_PAYOUT_RULE]);
        $this->assertEmpty($response[BACore::BALANCE_CHANGE_RULE]);
        $this->assertEquals([$basDetails->getMerchantId()], $response[BACore::MANDATORY_UPDATE_RULE]);
    }

    public function testBalanceFetchWhenMerchantDoesPayout()
    {
        $this->testCreatePayout();

        $this->setMockRazorxTreatment(['gateway_balance_fetch_v2' => 'on']);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        $response = $this->setupAxisDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetails */
        $basDetails = $this->getDbEntity('banking_account_statement_details',
                                         ['account_number' => 2224440041626905]);

        $this->assertArrayHasKey(BACore::MADE_PAYOUT_RULE, $response);
        $this->assertArrayHasKey(BACore::BALANCE_CHANGE_RULE, $response);
        $this->assertArrayHasKey(BACore::MANDATORY_UPDATE_RULE, $response);

        $this->assertEquals([$basDetails->getMerchantId()], $response[BACore::MADE_PAYOUT_RULE]);
        $this->assertEmpty($response[BACore::BALANCE_CHANGE_RULE]);
        $this->assertEmpty($response[BACore::MANDATORY_UPDATE_RULE]);
    }

    public function testBalanceFetchWhenMerchantGatewayBalanceChanges()
    {
        $this->setMockRazorxTreatment(['gateway_balance_fetch_v2' => 'on']);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        /** @var Details\Entity $basDetails */
        $basDetails = $this->getDbEntity('banking_account_statement_details',
                                         ['account_number' => 2224440041626905]);

        $this->fixtures->edit('banking_account_statement_details', $basDetails->getId(),
                              [Details\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::now()->subMinute()->getTimestamp()]);

        $response = $this->setupAxisDispatchGatewayBalanceUpdateForMerchants();

        $this->assertArrayHasKey(BACore::MADE_PAYOUT_RULE, $response);
        $this->assertArrayHasKey(BACore::BALANCE_CHANGE_RULE, $response);
        $this->assertArrayHasKey(BACore::MANDATORY_UPDATE_RULE, $response);

        $this->assertEmpty($response[BACore::MADE_PAYOUT_RULE]);
        $this->assertEquals([$basDetails->getMerchantId()], $response[BACore::BALANCE_CHANGE_RULE]);
        $this->assertEmpty($response[BACore::MANDATORY_UPDATE_RULE]);
    }

    public function testCreatePayoutWithFetchAndUpdateBalanceFromGatewayAndBalanceLessThanPayoutAmount()
    {
        $oldDateTime = Carbon::create(2020, 01, 21, 12, 23, null, Timezone::IST);

        $this->fixtures->edit('balance', $this->bankingBalance->getId(), [
            'updated_at' => $oldDateTime->getTimestamp(),
        ] );

        $bankingBalance = $this->getDbLastEntity('balance');

        $this->fixtures->balance->edit($bankingBalance['id'], ['balance' => 100]);

        $this->fixtures->edit('banking_account_statement_details', 'xbas0000000002', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ] );

        $this->mockMozartResponseForFetchingBalanceFromAxisGateway(500);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutWithFetchAndUpdateBalanceFromGatewayAndBalanceMoreThanPayoutAmount()
    {
        $oldDateTime = Carbon::create(2020, 01, 21, 12, 23, null, Timezone::IST);

        $this->fixtures->edit('banking_account_statement_details', 'xbas0000000002', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ] );

        $this->mockMozartResponseForFetchingBalanceFromAxisGateway(50000);

        sleep(1);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayout()
    {
        $this->ba->privateAuth();

        $attributes = [
            'bas_business_id'   => '10000000000000',
            'merchant_id'       => '10000000000000',
        ];

        $this->fixtures->create('merchant_detail', $attributes);

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals('axis', $payout['channel']);
        $this->assertEquals('processing', $payout['status']);

        $transaction = $this->getLastEntity('transaction', true);
        $this->assertNull($transaction);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $mock = Mockery::mock(\RZP\Services\FTS\FundTransfer::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive([
                                 'shouldAllowTransfersViaFts' => [true, 'Dummy'],
                             ]);

        $this->app->instance('fts_fund_transfer', $mock);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::INITIATED);

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);
    }

    public function testCreatePayoutWithZeroPricing()
    {
        $this->fixtures->edit('pricing', 'Bbg7e4oKCgaxxx', ['fixed_rate' => 0]);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(0, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);
    }

    public function testCreatePayoutWithMissingPricingRule()
    {
        $this->ba->privateAuth();

        $metricsMock = $this->createMetricsMock();

        $boolMetricCaptured = false;

        $this->mockAndCaptureCountMetric(
            Payout\Metric::SERVER_ERROR_PRICING_RULE_ABSENT_TOTAL,
            $metricsMock,
            $boolMetricCaptured,
            [
                'route_name' => 'payout_create'
            ]
        );

        $attributes = [
            'bas_business_id'   => '10000000000000',
            'merchant_id'       => '10000000000000',
        ];

        $this->fixtures->create('merchant_detail', $attributes);

        $this->fixtures->edit('pricing', 'Bbg7e4oKCgaxxx', ['plan_id' => 'plan1234567890']);

        $this->startTest();

        $this->assertTrue($boolMetricCaptured);

        $payout = $this->getDbLastEntity('payout');

        $this->assertNull($payout);
    }

    public function testCreatePayoutImps()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals('axis', $payout['channel']);
        $this->assertEquals('processing', $payout['status']);
        $this->assertEquals('IMPS', $payout['mode']);

        $transaction = $this->getLastEntity('transaction', true);
        $this->assertNull($transaction);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $mock = Mockery::mock(\RZP\Services\FTS\FundTransfer::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive([
                                 'shouldAllowTransfersViaFts' => [true, 'Dummy'],
                             ]);

        $this->app->instance('fts_fund_transfer', $mock);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::INITIATED);

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);
    }

    public function testCreatePayoutRtgs()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals('axis', $payout['channel']);
        $this->assertEquals('processing', $payout['status']);
        $this->assertEquals('RTGS', $payout['mode']);

        $transaction = $this->getLastEntity('transaction', true);
        $this->assertNull($transaction);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $mock = Mockery::mock(\RZP\Services\FTS\FundTransfer::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive([
                                 'shouldAllowTransfersViaFts' => [true, 'Dummy'],
                             ]);

        $this->app->instance('fts_fund_transfer', $mock);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::INITIATED);

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);
    }

    /* Asserts Error that UPI is not supported for AXIS */
    public function testCreatePayoutUpi()
    {
        $contact = $this->getDbLastEntity('contact');

        $this->fixtures->create('fund_account:vpa', [
            'id'          => '100000000003fa',
            'source_type' => 'contact',
            'source_id'   => $contact->getId(),
        ]);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutProcessed()
    {
        $this->testCreatePayout();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::PROCESSED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::PROCESSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);
        $this->assertEquals(1062, $payout['fees']);
        $this->assertEquals(162, $payout['tax']);
    }

    public function testPayoutFailed()
    {
        $this->testCreatePayout();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::FAILED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals('axis', $payout['channel']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);
    }

    public function testPayoutReversed()
    {
        $this->testCreatePayout();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::REVERSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::REVERSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);

        $reversal = $this->getDbLastEntity('reversal');
        $this->assertEquals($payout['id'], $reversal['entity_id']);
        $this->assertNull($payout['transaction_id']);
        $this->assertNull($reversal['transaction_id']);
    }

    public function testAxisPayoutUsingCredits()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 500 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 500 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(900, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(500, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(500, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::PROCESSED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::PROCESSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);
        $this->assertNull($payout['transaction_id']);
    }

    public function testAxisPayoutUsingCreditsFailed()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 500 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(900, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(500, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(500, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated', 'amount' => '104']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S55959']);

        $this->fixtures->edit('balance', $payout['balance_id'], ['balance' => 30019995]);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::FAILED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(500, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[2]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[2]['entity_id']);
        $this->assertEquals(-500, $creditTxnEntities[2]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[3]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[3]['entity_id']);
        $this->assertEquals(-400, $creditTxnEntities[3]['credits_used']);
    }

    public function testAxisPayoutUsingCreditsReversed()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 500 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(900, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(500, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(500, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated', 'amount' => '104']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S55959']);

        $this->fixtures->edit('balance', $payout['balance_id'], ['balance' => 0]);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $payout = $this->getDbLastEntity('payout');

        $reversal = $this->getDbLastEntity('reversal');
        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::REVERSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::REVERSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(500, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $this->assertEquals('reversal', $creditTxnEntities[2]['entity_type']);
        $this->assertEquals($reversal['id'],  $creditTxnEntities[2]['entity_id']);
        $this->assertEquals(-500, $creditTxnEntities[2]['credits_used']);

        $this->assertEquals('reversal', $creditTxnEntities[3]['entity_type']);
        $this->assertEquals($reversal['id'],  $creditTxnEntities[3]['entity_id']);
        $this->assertEquals(-400, $creditTxnEntities[3]['credits_used']);
    }

    protected function createPendingPayoutAndApprovePayoutUptoSecondLevel(int $gatewayBalance, $queueFlag = 1)
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/' . $payout['id'] . '/approve',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'token'                => 'BUIj3m2Nx2VvVj',
                'otp'                  => '0007',
                'queue_if_low_balance' => $queueFlag,
            ],
        ];

        $this->makeRequestAndGetContent($request);

        $this->app['config']->set('database.default', 'live');

        // Make Request to Approve pending payout for second level from Finance L3 role
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL3RoleUser->getId());

        $this->mockMozartResponseForFetchingBalanceFromAxisGateway($gatewayBalance);

        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        $this->fixtures->edit('balance', $this->bankingBalance->getId(), [
            'updated_at' => $oldDateTime->getTimestamp(),
        ] );

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    // Case when payout amount is greater than balance in CA and queue_if_low_balance = true
    public function testApprovePendingPayoutWithQueueFlagBalanceLess()
    {
        $response = $this->createPendingPayoutAndApprovePayoutUptoSecondLevel(50);

        $this->assertEquals('queued', $response['status']);
    }

    public function testQueuedPayout()
    {
        $this->mockMozartResponseForFetchingBalanceFromAxisGateway(500);

        $firstQueuedPayoutAttributes = [
            'account_number'       => '2224440041626905',
            'amount'               => 20000099,
            'queue_if_low_balance' => 1,
        ];

        $this->createQueuedOrPendingPayout($firstQueuedPayoutAttributes);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('queued', $payout['status']);
        $this->assertEquals('axis', $payout['channel']);

        $bankingBalance = $this->getDbLastEntity('balance');

        $this->fixtures->balance->edit($bankingBalance['id'], ['balance' => 21000000]);

        $this->dispatchQueuedPayouts();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('created', $payout['status']);
        $this->assertEquals('axis', $payout['channel']);
    }

    public function testQueuedPayoutByFetchingBalanceFromGatewayBalance()
    {
        $this->mockMozartResponseForFetchingBalanceFromAxisGateway(500);

        $this->setMockRazorxTreatment([RazorxTreatment::USE_GATEWAY_BALANCE    => 'on']);

        $firstQueuedPayoutAttributes = [
            'account_number'       => '2224440041626905',
            'amount'               => 20000099,
            'queue_if_low_balance' => 1,
        ];

        $this->fixtures->edit('banking_account_statement_details','xbas0000000002',[
            'gateway_balance'         => 2500,
            'balance_last_fetched_at' => 1565944927,
            'account_type'            => 'direct'
        ]);

        $this->createQueuedOrPendingPayout($firstQueuedPayoutAttributes);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('queued', $payout['status']);
        $this->assertEquals('axis', $payout['channel']);

        $this->fixtures->edit('banking_account_statement_details','xbas0000000002',[
            'gateway_balance'         => 500000000
        ]);

        $this->dispatchQueuedPayouts();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('created', $payout['status']);
        $this->assertEquals('axis', $payout['channel']);
    }

    public function testCreateFreePayoutForNEFTModeDirectAccountProxyAuth()
    {
        $testData = $this->testData['testCreateFreePayoutForNEFTModeDirectAccountProxyAuth'];
        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['server'] = [
            'HTTP_X-Request-Origin' => config('applications.banking_service_url')
        ];
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $balance = $this->getDbEntities('balance',
                                        [
                                            'merchant_id'  => "10000000000000",
                                            'account_type' => 'direct',
                                            'channel'      => 'axis'
                                        ])->first();

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('direct', $balanceId, 'axis');

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->proxyAuth('rzp_test_10000000000000', 'MerchantUser01');
        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals("MerchantUser01", $payout->getUserId());

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $payout->getFees());
        $this->assertEquals(0, $payout->getTax());

        // Assert that free_payout is assigned as fee_type for such payouts.
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'direct',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        // Assert that pricing rule id in payouts is correct
        $this->assertEquals('Bbg7cl6t6I3XB2', $payout['pricing_rule_id']);
    }

    protected function createPayout($balance)
    {
        $this->ba->privateAuth();

        $contact = $this->createContact();

        $fundAccount = $this->createFundAccountForContact($contact);

        $this->createPayoutForFundAccount($fundAccount, $balance);
    }

    public function createPayoutForFundAccount($fundAccount, $balance)
    {
        $content = [
            'account_number'        => $balance->getAccountNumber(),
            'amount'                => 1000,
            'currency'              => 'INR',
            'purpose'               => 'payout',
            'narration'             => 'Payout',
            'fund_account_id'       => 'fa_' . $fundAccount->getId(),
            'mode'                  => 'IMPS',
            'queue_if_low_balance'  => true,
            'notes'                 => [
                'abc' => 'xyz',
            ],
        ];

        $request = [
            'url'       => '/payouts',
            'method'    => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content'   => $content
        ];

        $this->ba->privateAuth();

        $this->makeRequestAndGetContent($request);

        // Adding this here so that all payouts that get created go to initiated state automatically
        $this->initiatePayoutFromCreated();
    }

    protected function createFundAccountForContact($contact, $ifsc = 'AXIS0000047', $accountNumber = '111000111000')
    {
        $fundAccount = $this->fixtures->fund_account->createBankAccount(
            [
                'source_type' => 'contact',
                'source_id'   => $contact->getId(),
            ],
            [
                'name'           => "test",
                'ifsc'           => $ifsc,
                'account_number' => $accountNumber,
            ]);

        return $fundAccount;
    }

    protected function createContact()
    {
        $contact = $this->fixtures->create(
            'contact',
            [
                'id'      => '1010101contact',
                'email'   => 'rzp@rzp.com',
                'contact' => '8989898989',
                'name'    => 'desiboi',
            ]
        );

        return $contact;
    }

    protected function createRzpFeesContactAndFundAccountForAxis()
    {
        $this->rzpFeesContact = $this->fixtures->create(
            'contact',
            [
                'id'      => '1010101hokage2',
                'email'   => 'rzp@rzp.com',
                'contact' => '9989898989',
                'name'    => 'naruto',
                'type'    => 'rzp_fees'
            ]
        );

        $this->rzpFeesFundAccount = $this->createFundAccountForContact($this->rzpFeesContact,
                                                                       'HDFC0000011',
                                                                       '101010101010');
    }

    protected function initiatePayoutFromCreated()
    {
        $payout = $this->getDbLastEntity('payout');

        if ($payout->getStatus() === Payout\Status::CREATED)
        {
            $payout->setStatus(Payout\Status::INITIATED);

            $payout->saveOrFail();
        }
    }

    public function testCreateFeeRecoveryAtPayoutCreationForAxisPayouts()
    {
        $this->mockMozartResponseForFetchingBalanceFromAxisGateway(500);

        $this->ba->privateAuth();

        sleep(1);

        $this->createPayout($this->bankingBalance);

        $payout = $this->getDbLastEntity('payout')->toArray();

        $feeRecovery = $this->getDbLastEntity('fee_recovery')->toArray();

        $this->assertEquals($payout['id'], $feeRecovery['entity_id']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecovery['status']);
        $this->assertEquals(0, $feeRecovery['attempt_number']);
        $this->assertNull($feeRecovery['recovery_payout_id']);
    }

    public function testFeeRecoveryPayoutCronForAxis()
    {
        $oldTime = Carbon::create(2020, 1, 3, null, null, null);

        Carbon::setTestNow($oldTime);

        $this->setMockRazorxTreatment([RazorxTreatment::USE_GATEWAY_BALANCE    => 'on']);

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->bankingBalance);

        $this->setupScheduleAndScheduleTaskForMerchant();

        $oldTimeStamp = $oldTime->getTimestamp();

        // Create first payout
        $this->testCreateFeeRecoveryAtPayoutCreationForAxisPayouts();

        $payout = $this->getDbLastEntity('payout');

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->fixtures->edit('payout', $payout['id'], ['initiated_at' => $oldTimeStamp]);

        // Create second payout
        $this->createPayoutForFundAccount($fundAccount, $this->bankingBalance);

        $payout2 = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout2['id'], ['initiated_at' => $oldTimeStamp]);

        // Fail the second payout
        $this->updateFtaAndSource($payout2['id'], Payout\Status::FAILED);

        // Create a third payout
        $this->createPayoutForFundAccount($fundAccount, $this->bankingBalance);

        $payout3 = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout3['id'], ['initiated_at' => $oldTimeStamp]);

        // Updating FTA and Payout status to initiated to allow transition to reversed
        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $payout3->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::INITIATED]);

        $this->fixtures->edit('payout', $payout3->getId(), ['status' => Payout\Status::INITIATED]);

        // Reverse the third payout
        $this->updateFtaAndSource($payout3->getId(), Payout\Status::REVERSED,'944926344925');

        $this->createRzpFeesContactAndFundAccountForAxis();

        $newTime = Carbon::create(2020, 1, 10, null, null, null);

        Carbon::setTestNow($newTime);

        $this->ba->cronAuth();

        $this->processFeeRecoveryCron();

        $feeRecoveryPayout = $this->getDbLastEntity('payout');

        // Moving this payout to initiated
        $feeRecoveryPayout->setStatus(Payout\Status::INITIATED);
        $feeRecoveryPayout->saveOrFail();

        $this->assertEquals('NEFT', $feeRecoveryPayout->getMode());

        // Fee Recovery entity for initial payout
        $feeRecovery1 = $this->getDbEntity('fee_recovery', ['entity_id' => $payout['id']])->toArray();

        $this->assertEquals($payout['id'], $feeRecovery1['entity_id']);
        $this->assertEquals(FeeRecovery\Status::PROCESSING, $feeRecovery1['status']);
        $this->assertEquals($feeRecovery1['type'], FeeRecovery\Type::DEBIT);
        $this->assertEquals(1, $feeRecovery1['attempt_number']);
        $this->assertEquals($feeRecovery1['recovery_payout_id'], $feeRecoveryPayout['id']);

        // Fee Recovery entity for second payout

        $feeRecovery2 = $this->getDbEntity('fee_recovery', [
            'entity_id' => $payout2['id'],
            'type'      => FeeRecovery\Type::DEBIT
        ])->toArray();

        $this->assertEquals($payout2['id'], $feeRecovery2['entity_id']);
        $this->assertEquals(FeeRecovery\Status::PROCESSING, $feeRecovery2['status']);
        $this->assertEquals(1, $feeRecovery2['attempt_number']);
        $this->assertEquals($feeRecovery2['recovery_payout_id'], $feeRecoveryPayout['id']);

        // Fee Recovery entity for second payout (Failed)

        $feeRecovery3 = $this->getDbEntity('fee_recovery', [
            'entity_id' => $payout2['id'],
            'type'      => FeeRecovery\Type::CREDIT
        ])->toArray();

        $this->assertEquals($payout2['id'], $feeRecovery3['entity_id']);
        $this->assertEquals(FeeRecovery\Status::PROCESSING, $feeRecovery3['status']);
        $this->assertEquals(1, $feeRecovery3['attempt_number']);
        $this->assertEquals($feeRecovery3['recovery_payout_id'], $feeRecoveryPayout['id']);

        // Fee Recovery entity for third payout

        $feeRecovery4 = $this->getDbEntity('fee_recovery', [
            'entity_id' => $payout3['id'],
            'type'      => FeeRecovery\Type::DEBIT
        ])->toArray();

        $this->assertEquals($payout3['id'], $feeRecovery4['entity_id']);
        $this->assertEquals(FeeRecovery\Status::PROCESSING, $feeRecovery4['status']);
        $this->assertEquals(1, $feeRecovery4['attempt_number']);
        $this->assertEquals($feeRecovery4['recovery_payout_id'], $feeRecoveryPayout['id']);

        // Fee Recovery entity for reversal (Reversal of the third payout)

        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals($reversal['entity_id'], $payout3['id']);

        $feeRecovery5 = $this->getDbEntity('fee_recovery', [
            'entity_id' => $reversal['id'],
            'type'      => FeeRecovery\Type::CREDIT
        ])->toArray();

        $this->assertEquals($reversal['id'], $feeRecovery5['entity_id']);
        $this->assertEquals(FeeRecovery\Status::PROCESSING, $feeRecovery5['status']);
        $this->assertEquals(FeeRecovery\Entity::REVERSAL, $feeRecovery5['entity_type']);
        $this->assertEquals(1, $feeRecovery5['attempt_number']);
        $this->assertEquals($feeRecovery5['recovery_payout_id'], $feeRecoveryPayout['id']);

        // Fee Recovery entity for recovery payout
        $feeRecovery = $this->getDbLastEntity('fee_recovery')->toArray();

        $this->assertEquals($feeRecoveryPayout['id'], $feeRecovery['entity_id']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecovery['status']);
        $this->assertEquals(0, $feeRecovery['attempt_number']);
        $this->assertNull($feeRecovery['recovery_payout_id']);
        $this->assertEquals($feeRecovery['type'], FeeRecovery\Type::DEBIT);
    }

    public function testCreateFreePayoutForUPIModeDirectAccountPrivateAuth()
    {
        $fundAccountRequest = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                "account_type" => "vpa",
                "contact_id"   => "cont_1000001contact",
                "vpa"          => [
                    "address" => 'axis@upi',
                ]
            ]];

        $this->ba->privateAuth();

        $fundAccount = $this->makeRequestAndGetContent($fundAccountRequest);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccount['id'];

        $this->testData[__FUNCTION__] = $testData;

        $balance = $this->getDbEntities('balance',
                                        [
                                            'merchant_id'  => "10000000000000",
                                            'account_type' => 'direct',
                                            'channel'      => 'axis'
                                        ])->first();

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('direct', $balanceId, 'axis');

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testCreateFreePayoutForIMPSModeDirectAccountPrivateAuth()
    {
        $testData = $this->testData[__FUNCTION__];

        $balance = $this->getDbEntities('balance',
                                        [
                                            'merchant_id'  => "10000000000000",
                                            'account_type' => 'direct',
                                            'channel'      => 'axis'
                                        ])->first();

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('direct', $balanceId, 'axis');

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        // Assert 0 fee and tax in payout
        $this->assertEquals(0, $payout->getFees());
        $this->assertEquals(0, $payout->getTax());

        // Assert that free_payout is assigned as fee_type for such payouts
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout->getFeeType());

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'direct',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        // Assert that pricing rule id in payouts is correct
        $this->assertEquals('Bbg7cl6t6I3XB0', $payout['pricing_rule_id']);

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'direct',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());
    }

    protected function mockBASResponseForFetchingBankingCredentialsForAxisGateway($exception = null): void
    {
        $basMock = $this->getMockBuilder(BankingAccountService::class)
                                  ->setConstructorArgs([$this->app])
                                  ->setMethods(['fetchBankingCredentials'])
                                  ->getMock();

        $basMock->method('fetchBankingCredentials')
                          ->willReturn([
                                           Fields::CREDENTIALS => [
                                               Axis\Fields::CLIENT_SECRET => 'client-secret',
                                               Axis\Fields::CLIENT_ID => 'client-id',
                                           ]
                                       ]);

        if ($exception !== null)
        {
            $basMock->method('sendMozartRequest')
                              ->willThrowException($exception);
        }

        $this->app->instance('banking_account_service', $basMock);
    }


    public function testBasResponseForFetchingBankingAccountCredentials()
    {
        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                       ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $this->mockBASResponseForFetchingBankingCredentialsForAxisGateway();

        $this->setupAxisDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsAfterCronRuns->getBalanceLastFetchedAt());
    }

    public function testScheduledPayoutProcessingAutoRejectForAxis()
    {
        Mail::fake();

        // Timestamp of 9 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();
        $scheduledAtStartOfHour = Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST)->startOfHour()->getTimestamp();

        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->createPayoutWithOtpWithWorkflow(
            [
                'scheduled_at' => $scheduledAtTime
            ],
            'rzp_live_10000000000000', $this->ownerRoleUser->getId());

        // Calling this scheduled payout but it hasn't been approved yet
        $scheduledPayout = $this->getDbLastEntity('payout', 'live');

        $this->assertEquals(Status::PENDING, $scheduledPayout['status']);

        $this->fixtures->edit('balance', $this->bankingBalance->getId(), ['balance' => 0]);

        // Setting this to 1 second after the start of the time slot
        Carbon::setTestNow(Carbon::createFromTimestamp($scheduledAtStartOfHour+1, Timezone::IST));

        $this->ba->cronAuth('live');

        $result = $this->startTest();

        $expectedResponse = [
            $this->bankingBalance['id'] => [
                'total_payout_count'        => 1,
                'dispatched_payout_count'   => 1,
                'dispatched_payout_amount'  => 10000
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $result);

        $updatedScheduledPayout = $this->getDbEntityById('payout', $scheduledPayout['id'], 'live');

        $id = $updatedScheduledPayout['id'];

        $payout = $updatedScheduledPayout;

        // Assert that the scheduled payout has now gone to the processing state
        $this->assertEquals(Status::REJECTED, $updatedScheduledPayout['status']);

        Mail::assertQueued(AutoRejectedPayout::class, function($mail) use ($id, $payout) {
            $mail->build();

            $formattedScheduledFor = $payout->getFormattedScheduledFor();
            $formattedAmount = $payout->getFormattedAmount();

            $this->assertEquals($mail->subject, "Scheduled Payout <pout_" . $id ."> for " .
                                                $formattedScheduledFor . " worth " .
                                                $formattedAmount . " has been auto rejected");

            $viewData = $mail->viewData;
            $this->assertEquals("100", $viewData[PayoutEntity::AMOUNT][1]);
            $this->assertEquals("00", $viewData[PayoutEntity::AMOUNT][2]);
            $this->assertEquals("pout_" . $id, $viewData[PayoutEntity::PAYOUT_ID]);
            $this->assertEquals($formattedScheduledFor, $viewData["scheduled_for"]);
            $this->assertEquals($payout->balance->getAccountNumber(), $viewData["account_no"]);

            $accountType = 'AXIS Current Account';

            $this->assertEquals($accountType, $viewData[Balance\Entity::ACCOUNT_TYPE]);

            $mail->hasTo('naruto@gmail.com');
            $mail->hasFrom('no-reply@razorpay.com');
            $mail->hasReplyTo('no-reply@razorpay.com');

            return true;
        });
    }

    public function mockDcsFetchConfigurationWithGivenResponse($response) {

        $dcsConfigService = $this->getMockBuilder( DcsConfigService::class)
                                 ->setConstructorArgs([$this->app])
                                 ->getMock();

        $this->app->instance('dcs_config_service', $dcsConfigService);

        $this->app['dcs_config_service']
             ->method('fetchConfiguration')
             ->willReturn($response);
    }

    public function testCreateFreePayoutForUPIModeDirectAccountWithFeatureEnabled()
    {
        $fundAccountRequest = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                "account_type" => "vpa",
                "contact_id"   => "cont_1000001contact",
                "vpa"          => [
                    "address" => 'axis@upi',
                ]
            ]];

        $this->ba->privateAuth();

        $fundAccount = $this->makeRequestAndGetContent($fundAccountRequest);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccount['id'];

        $testData['response']['content']['fund_account_id'] = $fundAccount['id'];

        $this->testData[__FUNCTION__] = $testData;

        $balance = $this->getDbEntities('balance',
            [
                'merchant_id'  => "10000000000000",
                'account_type' => 'direct',
                'channel'      => 'axis'
            ])->first();

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('direct', $balanceId, 'axis');

        $dcsResponse = [
            Payout\Configurations\DirectAccounts\PayoutModeConfig\Constants::ALLOWED_UPI_CHANNELS => ["axis"],
        ];

        $this->mockDcsFetchConfigurationWithGivenResponse($dcsResponse);

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $expectedStatus = (env('FTS_MOCK') === true) ? 'created' : 'initiated';

        $this->assertEquals('axis', $payout->getChannel());

        $this->assertEquals($expectedStatus, $payout->getStatus());

        $this->assertEquals('UPI', $payout->getMode());
    }

    public function testCreateAxisPayoutToUpiWithFeatureEnabledAndNonFreePayout()
    {
        $fundAccountRequest = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                "account_type" => "vpa",
                "contact_id"   => "cont_1000001contact",
                "vpa"          => [
                    "address" => 'axis@upi',
                ]
            ]];

        $this->ba->privateAuth();

        $fundAccount = $this->makeRequestAndGetContent($fundAccountRequest);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] = $fundAccount['id'];

        $testData['response']['content']['fund_account_id'] = $fundAccount['id'];

        $this->testData[__FUNCTION__] = $testData;

        $balance = $this->getDbEntities('balance',
            [
                'merchant_id'  => "10000000000000",
                'account_type' => 'direct',
                'channel'      => 'axis'
            ])->first();

        $dcsResponse = [
            Payout\Configurations\DirectAccounts\PayoutModeConfig\Constants::ALLOWED_UPI_CHANNELS => ["axis"],
        ];

        $this->mockDcsFetchConfigurationWithGivenResponse($dcsResponse);

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $expectedStatus = (env('FTS_MOCK') === true) ? 'created' : 'initiated';

        $this->assertEquals('axis', $payout->getChannel());

        $this->assertEquals($expectedStatus, $payout->getStatus());

        $this->assertEquals('UPI', $payout->getMode());
    }

    public function testAsyncRetryBasSourceLinkingQueuePush()
    {
        $this->testCreatePayout();

        Queue::fake();

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'debit',
                'amount'                    => '104',
                'channel'                   => 'axis',
                'account_number'            => '2224440041626905',
                'utr'                       => 'asdfghjkl',
                'transaction_id'            => null,
                'bank_transaction_id'       => 'SDHDH',
                'balance'                   => 30019891,
                'transaction_date'          => 1584987183,
                'merchant_id'               => '10000000000000',
            ]);

        $payoutId = $payout->getId();

        $this->ba->ftsAuth();

        // Processed Webhook sent from FTS
        $ftsWebhook = [
            'bank_processed_time' => '',
            'bank_account_type'   => null,
            'bank_status_code'    => 'SUCCESS',
            'channel'             => 'axis',
            'extra_info'          => [
                'beneficiary_name' => 'Chirag',
                'cms_ref_no'       => '7a452792bee81',
                'internal_error'   => false,
                'ponum'            => '',
            ],
            'failure_reason'      => '',
            'fund_transfer_id'    => $attempt['fts_transfer_id'],
            'gateway_error_code'  => '',
            'gateway_ref_no'      => 'JKjdVokXZ2KMcP',
            'mode'                => 'NEFT',
            'narration'           => '256557209A0A',
            'remarks'             => '',
            'return_utr'          => '',
            'source_account_id'   => 1,
            'source_id'           => $payoutId,
            'source_type'         => 'payout',
            'status'              => 'PROCESSED',
            'utr'                 => 'asdfghjkl',
            'status_details'      => null,
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/update_fts_fund_transfer',
            'content' => $ftsWebhook,
        ];

        $this->makeRequestAndGetContent($request);

        Queue::assertPushed(BankingAccountStatementSourceLinking::class, function($job) use ($payoutId)
        {
            $this->assertEquals($payoutId, $job->params['payout_id']);

            return true;
        });

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::PROCESSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);
        $this->assertEquals(1062, $payout['fees']);
        $this->assertEquals(162, $payout['tax']);
    }

    public function testAsyncRetryBasSourceLinkingAttemptForNoQueuePush()
    {
        $this->testCreatePayout();

        Queue::fake();

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout->getId(), ['status' => "processed"]);

        $this->fixtures->edit('fund_transfer_attempt', $attempt->getId(), ['status' => "processed"]);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'debit',
                'amount'                    => '104',
                'channel'                   => 'axis',
                'account_number'            => '2224440041626905',
                'utr'                       => 'asdfghjkl',
                'transaction_id'            => null,
                'bank_transaction_id'       => 'SDHDH',
                'balance'                   => 30019891,
                'transaction_date'          => 1584987183,
                'merchant_id'               => '10000000000000',
            ]);

        $payoutId = $payout->getId();

        $params = [
            "payout_id" => $payoutId
        ];

        (new BAS\Core)->retryBasSourceLinkingForProcessedPayout($params);

        Queue::assertNotPushed(BankingAccountStatementSourceLinking::class);
    }
}
