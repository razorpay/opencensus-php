<?php

namespace RZP\Tests\Functional\Payout;

use Queue;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Models\Payout;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Services\Mock\Mozart;
use RZP\Models\BankingAccount;
use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccount\Gateway\Rbl;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Jobs\RblBankingAccountGatewayBalanceUpdate;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;

class RblPayoutTest extends TestCase
{
    use PayoutTrait;
    use PaymentTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    private $checkerRoleUser;

    private $ownerRoleUser;

    private $finL3RoleUser;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutTestData.php';

        parent::setUp();

        $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000, 'direct', 'rbl');

        $bankingAccountParams = [
            'id' => 'xba00000000000',
            'merchant_id' => '10000000000000',
            'account_ifsc' => 'RATN0000088',
            'account_number' => '2224440041626905',
            'status' => 'active',
            'channel' => 'rbl',
            'balance_id' => $this->bankingBalance->getId(),
        ];

        $this->createBankingAccount($bankingAccountParams);

        $this->flushCache();

        $this->ba->privateAuth();
    }

    protected function liveSetUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutTestData.php';

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

        $this->setUpMerchantForBusinessBankingLive(true, 10000000, 'direct', 'rbl');

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
    }

    protected function createPendingPayout(array $attributes = [], string $authKey = null)
    {
        $this->ba->privateAuth($authKey);

        $request = [
            'url'     => '/payouts',
            'method'  => 'POST',
            'content' => [
                'account_number'        => $attributes["account_number"] ?? '2224440041626905',
                'amount'                => $attributes["amount"] ?? 10000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'mode'                  => 'NEFT',
                'queue_if_low_balance'  => $attributes["queue_if_low_balance"] ?? 0,
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function mockMozartResponseForFetchingBalanceFromRblGateway(int $amount): void
    {
        $mozartServiceMock = $this->getMockBuilder(Mozart::class)
                                  ->setConstructorArgs([$this->app])
                                  ->setMethods(['sendMozartRequest'])
                                  ->getMock();

        $mozartServiceMock->method('sendMozartRequest')
                          ->willReturn([
                               'data' => [
                                   'success' => true,
                                   Rbl\Fields::GET_ACCOUNT_BALANCE => [
                                       Rbl\Fields::BODY => [
                                           Rbl\Fields::BAL_AMOUNT => [
                                               Rbl\Fields::AMOUNT_VALUE => $amount
                                           ]
                                       ]
                                   ]
                               ]
                          ]);

        $this->app->instance('mozart', $mozartServiceMock);
    }

    public function testCreatingPendingPayoutsForRblWithUnsupportedModeChannelDestinationTypeCombo()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createWorkflowFeature();

        $workflow = $this->createWorkflow([
          'org_id'      => '100000razorpay',
          'name'        => 'some workflow',
          'permissions' => ['create_payout'],
        ]);

        $attributes = [
            'merchant_id' => '10000000000000',
            'min_amount'  => 0,
            'max_amount'  => 1000000,
            'workflow_id' => $workflow->getId(),
        ];

        $this->fixtures->create('workflow_payout_amount_rules', $attributes);

        $this->createVpaFundAccount(['id' => 'D6XkDQaM3whg5v']);

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testCreatingPendingPayoutsForRblWithSupportedModeChannelDestinationTypeCombo()
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

    public function testCreatePayoutWithFetchAndUpdateBalanceFromGatewayAndBalanceLessThanPayoutAmount()
    {
        $oldDateTime = Carbon::create(2020, 01, 21, 12, 23, null, Timezone::IST);

        $this->fixtures->edit('banking_account', 'xba00000000000', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ] );

        $this->fixtures->edit('balance', $this->bankingBalance->getId(), [
            'updated_at' => $oldDateTime->getTimestamp(),
        ] );

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        $this->startTest();
    }

    public function testCreatePayoutWithFetchAndUpdateBalanceFromGatewayAndBalanceMoreThanPayoutAmount()
    {
        $oldDateTime = Carbon::create(2020, 01, 21, 12, 23, null, Timezone::IST);

        $this->fixtures->edit('banking_account', 'xba00000000000', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ] );

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(50000);

        $this->startTest();
    }

    public function testQueuedPayoutWithFetchAndUpdateBalanceFromGateway()
    {
        $oldDateTime = Carbon::create(2020, 01, 21, 12, 23, null, Timezone::IST);

        $this->fixtures->edit('banking_account', 'xba00000000000', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ] );

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        sleep(1);

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 2000000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => true,
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ];

        $this->makeRequestAndGetContent($request);

        $this->fixtures->edit('banking_account', 'xba00000000000', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ]);

        $summary = $this->makePayoutSummaryRequest();

        // Assert that there is a payout in queued state with amount 2000000.
        $this->assertEquals(1, $summary['bacc_xba00000000000'][Payout\Status::QUEUED]['count']);
        $this->assertEquals(2000000, $summary['bacc_xba00000000000'][Payout\Status::QUEUED]['total_amount']);

        // Add enough balance to allow the payout to get processed
        $this->mockMozartResponseForFetchingBalanceFromRblGateway(50000);

        $dispatchResponse = $this->dispatchQueuedPayouts();

        $balanceId = $this->bankingBalance->getId();

        $this->assertEquals($dispatchResponse['balance_id_list'][0], $balanceId);

        $updatedSummary = $this->makePayoutSummaryRequest();

        // Assert that there are no payouts in queued state.
        $this->assertEquals(0, $updatedSummary['bacc_xba00000000000'][Payout\Status::QUEUED]['count']);
        $this->assertEquals(0, $updatedSummary['bacc_xba00000000000'][Payout\Status::QUEUED]['total_amount']);
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

        $this->mockMozartResponseForFetchingBalanceFromRblGateway($gatewayBalance);

        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        $this->fixtures->edit('balance', $this->bankingBalance->getId(), [
            'updated_at' => $oldDateTime->getTimestamp(),
        ] );

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function setupRblDispatchGatewayBalanceUpdateForMerchants()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT => 1]);

        $request = [
            'method'  => 'put',
            'url'     => '/banking_accounts/gateway/rbl/balance',
        ];

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
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

        $this->mockMozartResponseForFetchingBalanceFromRblGateway($gatewayBalance);

        $this->makeRequestAndGetContent($request);

        return [$payout1['id'], $payout2['id']];
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

    // Case when payout amount is greater than balance in CA and queue_if_low_balance = false. it will fail at fts
    // with current implementation of payout module for CA
    public function testApprovePendingPayoutWithQueueFlagFalseAndBalanceLess()
    {
        $response = $this->createPendingPayoutAndApprovePayoutUptoSecondLevel(500, 0);

        $this->assertEquals('processing', $response['status']);
    }

    // Case when payout amount is less than balance in CA and queue_if_low_balance = false
    public function testApprovePendingPayoutWithQueueFlagFalseAndBalanceGreater()
    {
        $response = $this->createPendingPayoutAndApprovePayoutUptoSecondLevel(500, 0);

        $this->assertEquals('processing', $response['status']);
    }

    // Case when both payout amount is greater than balance in CA and queue_if_low_balance = true
    public function testBulkApprovePendingPayoutWithQueueFlagAndBalanceLess()
    {
        list($payoutId1, $payoutId2) = $this->createBulkPendingPayoutAndApprovePayoutsUptoSecondLevel(50);

        $payout1 = $this->getDbEntityById('payout', $payoutId1)->toArray();
        $payout2 = $this->getDbEntityById('payout', $payoutId2)->toArray();

        $this->assertEquals('queued', $payout1['status']);
        $this->assertEquals('queued', $payout2['status']);
    }

    // Case when both payouts amount is less than balance in CA and queue_if_low_balance = true
    public function testBulkApprovePendingPayoutWithQueueFlagAndBalanceGreater()
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

    public function testDispatchGatewayBalanceUpdateJob()
    {
        Queue::fake();

        $this->setupRblDispatchGatewayBalanceUpdateForMerchants();

        Queue::assertPushed(RblBankingAccountGatewayBalanceUpdate::class, 1);

        Queue::assertPushed(RblBankingAccountGatewayBalanceUpdate::class, function($job)
        {
            $this->assertEquals($job->getOriginProduct(), 'banking');

            return true;
        });
    }

    public function testProcessGatewayBalanceUpdate()
    {
        /** @var BankingAccount\Entity $baBeforeTest */
        $baBeforeTest = $this->getDbEntityById('banking_account', 'xba00000000000');

        $this->assertNull($baBeforeTest->getBalanceLastFetchedAt());

        $response = $this->setupRblDispatchGatewayBalanceUpdateForMerchants();

        /** @var BankingAccount\Entity $baAfterCronRuns */
        $baAfterCronRuns = $this->getDbEntityById('banking_account', 'xba00000000000');

        $this->assertNotNull($baAfterCronRuns->getBalanceLastFetchedAt());
    }

    public function testDispatchGatewayBalanceUpdateJobForInvalidDirectChannel()
    {
        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testRblPayoutWithInvalidMode()
    {
        $this->startTest();
    }
}
