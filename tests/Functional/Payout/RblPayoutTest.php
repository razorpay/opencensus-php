<?php

namespace RZP\Tests\Functional\Payout;

use Mail;
use Queue;
use Mockery;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Models\Payout;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Card\Type;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Services\Mock\Mozart;
use RZP\Models\Payout\Status;
use RZP\Models\BankingAccount;
use RZP\Models\Merchant\Balance;
use RZP\Mail\Payout\FailedPayout;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Mail\Payout\AutoRejectedPayout;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Services\Mozart as MozartService;
use RZP\Models\BankingAccount\Gateway\Rbl;
use RZP\Models\Merchant\Balance\FreePayout;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Models\BankingAccountStatement\Details;
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
    use TestsWebhookEvents;
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

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $bankingAccountParams = [
            'id'             => 'xba00000000000',
            'merchant_id'    => '10000000000000',
            'account_ifsc'   => 'RATN0000088',
            'account_number' => '2224440041626905',
            'status'         => 'activated',
            'channel'        => 'rbl',
            'balance_id'     => $this->bankingBalance->getId(),
        ];

        $this->fixtures->edit('banking_account', $bankingAccount['id'], $bankingAccountParams);

        $this->fixtures->create('banking_account_statement_details',[
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $this->bankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626905',
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $this->flushCache();

        $this->ba->privateAuth();

        $this->app['config']->set('applications.banking_account_service.mock', true);
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
        $this->fixtures->on('live')->create('banking_account_statement_details',[
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $this->bankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626905',
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);
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

        $this->fixtures->edit('banking_account_statement_details', 'xbas0000000002', [
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

        $this->fixtures->edit('banking_account_statement_details', 'xbas0000000002', [
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

        $this->fixtures->edit('banking_account_statement_details', 'xbas0000000002', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ] );

        $summary = $this->makePayoutSummaryRequest();

        // Assert that there is a payout in queued state with amount 2000000.
        $this->assertEquals(1, $summary['bacc_xba00000000000'][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(2000000, $summary['bacc_xba00000000000'][Payout\Status::QUEUED]['low_balance']['total_amount']);

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

    public function testDispatchGatewayBalanceV2UpdateJobWhenBASDetailsInMaintenance()
    {
        Queue::fake();

        $this->setMockRazorxTreatment(['gateway_balance_fetch_v2' => 'on']);

        $basDetails = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
            $basDetails[Details\Entity::ID],
            [
                'status'                    => Details\Status::UNDER_MAINTENANCE,
                'account_number'            => '2224440041626905',
                'channel'                   => Details\Channel::RBL,
                'balance_last_fetched_at'   => Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
                'gateway_balance_change_at' => Carbon::now(Timezone::IST)->subHours(2)->getTimestamp()
            ]);

        $this->setupRblDispatchGatewayBalanceUpdateForMerchants();

        Queue::assertPushed(RblBankingAccountGatewayBalanceUpdate::class, 1);

        Queue::assertPushed(RblBankingAccountGatewayBalanceUpdate::class, function($job)
        {
            $this->assertEquals($job->getOriginProduct(), 'banking');

            return true;
        });
    }

    public function testDispatchGatewayBalanceV1UpdateJobWhenBASDetailsInMaintenance()
    {
        Queue::fake();

        $basDetails = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
            $basDetails[Details\Entity::ID],
            [
                'status'                    => Details\Status::UNDER_MAINTENANCE,
                'account_number'            => '2224440041626905',
                'channel'                   => Details\Channel::RBL,
                'balance_last_fetched_at'   => Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
                'gateway_balance_change_at' => Carbon::now(Timezone::IST)->subHours(2)->getTimestamp()
            ]);

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

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        $response = $this->setupRblDispatchGatewayBalanceUpdateForMerchants();

        /** @var BankingAccount\Entity $baAfterCronRuns */
        $baAfterCronRuns = $this->getDbEntityById('banking_account', 'xba00000000000');

        /** @var Details\Entity $baAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(50000, $basDetailsAfterCronRuns->getGatewayBalance());
        $this->assertNotNull($basDetailsAfterCronRuns->getBalanceLastFetchedAt());
        $this->assertNotNull($basDetailsAfterCronRuns->getGatewayBalanceChangeAt());
        $this->assertEquals(50000, $baAfterCronRuns->getGatewayBalance());
        $this->assertNotNull($baAfterCronRuns->getBalanceLastFetchedAt());
    }

    public function testProcessGatewayBalanceUpdateInDeleteMode()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE => True]);

        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertNull($basDetailsBeforeCronRuns->getGatewayBalance());
        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());
        $this->assertNull($basDetailsBeforeCronRuns->getGatewayBalanceChangeAt());

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        $response = $this->setupRblDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals($basDetailsBeforeCronRuns->toArray(), $basDetailsAfterCronRuns->toArray());
    }

    public function testDispatchGatewayBalanceUpdateJobForInvalidDirectChannel()
    {
        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testOnHoldPayoutCreateForFeatureNotEnabledAndDirectAccount()
    {
        $this->ba->privateAuth();

        $benebankConfig =
            [
                "BENEFICIARY" => [
                    "SBIN"    => [
                        "status" => "started",
                    ],
                    "RZPB"    => [
                        "status" => "started",
                    ],
                    "default" => "started",
                ]
            ];

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        $this->expectWebhookEvent('payout.queued');

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('on_hold', $payout['status']);
        $this->assertEquals('beneficiary_bank_down', $payout['queued_reason']);
    }

    public function testRblPayoutWithInvalidMode()
    {
        $this->startTest();
    }

    public function testCreateM2PPayoutForMerchantDirectAccountCardMode()
    {
        $balanceAttributes = [
            'balance' => 10000000,
            'balanceType' => 'direct',
            'channel' => 'rbl',
        ];

        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balanceAttributes["balance"],
            '10000000000000',
            $balanceAttributes["balanceType"] ,
            $balanceAttributes["channel"]
        );

        $virtualAccount = $this->fixtures->create('virtual_account');
        $secondBankAccount    = $this->fixtures->create(
            'bank_account',
            [
                'type'           => 'virtual_account',
                'entity_id'      => $virtualAccount->getId(),
                'account_number' => '2224440041626906',
                'ifsc_code'      => 'RAZRB000000',
            ]);

        $virtualAccount->bankAccount()->associate($secondBankAccount);
        $virtualAccount->balance()->associate($bankingBalance);
        $virtualAccount->save();

        $bankingBalance->setAccountNumber($virtualAccount->bankAccount->getAccountNumber());
        $bankingBalance->save();

        $this->fixtures->create('counter', [
            'account_type'          => 'direct',
            'balance_id'            => $bankingBalance->getId(),
            'free_payouts_consumed' => FreePayout::DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL_SLAB1,
        ]);

        $this->ba->privateAuth();

        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::DEBIT,
            'issuer'  => Issuer::YESB
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->mockCardVault(null, true, [
            'iin'          => '340169',
        ]);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutViaAmazonPayFromDirectAccount()
    {
        $contact = $this->getDbLastEntity('contact');

        $balanceAttributes = [
            'balance' => 10000000,
            'balanceType' => 'direct',
            'channel' => 'rbl',
        ];

        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balanceAttributes["balance"],
            '10000000000000',
            $balanceAttributes["balanceType"] ,
            $balanceAttributes["channel"]
        );

        $virtualAccount = $this->fixtures->create('virtual_account');
        $secondBankAccount    = $this->fixtures->create(
            'bank_account',
            [
                'type'           => 'virtual_account',
                'entity_id'      => $virtualAccount->getId(),
                'account_number' => '2224440041626906',
                'ifsc_code'      => 'RAZRB000000',
            ]);

        $virtualAccount->bankAccount()->associate($secondBankAccount);
        $virtualAccount->balance()->associate($bankingBalance);
        $virtualAccount->save();

        $bankingBalance->setAccountNumber($virtualAccount->bankAccount->getAccountNumber());
        $bankingBalance->save();

        $this->fixtures->create('counter', [
            'account_type'          => 'direct',
            'balance_id'            => $bankingBalance->getId(),
            'free_payouts_consumed' => FreePayout::DEFAULT_FREE_DIRECT_ACCOUNT_PAYOUTS_COUNT_RBL_SLAB1,
        ]);

        $this->ba->privateAuth();

        $this->fixtures->create('fund_account:wallet_account', [
            'id'            => '100000000003fa',
            'source_type'   => 'contact',
            'source_id'     => $contact->getId(),
        ]);

        $this->startTest();
    }

    public function testCreateRblPayoutToUpiWithoutFeatureEnabled()
    {
        $this->liveSetUpForRbl();

        $this->fixtures->on('live')->create(
            'vpa',
            [
                'id' => '10000000000vpa',
                'entity_type' => 'contact',
                'entity_id' => '1000001contact',
                'username' => 'test',
                'handle' => 'rzp',
                'merchant_id' => '10000000000000',
            ]
        );

        $this->fixtures->on('live')->create(
            'fund_account',
            [
                'id'           => '100000000002fa',
                'account_type' => 'vpa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_id'   => '10000000000vpa',
                'active'       => 1,
            ]);

        $counter = $this->getDbLastEntity('counter', 'live');

        $this->fixtures->on('live')->edit('counter', $counter->getId(), [
            'free_payouts_consumed' => 0,
        ]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateRblPayoutToUpiWithFeatureEnabledAndFreePayoutsAvailable()
    {
        $this->liveSetUpForRbl();

        $this->fixtures->on('live')->merchant->addFeatures([Features::RBL_CA_UPI]);

        $this->fixtures->on('live')->create(
            'vpa',
            [
                'id' => '10000000000vpa',
                'entity_type' => 'contact',
                'entity_id' => '1000001contact',
                'username' => 'test',
                'handle' => 'rzp',
                'merchant_id' => '10000000000000',
            ]
        );

        $this->fixtures->on('live')->create(
            'fund_account',
            [
                'id'           => '100000000002fa',
                'account_type' => 'vpa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_id'   => '10000000000vpa',
                'active'       => 1,
            ]);

        $counter = $this->getDbLastEntity('counter', 'live');

        $this->fixtures->on('live')->edit('counter', $counter->getId(), [
            'free_payouts_consumed' => 0,
        ]);

        // Reloading from DB as an updated happened above
        $freePayoutsConsumedBefore = $this->getDbLastEntity('counter', 'live')->getFreePayoutsConsumed();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $freePayoutsConsumedAfter = $this->getDbLastEntity('counter', 'live')->getFreePayoutsConsumed();

        $this->assertEquals($freePayoutsConsumedBefore + 1, $freePayoutsConsumedAfter);

        $payout = $this->getDbLastEntity('payout', 'live');

        $expectedStatus = (env('FTS_MOCK') === true) ? 'created' : 'initiated';

        $this->assertEquals('rbl', $payout->getChannel());
        $this->assertEquals($expectedStatus, $payout->getStatus());
        $this->assertEquals('UPI', $payout->getMode());
        $this->assertEquals('free_payout', $payout->getFeeType());
    }

    public function testCreateRblPayoutToUpiWithFeatureEnabledAndFreePayoutsUnavailable()
    {
        $this->liveSetUpForRbl();

        $this->fixtures->on('live')->merchant->addFeatures([Features::RBL_CA_UPI]);

        $this->fixtures->on('live')->create(
            'vpa',
            [
                'id' => '10000000000vpa',
                'entity_type' => 'contact',
                'entity_id' => '1000001contact',
                'username' => 'test',
                'handle' => 'rzp',
                'merchant_id' => '10000000000000',
            ]
        );

        $this->fixtures->on('live')->create(
            'fund_account',
            [
                'id'           => '100000000002fa',
                'account_type' => 'vpa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_id'   => '10000000000vpa',
                'active'       => 1,
            ]);

        $counter = $this->getDbLastEntity('counter', 'live');

        $freePayoutsConsumedBefore = $counter->getFreePayoutsConsumed();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

        $freePayoutsConsumedAfter = $this->getDbLastEntity('counter', 'live')->getFreePayoutsConsumed();

        $this->assertEquals($freePayoutsConsumedBefore, $freePayoutsConsumedAfter);

        $payout = $this->getDbLastEntity('payout', 'live');

        $expectedStatus = (env('FTS_MOCK') === true) ? 'created' : 'initiated';

        $this->assertEquals('rbl', $payout->getChannel());
        $this->assertEquals($expectedStatus, $payout->getStatus());
        $this->assertEquals('UPI', $payout->getMode());
        // The next assertion is to make sure that the fee type free_payout is NOT picked up
        $this->assertNull($payout->getFeeType());
    }

    public function testCreateRblPayoutToCardViaUpi()
    {
        $this->liveSetUpForRbl();

        $this->fixtures->on('live')->merchant->addFeatures([Features::RBL_CA_UPI, Features::S2S, Features::PAYOUT_TO_CARDS]);

        $this->mockRazorxTreatment();

        $this->mockCardVault(null, true, [
            'iin' => '378282',
            'name'=> 'Tester Test'
        ]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        // The card number is a sample Amex card number, as only Amex card supports payout via UPI
        $cardFundAccountCreateRequest = [
            'url'     => '/fund_accounts',
            'method'  => 'POST',
            'content' => [
                'contact_id'   => 'cont_1000001contact',
                'account_type' => 'card',
                'card'         => [
                    'number' => '378282246310005',
                    'name'   => 'Tester Test',
                ],
            ],
        ];

        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        $expectedStatus = (env('FTS_MOCK') === true) ? 'created' : 'initiated';

        $this->assertEquals('rbl', $payout->getChannel());
        $this->assertEquals($expectedStatus, $payout->getStatus());
        $this->assertEquals('UPI', $payout->getMode());
    }

    public function testBalanceFetchWithURL()
    {
        $this->app['rzp.mode'] = Mode::TEST;

        $mock = Mockery::mock(MozartService::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive('sendRawRequest')
             ->andReturnUsing(function(array $request) {

                 $requestData = json_decode($request['content'], true);

                 if ((array_key_exists('url',$requestData['entities']) === true) and
                     (array_key_exists('version', $requestData['entities']['url']) === true) and
                     ($requestData['entities']['url']['version'] === 'v2'))
                 {
                     $mockResponse =  $this->getMozartServiceSuccessResponse();
                 }
                 else
                 {
                     $mockResponse =  $this->getMozartServiceFailureResponse();
                 }

                 return json_encode($mockResponse);
             });

        $this->app->instance('mozart', $mock);

        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $this->setupRblDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertNotEquals(0, $basDetailsAfterCronRuns->getBalanceLastFetchedAt());

        $this->assertEquals(100, $basDetailsAfterCronRuns->getGatewayBalance());
    }

    public function testBalanceFetch()
    {
        $this->setMockRazorxTreatment(['gateway_balance_fetch_v2' => 'on']);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        $response = $this->setupRblDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetails */
        $basDetails = $this->getDbEntity('banking_account_statement_details',
                                                       ['account_number' => 2224440041626905]);

        $this->assertArrayHasKey(BankingAccount\Core::MADE_PAYOUT_RULE, $response);
        $this->assertArrayHasKey(BankingAccount\Core::BALANCE_CHANGE_RULE, $response);
        $this->assertArrayHasKey(BankingAccount\Core::MANDATORY_UPDATE_RULE, $response);

        $this->assertEmpty($response[BankingAccount\Core::MADE_PAYOUT_RULE]);
        $this->assertEmpty($response[BankingAccount\Core::BALANCE_CHANGE_RULE]);
        $this->assertEquals([$basDetails->getMerchantId()], $response[BankingAccount\Core::MANDATORY_UPDATE_RULE]);
    }

    public function testBalanceFetchWhenMerchantDoesPayout()
    {
        $this->testCreatePayoutWithFetchAndUpdateBalanceFromGatewayAndBalanceMoreThanPayoutAmount();

        $this->setMockRazorxTreatment(['gateway_balance_fetch_v2' => 'on']);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        $response = $this->setupRblDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetails */
        $basDetails = $this->getDbEntity('banking_account_statement_details',
                                         ['account_number' => 2224440041626905]);

        $this->assertArrayHasKey(BankingAccount\Core::MADE_PAYOUT_RULE, $response);
        $this->assertArrayHasKey(BankingAccount\Core::BALANCE_CHANGE_RULE, $response);
        $this->assertArrayHasKey(BankingAccount\Core::MANDATORY_UPDATE_RULE, $response);

        $this->assertEquals([$basDetails->getMerchantId()], $response[BankingAccount\Core::MADE_PAYOUT_RULE]);
        $this->assertEmpty($response[BankingAccount\Core::BALANCE_CHANGE_RULE]);
        $this->assertEmpty($response[BankingAccount\Core::MANDATORY_UPDATE_RULE]);
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

        $response = $this->setupRblDispatchGatewayBalanceUpdateForMerchants();

        $this->assertArrayHasKey(BankingAccount\Core::MADE_PAYOUT_RULE, $response);
        $this->assertArrayHasKey(BankingAccount\Core::BALANCE_CHANGE_RULE, $response);
        $this->assertArrayHasKey(BankingAccount\Core::MANDATORY_UPDATE_RULE, $response);

        $this->assertEmpty($response[BankingAccount\Core::MADE_PAYOUT_RULE]);
        $this->assertEquals([$basDetails->getMerchantId()], $response[BankingAccount\Core::BALANCE_CHANGE_RULE]);
        $this->assertEmpty($response[BankingAccount\Core::MANDATORY_UPDATE_RULE]);
    }

    protected function getMozartServiceSuccessResponse(int $amount = 1)
    {
        $response = [
            'data' => [
                'PayGenRes' => [
                    'Body' => [
                        'BalAmt' => [
                            'amountValue' => $amount
                        ]
                    ],
                    'Header' => [
                        'Corp_ID' => 'RAZORPAY',
                        'Error_Cde' => '',
                        'Error_Desc' => '',
                        'Status' => 'SUCCESS',
                        'TranID' => '1'
                    ]
                ]
            ],
            'error' => null,
            'external_trace_id' => '',
            'mozart_id' => 'bjt1l8jc1osqk0jtadrg',
            'next' => [],
            'success' => true
        ];

        return $response;
    }

    protected function getMozartServiceFailureResponse()
    {
        $response = [
            'data' => [
                'PayGenRes' => [
                    'Header' => [
                        'Corp_ID' => 'RAZORPAY',
                        'Error_Cde' => 'ER034',
                        'Error_Desc' => 'Request not valid for the given Account Number',
                        'Status' => 'FAILED',
                        'TranID' => 'A1'
                    ]
                ],
            ],
            'error' => [
                'description' => 'Request not valid for the given Account Number',
                'gateway_error_code' => 'ER034',
                'gateway_error_description' => 'Request not valid for the given Account Number',
                'gateway_status_code' => 200,
                'internal_error_code' => 'VALIDATION_ERROR'
            ],
            'external_trace_id' => '',
            'mozart_id' => 'bk1mej3c1osssas3oghg',
            'next' => [],
            'success' => false
        ];

        return $response;
    }

    public function testScheduledPayoutProcessingAutoRejectForRbl()
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

        $this->testData[__FUNCTION__] = $this->testData['testScheduledPayoutProcessingAutoReject'];

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

            $accountType = 'RBL Current Account';

            $this->assertEquals($accountType, $viewData[Balance\Entity::ACCOUNT_TYPE]);

            $mail->hasTo('naruto@gmail.com');
            $mail->hasFrom('no-reply@razorpay.com');
            $mail->hasReplyTo('no-reply@razorpay.com');

            return true;
        });
    }

    public function testODBalanceCheckForPayouts()
    {
        $this->fixtures->merchant->addFeatures([Features::REDUCE_OD_BALANCE_FOR_CA]);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_OD_BALANCE_CONFIGURED_FOR_MAGICBRICKS => 20000]);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        sleep(1);

        $this->startTest();

        $this->testData[__FUNCTION__]['request']['content']['amount'] = 40000;

        $this->testData[__FUNCTION__]['response']['content']['amount'] = 40000;

        $this->testData[__FUNCTION__]['response']['content']['status'] = 'queued';

        $this->startTest();
    }

    public function testODBalanceCheckForQueuedPayoutDispatch()
    {
        $this->fixtures->merchant->addFeatures([Features::REDUCE_OD_BALANCE_FOR_CA]);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_OD_BALANCE_CONFIGURED_FOR_MAGICBRICKS => 20000]);

        $oldDateTime = Carbon::create(2020, 01, 21, 12, 23, null, Timezone::IST);

        $this->fixtures->edit('banking_account', 'xba00000000000', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ]);

        $this->fixtures->edit('banking_account_statement_details', 'xbas0000000002', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ]);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        sleep(1);

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 40000,
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

        $payoutResponse = $this->makeRequestAndGetContent($request);

        $this->fixtures->edit('banking_account', 'xba00000000000', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ]);

        $this->fixtures->edit('banking_account_statement_details', 'xbas0000000002', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ]);

        $updatedPayout = $this->getDbEntityById('payout', $payoutResponse['id']);

        // assert that payout is queued due to low balance
        $this->assertEquals('queued', $updatedPayout->getStatus());

        // dispatch queued payouts for processing
        $dispatchResponse = $this->dispatchQueuedPayouts();

        $balanceId = $this->bankingBalance->getId();

        // assert that the balance id was picked up for queued payout processing
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $balanceId);

        $updatedPayout->reload();

        // Assert that the payout is still in queued state due to insufficient balance.
        $this->assertEquals('queued', $updatedPayout->getStatus());

        $this->fixtures->edit('banking_account', 'xba00000000000', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ]);

        $this->fixtures->edit('banking_account_statement_details', 'xbas0000000002', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ]);

        // Add enough balance to allow the payout to get processed
        $this->mockMozartResponseForFetchingBalanceFromRblGateway(700);

        $dispatchResponse = $this->dispatchQueuedPayouts();

        $balanceId = $this->bankingBalance->getId();

        $this->assertEquals($dispatchResponse['balance_id_list'][0], $balanceId);

        $updatedPayout->reload();

        // Assert that the payout was processed after adding sufficient balance
        $this->assertEquals('created', $updatedPayout->getStatus());
    }

    public function testPartnerBankOnHoldPayoutForDirectAccount()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::PARTNER_BANK_ON_HOLD_PAYOUT      => 'on']);

        $this->ba->privateAuth();

        $testDataDowntime = [
            "payload" => [
                "mode" => "IMPS",
                "account_type"=>"direct",
                "channel" => "RBL",
                "status" => "downtime",
                "include_merchants"=> ["ALL"],
                "exclude_merchants" => [],
            ]
        ];
        $this->setDowntimeInformationForOnHold($testDataDowntime);

        $this->startTest();

        $this->expectWebhookEvent('payout.queued');

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('on_hold', $payout['status']);
        $this->assertEquals(Payout\QueuedReasons::GATEWAY_DEGRADED, $payout['queued_reason']);

        // tear down
        $testDataDowntime['payload']['status'] = 'uptime';
        $this->setDowntimeInformationForOnHold($testDataDowntime);
    }

    public function testPartnerBankOnHoldPayoutForDirectAccountWithExcludeMerchant()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::PARTNER_BANK_ON_HOLD_PAYOUT      => 'on']);
        $this->ba->privateAuth();

        $testDataDowntime = [
            "payload" => [
                "mode" => "IMPS",
                "account_type"=>"direct",
                "channel" => "RBL",
                "status" => "downtime",
                "include_merchants"=> ["ALL"],
                "exclude_merchants" => ["10000000000000"],
            ]
        ];
        $this->setDowntimeInformationForOnHold($testDataDowntime);

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertNotEquals('on_hold', $payout['status']);
        $this->assertNotEquals(Payout\QueuedReasons::GATEWAY_DEGRADED, $payout['queued_reason']);

        // tear down
        $testDataDowntime['payload']['status'] = 'uptime';
        $this->setDowntimeInformationForOnHold($testDataDowntime);
    }

    public function testPartnerBankOnHoldPayoutForDirectAccountWithRazorxOff()
    {
        $this->ba->privateAuth();
        $testDataDowntime = [
            "payload" => [
                "mode" => "IMPS",
                "account_type"=>"direct",
                "channel" => "RBL",
                "status" => "downtime",
                "include_merchants"=> ["ALL"],
                "exclude_merchants" => [],
            ]
        ];
        $this->setDowntimeInformationForOnHold($testDataDowntime);

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertNotEquals('on_hold', $payout['status']);
        $this->assertNotEquals(Payout\QueuedReasons::GATEWAY_DEGRADED, $payout['queued_reason']);

        // tear down
        $testDataDowntime['payload']['status'] = 'uptime';
        $this->setDowntimeInformationForOnHold($testDataDowntime);
    }

    public function testProcessPartnerBankOnHoldPayoutForDirectAccount()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::PARTNER_BANK_ON_HOLD_PAYOUT      => 'on']);
        $this->ba->privateAuth();

        $testDataDowntime = [
            "payload" => [
                "mode" => "IMPS",
                "account_type"=>"direct",
                "channel" => "RBL",
                "status" => "downtime",
                "include_merchants"=> ["ALL"],
                "exclude_merchants" => [],
            ]
        ];
        $this->createOnHoldPayoutPartnerBankDown($testDataDowntime);

        $payout1 = $this->getDbLastEntity('payout');
        $ftaForPayout = $this->getDbEntities(
            'fund_transfer_attempt',
            [
                'source_id'   => $payout1['id'],
                'source_type' => 'payout',
            ]
        )->toArray();

        $this->assertNotEmpty($payout1['status_details_id']);
        $this->assertEquals(0, sizeof($ftaForPayout));
        $this->assertEquals('on_hold', $payout1['status']);
        $this->assertEquals(Payout\QueuedReasons::GATEWAY_DEGRADED, $payout1['queued_reason']);

        $this->createOnHoldPayoutPartnerBankDown($testDataDowntime);

        $payout2 = $this->getDbLastEntity('payout');
        $ftaForPayout = $this->getDbEntities(
            'fund_transfer_attempt',
            [
                'source_id'   => $payout2['id'],
                'source_type' => 'payout',
            ]
        )->toArray();

        $this->assertNotEmpty($payout2['status_details_id']);
        $this->assertEquals(0, sizeof($ftaForPayout));
        $this->assertEquals('on_hold', $payout2['status']);
        $this->assertEquals(Payout\QueuedReasons::GATEWAY_DEGRADED, $payout2['queued_reason']);

        $testDataDowntime['payload']['status'] = 'uptime';
        $this->setDowntimeInformationForOnHold($testDataDowntime);

        $this->ba->cronAuth();

        $this->startTest();

        $payout1 = $this->getDbEntityById('payout', $payout1['id'])->toArray();
        $ftaForPayout = $this->getDbEntities(
            'fund_transfer_attempt',
            [
                'source_id'   => $payout1['id'],
                'source_type' => 'payout',
            ]
        )->toArray();

        $this->assertNotEmpty($payout1['status_details_id']);
        $this->assertEquals(1, sizeof($ftaForPayout));
        $this->assertEquals($payout1['status'], Payout\Status::CREATED);

        $payout2 = $this->getDbEntityById('payout', $payout2['id'])->toArray();
        $ftaForPayout = $this->getDbEntities(
            'fund_transfer_attempt',
            [
                'source_id'   => $payout2['id'],
                'source_type' => 'payout',
            ]
        )->toArray();

        $this->assertNotEmpty($payout2['status_details_id']);
        $this->assertEquals(1, sizeof($ftaForPayout));
        $this->assertEquals($payout2['status'], Payout\Status::CREATED);

        // tear down
        $testDataDowntime['payload']['status'] = 'uptime';
        $this->setDowntimeInformationForOnHold($testDataDowntime);
    }

    public function testFailOnHoldPayoutsWhenSlaBreachedForDirectAccount()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::PARTNER_BANK_ON_HOLD_PAYOUT      => 'on']);
        $this->ba->privateAuth();

        $testDataDowntime = [
            "payload" => [
                "mode" => "IMPS",
                "account_type"=>"direct",
                "channel" => "RBL",
                "status" => "downtime",
                "include_merchants"=> ["ALL"],
                "exclude_merchants" => [],
            ]
        ];
        $this->createOnHoldPayoutPartnerBankDown($testDataDowntime);

        $payout1 = $this->getDbLastEntity('payout');
        $ftaForPayout = $this->getDbEntities(
            'fund_transfer_attempt',
            [
                'source_id'   => $payout1['id'],
                'source_type' => 'payout',
            ]
        )->toArray();

        $this->assertNotEmpty($payout1['status_details_id']);
        $this->assertEquals(0, sizeof($ftaForPayout));
        $this->assertEquals('on_hold', $payout1['status']);
        $this->assertEquals(Payout\QueuedReasons::GATEWAY_DEGRADED, $payout1['queued_reason']);

        $this->createOnHoldPayoutPartnerBankDown($testDataDowntime);

        $payout2 = $this->getDbLastEntity('payout');
        $ftaForPayout = $this->getDbEntities(
            'fund_transfer_attempt',
            [
                'source_id'   => $payout2['id'],
                'source_type' => 'payout',
            ]
        )->toArray();

        $this->assertNotEmpty($payout2['status_details_id']);
        $this->assertEquals(0, sizeof($ftaForPayout));
        $this->assertNotEmpty($payout2['status_details_id']);
        $this->assertEquals('on_hold', $payout2['status']);
        $this->assertEquals(Payout\QueuedReasons::GATEWAY_DEGRADED, $payout2['queued_reason']);

        $this->fixtures->edit('payout', $payout1['id'], ['on_hold_at' => strtotime(('-4000 seconds'), time())]);

        $this->fixtures->edit('payout', $payout2['id'], ['on_hold_at' => strtotime(('-4000 seconds'), time())]);

        $this->ba->cronAuth();

        $this->startTest();

        $payout1 = $this->getDbEntityById('payout', $payout1['id'])->toArrayPublic();
        $ftaForPayout = $this->getDbEntities(
            'fund_transfer_attempt',
            [
                'source_id'   => $payout1['id'],
                'source_type' => 'payout',
            ]
        )->toArray();

        $this->assertNotEmpty($payout1['status_details_id']);
        $this->assertEquals(0, sizeof($ftaForPayout));
        $this->assertEquals($payout1['status'], Payout\Status::FAILED);

        $payout2 = $this->getDbEntityById('payout', $payout2['id'])->toArray();
        $ftaForPayout = $this->getDbEntities(
            'fund_transfer_attempt',
            [
                'source_id'   => $payout2['id'],
                'source_type' => 'payout',
            ]
        )->toArray();

        $this->assertNotEmpty($payout2['status_details_id']);
        $this->assertEquals(0, sizeof($ftaForPayout));
        $this->assertEquals($payout2['status'], Payout\Status::FAILED);

        // tear down
        $testDataDowntime['payload']['status'] = 'uptime';
        $this->setDowntimeInformationForOnHold($testDataDowntime);
    }

    public function testProcessPartnerBankOnHoldPayoutAndMoveToBeneBankDowntime()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::PARTNER_BANK_ON_HOLD_PAYOUT      => 'on']);
        $this->ba->privateAuth();

        $testDataDowntime = [
            "payload" => [
                "mode" => "IMPS",
                "account_type"=>"direct",
                "channel" => "RBL",
                "status" => "downtime",
                "include_merchants"=> ["ALL"],
                "exclude_merchants" => [],
            ]
        ];
        $this->createOnHoldPayoutPartnerBankDown($testDataDowntime);

        $payout1 = $this->getDbLastEntity('payout');

        $this->assertEquals('on_hold', $payout1['status']);
        $this->assertEquals(Payout\QueuedReasons::GATEWAY_DEGRADED, $payout1['queued_reason']);

        $this->createOnHoldPayoutPartnerBankDown($testDataDowntime);

        $payout2 = $this->getDbLastEntity('payout');

        $this->assertEquals('on_hold', $payout2['status']);
        $this->assertEquals(Payout\QueuedReasons::GATEWAY_DEGRADED, $payout2['queued_reason']);

        $testDataDowntime['payload']['status'] = 'uptime';
        $this->setDowntimeInformationForOnHold($testDataDowntime);

        $benebankConfig =
            [
                "BENEFICIARY" =>
                    [
                        "SBIN" => [
                            "status" => "started",
                        ],
                        "RZPB" => [
                            "status" => "started",
                        ],
                        'HDFC' => [
                            'status' => "started"
                        ],
                    ]
            ];
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);


        $this->ba->cronAuth();

        $this->startTest();

        $payoutFinal1 = $this->getDbEntityById('payout', $payout1['id'])->toArray();
        $payoutFinal2 = $this->getDbEntityById('payout', $payout2['id'])->toArray();

        $this->assertEquals(Payout\Status::ON_HOLD, $payoutFinal1['status']);
        $this->assertEquals(Payout\QueuedReasons::BENE_BANK_DOWN, $payoutFinal1['queued_reason']);

        $this->assertEquals(Payout\Status::ON_HOLD, $payoutFinal2['status']);
        $this->assertEquals(Payout\QueuedReasons::BENE_BANK_DOWN, $payoutFinal2['queued_reason']);

        // tear down
        $testDataDowntime['payload']['status'] = 'uptime';
        $this->setDowntimeInformationForOnHold($testDataDowntime);
        $benebankConfigResolved =
            [
                "BENEFICIARY" =>
                    [
                        "SBIN" => [
                            "status" => "resolved",
                        ],
                        "RZPB" => [
                            "status" => "resolved",
                        ],
                        'HDFC' => [
                            'status' => "resolved"
                        ],
                    ]
            ];
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfigResolved]);

    }

    public function testDashboardSummaryWithPartnerBankOnHoldPayout()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::PARTNER_BANK_ON_HOLD_PAYOUT      => 'on']);

        $this->ba->privateAuth();

        $testDataDowntime = [
            "payload" => [
                "mode" => "IMPS",
                "account_type"=>"direct",
                "channel" => "RBL",
                "status" => "downtime",
                "include_merchants"=> ["ALL"],
                "exclude_merchants" => [],
            ]
        ];
        $this->createOnHoldPayoutPartnerBankDown($testDataDowntime);

        $merchantUser = $this->getDbEntity('merchant_user', ['role' => 'owner', 'product' => 'banking'], 'live')->toArray();

        $userId = $merchantUser['user_id'];

        $this->ba->proxyAuth('rzp_test_10000000000000', $userId);

        $completeSummary = $this->startTest();
        $this->assertEquals(10000000, $completeSummary['bacc_xba00000000000']['queued']['gateway_degraded']['balance']);
        $this->assertEquals(2000000, $completeSummary['bacc_xba00000000000']['queued']['gateway_degraded']['total_amount']);
        $this->assertEquals(1, $completeSummary['bacc_xba00000000000']['queued']['gateway_degraded']['count']);

        // tear down
        $testDataDowntime['payload']['status'] = 'uptime';
        $this->setDowntimeInformationForOnHold($testDataDowntime);
    }
}
