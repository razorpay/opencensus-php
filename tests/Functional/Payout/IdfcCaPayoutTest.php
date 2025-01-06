<?php

namespace RZP\Tests\Functional\Payout;

use Queue;
use Mockery;
use Carbon\Carbon;
use RZP\Exception\GatewayErrorException;
use RZP\Jobs\ConnectedBankingAccountGatewayBalanceUpdate;
use RZP\Models\Admin;
use RZP\Models\BankingAccount\Core as BACore;
use RZP\Models\Payout;
use RZP\Error\ErrorCode;
use RZP\Models\Schedule;
use RZP\Models\Pricing\Fee;
use RZP\Models\FeeRecovery;
use RZP\Constants\Timezone;
use Rzp\Models\FundTransfer;
use RZP\Services\Mock\Mozart;
use RZP\Models\Settlement\Channel;
use RZP\Constants\Mode as EnvMode;
use RZP\Tests\Functional\TestCase;
Use RZP\Models\FundTransfer\Attempt;
use RZP\Exception\ServerErrorException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\BankingAccount\Gateway\Idfc;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;

class IdfcCaPayoutTest extends TestCase
{
    use PayoutTrait;
    use AttemptTrait;
    use PaymentTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
    use TestsBusinessBanking;

    private $ownerRoleUser;

    protected $merchant;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/IdfcCaPayoutTestData.php';

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

        $this->setUpMerchantForBusinessBanking(false, 0, 'direct', 'idfc');

        $this->fixtures->create('banking_account_statement_details',[
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $this->bankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626905',
            Details\Entity::CHANNEL        => Details\Channel::IDFC,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
            Details\Entity::BALANCE_LAST_FETCHED_AT => 123456789,
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
        $channel = Channel::IDFC)
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
        $this->testDataFilePath = __DIR__ . '/helpers/IdfcCaPayoutTestData.php';

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

        $this->setUpMerchantForBusinessBankingLive(true, 10000000, 'direct', 'idfc');

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
            Details\Entity::CHANNEL        => Details\Channel::IDFC,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    protected function mockMozartResponseForFetchingBalanceFromIdfcGateway($amount, $exception = null): void
    {
        $mozartServiceMock = $this->getMockBuilder(Mozart::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendMozartRequest'])
            ->getMock();

        $mozartServiceMock->method('sendMozartRequest')
            ->willReturn([
                Idfc\Fields::DATA => [
                    Idfc\Fields::BALANCE => $amount
                ]
            ]);

        if ($exception !== null)
        {
            $mozartServiceMock->method('sendMozartRequest')
                ->willThrowException($exception);
        }

        $this->app->instance('mozart', $mozartServiceMock);
    }

    protected function setupIdfcDispatchGatewayBalanceUpdateForMerchants()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT => 1]);

        $request = [
            'method'  => 'put',
            'url'     => '/banking_accounts/gateway/idfc/balance',
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

        $this->setupIdfcDispatchGatewayBalanceUpdateForMerchants();

        Queue::assertPushed(ConnectedBankingAccountGatewayBalanceUpdate::class, 1);
    }

    public function testBalanceFetchWithBlacklistedMerchants()
    {
        Queue::fake();

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT => 1]);

        $request = [
            'method'  => 'put',
            'url'     => '/banking_accounts/gateway/idfc/balance',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'blacklisted_merchant_ids' => ['10000000000000']
            ]
        ];

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        Queue::assertNotPushed(ConnectedBankingAccountGatewayBalanceUpdate::class);
    }

    public function testProcessGatewayBalanceUpdate()
    {
        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
            ['account_number' => 2224440041626905]);

        $this->assertEquals(123456789, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $this->mockMozartResponseForFetchingBalanceFromIdfcGateway(500);

        $response = $this->setupIdfcDispatchGatewayBalanceUpdateForMerchants();



        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
            ['account_number' => 2224440041626905]);

        $this->assertEquals(50000, $basDetailsAfterCronRuns->getGatewayBalance());

        $this->assertNotEquals(123456789,$basDetailsAfterCronRuns->getBalanceLastFetchedAt());

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
        $this->assertEquals(123456789, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());
        $this->assertNull($basDetailsBeforeCronRuns->getGatewayBalanceChangeAt());

        $response = $this->setupIdfcDispatchGatewayBalanceUpdateForMerchants();

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

        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
            ['account_number' => 2224440041626905]);

        $this->assertEquals(123456789, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $this->mockMozartResponseForFetchingBalanceFromIdfcGateway(null, $exception);

        $response = $this->setupIdfcDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
            ['account_number' => 2224440041626905]);

        $this->assertEquals(123456789, $basDetailsAfterCronRuns->getBalanceLastFetchedAt());
    }

    public function testBalanceFetch()
    {
        $this->setMockRazorxTreatment(['gateway_balance_fetch_v2' => 'on']);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        $response = $this->setupIdfcDispatchGatewayBalanceUpdateForMerchants();

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



    public function testBalanceFetchWhenMerchantGatewayBalanceChanges()
    {
        $this->setMockRazorxTreatment(['gateway_balance_fetch_v2' => 'on']);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        /** @var Details\Entity $basDetails */
        $basDetails = $this->getDbEntity('banking_account_statement_details',
            ['account_number' => 2224440041626905]);

        $this->fixtures->edit('banking_account_statement_details', $basDetails->getId(),
            [Details\Entity::GATEWAY_BALANCE_CHANGE_AT => Carbon::now()->subMinute()->getTimestamp()]);

        $response = $this->setupIdfcDispatchGatewayBalanceUpdateForMerchants();

        $this->assertArrayHasKey(BACore::MADE_PAYOUT_RULE, $response);
        $this->assertArrayHasKey(BACore::BALANCE_CHANGE_RULE, $response);
        $this->assertArrayHasKey(BACore::MANDATORY_UPDATE_RULE, $response);

        $this->assertEmpty($response[BACore::MADE_PAYOUT_RULE]);
        $this->assertEquals([$basDetails->getMerchantId()], $response[BACore::BALANCE_CHANGE_RULE]);
        $this->assertEmpty($response[BACore::MANDATORY_UPDATE_RULE]);
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
}
