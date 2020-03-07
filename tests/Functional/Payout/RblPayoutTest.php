<?php

namespace RZP\Tests\Functional\Payout;

use Queue;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Constants\Timezone;
use RZP\Services\Mock\Mozart;
use RZP\Models\BankingAccount;
use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccount\Gateway\Rbl;
use RZP\Jobs\BankingAccountGatewayBalanceUpdate;
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

    public function setUp()
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

        $this->app['cache']->flush();

        $this->ba->privateAuth();
    }

    protected function mockMozartResponseForFetchingBalanceFromRblGateway($amount): void
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
        ] );

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(50000);

        $dispatchResponse = $this->dispatchQueuedPayouts();

        $balanceId = $this->bankingBalance->getId();

        $expectedResponse = [
            $balanceId => [
                'original_balance'         => 5000000,
                'balance_remaining'        => 3000000,
                'total_payout_count'       => 1,
                'dispatched_payout_count'  => 1,
                'dispatched_payout_amount' => 2000000,
            ]
        ];

        $this->assertArraySelectiveEquals($dispatchResponse, $expectedResponse);
    }

    protected function setupRblDispatchGatewayBalanceUpdateForMerchants()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT => 1]);

        $request = [
            'method'  => 'put',
            'url'     => '/banking_accounts/gateway/rbl/balance',
        ];

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function testDispatchGatewayBalanceUpdateJob()
    {
        Queue::fake();

        $this->setupRblDispatchGatewayBalanceUpdateForMerchants();

        Queue::assertPushed(BankingAccountGatewayBalanceUpdate::class, 1);
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
}
