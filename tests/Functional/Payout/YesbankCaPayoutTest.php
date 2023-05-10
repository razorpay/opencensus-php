<?php

namespace Functional\Payout;

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
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Mode as EnvMode;
use RZP\Models\Settlement\Channel;
Use RZP\Models\FundTransfer\Attempt;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Balance\FreePayout;
use RZP\Services\Mock\BankingAccountService;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\BankingAccount\Gateway\Fields;
use RZP\Models\BankingAccount\Core as BACore;
use RZP\Models\BankingAccount\Gateway\Yesbank;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Jobs\ConnectedBankingAccountGatewayBalanceUpdate;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;

class YesbankCaPayoutTest extends TestCase
{
    use PayoutTrait;
    use AttemptTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    private $ownerRoleUser;

    protected $merchant;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/YesbankCaPayoutTestData.php';

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

        $this->setUpMerchantForBusinessBanking(false, 0, 'direct', 'yesbank');

        $this->fixtures->create('banking_account_statement_details',[
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $this->bankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626905',
            Details\Entity::CHANNEL        => Details\Channel::YESBANK,
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
        $this->testDataFilePath = __DIR__ . '/helpers/YesbankCaPayoutTestData.php';

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

        $this->setUpMerchantForBusinessBankingLive(true, 10000000, 'direct', 'yesbank');

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
            Details\Entity::CHANNEL        => Details\Channel::YESBANK,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    protected function mockMozartResponseForFetchingBalanceFromYesbankGateway($amount, $exception = null): void
    {
        $mozartServiceMock = $this->getMockBuilder(Mozart::class)
                                  ->setConstructorArgs([$this->app])
                                  ->setMethods(['sendMozartRequest'])
                                  ->getMock();

        $mozartServiceMock->method('sendMozartRequest')
                          ->willReturn([
                                           Yesbank\Fields::DATA => [

                                               Yesbank\Fields::ACCOUNT_BALANCE_AMOUNT => $amount,
                                               "accountCurrencyCode"               => "INR",
                                               "faultValue"                        => null,
                                               "lowBalanceAlert"                   => false
                                           ]
                                       ]);

        if ($exception !== null)
        {
            $mozartServiceMock->method('sendMozartRequest')
                              ->willThrowException($exception);
        }

        $this->app->instance('mozart', $mozartServiceMock);
    }

    protected function setupYesbankDispatchGatewayBalanceUpdateForMerchants()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT => 1]);

        $request = [
            'method'  => 'put',
            'url'     => '/banking_accounts/gateway/yesbank/balance',
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

        $this->setupYesbankDispatchGatewayBalanceUpdateForMerchants();

        Queue::assertPushed(ConnectedBankingAccountGatewayBalanceUpdate::class, 1);
    }

