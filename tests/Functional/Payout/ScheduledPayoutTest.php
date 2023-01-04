<?php

namespace RZP\Tests\Functional\Payout;

use DB;
use Mail;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Pricing\Fee;
use RZP\Constants\Timezone;
use RZP\Models\Payout\Status;
use RZP\Models\Payout\Entity;
use RZP\Models\Merchant\Balance;
use RZP\Mail\Payout\FailedPayout;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Webhook\Event;
use RZP\Mail\Payout\AutoRejectedPayout;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;

class ScheduledPayoutTest extends TestCase
{
    use PayoutTrait;
    use WebhookTrait;
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

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $bankingAccountParams = [
            'id' => 'xba00000000000',
            'merchant_id' => '10000000000000',
            'account_ifsc' => 'RATN0000088',
            'account_number' => '2224440041626905',
            'status' => 'active',
            'channel' => 'yesbank',
            'balance_id' => $this->bankingBalance->getId(),
        ];

        $this->createBankingAccount($bankingAccountParams);

//        $this->flushCache();

        $this->mockStorkService();

        $this->ba->privateAuth();

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function liveSetUp()
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

        $this->setUpMerchantForBusinessBankingLive(true, 10000000);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        // Merchant needs to be activated to make live requests
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);
    }

    /**
     * Test creation of a scheduled payout
     */
    public function testCreateScheduledPayout()
    {
        // Timestamp of 9 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();
        $scheduledAtStartOfHour = Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST)->startOfHour()->getTimestamp();

        $testData = $this->testData['testCreateScheduledPayout'];

        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['otp']   = '0007';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['scheduled_at'] = $scheduledAtTime;

        $this->testData[__FUNCTION__] = $testData;

        $currentTime = Carbon::now(Timezone::IST);

        Carbon::setTestNow($currentTime);

        $this->ba->proxyAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals("MerchantUser01", $payout['user_id']);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals($scheduledAtStartOfHour, $payout['scheduled_at']);
        $this->assertEquals($this->bankingBalance['id'], $payout['balance_id']);
        $this->assertEquals($currentTime->getTimestamp(), $payout['scheduled_on']);

        // Assert that no FTA got created
        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $payout['id']]);
        $this->assertNull($fta);

        // Assert that no transaction got created
        $transaction = $this->getDbEntity('transaction', ['entity_id' => $payout['id']]);
        $this->assertNull($transaction);
    }

    /**
     * Test error when :
     *      1. scheduled_at time is before current time
     *      2. scheduled_at time is more than 3 months from now
     */
    public function testCreateScheduledPayoutInvalidTimeStamp()
    {
        // Any time before current time
        $pastTime = Carbon::now(Timezone::IST)->subMinutes(2)->getTimestamp();

        // Any time more than 3 months from now
        $futureTime = Carbon::now(Timezone::IST)->addMonths(3)->addMinutes(1)->getTimestamp();

        $testData = $this->testData['testCreateScheduledPayoutInvalidTimeStamp'];

        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['otp']   = '0007';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';

        $testData['request']['content']['scheduled_at'] = $pastTime;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();

        $this->startTest();

        $testData['request']['content']['scheduled_at'] = $futureTime;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     *  Test error when the scheduled_at timestamp is not between the allowed timestamps.
     */
    public function testCreateScheduledPayoutWhereTimeStampOutOfTimeSlot()
    {
        // Timestamp of 11 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(11)->addMonths(2)->getTimestamp();

        $testData = $this->testData['testCreateScheduledPayoutWhereTimeStampOutOfTimeSlot'];

        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['otp']   = '0007';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';

        $testData['request']['content']['scheduled_at'] = $scheduledAtTime;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Test error when merchant attempts scheduling payouts via API.
     */
    public function testCreateScheduledPayoutPrivateAuth()
    {
        // Timestamp of 9 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();

        $testData = $this->testData['testCreateScheduledPayoutPrivateAuth'];

        $testData['request']['content']['scheduled_at'] = $scheduledAtTime;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->privateAuth();

        $this->startTest();
    }

    /**
     * Test that payout gets scheduled after it is approved.
     */
    public function testScheduledPayoutCreationPostPayoutApproval()
    {
        // Timestamp of 9 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();

        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithOtpWithWorkflow(
            [
                'scheduled_at' => $scheduledAtTime
            ],
            'rzp_live_10000000000000', $this->ownerRoleUser->getId());

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $firstApprovalResponse = $this->startTest();

        // Validating first approval response
        $firstActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(2, $firstApprovalResponse['workflow_history']['current_level']);
        $this->assertEquals('pending', $firstApprovalResponse['status']);
        $this->assertEquals('Approving', $firstActionChecker['user_comment']);
        $this->assertEquals(true, $firstActionChecker['approved']);

        $this->app['config']->set('database.default', 'live');

        // Make Request to Approve pending payout for second level from Finance L3 role
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL3RoleUser->getId());
        $this->startTest();

        // Validating second approval response
        $secondActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals('Approving', $secondActionChecker['user_comment']);
        $this->assertEquals(true, $secondActionChecker['approved']);

        $payout = $this->getDbLastEntity('payout', 'live');

        // Assert
        $this->assertEquals(Status::SCHEDULED, $payout['status']);
        $this->assertEquals($this->bankingBalance['id'], $payout['balance_id']);

        $publicPayout = $payout->toArrayPublic();
        $this->assertEquals(2, $publicPayout['workflow_history']['current_level']);
    }

    /**
     * Test webhook during creation of a scheduled payout
     */
    public function testFiringOfWebhooksOnPayoutScheduled()
    {
        $this->markTestSkipped('Not keeping Scheduled Payouts Webhooks for now');

        Mail::fake();

        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $payoutScheduledEventTestDataKey = $this->testData['testFiringOfWebhooksOnPayoutScheduledEventData'];

        $this->mockServiceStorkRequest(
            function ($path, $payload) use ($payoutScheduledEventTestDataKey)
            {
                $this->assertContains($payload['event']['name'], ['payout.scheduled']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_SCHEDULED:
                        $this->validateStorkWebhookFireEvent('payout.scheduled', $payoutScheduledEventTestDataKey, $payload);
                        break;
                }

                return new \WpOrg\Requests\Response();
            })->times(1);

        // Timestamp of 9 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();
        $scheduledAtStartOfHour = Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST)->startOfHour()->getTimestamp();

        $testData = $this->testData['testFiringOfWebhooksOnPayoutScheduled'];

        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['otp']   = '0007';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['scheduled_at'] = $scheduledAtTime;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals("MerchantUser01", $payout['user_id']);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals($scheduledAtStartOfHour, $payout['scheduled_at']);
        $this->assertEquals($this->bankingBalance['id'], $payout['balance_id']);
        $this->assertEquals(Status::SCHEDULED, $payout['status']);

        // Assert that no FTA got created
        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $payout['id']]);
        $this->assertNull($fta);

        // Assert that no transaction got created
        $transaction = $this->getDbEntity('transaction', ['entity_id' => $payout['id']]);
        $this->assertNull($transaction);
    }

    /**
     * Test webhook during creation of a scheduled payout
     */
    public function testFiringOfWebhooksOnPayoutScheduledFromPending()
    {
        $this->markTestSkipped('Not keeping Scheduled Payouts Webhooks for now');

        Mail::fake();

        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $payoutScheduledEventTestDataKey = $this->testData['testFiringOfWebhooksOnPayoutScheduledEventData'];

        $payoutPendingEventTestDataKey = $this->testData['testFiringOfWebhooksOnPayoutScheduledFromPendingEventData'];

        $this->mockServiceStorkRequest(
            function ($path, $payload) use ($payoutScheduledEventTestDataKey, $payoutPendingEventTestDataKey)
            {
                $this->assertContains($payload['event']['name'], ['payout.scheduled', 'payout.pending']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_SCHEDULED:
                        $this->validateStorkWebhookFireEvent('payout.scheduled', $payoutScheduledEventTestDataKey, $payload, 'live');
                        break;

                    case Event::PAYOUT_PENDING:
                        $this->validateStorkWebhookFireEvent('payout.pending', $payoutPendingEventTestDataKey, $payload, 'live');
                        break;
                }

                return new \WpOrg\Requests\Response();
            })->times(2);

        // Timestamp of 9 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();

        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithOtpWithWorkflow(
            [
                'scheduled_at' => $scheduledAtTime
            ],
            'rzp_live_10000000000000', $this->ownerRoleUser->getId());

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';
        $testData['request']['content']['otp']   = '0007';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';

        $firstApprovalResponse = $this->startTest();

        // Validating first approval response
        $firstActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(2, $firstApprovalResponse['workflow_history']['current_level']);
        $this->assertEquals('pending', $firstApprovalResponse['status']);
        $this->assertEquals('Approving', $firstActionChecker['user_comment']);
        $this->assertEquals(true, $firstActionChecker['approved']);

        $this->app['config']->set('database.default', 'live');

        // Make Request to Approve pending payout for second level from Finance L3 role
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL3RoleUser->getId());
        $this->startTest();

        // Validating second approval response
        $secondActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals('Approving', $secondActionChecker['user_comment']);
        $this->assertEquals(true, $secondActionChecker['approved']);

        $payout = $this->getDbLastEntity('payout', 'live');

        // Assert
        $this->assertEquals(Status::SCHEDULED, $payout['status']);
        $this->assertEquals($this->bankingBalance['id'], $payout['balance_id']);

        $publicPayout = $payout->toArrayPublic();
        $this->assertEquals(2, $publicPayout['workflow_history']['current_level']);
    }

    public function testCancelScheduledPayout()
    {
        $this->testCreateScheduledPayout();

        $scheduledPayout = $this->getDbLastEntity('payout');

        $cancellationUser = $this->getDbEntityById('user', 'MerchantUser01')->toArrayPublic();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $scheduledPayout->getPublicId() . '/cancel';

        $testData['response']['content']['cancellation_user_id'] = 'MerchantUser01';
        $testData['response']['content']['cancellation_user'] = $cancellationUser;

        $this->ba->proxyAuth();

        $this->startTest();

        $cancelledPayout = $this->getDbLastEntity('payout');

        // Assert that payout got cancelled
        $this->assertEquals(Status::CANCELLED, $cancelledPayout['status']);
        $this->assertEquals($this->bankingBalance['id'], $cancelledPayout['balance_id']);

        // Assert that payout has the correct cancellation user id as well.
        $this->assertEquals('MerchantUser01', $cancelledPayout['cancellation_user_id']);
    }

    public function testCancelScheduledPayoutWithComments()
    {
        $this->testCreateScheduledPayout();

        $scheduledPayout = $this->getDbLastEntity('payout');

        $userComment = "Payout cancelled by Mehul";

        $cancellationUser = $this->getDbEntityById('user', 'MerchantUser01')->toArrayPublic();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $scheduledPayout->getPublicId() . '/cancel';
        $testData['request']['content']['remarks'] = $userComment;

        $testData['response']['content']['remarks'] = $userComment;
        $testData['response']['content']['cancellation_user_id'] = 'MerchantUser01';
        $testData['response']['content']['cancellation_user'] = $cancellationUser;

        $this->ba->proxyAuth();

        $this->startTest();

        $cancelledPayout = $this->getDbLastEntity('payout');

        // Assert that payout got cancelled with comments
        $this->assertEquals(Status::CANCELLED, $cancelledPayout['status']);
        $this->assertEquals($this->bankingBalance['id'], $cancelledPayout['balance_id']);
        $this->assertEquals($userComment, $cancelledPayout['remarks']);

        // Assert that payout has the correct cancellation user id as well.
        $this->assertEquals('MerchantUser01', $cancelledPayout['cancellation_user_id']);
    }

    public function testCancelScheduledPayoutPrivateAuth()
    {
        $this->testCreateScheduledPayout();

        $scheduledPayout = $this->getDbLastEntity('payout');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $scheduledPayout->getPublicId() . '/cancel';

        $this->ba->privateAuth();

        $this->startTest();

        $cancelledPayout = $this->getDbLastEntity('payout');

        // Assert that payout remains in scheduled state
        $this->assertEquals(Status::SCHEDULED, $cancelledPayout['status']);
        $this->assertEquals($this->bankingBalance['id'], $cancelledPayout['balance_id']);
    }

    /**
     * Test that payout cannot be approved after scheduledAt time has passed
     */
    public function testApproveScheduledPayoutAfterScheduledAtTime()
    {
        // Timestamp of 9 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();

        // 1 second after scheduledAtTime
        $futureTime = $scheduledAtTime +1;

        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithOtpWithWorkflow(
            [
                'scheduled_at' => $scheduledAtTime
            ],
            'rzp_live_10000000000000', $this->ownerRoleUser->getId());

        Carbon::setTestNow(Carbon::createFromTimestamp($futureTime, Timezone::IST));

        $firstApprovalResponse = $this->approvePayoutWithRole($payout['id'],
                                                              'rzp_live_10000000000000',
                                                              $this->ownerRoleUser->getId());

        // Validating first approval response
        $firstActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(2, $firstApprovalResponse['workflow_history']['current_level']);
        $this->assertEquals('pending', $firstApprovalResponse['status']);
        $this->assertEquals('Approving', $firstActionChecker['user_comment']);
        $this->assertEquals(true, $firstActionChecker['approved']);

        $this->app['config']->set('database.default', 'live');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        // Make Request to Approve pending payout for second level from Finance L3 role
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL3RoleUser->getId());
        $this->startTest();

        // Validating second approval response
        $secondActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals('Approving', $secondActionChecker['user_comment']);
        $this->assertEquals(true, $secondActionChecker['approved']);

        $payout = $this->getDbLastEntity('payout', 'live');

        // Assert that payout remains in pending state
        $this->assertEquals(Status::PENDING, $payout['status']);
        $this->assertEquals($this->bankingBalance['id'], $payout['balance_id']);
    }

    public function testCancelScheduledPayoutAfterScheduledAtTime()
    {
        $this->testCreateScheduledPayout();

        $scheduledPayout = $this->getDbLastEntity('payout');

        $scheduledAtTime = $scheduledPayout->getScheduledAt();

        // Setting current time equal to the scheduledAt time
        Carbon::setTestNow(Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST));

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $scheduledPayout->getPublicId() . '/cancel';

        $this->ba->proxyAuth();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        // Assert that payout remains in pending state
        $this->assertEquals(Status::SCHEDULED, $payout['status']);
        $this->assertEquals($this->bankingBalance['id'], $payout['balance_id']);
    }

    public function testRejectScheduledPayoutAfterScheduledAtTime()
    {
        // Timestamp of 9 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();

        // 1 second after scheduledAtTime
        $futureTime = $scheduledAtTime +1;

        $this->liveSetUp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithOtpWithWorkflow(
            [
                'scheduled_at' => $scheduledAtTime
            ],
            'rzp_live_10000000000000', $this->ownerRoleUser->getId());

        Carbon::setTestNow(Carbon::createFromTimestamp($futureTime, Timezone::IST));

        $this->app['config']->set('database.default', 'live');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/reject';

        // Make Request to reject pending payout for second level from Finance L3 role
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());
        $this->startTest();

        $payout = $this->getDbLastEntity('payout', 'live');

        // Assert that payout remains in pending state
        $this->assertEquals(Status::PENDING, $payout['status']);
        $this->assertEquals($this->bankingBalance['id'], $payout['balance_id']);
    }

    public function testScheduledPayoutProcessing()
    {
        // Timestamp of 9 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();
        $scheduledAtStartOfHour = Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST)->startOfHour()->getTimestamp();

        $this->createPayoutWithOtpWithWorkflow(
            [
                'scheduled_at' => $scheduledAtTime
            ],
            'rzp_test_10000000000000');

        $scheduledPayout = $this->getDbLastEntity('payout');

        $this->assertEquals(Status::SCHEDULED, $scheduledPayout['status']);

        $this->fixtures->edit('balance', $this->bankingBalance['id'], ['balance' => 100000000]);

        // Setting this to 1 second after the start of the time slot
        Carbon::setTestNow(Carbon::createFromTimestamp($scheduledAtStartOfHour+1, Timezone::IST));

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->bankingBalance);

        $this->ba->cronAuth();

        $result = $this->startTest();

        $expectedResponse = [
            $this->bankingBalance['id'] => [
                'total_payout_count'        => 1,
                'dispatched_payout_count'   => 1,
                'dispatched_payout_amount'  => 10000
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $result);

        $updatedScheduledPayout = $this->getDbEntityById('payout', $scheduledPayout['id'])->toArrayPublic();

        // Assert that the scheduled payout has now gone to the processing state
        $this->assertEquals(Status::PROCESSING, $updatedScheduledPayout['status']);
    }


    public function testScheduledPayoutProcessingWithNewCreditsFlow()
    {
        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'payout_credits_new_flow']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 2000 ]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditBalanceBefore = $creditBalanceEntity['balance'];

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 1900 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        // Timestamp of 9 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();
        $scheduledAtStartOfHour = Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST)->startOfHour()->getTimestamp();

        $this->createPayoutWithOtpWithWorkflow(
            [
                'scheduled_at' => $scheduledAtTime
            ],
            'rzp_test_10000000000000');

        $scheduledPayout = $this->getDbLastEntity('payout');

        $this->assertEquals(Status::SCHEDULED, $scheduledPayout['status']);

        $this->fixtures->edit('balance', $this->bankingBalance['id'], ['balance' => 100000000]);

        // Setting this to 1 second after the start of the time slot
        Carbon::setTestNow(Carbon::createFromTimestamp($scheduledAtStartOfHour+1, Timezone::IST));

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->bankingBalance);

        $this->ba->cronAuth();

        $result = $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $expectedResponse = [
            $this->bankingBalance['id'] => [
                'total_payout_count'        => 1,
                'dispatched_payout_count'   => 1,
                'dispatched_payout_amount'  => 10000
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $result);

        $updatedScheduledPayout = $this->getDbEntityById('payout', $scheduledPayout['id'])->toArrayPublic();

        // Assert that the scheduled payout has now gone to the processing state
        $this->assertEquals(Status::PROCESSING, $updatedScheduledPayout['status']);
    }

    public function testScheduledPayoutProcessingLowBalance($mode = 'test')
    {
        Mail::fake();

        // Timestamp of 9 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();
        $scheduledAtStartOfHour = Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST)->startOfHour()->getTimestamp();

        $this->createPayoutWithOtpWithWorkflow(
            [
                'scheduled_at' => $scheduledAtTime
            ],
            'rzp_'. $mode .'_10000000000000');

        $scheduledPayout = $this->getDbLastEntity('payout', $mode);

        $this->assertEquals(Status::SCHEDULED, $scheduledPayout['status']);

        $this->fixtures->on($mode)->edit('balance', $this->bankingBalance->getId(), ['balance' => 0]);

        // Setting this to 1 second after the start of the time slot
        Carbon::setTestNow(Carbon::createFromTimestamp($scheduledAtStartOfHour+1, Timezone::IST));

        $this->ba->cronAuth($mode);

        $result = $this->startTest();

        $expectedResponse = [
            $this->bankingBalance['id'] => [
                'total_payout_count'        => 1,
                'dispatched_payout_count'   => 1,
                'dispatched_payout_amount'  => 10000
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $result);

        $updatedScheduledPayout = $this->getDbEntityById('payout', $scheduledPayout['id'], $mode);

        $updatedScheduledPayoutArray = $updatedScheduledPayout->toArray();

        $id = $updatedScheduledPayout['id'];

        $payout = $updatedScheduledPayout;

        // Assert that the scheduled payout has now gone to the processing state
        $this->assertEquals(Status::FAILED, $updatedScheduledPayoutArray['status']);

        $this->assertEquals(ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING, $updatedScheduledPayoutArray['status_code']);

        Mail::assertQueued(FailedPayout::class, function($mail) use ($id, $payout) {
            $mail->build();

            $formattedScheduledFor = $payout->getFormattedScheduledFor();
            $formattedAmount = $payout->getFormattedAmount();

            $this->assertEquals($mail->subject, "Scheduled Payout <pout_" . $id ."> for " .
                                                $formattedScheduledFor . " worth " .
                                                $formattedAmount . " is failed");

            $viewData = $mail->viewData;
            $this->assertEquals("100", $viewData[Entity::AMOUNT][1]);
            $this->assertEquals("00", $viewData[Entity::AMOUNT][2]);
            $this->assertEquals("pout_" . $id, $viewData[Entity::PAYOUT_ID]);
            $this->assertEquals($formattedScheduledFor, $viewData["scheduled_for"]);
            $this->assertEquals($payout->balance->getAccountNumber(), $viewData["account_no"]);

            $accountType = 'RazorpayX account';

            $this->assertEquals($accountType, $viewData[Balance\Entity::ACCOUNT_TYPE]);

            $mail->hasTo('naruto@gmail.com');
            $mail->hasFrom('no-reply@razorpay.com');
            $mail->hasReplyTo('no-reply@razorpay.com');

            return true;
        });

        return $updatedScheduledPayout;
    }

    public function testScheduledPayoutProcessingAutoReject()
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
            $this->assertEquals("100", $viewData[Entity::AMOUNT][1]);
            $this->assertEquals("00", $viewData[Entity::AMOUNT][2]);
            $this->assertEquals("pout_" . $id, $viewData[Entity::PAYOUT_ID]);
            $this->assertEquals($formattedScheduledFor, $viewData["scheduled_for"]);
            $this->assertEquals($payout->balance->getAccountNumber(), $viewData["account_no"]);

            $accountType = 'RazorpayX account';

            $this->assertEquals($accountType, $viewData[Balance\Entity::ACCOUNT_TYPE]);

            $mail->hasTo('naruto@gmail.com');
            $mail->hasFrom('no-reply@razorpay.com');
            $mail->hasReplyTo('no-reply@razorpay.com');

            return true;
        });
    }

    public function testScheduledPayoutProcessingAutoRejectWithWfs()
    {
        Mail::fake();

        // Timestamp of 9 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();
        $scheduledAtStartOfHour = Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST)->startOfHour()->getTimestamp();

        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

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

        // Assert that the scheduled payout has now gone to the processing state
        $this->assertEquals(Status::REJECTED, $updatedScheduledPayout['status']);

        Mail::assertQueued(AutoRejectedPayout::class);
    }

    public function testGetScheduleTimeSlotsForDashboard()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBulkScheduledPayouts()
    {
        // Hard-coding current time as May 1, we are passing epoch of July 4th 9AM (randomly chosen) as scheduled_at
        Carbon::setTestNow(Carbon::create(2020,05,01));

        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        $testData = & $this->testData[__FUNCTION__];

        // append headers
        $testData['request']['server'] = $headers;

        $this->startTest();
    }

    public function testScheduledPayoutSummary()
    {
        $juneFirstTime = Carbon::create(2020, 06, 01, 0,0,0, Timezone::IST);
        $juneFirst9AmTime = Carbon::create(2020, 06,01,9,0,0, Timezone::IST);
        $juneSecond9AmTime = Carbon::create(2020, 06,02,9,0,0, Timezone::IST);
        $juneFifth9AmTime = Carbon::create(2020, 06,05,9,0,0, Timezone::IST);
        $juneTwenty9AmTime = Carbon::create(2020, 06,20,9,0,0, Timezone::IST);

        Carbon::setTestNow($juneFirstTime);

        $this->liveSetUp();

        $queuedPayoutAttribute = [
            'queue_if_low_balance'  => 1,
            'amount'                => 1000000000
        ];

        // Will show up in today's scheduled payouts
        $scheduledPayoutAttribute1 = [
            'scheduled_at'  => $juneFirst9AmTime->getTimestamp(),
            'amount'        => 1000,
            'queue_if_low_balance'  => 0,
        ];

        // Will show up in next 2 day's scheduled payouts
        $scheduledPayoutAttribute2 = [
            'scheduled_at'  => $juneSecond9AmTime->getTimestamp(),
            'amount'        => 2000
        ];

        // Will show up in next 7 day's scheduled payouts
        $scheduledPayoutAttribute3 = [
            'scheduled_at'  => $juneFifth9AmTime->getTimestamp(),
            'amount'        => 3000
        ];

        // Will show up in next 30 day's scheduled payouts
        $scheduledPayoutAttribute4 = [
            'scheduled_at'  => $juneTwenty9AmTime->getTimestamp(),
            'amount'        => 4000
        ];

        // Create a queued payout
        $this->createQueuedPendingOrScheduledPayoutWithOtp($queuedPayoutAttribute, 'rzp_live_10000000000000');

        // Create 4 scheduled payouts
        $this->createQueuedPendingOrScheduledPayoutWithOtp($scheduledPayoutAttribute1, 'rzp_live_10000000000000');
        $this->createQueuedPendingOrScheduledPayoutWithOtp($scheduledPayoutAttribute2, 'rzp_live_10000000000000');
        $this->createQueuedPendingOrScheduledPayoutWithOtp($scheduledPayoutAttribute3, 'rzp_live_10000000000000');
        $this->createQueuedPendingOrScheduledPayoutWithOtp($scheduledPayoutAttribute4, 'rzp_live_10000000000000');

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        // Creating a scheduled payout that will go to pending state but should show up in today's scheduled payouts
        $this->createPayoutWithOtpWithWorkflow(
            [
                'scheduled_at' => $juneFirst9AmTime->getTimestamp(),
                'amount'       => 1000
            ],
            'rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $responseFromSummaryAPI = $this->startTest();

        $expectedResponse = [
            'bacc_1000000lcustba' =>  [
                // Only 1 queued payout
                 'queued' =>  [
                     'low_balance'=> [
                     'balance' =>  10000000,
                     'count' => 1,
                     'total_amount' =>  1000000000,
                     'total_fees' =>  0,
                      ],
                ],
                // Only 1 pending payout
                'pending' =>  [
                    'count' =>  1,
                    'total_amount' =>  1000,
                ],
                'scheduled' =>  [
                    // 1 scheduled payout and 1 pending payout (both amount 1000 each)
                    'today' =>  [
                        'balance' =>  10000000,
                        'count' =>  2,
                        'total_amount' =>  2000,
                        'total_fees' =>  0,
                    ],
                    // Only 1 scheduled payout in next 2 days (amount 2000)
                    'next_two_days' =>  [
                        'balance' =>  10000000,
                        'count' =>  1,
                        'total_amount' =>  2000,
                        'total_fees' =>  0,
                    ],
                    // 2 scheduled payout in next 7 days (amount 2000 and 3000 each)
                    'next_week' =>  [
                        'balance' =>  10000000,
                        'count' =>  2,
                        'total_amount' =>  5000,
                        'total_fees' =>  0,
                    ],
                    // 3 scheduled payout in next 7 days (amount 2000, 3000 and 4000 each)
                    'next_month' =>  [
                        'balance' =>  10000000,
                        'count' =>  3,
                        'total_amount' =>  9000,
                        'total_fees' =>  0,
                    ],
                    'all_time' =>  [
                        'balance' =>  10000000,
                        'count' =>  5,
                        'total_amount' =>  11000,
                        'total_fees' =>  0,
                    ],
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $responseFromSummaryAPI);
    }

    public function testProcessBulkScheduledPayouts()
    {
        // setTestNow is called inside this function and it sets current date as May 1.
        // We are scheduling the two bulk payouts for July 4 (randomly chosen)
        $this->testBulkScheduledPayouts();

        $scheduledAtTime = Carbon::create(2020, 07,04,9,30,00, Timezone::IST)->getTimestamp();
        $scheduledAtStartOfHour = Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST)->startOfHour()->getTimestamp();

        $bulkPayouts = $this->getDbEntities('payout')->toArray();

        $bulkScheduledPayout1Id = $bulkPayouts[0]['id'];
        $bulkScheduledPayout2Id = $bulkPayouts[1]['id'];

        $this->createPayoutWithOtpWithWorkflow(
            [
                'scheduled_at' => $scheduledAtTime
            ],
            'rzp_test_10000000000000');

        $scheduledPayout = $this->getDbLastEntity('payout')->toArray();

        $scheduledPayoutId = $scheduledPayout['id'];

        // Setting this to 1 second after the start of the time slot
        Carbon::setTestNow(Carbon::createFromTimestamp($scheduledAtStartOfHour+1, Timezone::IST));

        $this->ba->cronAuth();

        $this->startTest();

        $bulkScheduledPayout1 = $this->getDbEntityById('payout', $bulkScheduledPayout1Id)->toArray();
        $bulkScheduledPayout2 = $this->getDbEntityById('payout', $bulkScheduledPayout2Id)->toArray();
        $scheduledPayout1 = $this->getDbEntityById('payout', $scheduledPayoutId)->toArray();

        $this->assertEquals('batch_submitted', $bulkScheduledPayout1['status']);
        $this->assertEquals('batch_submitted', $bulkScheduledPayout2['status']);
        $this->assertEquals('created', $scheduledPayout1['status']);
    }

    public function testCreateScheduledPayoutWithFreePayoutsRemaining()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $this->testCreateScheduledPayout();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        $payout = $this->getDbLastEntity('payout');

        // Assert that the free payout was not consumed.
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        // Assert that null is assigned as fee_type.
        $this->assertEquals(null, $payout->getFeeType());
    }

    public function testScheduledPayoutCreationAndNoIncrementOfCounter()
    {
        $this->liveSetUp();

        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId, null, 'live');

        // Timestamp of 9 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $payout = $this->createPayoutWithOtpWithWorkflow(
            [
                'scheduled_at' => $scheduledAtTime
            ],
            'rzp_live_10000000000000', $this->ownerRoleUser->getId());

        // Approve with Owner role user
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payouts/' . $payout['id'] . '/approve';

        $firstApprovalResponse = $this->startTest();

        // Validating first approval response
        $firstActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals(2, $firstApprovalResponse['workflow_history']['current_level']);
        $this->assertEquals('pending', $firstApprovalResponse['status']);
        $this->assertEquals('Approving', $firstActionChecker['user_comment']);
        $this->assertEquals(true, $firstActionChecker['approved']);

        $this->app['config']->set('database.default', 'live');

        // Make Request to Approve pending payout for second level from Finance L3 role
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL3RoleUser->getId());
        $this->startTest();

        // Validating second approval response
        $secondActionChecker = $this->getDbLastEntity('action_checker', 'live');
        $this->assertEquals('Approving', $secondActionChecker['user_comment']);
        $this->assertEquals(true, $secondActionChecker['approved']);

        $payout = $this->getDbLastEntity('payout', 'live');

        // Assert
        $this->assertEquals(Status::SCHEDULED, $payout['status']);
        $this->assertEquals($this->bankingBalance['id'], $payout['balance_id']);

        $publicPayout = $payout->toArrayPublic();
        $this->assertEquals(2, $publicPayout['workflow_history']['current_level']);

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ],
                                        'live')->first();

        // Assert that the free payout was not consumed
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        // Assert that null is assigned as fee_type
        $this->assertEquals(null, $payout->getFeeType());
    }

    public function testProcessingOfFreeScheduledPayout()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        // Timestamp of 9 AM, 2 months from current time
        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();
        $scheduledAtStartOfHour = Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST)->startOfHour()->getTimestamp();

        $this->createPayoutWithOtpWithWorkflow(
            [
                'scheduled_at' => $scheduledAtTime
            ],
            'rzp_test_10000000000000');

        $scheduledPayout = $this->getDbLastEntity('payout');

        $this->assertEquals(Status::SCHEDULED, $scheduledPayout['status']);

        $this->fixtures->edit('balance', $this->bankingBalance['id'], ['balance' => 100000000]);

        // Setting this to 1 second after the start of the time slot
        Carbon::setTestNow(Carbon::createFromTimestamp($scheduledAtStartOfHour+1, Timezone::IST));

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__] = $this->testData['testScheduledPayoutProcessing'];

        $result = $this->startTest();

        $expectedResponse = [
            $this->bankingBalance['id'] => [
                'total_payout_count'        => 1,
                'dispatched_payout_count'   => 1,
                'dispatched_payout_amount'  => 10000
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $result);

        $updatedScheduledPayout = $this->getDbEntityById('payout', $scheduledPayout['id'])->toArrayPublic();

        // Assert that the scheduled payout has now gone to the processing state
        $this->assertEquals(Status::PROCESSING, $updatedScheduledPayout['status']);

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        $payout = $this->getDbLastEntity('payout');

        // Assert that the free payout was consumed.
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        // Assert that free_payout is assigned as fee_type after failing the payout
        $this->assertEquals(Entity::FREE_PAYOUT, $payout->getFeeType());
    }

    public function testFreePayout()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $this->testProcessBulkScheduledPayouts();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that the free payout was consumed.
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());
    }

    /**
     * Check for internal payout webhook error response processed in scheduled payout cron
     * and fails due to low balance
     *
     */
    public function testFailedWebhookPayoutResponseForNewBankingError()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        // When WebhookViaStork experiment is turned on, webhook setting is skipped and
        // stork is called regardless event setting is enabled or not
        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $payoutFailedEventData = $this->testData[__FUNCTION__];
        $payloadFailed = null;

        $this->mockServiceStorkRequest(
            function ($path, $payload) use ($payoutFailedEventData, & $payloadFailed) {
                $this->assertContains($payload['event']['name'], ['payout.failed']);
                switch ($payload['event']['name']) {
                    case Event::PAYOUT_FAILED:
                        $payloadFailed = $payload;
                        break;
                }

                return new \WpOrg\Requests\Response();
            });

        $payout = $this->testScheduledPayoutProcessingLowBalance();

        $this->app->events->dispatch('api.payout.failed', [$payout]);

        $this->validateStorkWebhookFireEvent('payout.failed', $payoutFailedEventData, $payloadFailed);
    }

    /**
     * Check for internal payout webhook error response processed in scheduled payout cron
     * and fails due to low balance on live mode
     *
     */
    public function testFailedWebhookPayoutResponseForNewBankingErrorOnLiveMode()
    {
        $this->liveSetUp();

        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        // When WebhookViaStork experiment is turned on, webhook setting is skipped and
        // stork is called regardless event setting is enabled or not
        $this->mockRazorxTreatment('yesbank', 'on', 'on');

        $payoutFailedEventData = $this->testData[__FUNCTION__];
        $payloadFailed = null;

        $this->mockServiceStorkRequest(
            function ($path, $payload) use ($payoutFailedEventData, & $payloadFailed) {
                $this->assertContains($payload['event']['name'], ['payout.failed']);
                switch ($payload['event']['name']) {
                    case Event::PAYOUT_FAILED:
                        $payloadFailed = $payload;
                        break;
                }

                return new \WpOrg\Requests\Response();
            });

        $payout = $this->testScheduledPayoutProcessingLowBalance('live');

        $this->app->events->dispatch('api.payout.failed', [$payout]);

        $this->validateStorkWebhookFireEvent('payout.failed', $payoutFailedEventData, $payloadFailed, 'live');
    }

    public function testCreateScheduledPayoutAndCheckCorrectWebhooksFired()
    {
        $this->dontExpectWebhookEvent('payout.initiated');

        $this->testCreateScheduledPayout();
    }

    public function testScheduledToOnHoldAndProcessing()
    {
        $this->fixtures->create('feature', [
            'name' => Feature\Constants::PAYOUTS_ON_HOLD,
            'entity_id' => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();
        $scheduledAtStartOfHour = Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST)->startOfHour()->getTimestamp();

        $this->createPayoutWithOtpWithWorkflow(
            [
                'scheduled_at' => $scheduledAtTime,
                'mode' => 'IMPS',
            ],
            'rzp_test_10000000000000');

        $scheduledPayout = $this->getDbLastEntity('payout');

        $this->assertEquals(Status::SCHEDULED, $scheduledPayout['status']);

        $counter = $this->getDbEntities('counter',
            [
                'account_type' => 'shared',
                'balance_id'   => $balanceId,
            ])->first();

        // Assert that zero free payout has been consumed when payout is in scheduled state
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        $this->fixtures->edit('balance', $this->bankingBalance['id'], ['balance' => 100000000]);

        // Setting this to 1 second after the start of the time slot
        Carbon::setTestNow(Carbon::createFromTimestamp($scheduledAtStartOfHour + 1, Timezone::IST));

        $benebankConfig =
            [
                "BENEFICIARY" => [
                    "SBIN" => [
                        "status" => "started",
                    ],
                    "RZPB" => [
                        "status" => "started",
                    ],
                    "default" => "started",
                ]
            ];

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT => $benebankConfig]);

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__] = $this->testData['testScheduledPayoutProcessing'];

        $this->startTest();

        $updatedScheduledPayout = $this->getDbEntityById('payout', $scheduledPayout['id'])->toArray();

        $counter = $this->getDbEntities('counter',
            [
                'account_type' => 'shared',
                'balance_id'   => $balanceId,
            ])->first();

        // Assert that zero free payout has been consumed when payout moved from scheduled to on_hold
        $this->assertEquals(0, $counter->getFreePayoutsConsumed());

        // Assert that the scheduled payout has now gone to the on_hold state
        $this->assertEquals(Status::ON_HOLD, $updatedScheduledPayout['status']);
        $this->assertNotNull( $updatedScheduledPayout['on_hold_at']);
    }

    public function testScheduledPayoutProcessingInLedgerReverseShadowMode()
    {
        $this->app['config']->set('applications.ledger.enabled', false);
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->testScheduledPayoutProcessing();
    }
}
