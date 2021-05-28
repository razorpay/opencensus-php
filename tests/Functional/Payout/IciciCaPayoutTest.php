<?php

namespace Functional\Payout;

use Queue;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Models\Payout;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use Rzp\Models\FundTransfer;
use RZP\Services\Mock\Mozart;
use RZP\Models\Admin\ConfigKey;
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Mode as EnvMode;
Use RZP\Models\FundTransfer\Attempt;
use RZP\Exception\GatewayErrorException;
use RZP\Models\BankingAccount\Gateway\Icici;
use RZP\Models\Admin\Service as AdminService;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Jobs\IciciBankingAccountGatewayBalanceUpdate;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Jobs\IciciBankingAccountStatement as IciciBankingAccountStatementJob;

class IciciCaPayoutTest extends TestCase
{
    use PayoutTrait;
    use AttemptTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    private $ownerRoleUser;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/IciciCaPayoutTestData.php';

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

        $this->setUpMerchantForBusinessBanking(false, 10000000, 'direct', 'icici');

        $this->fixtures->create('banking_account_statement_details',[
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $this->bankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626905',
            Details\Entity::CHANNEL        => Details\Channel::ICICI,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function testCreatingPendingPayoutsForIciciWithSupportedModeChannelDestinationTypeCombo()
    {
        $this->liveSetUp();
        $this->setupWorkflowForLiveMode();
        $this->disableWorkflowMocks();

        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testIciciAccountStatementFetchV2()
    {
        $this->ba->cronAuth();

        $request = [
            'url'       => '/banking_account_statement/process/icici',
            'method'    => 'POST'
        ];

        $this->flushCache();

        (new AdminService)->setConfigKeys([ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT => 1]);

        (new AdminService)->setConfigKeys([ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ["2224440041626905"]]);

        Queue::fake();

        $this->makeRequestAndGetContent($request);

        Queue::assertPushed(IciciBankingAccountStatementJob::class, 1);
    }

    protected function liveSetUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/IciciCaPayoutTestData.php';

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

        $this->setUpMerchantForBusinessBankingLive(true, 10000000, 'direct', 'icici');

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        // Merchant needs to be activated to make live requests
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        // Create merchant user mapping
        $this->fixtures->on('live')->user->createUserMerchantMapping([
                                                                         'merchant_id' => '10000000000000',
                                                                         'user_id'     => User::MERCHANT_USER_ID,
                                                                         'product'     => 'primary',
                                                                         'role'        => 'owner',
                                                                     ], 'live');

        $this->fixtures->on('live')->create('banking_account_statement_details',[
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $this->bankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626905',
            Details\Entity::CHANNEL        => Details\Channel::ICICI,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);
    }

    protected function mockMozartResponseForFetchingBalanceFromIciciGateway($amount, $exception = null): void
    {
        $mozartServiceMock = $this->getMockBuilder(Mozart::class)
                                  ->setConstructorArgs([$this->app])
                                  ->setMethods(['sendMozartRequest'])
                                  ->getMock();

        $mozartServiceMock->method('sendMozartRequest')
                          ->willReturn([
                                           Icici\Fields::DATA => [

                                               Icici\Fields::BALANCE => $amount
                                           ]
                                       ]);

        if ($exception !== null)
        {
            $mozartServiceMock->method('sendMozartRequest')
                              ->willThrowException($exception);
        }

        $this->app->instance('mozart', $mozartServiceMock);
    }

    protected function setupIciciDispatchGatewayBalanceUpdateForMerchants()
    {
        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::ICICI_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT => 1]);

        $request = [
            'method'  => 'put',
            'url'     => '/banking_accounts/gateway/icici/balance',
        ];

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function testDispatchGatewayBalanceUpdateJob()
    {
        Queue::fake();

        $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        Queue::assertPushed(IciciBankingAccountGatewayBalanceUpdate::class, 1);
    }

    public function testProcessGatewayBalanceUpdate()
    {
        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(500);

        $response = $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(50000, $basDetailsAfterCronRuns->getGatewayBalance());

        $this->assertNotNull($basDetailsAfterCronRuns->getBalanceLastFetchedAt());

        $this->assertNotNull($basDetailsAfterCronRuns->getGatewayBalanceChangeAt());
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

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(null, $exception);

        $response = $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsAfterCronRuns->getBalanceLastFetchedAt());
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

        $this->assertEquals('icici', $payout['channel']);
        $this->assertEquals('processing', $payout['status']);

        $transaction = $this->getLastEntity('transaction', true);
        $this->assertNull($transaction);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

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
        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
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
    }

    public function testIciciPayoutUsingCredits()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

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
    }

    public function testIciciPayoutUsingCreditsFailed()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

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
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[2]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[2]['entity_id']);
        $this->assertEquals(-100, $creditTxnEntities[2]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[3]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[3]['entity_id']);
        $this->assertEquals(-400, $creditTxnEntities[3]['credits_used']);

    }

    public function testIciciPayoutUsingCreditsReversed()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

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
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $this->assertEquals('reversal', $creditTxnEntities[2]['entity_type']);
        $this->assertEquals($reversal['id'],  $creditTxnEntities[2]['entity_id']);
        $this->assertEquals(-100, $creditTxnEntities[2]['credits_used']);

        $this->assertEquals('reversal', $creditTxnEntities[3]['entity_type']);
        $this->assertEquals($reversal['id'],  $creditTxnEntities[3]['entity_id']);
        $this->assertEquals(-400, $creditTxnEntities[3]['credits_used']);
    }

    // Case when payout amount is greater than balance in CA and queue_if_low_balance = true
    public function testApprovePendingPayoutWithQueueFlagBalanceLess()
    {
        $response = $this->createPendingPayoutAndApprovePayoutUptoSecondLevel(50);

        $this->assertEquals('queued', $response['status']);
    }

    // Case when payout amount is less than balance in CA and queue_if_low_balance = true
    public function testApprovePendingPayoutWithQueueFlagBalanceGreater()
    {
        $response = $this->createPendingPayoutAndApprovePayoutUptoSecondLevel(500);

        $this->assertEquals('processing', $response['status']);
    }

    // Case when payout amount is less than balance in CA and queue_if_low_balance = false
    public function testApprovePendingPayoutWithQueueFlagFalseAndBalanceGreater()
    {
        $response = $this->createPendingPayoutAndApprovePayoutUptoSecondLevel(500, 0);

        $this->assertEquals('processing', $response['status']);
    }

    // Case when payout amount is greater than balance in CA and queue_if_low_balance = false. it will fail at fts
    // with current implementation of payout module for CA
    public function testApprovePendingPayoutWithQueueFlagFalseAndBalanceLess()
    {
        $response = $this->createPendingPayoutAndApprovePayoutUptoSecondLevel(50, 0);

        $this->assertEquals('processing', $response['status']);
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

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway($gatewayBalance);

        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        $this->fixtures->edit('balance', $this->bankingBalance->getId(), [
            'updated_at' => $oldDateTime->getTimestamp(),
        ] );

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function testBulkApprovePayoutWithComment()
    {
        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout1 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');
        $payout2 = $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['content']['payout_ids'] = [$payout1['id'], $payout2['id']];

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->startTest();

        $actionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(true, $actionChecker['approved']);
        $this->assertEquals($testData['request']['content']['user_comment'], $actionChecker['user_comment']);
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

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway($gatewayBalance);

        $this->makeRequestAndGetContent($request);

        return [$payout1['id'], $payout2['id']];
    }
}