    public function testProcessGatewayBalanceUpdate()
    {
        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                       ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $this->mockMozartResponseForFetchingBalanceFromYesbankGateway(500);

        $response = $this->setupYesbankDispatchGatewayBalanceUpdateForMerchants();

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

        $this->mockMozartResponseForFetchingBalanceFromYesbankGateway(500);

        $response = $this->setupYesbankDispatchGatewayBalanceUpdateForMerchants();

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

        $this->mockMozartResponseForFetchingBalanceFromYesbankGateway(null, $exception);

        $response = $this->setupYesbankDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsAfterCronRuns->getBalanceLastFetchedAt());
    }

    public function testBalanceFetch()
    {
        $this->setMockRazorxTreatment(['gateway_balance_fetch_v2' => 'on']);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        $response = $this->setupYesbankDispatchGatewayBalanceUpdateForMerchants();

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

        $response = $this->setupYesbankDispatchGatewayBalanceUpdateForMerchants();

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

        $response = $this->setupYesbankDispatchGatewayBalanceUpdateForMerchants();

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

        $this->mockMozartResponseForFetchingBalanceFromYesbankGateway(500);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutWithFetchAndUpdateBalanceFromGatewayAndBalanceMoreThanPayoutAmount()
    {
        $oldDateTime = Carbon::create(2020, 01, 21, 12, 23, null, Timezone::IST);

        $this->fixtures->edit('banking_account_statement_details', 'xbas0000000002', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ] );

        $this->mockMozartResponseForFetchingBalanceFromYesbankGateway(50000);

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

        $this->assertEquals('yesbank', $payout['channel']);
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
        $this->fixtures->edit('pricing', 'Bbg7e4oKCgyesb', ['fixed_rate' => 0]);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(0, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);
    }

    public function testCreatePayoutImps()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals('yesbank', $payout['channel']);
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

        $this->assertEquals('yesbank', $payout['channel']);
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

    /* Asserts Error that UPI is not supported for YESBANK */
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
        $this->assertEquals('yesbank', $payout['channel']);
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

    public function testYesbankPayoutUsingCredits()
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

    public function testYesbankPayoutUsingCreditsFailed()
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

    public function testYesbankPayoutUsingCreditsReversed()
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

        $this->mockMozartResponseForFetchingBalanceFromYesbankGateway($gatewayBalance);

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

    public function testApprovePendingPayoutWithQueueFlagBalanceGreater()
    {
        $response = $this->createPendingPayoutAndApprovePayoutUptoSecondLevel(500);

        $this->assertEquals('processing', $response['status']);
    }

    public function testApprovePendingPayoutWithQueueFlagFalseAndBalanceGreater()
    {
        $response = $this->createPendingPayoutAndApprovePayoutUptoSecondLevel(500, 0);

        $this->assertEquals('processing', $response['status']);
    }

    public function testApprovePendingPayoutWithQueueFlagFalseAndBalanceLess()
    {
        $response = $this->createPendingPayoutAndApprovePayoutUptoSecondLevel(50, 0);

        $this->assertEquals('processing', $response['status']);
    }

    protected function createBulkPendingPayoutAndApprovePayoutsUptoSecondLevel(int $gatewayBalance, $queueFlag = 1)
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $payout2 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/approve/bulk',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'payout_ids'           => [$payout1['id'], $payout2['id']],
                'token'                => 'BUIj3m2Nx2VvVj',
                'otp'                  => '0007',
                'queue_if_low_balance' => $queueFlag,
            ],
        ];

        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        $this->fixtures->edit('balance', $this->bankingBalance->getId(), [
            'updated_at' => $oldDateTime->getTimestamp(),
        ] );

        $this->makeRequestAndGetContent($request);

        $this->app['config']->set('database.default', 'live');

        // Make Request to Approve pending payout for second level from Finance L3 role
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL3RoleUser->getId());

        $this->fixtures->edit('balance', $this->bankingBalance->getId(), [
            'updated_at' => $oldDateTime->getTimestamp(),
        ] );

        $this->mockMozartResponseForFetchingBalanceFromYesbankGateway($gatewayBalance);

        $this->makeRequestAndGetContent($request);

        return [$payout1['id'], $payout2['id']];
    }

    // Case when both payouts amount is less than balance in CA and queue_if_low_balance = false
    public function testBulkApprovePendingPayoutWithQueueFlagFalseAndBalanceGreater()
    {
        list($payoutId1, $payoutId2) = $this->createBulkPendingPayoutAndApprovePayoutsUptoSecondLevel(500, 0);

        $payout1 = $this->getDbEntityById('payout', $payoutId1)->toArray();
        $payout2 = $this->getDbEntityById('payout', $payoutId2)->toArray();

        //if FTS_MOCK = false then these status will be initiated
        if (env('FTS_MOCK') === true)
        {
            $this->assertEquals('created', $payout2['status']);
            $this->assertEquals('created', $payout1['status']);
        }
        else
        {
            $this->assertEquals('initiated', $payout2['status']);
            $this->assertEquals('initiated', $payout1['status']);
        }
    }

    // Case when both payouts amount is less than balance in CA and queue_if_low_balance = true
    public function testBulkApprovePendingPayoutWithQueueFlagTrueAndBalanceGreater()
    {
        list($payoutId1, $payoutId2) = $this->createBulkPendingPayoutAndApprovePayoutsUptoSecondLevel(500);

        $payout1 = $this->getDbEntityById('payout', $payoutId1)->toArray();
        $payout2 = $this->getDbEntityById('payout', $payoutId2)->toArray();

        //if FTS_MOCK = false then these status will be initiated
        if (env('FTS_MOCK') === true)
        {
            $this->assertEquals('created', $payout2['status']);
            $this->assertEquals('created', $payout1['status']);
        }
        else
        {
            $this->assertEquals('initiated', $payout2['status']);
            $this->assertEquals('initiated', $payout1['status']);
        }
    }

    // Case when both payout amount is greater than balance in CA and queue_if_low_balance = false
    // it will fail at fts with current implementation of payout module for CA
    public function testBulkApprovePendingPayoutWithQueueFlagFalseAndBalanceLess()
    {
        list($payoutId1, $payoutId2) = $this->createBulkPendingPayoutAndApprovePayoutsUptoSecondLevel(50, 0);

        $payout1 = $this->getDbEntityById('payout', $payoutId1)->toArray();
        $payout2 = $this->getDbEntityById('payout', $payoutId2)->toArray();

        $this->assertEquals('created', $payout1['status']);
        $this->assertEquals('created', $payout2['status']);
    }

    // Case when both payout amount is greater than balance in CA and queue_if_low_balance = true
    public function testBulkApprovePendingPayoutWithQueueFlagTrueAndBalanceLess()
    {
        list($payoutId1, $payoutId2) = $this->createBulkPendingPayoutAndApprovePayoutsUptoSecondLevel(50);

        $payout1 = $this->getDbEntityById('payout', $payoutId1)->toArray();
        $payout2 = $this->getDbEntityById('payout', $payoutId2)->toArray();

        $this->assertEquals('queued', $payout1['status']);
        $this->assertEquals('queued', $payout2['status']);
    }

    public function testQueuedPayout()
    {
        $this->mockMozartResponseForFetchingBalanceFromYesbankGateway(500);

        $firstQueuedPayoutAttributes = [
            'account_number'       => '2224440041626905',
            'amount'               => 20000099,
            'queue_if_low_balance' => 1,
        ];

        $this->createQueuedOrPendingPayout($firstQueuedPayoutAttributes);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('queued', $payout['status']);
        $this->assertEquals('yesbank', $payout['channel']);

        $bankingBalance = $this->getDbLastEntity('balance');

        $this->fixtures->balance->edit($bankingBalance['id'], ['balance' => 21000000]);

        $this->dispatchQueuedPayouts();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('created', $payout['status']);
        $this->assertEquals('yesbank', $payout['channel']);
    }

    public function testQueuedPayoutByFetchingBalanceFromGatewayBalance()
    {
        $this->mockMozartResponseForFetchingBalanceFromYesbankGateway(500);

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
        $this->assertEquals('yesbank', $payout['channel']);

        $this->fixtures->edit('banking_account_statement_details','xbas0000000002',[
            'gateway_balance'         => 500000000
        ]);

        $this->dispatchQueuedPayouts();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('created', $payout['status']);
        $this->assertEquals('yesbank', $payout['channel']);
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
                                            'channel'      => 'yesbank'
                                        ])->first();

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('direct', $balanceId, 'yesbank');

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

    protected function createFundAccountForContact($contact, $ifsc = 'YESB0000047', $accountNumber = '111000111000')
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

    protected function createRzpFeesContactAndFundAccountForYesbank()
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

    public function testCreateFeeRecoveryAtPayoutCreationForYesbankPayouts()
    {
        $this->mockMozartResponseForFetchingBalanceFromYesbankGateway(500);

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

    public function testFeeRecoveryPayoutCronForYesbank()
    {
        $oldTime = Carbon::create(2020, 1, 3, null, null, null);

        Carbon::setTestNow($oldTime);

        $this->setMockRazorxTreatment([RazorxTreatment::USE_GATEWAY_BALANCE    => 'on']);

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->bankingBalance);

        $this->setupScheduleAndScheduleTaskForMerchant();

        $oldTimeStamp = $oldTime->getTimestamp();

        // Create first payout
        $this->testCreateFeeRecoveryAtPayoutCreationForYesbankPayouts();

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

        $this->createRzpFeesContactAndFundAccountForYesbank();

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
                    "address" => 'yesbank@upi',
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
                                            'channel'      => 'yesbank'
                                        ])->first();

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('direct', $balanceId, 'yesbank');

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
                                            'channel'      => 'yesbank'
                                        ])->first();

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('direct', $balanceId, 'yesbank');

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

    protected function mockBASResponseForFetchingBankingCredentialsForYesbankGateway($exception = null): void
    {
        $basMock = $this->getMockBuilder(BankingAccountService::class)
                        ->setConstructorArgs([$this->app])
                        ->setMethods(['fetchBankingCredentials'])
                        ->getMock();

        $basMock->method('fetchBankingCredentials')
                ->willReturn([
                                 Fields::CREDENTIALS => [
                                     Yesbank\Fields::AES_KEY       => 'aes123',
                                     Yesbank\Fields::CLIENT_SECRET => 'client-secret',
                                     Yesbank\Fields::CLIENT_ID     => 'client-id',
                                     Yesbank\Fields::AUTH_USERNAME => 'auth-user',
                                     Yesbank\Fields::AUTH_PASSWORD => 'auth-pass',
                                     Yesbank\Fields::APP_ID        => 'app123',
                                     Yesbank\Fields::CUSTOMER_ID   => 'cust123',
                                 ],
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

        $this->mockBASResponseForFetchingBankingCredentialsForYesbankGateway();

        $this->setupYesbankDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsAfterCronRuns->getBalanceLastFetchedAt());
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

    public function testCreateFreePayoutForUPIModeDirectAccountWithUpiEnabled()
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
                    "address" => 'yesbank@upi',
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
                'channel'      => 'yesbank'
            ])->first();

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('direct', $balanceId, 'yesbank');

        $dcsResponse = [
            Payout\Configurations\DirectAccounts\PayoutModeConfig\Constants::ALLOWED_UPI_CHANNELS => ["yesbank","axis"],
        ];

        $this->mockDcsFetchConfigurationWithGivenResponse($dcsResponse);

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $expectedStatus = (env('FTS_MOCK') === true) ? 'created' : 'initiated';

        $this->assertEquals('yesbank', $payout->getChannel());

        $this->assertEquals($expectedStatus, $payout->getStatus());

        $this->assertEquals('UPI', $payout->getMode());
    }

    public function testCreateNonFreePayoutForUPIModeDirectAccountWithUpiEnabled()
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
                    "address" => 'yesbank@upi',
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
                'channel'      => 'yesbank'
            ])->first();

        $dcsResponse = [
            Payout\Configurations\DirectAccounts\PayoutModeConfig\Constants::ALLOWED_UPI_CHANNELS => ["yesbank", "axis"],
        ];

        $this->mockDcsFetchConfigurationWithGivenResponse($dcsResponse);

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $expectedStatus = (env('FTS_MOCK') === true) ? 'created' : 'initiated';

        $this->assertEquals('yesbank', $payout->getChannel());

        $this->assertEquals($expectedStatus, $payout->getStatus());

        $this->assertEquals('UPI', $payout->getMode());
    }
}
