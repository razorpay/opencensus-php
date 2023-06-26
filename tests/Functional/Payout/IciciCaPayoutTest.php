<?php

namespace Functional\Payout;

use Mail;
use Queue;
use Mockery;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Models\Payout;
use RZP\Models\Feature;
use RZP\Models\Schedule;
use RZP\Models\Card\Type;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Models\FeeRecovery;
use Rzp\Models\FundTransfer;
use RZP\Services\Mock\Mozart;
use RZP\Models\Payout\Status;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Balance;
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Mode as EnvMode;
use RZP\Models\Settlement\Channel;
Use RZP\Models\FundTransfer\Attempt;
use RZP\Mail\Payout\AutoRejectedPayout;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\Mock\BankingAccountService;
use RZP\Models\BankingAccount\Gateway\Icici;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\BankingAccount\Gateway\Fields;
use RZP\Models\Feature\Constants as Features;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Jobs\IciciBankingAccountGatewayBalanceUpdate;
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

    protected $merchant;

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

   //2fa approval flow first attempt with otp
    public function testCreatingPendingPayoutsAndApprovalWithOtp()
    {
        $this->testPayoutCreateWithIcici2FaSuccess();

        $payout = $this->getDbLastEntity('payout');

        // Approve with Owner role user
        $this->ba->proxyAuth();

        $testData = &$this->testData[__FUNCTION__];
        $testData['request']['content']['payout_id'] = 'pout_'.$payout['id'];

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $settings = $this->getDbLastEntity('settings');

        $publicResponse = $payout->toArrayPublic();

        $this->assertEquals('pending_on_otp', $payout['internal_status']);
        $this->assertEquals('pending', $publicResponse['status']);
        $this->assertEquals($payout['id'], $fta['source_id']);
        $this->assertEquals('abc123pqr', $settings['value']);
    }

    //Retry flow for payout approval once invalid or expired otp is entered.
    public function testCreatingPendingPayoutsAndRetryApprovalWithOtp()
    {
        $this->testPayoutCreateWithIcici2FaSuccess();

        $payout = $this->getDbLastEntity('payout');

        // Approve with Owner role user
        $this->ba->proxyAuth();

        $testData = &$this->testData[__FUNCTION__];
        $testData['request']['content']['payout_id'] = 'pout_'.$payout['id'];

        $this->startTest();

        $oldPayout = $this->getDbLastEntity('payout');

        $oldFta = $this->getDbLastEntity('fund_transfer_attempt');

        $publicResponse = $oldPayout->toArrayPublic();

        $settings = $this->getDbLastEntity('settings');

        $this->assertEquals('pending_on_otp', $oldPayout['internal_status']);
        $this->assertEquals('pending', $publicResponse['status']);
        $this->assertEquals($payout['id'], $oldFta['source_id']);

        //Moving payout back to pending assuming that it was an invalid otp and reattempting
        $this->fixtures->edit(
            'payout',
            $oldPayout->getId(),
            [
                'status' => 'pending',
                'status_code' => 'INVALID_OTP',
            ]);

        $this->startTest();

        $newPayout = $this->getDbLastEntity('payout');

        $newFta = $this->getDbLastEntity('fund_transfer_attempt');

        $publicResponseNew = $newPayout->toArrayPublic();

        $this->assertEquals('pending_on_otp', $newPayout['internal_status']);
        $this->assertEquals('pending', $publicResponseNew['status']);
        $this->assertEquals($oldFta['id'], $newFta['id']);
        $this->assertEquals($oldPayout['id'], $newPayout['id']);
        $this->assertEquals('abc123pqr', $settings['value']);
    }

    //Reattempt for approval when otp is already submitted and payout is in pending_on_otp state
    public function testRetryApprovalWhenOtpIsAlreadySubmitted()
    {
        $this->testCreatingPendingPayoutsAndApprovalWithOtp();

        $payout = $this->getDbLastEntity('payout');

        $this->ba->proxyAuth();

        $testData = &$this->testData[__FUNCTION__];
        $testData['request']['content']['payout_id'] = 'pout_'.$payout['id'];

        $this->startTest();

    }

    public function testInitiatedWebhookForIcici2FAWithIncorrectOtp()
    {
        $this->testCreatingPendingPayoutsAndApprovalWithOtp();

        $payout = $this->getDbLastEntity('payout');

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
                'is_fts'      => true,
            ])->first();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();

        $this->startTest();

        $payout->reload();

        $ftaForPayout->reload();

        $settings = $this->getDbLastEntity('settings');

        $this->assertEquals('pending', $payout->getStatus());

        $this->assertNull($payout->getPricingRuleId());

        $this->assertEquals('abc123pqr', $settings['value']);
    }

    public function testInitiatedWebhookForIcici2FAWithEmptyBankStatusCode()
    {
        $this->testCreatingPendingPayoutsAndApprovalWithOtp();

        $payout = $this->getDbLastEntity('payout');

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
                'is_fts'      => true,
            ])->first();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();

        $this->startTest();

        $payout->reload();

        $ftaForPayout->reload();

        $settings = $this->getDbLastEntity('settings');

        $this->assertEquals('pending_on_otp', $payout->getStatus());

        $this->assertNull($payout->getPricingRuleId());

        $this->assertEquals('abc123pqr', $settings['value']);
    }

    public function testProcessedWebhookWithoutInitiatedForIcici2FAPayout()
    {
        $this->testCreatingPendingPayoutsAndApprovalWithOtp();

        $payout = $this->getDbLastEntity('payout');

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
                'is_fts'      => true,
            ])->first();

        $this->setUpCounterAndFreePayoutsCount('direct', $payout->getBalanceId(), 'icici');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();

        $this->startTest();

        $payout->reload();

        $ftaForPayout->reload();

        $settings = $this->getDbLastEntity('settings');

        $this->assertEquals('processed', $payout->getStatus());

        $this->assertNotNull($payout->getInitiatedAt());

        // Assert that free_payout is assigned as fee_type for such payouts.
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout->getFeeType());

        $this->assertNotNull($payout->getPricingRuleId());

        $counter = $this->getDbEntities('counter',
            [
                'account_type' => 'direct',
                'balance_id'   => $payout->getBalanceId(),
            ])->first();

        // Assert that one free payout has been consumed
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        $this->assertNull($settings['value']);
    }

    public function testProcessedWebhookAfterInitiatedForIcici2FAPayout()
    {
        $this->testCreatingPendingPayoutsAndApprovalWithOtp();

        $payout = $this->getDbLastEntity('payout');

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
                'is_fts'      => true,
            ])->first();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();

        $payloadForInitiatedWebhook = $testData['request']['content'];

        $payloadForInitiatedWebhook['bank_status_code'] = 'success';

        $payloadForInitiatedWebhook['status'] = 'INITIATED';

        $request = [
            'method'  => $testData['request']['method'],
            'url'     => $testData['request']['url'],
            'content' => $payloadForInitiatedWebhook,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->startTest();

        $payout->reload();

        $ftaForPayout->reload();

        $feeRecovery = $this->getDbLastEntity('fee_recovery');

        $settings = $this->getDbLastEntity('settings');

        $this->assertEquals($payout['id'], $feeRecovery->getEntityId());

        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecovery->getStatus());

        $this->assertEquals(0, $feeRecovery->getAttemptNumber());

        $this->assertNull($feeRecovery['recovery_payout_id']);

        $this->assertEquals('processed', $payout->getStatus());

        $this->assertNotNull($payout->getInitiatedAt());

        $this->assertNotNull($payout->getPricingRuleId());

        $this->assertNull($settings['value']);
    }

    public function testFailedWebhookWithoutInitiatedForIcici2FAPayout()
    {
        $this->testCreatingPendingPayoutsAndApprovalWithOtp();

        $payout = $this->getDbLastEntity('payout');

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
                'is_fts'      => true,
            ])->first();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();

        $this->startTest();

        $payout->reload();

        $ftaForPayout->reload();

        $feeRecovery = $this->getDbLastEntity('fee_recovery');

        $settings = $this->getDbLastEntity('settings');

        $this->assertEquals($payout['id'], $feeRecovery->getEntityId());

        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecovery->getStatus());

        $this->assertEquals(0, $feeRecovery->getAttemptNumber());

        $this->assertNull($feeRecovery['recovery_payout_id']);

        $this->assertEquals('failed', $payout->getStatus());

        $this->assertNotNull($payout->getInitiatedAt());

        $this->assertNotNull($payout->getPricingRuleId());

        $this->assertNull($settings['value']);
    }

    public function testFailedWebhookWithInitiatedForIcici2FAPayout()
    {
        $this->testCreatingPendingPayoutsAndApprovalWithOtp();

        $payout = $this->getDbLastEntity('payout');

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
                'is_fts'      => true,
            ])->first();

        $this->ba->ftsAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $testData['request']['content']['status'] = 'INITIATED';

        $testData['request']['content']['bank_status_code'] = 'INVALID_OTP';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $this->fixtures->edit(
            'payout',
            $payout->getId(),
            [
                'status' => 'pending_on_otp',
                'status_code' => 'INVALID_OTP',
            ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $testData['request']['content']['status'] = 'INITIATED';

        $testData['request']['content']['bank_status_code'] = '';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $testData['request']['content']['status'] = 'FAILED';

        $testData['request']['content']['bank_status_code'] = 'OTP_RETRIES_EXHAUSTED';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $payout->reload();

        $ftaForPayout->reload();

        $feeRecovery = $this->getDbLastEntity('fee_recovery');

        $settings = $this->getDbLastEntity('settings');

        $this->assertEquals($payout['id'], $feeRecovery->getEntityId());

        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecovery->getStatus());

        $this->assertEquals(0, $feeRecovery->getAttemptNumber());

        $this->assertNull($feeRecovery['recovery_payout_id']);

        $this->assertEquals('failed', $payout->getStatus());

        $this->assertNotNull($payout->getInitiatedAt());

        $this->assertNotNull($payout->getPricingRuleId());

        $this->assertNull($settings['value']);
    }

    public function testFailedWebhookWithoutInitiatedForIcici2FAPayoutProcessType1()
    {
        $this->testCreatingPendingPayoutsAndApprovalWithOtp();

        $payout = $this->getDbLastEntity('payout');

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
                'is_fts'      => true,
            ])->first();

        (new AdminService)->setConfigKeys([ConfigKey::RX_ICICI_2FA_WEBHOOK_PROCESS_TYPE => 1]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();

        $this->startTest();

        $payout->reload();

        $ftaForPayout->reload();

        $feeRecovery = $this->getDbLastEntity('fee_recovery');

        $settings = $this->getDbLastEntity('settings');

        $this->assertEquals($payout['id'], $feeRecovery->getEntityId());

        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecovery->getStatus());

        $this->assertEquals(0, $feeRecovery->getAttemptNumber());

        $this->assertNull($feeRecovery['recovery_payout_id']);

        $this->assertEquals('failed', $payout->getStatus());

        $this->assertNotNull($payout->getInitiatedAt());

        $this->assertNotNull($payout->getPricingRuleId());

        $this->assertNull($settings['value']);
    }

    public function testFailedWebhookWithoutInitiatedForIcici2FAPayoutProcessType2()
    {
        $this->testCreatingPendingPayoutsAndApprovalWithOtp();

        $payout = $this->getDbLastEntity('payout');

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
                'is_fts'      => true,
            ])->first();

        (new AdminService)->setConfigKeys([ConfigKey::RX_ICICI_2FA_WEBHOOK_PROCESS_TYPE => 2]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();

        $this->startTest();

        $payout->reload();

        $ftaForPayout->reload();

        $feeRecovery = $this->getDbLastEntity('fee_recovery');

        $settings = $this->getDbLastEntity('settings');

        $this->assertEquals($payout['id'], $feeRecovery->getEntityId());

        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecovery->getStatus());

        $this->assertEquals(0, $feeRecovery->getAttemptNumber());

        $this->assertNull($feeRecovery['recovery_payout_id']);

        $this->assertEquals('failed', $payout->getStatus());

        $this->assertNotNull($payout->getInitiatedAt());

        $this->assertNotNull($payout->getPricingRuleId());

        $this->assertNull($settings['value']);
    }

    public function testReversedWebhookWithoutInitiatedForIcici2FAPayout()
    {
        $this->testCreatingPendingPayoutsAndApprovalWithOtp();

        $payout = $this->getDbLastEntity('payout');

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
                'is_fts'      => true,
            ])->first();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['source_id'] = $payout->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->ftsAuth();

        $this->startTest();

        $payout->reload();

        $ftaForPayout->reload();

        $reversal = $this->getDbLastEntity('reversal');

        $feeRecovery = $this->getDbLastEntity('fee_recovery');

        $settings = $this->getDbLastEntity('settings');

        $this->assertEquals($reversal['id'], $feeRecovery->getEntityId());

        $this->assertEquals($reversal['entity_id'], $payout['id']);

        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecovery->getStatus());

        $this->assertEquals(0, $feeRecovery->getAttemptNumber());

        $this->assertNull($feeRecovery['recovery_payout_id']);

        $this->assertEquals('reversed', $payout->getStatus());

        $this->assertNotNull($payout->getInitiatedAt());

        $this->assertNotNull($payout->getPricingRuleId());

        $this->assertNull($settings['value']);
    }

    public function testFeeRecoveryPayoutCronForIcici2FAFeatureEnabledMechant()
    {
        $oldTime = Carbon::create(2020, 1, 3, null, null, null);

        Carbon::setTestNow($oldTime);

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->bankingBalance);

        $this->setupScheduleAndScheduleTaskForMerchant();

        $oldTimeStamp = $oldTime->getTimestamp();

        // Create first payout
        $this->testCreateFeeRecoveryAtPayoutCreationForICICIPayouts();

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

        $this->createRzpFeesContactAndFundAccountForIcici();

        $newTime = Carbon::create(2020, 1, 10, null, null, null);

        Carbon::setTestNow($newTime);

        $this->ba->cronAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->processFeeRecoveryCron();

        $feeRecoveryPayout = $this->getDbLastEntity('payout');

        $this->assertNotNull($feeRecoveryPayout[Payout\Entity::PRICING_RULE_ID]);
        $this->assertNotNull($feeRecoveryPayout[Payout\Entity::FEES]);
        $this->assertNotNull($feeRecoveryPayout[Payout\Entity::TAX]);

        // Moving this payout to initiated
        $feeRecoveryPayout->setStatus(Payout\Status::INITIATED);
        $feeRecoveryPayout->saveOrFail();

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

    public function testIciciAccountStatementFetchV2()
    {
        $setDate = Carbon::create(2016, 6, 17, 10, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        $this->ba->cronAuth();

        $request = [
            'url'       => '/banking_account_statement/process/icici',
            'method'    => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ];

        $this->flushCache();

        (new AdminService)->setConfigKeys([ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT => 1]);

        (new AdminService)->setConfigKeys([ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ["2224440041626905"]]);

        Queue::fake();

        $this->makeRequestAndGetContent($request);

        Queue::assertPushed(IciciBankingAccountStatementJob::class, 1);

        Carbon::setTestNow();
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
        $this->fixtures->on('live')->create('banking_account_statement_details',[
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $this->bankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626905',
            Details\Entity::CHANNEL        => Details\Channel::ICICI,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $this->app['config']->set('applications.banking_account_service.mock', true);
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

    public function testProcessGatewayBalanceUpdateInDeleteMode()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::ICICI_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE => True]);

        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                       ['account_number' => 2224440041626905]);

        $this->assertNull($basDetailsBeforeCronRuns->getGatewayBalance());
        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());
        $this->assertNull($basDetailsBeforeCronRuns->getGatewayBalanceChangeAt());

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        $response = $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

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

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(null, $exception);

        $response = $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsAfterCronRuns->getBalanceLastFetchedAt());
    }

    public function testCreatePayout($testData = [])
    {
        $this->ba->privateAuth();

        $attributes = [
            'bas_business_id'   => '10000000000000',
            'merchant_id'       => '10000000000000',
        ];

        $this->fixtures->create('merchant_detail', $attributes);

        $this->startTest($testData);

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

    public function testCreatePayoutImps()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals('icici', $payout['channel']);
        $this->assertEquals('processing', $payout['status']);
        $this->assertEquals('IMPS', $payout['mode']);

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

    public function testCreatePayoutRtgs()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals('icici', $payout['channel']);
        $this->assertEquals('processing', $payout['status']);
        $this->assertEquals('RTGS', $payout['mode']);

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

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway($gatewayBalance);

        $this->makeRequestAndGetContent($request);

        return [$payout1['id'], $payout2['id']];
    }

    protected function createContact()
    {
        $contact = $this->fixtures->create(
            'contact',
            [
                'id'        => '1010101contact',
                'email'     => 'rzp@rzp.com',
                'contact'   => '8989898989',
                'name'      => 'desiboi',
            ]
        );

        return $contact;
    }

    protected function createRzpFeesContactAndFundAccountForIcici()
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
                                                                       'ICIC0000047',
                                                                       '12345678903833');
    }

    protected function createFundAccountForContact($contact, $ifsc = 'ICIC0000047', $accountNumber = '111000111000')
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
            'amount'                => 10000,
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

    public function testQueuedPayout()
    {
        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(500);

        $firstQueuedPayoutAttributes = [
            'account_number'       => '2224440041626905',
            'amount'               => 20000099,
            'queue_if_low_balance' => 1,
        ];

        $this->createQueuedOrPendingPayout($firstQueuedPayoutAttributes);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('queued', $payout['status']);
        $this->assertEquals('icici', $payout['channel']);

        $bankingBalance = $this->getDbLastEntity('balance');

        $this->fixtures->balance->edit($bankingBalance['id'], ['balance' => 21000000]);

        $this->dispatchQueuedPayouts();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('created', $payout['status']);
        $this->assertEquals('icici', $payout['channel']);
    }

    public function testQueuedPayoutByFetchingBalanceFromGatewayBalance()
    {
        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(500);

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
        $this->assertEquals('icici', $payout['channel']);

        $this->fixtures->edit('banking_account_statement_details','xbas0000000002',[
            'gateway_balance'         => 500000000
        ]);

        $this->dispatchQueuedPayouts();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('created', $payout['status']);
        $this->assertEquals('icici', $payout['channel']);
    }

    public function testQueuedPayoutWithFetchAndUpdateBalanceFromGateway()
    {
        $oldDateTime = Carbon::create(2020, 01, 21, 12, 23, null, Timezone::IST);

        $this->fixtures->edit('banking_account_statement_details', 'xbas0000000002', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ] );

        $this->fixtures->edit('balance', $this->bankingBalance->getId(), [
            'updated_at' => $oldDateTime->getTimestamp(),
        ] );

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(500);

        sleep(1);

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
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

        $this->ba->privateAuth();

        $this->makeRequestAndGetContent($request);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('queued', $payout['status']);

        $this->fixtures->edit('banking_account_statement_details', 'xbas0000000002', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ] );

        $this->fixtures->edit('balance', $this->bankingBalance->getId(), [
            'updated_at' => $oldDateTime->getTimestamp(),
        ] );

        // Add enough balance to allow the payout to get processed
        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(3000000);

        $this->dispatchQueuedPayouts();

        $payout = $this->getDbLastEntity('payout');
        $this->assertEquals('created', $payout['status']);
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
                'channel'      => 'icici'
            ])->first();

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('direct', $balanceId, 'icici');

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

    protected function initiatePayoutFromCreated()
    {
        $payout = $this->getDbLastEntity('payout');

        if ($payout->getStatus() === Payout\Status::CREATED)
        {
            $payout->setStatus(Payout\Status::INITIATED);

            $payout->saveOrFail();
        }
    }

    public function testCreateFeeRecoveryAtPayoutCreationForICICIPayouts()
    {
        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(500);

        $this->ba->privateAuth();

        $this->createPayout($this->bankingBalance);

        $payout = $this->getDbLastEntity('payout')->toArray();

        $feeRecovery = $this->getDbLastEntity('fee_recovery')->toArray();

        $this->assertEquals($payout['id'], $feeRecovery['entity_id']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecovery['status']);
        $this->assertEquals(0, $feeRecovery['attempt_number']);
        $this->assertNull($feeRecovery['recovery_payout_id']);
    }

    public function testFeeRecoveryPayoutCronForICICI()
    {
        $oldTime = Carbon::create(2020, 1, 3, null, null, null);

        Carbon::setTestNow($oldTime);

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->bankingBalance);

        $this->setupScheduleAndScheduleTaskForMerchant();

        $oldTimeStamp = $oldTime->getTimestamp();

        // Create first payout
        $this->testCreateFeeRecoveryAtPayoutCreationForICICIPayouts();

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

        $this->createRzpFeesContactAndFundAccountForIcici();

        $newTime = Carbon::create(2020, 1, 10, null, null, null);

        Carbon::setTestNow($newTime);

        $this->ba->cronAuth();

        $this->processFeeRecoveryCron();

        $feeRecoveryPayout = $this->getDbLastEntity('payout');

        // Moving this payout to initiated
        $feeRecoveryPayout->setStatus(Payout\Status::INITIATED);
        $feeRecoveryPayout->saveOrFail();

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

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(500);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreatePayoutWithFetchAndUpdateBalanceFromGatewayAndBalanceMoreThanPayoutAmount()
    {
        $oldDateTime = Carbon::create(2020, 01, 21, 12, 23, null, Timezone::IST);

        $this->fixtures->edit('banking_account_statement_details', 'xbas0000000002', [
            'balance_last_fetched_at' => $oldDateTime->getTimestamp(),
        ] );

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(50000);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testDashboardSummary()
    {
        $this->liveSetUp();

        $this->fixtures->on('live');

        $this->ownerRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'owner','live');

        // Create two queued payouts

        $firstQueuedPayoutAttributes = [
            'account_number'        =>  '2224440041626905',
            'amount'                =>  20000099,
            'queue_if_low_balance'  =>  1,
        ];

        $this->createQueuedOrPendingPayout($firstQueuedPayoutAttributes, 'rzp_live_TheLiveAuthKey');

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->assertEquals('queued', $payout['status']);
        $this->assertEquals('icici', $payout['channel']);

        $merchantUser = $this->getDbEntity('merchant_user',['role' => 'owner','product' => 'banking'],'live')->toArray();

        $userId = $merchantUser['user_id'];

        $this->ba->proxyAuth('rzp_live_10000000000000',$userId);

        $completeSummary = $this->startTest();

        $balanceId = $this->bankingBalance->getId();

        $bankingAccountId = app('banking_account_service')->fetchBankingAccountId($balanceId);

        $this->assertEquals(1, $completeSummary[$bankingAccountId]['queued']['low_balance']['count']);
        $this->assertEquals(20000099, $completeSummary[$bankingAccountId]['queued']['low_balance']['total_amount']);

        // assertions will break as summary API is not handled for ICICI CA
        // https://razorpay.slack.com/archives/C01CV2HQMEV/p1621860081056900
    }

    public function testPayoutToAmexCardWithSupportedIssuerSupportedMode()
    {
        $this->markTestSkipped();

        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::AMEX],
            'type'    => Type::CREDIT,
            'issuer'  => Issuer::SCBL
        ]);

        $fundAccountRequest = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                "account_type" => "card",
                "contact_id"   => "cont_1000001contact",
                "card"         => [
                    "name"         => "Prashanth YV",
                    "number"       => "340169570990137",
                    "cvv"          => "2126",
                    "expiry_month" => 10,
                    "expiry_year"  => 29,
                ]
            ]
        ];

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

        $this->ba->privateAuth();

        $fundAccount = $this->makeRequestAndGetContent($fundAccountRequest);

        $this->assertEquals(Issuer::SCBL, $fundAccount['card']['issuer']);
        $this->assertEquals(Network::$fullName[Network::AMEX], $fundAccount['card']['network']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id']  = $fundAccount['id'];
        $testData['response']['content']['fund_account_id'] = $fundAccount['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('icici', $payout['channel']);
    }

    public function testCreateM2PPayoutForMerchantDirectAccountCardMode()
    {
        $this->markTestSkipped();

        $contact = $this->getDbLastEntity('contact');

        $this->ba->privateAuth();

        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::DEBIT,
            'issuer'  => Issuer::YESB
        ]);

        $fundAccountRequest = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                "account_type" => "card",
                "contact_id"   => "cont_" . $contact["id"],
                "card"         => [
                    "name"         => "Prashanth YV",
                    "number"       => "340169570990137",
                    "cvv"          => "212",
                    "expiry_month" => 10,
                    "expiry_year"  => 29,
                ]
            ]
        ];

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

        $this->ba->privateAuth();

        $fundAccount = $this->makeRequestAndGetContent($fundAccountRequest);

        $this->assertEquals(Issuer::YESB, $fundAccount['card']['issuer']);
        $this->assertEquals(Network::$fullName[Network::MC], $fundAccount['card']['network']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id']  = $fundAccount['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
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
                    "address" => 'icici@upi',
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
                'channel'      => 'icici'
            ])->first();

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('direct', $balanceId, 'icici');

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
                'channel'      => 'icici'
            ])->first();

        $balanceId = $balance->getId();

        $this->setUpCounterAndFreePayoutsCount('direct', $balanceId, 'icici');

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


    public function testPayoutCreateWithIcici2FaMerchantNotAllowedFeatureNotEnabled()
    {
        $this->ba->proxyAuth();

        $this->liveSetUp();

        $this->startTest();
    }

    public function testPayoutCreateWithIcici2FaInvalidPayoutPayload()
    {
        $this->ba->proxyAuth();

        $this->liveSetUp();

        $this->startTest();
    }

    public function testPayoutCreateWithIcici2FaSuccess()
    {
        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('pending', $payout->getStatus());
    }

    public function testPayoutCreateWithIcici2FaSuccessInternalRoute()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('pending', $payout->getStatus());
    }

    public function testPayoutCreateWithIcici2FaSuccessInternalRouteWithIdempotency()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('pending', $payout->getStatus());

        $response = $this->startTest();

        $this->assertEquals($payout->getPublicId(), $response['id']);
    }

    public function testPayout2faOtpSend()
    {
        $this->ba->proxyAuth();

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->fixtures->on('test')->create('payout', [
            'id'              => 'FUj82QLoJgRcM0',
            'merchant_id'     => $this->merchant->getId(),
            'fund_account_id' => '100000000000fa',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'amount'          => 100,
            'balance_id'      => $this->bankingBalance->getId(),
            'status'          => 'pending'
        ]);

        $this->startTest();
    }

    public function testPayout2faOtpSendInvalidPayload()
    {
        $this->ba->proxyAuth();

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->fixtures->on('test')->create('payout', [
            'id'              => 'FUj82QLoJgRcM0',
            'merchant_id'     => $this->merchant->getId(),
            'fund_account_id' => '100000000000fa',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'amount'          => 100,
            'balance_id'      => $this->bankingBalance->getId(),
            'status'          => 'created'
        ]);

        $this->startTest();
    }

    public function testPayout2faOtpSendPayoutNotInPendingState()
    {
        $this->ba->proxyAuth();

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->fixtures->on('test')->create('payout', [
            'id'              => 'FUj82QLoJgRcM0',
            'merchant_id'     => $this->merchant->getId(),
            'fund_account_id' => '100000000000fa',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'amount'          => 100,
            'balance_id'      => $this->bankingBalance->getId(),
            'status'          => 'created'
        ]);

        $this->startTest();
    }

    public function testPayout2faOtpSendMerchantNotEnabled()
    {
        $this->ba->proxyAuth();

        $this->fixtures->on('test')->create('payout', [
            'id' => 'FUj82QLoJgRcM0',
            'merchant_id' => $this->merchant->getId(),
            'fund_account_id' => '100000000000fa',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'amount' => 100,
            'balance_id' => $this->bankingBalance->getId(),
            'status' => 'created'
        ]);

        $this->startTest();
    }

    public function testGetPayoutByIdForIciciCa2faPayoutPendingStatusForInvalidOtp()
    {
        $this->ba->proxyAuth();

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->fixtures->on('test')->create('payout', [
            'id'              => 'FUj82QLoJgRcM0',
            'merchant_id'     => $this->merchant->getId(),
            'fund_account_id' => '100000000000fa',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'amount'          => 100,
            'balance_id'      => $this->bankingBalance->getId(),
            'status'          => 'pending',
            'status_code'     => 'INVALID_OTP',
        ]);

        $request = array(
            'url'    => '/payouts/pout_' . 'FUj82QLoJgRcM0',
            'method' => 'GET');

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals('The OTP entered is incorrect. Request for a new OTP.', $content['pending_reason']);
        $this->assertEquals('pending', $content['status']);
        $this->assertEquals('pending', $content['internal_status']);
    }

    public function testGetPayoutByIdForIciciCa2faPayoutPendingStatusForExpiredOtp()
    {
        $this->ba->proxyAuth();

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->fixtures->on('test')->create('payout', [
            'id'              => 'FUj82QLoJgRcM0',
            'merchant_id'     => $this->merchant->getId(),
            'fund_account_id' => '100000000000fa',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'amount'          => 100,
            'balance_id'      => $this->bankingBalance->getId(),
            'status'          => 'pending',
            'status_code'     => 'EXPIRED_OTP',
        ]);

        $request = array(
            'url'    => '/payouts/pout_' . 'FUj82QLoJgRcM0',
            'method' => 'GET');

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals('The OTP has expired. Request for a new OTP.', $content['pending_reason']);
        $this->assertEquals('pending', $content['status']);
        $this->assertEquals('pending', $content['internal_status']);
    }

    public function testGetPayoutByIdForIciciCa2faPayoutPendingOnOtpStatus()
    {
        $this->ba->proxyAuth();

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->fixtures->on('test')->create('payout', [
            'id'              => 'FUj82QLoJgRcM0',
            'merchant_id'     => $this->merchant->getId(),
            'fund_account_id' => '100000000000fa',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'amount'          => 100,
            'balance_id'      => $this->bankingBalance->getId(),
            'status'          => 'pending_on_otp',
            'status_code'     => null,
        ]);

        $request = array(
            'url'    => '/payouts/pout_' . 'FUj82QLoJgRcM0',
            'method' => 'GET');

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayNotHasKey('pending_reason', $content);
        $this->assertEquals('pending', $content['status']);
        $this->assertEquals('pending_on_otp', $content['internal_status']);
    }

    public function testGetPayoutByIdForIciciCa2faPayoutInitiatedStatus()
    {
        $this->ba->proxyAuth();

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->fixtures->on('test')->create('payout', [
            'id'              => 'FUj82QLoJgRcM0',
            'merchant_id'     => $this->merchant->getId(),
            'fund_account_id' => '100000000000fa',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'amount'          => 100,
            'balance_id'      => $this->bankingBalance->getId(),
            'status'          => 'initiated',
            'status_code'     => null,
        ]);

        $request = array(
            'url'    => '/payouts/pout_' . 'FUj82QLoJgRcM0',
            'method' => 'GET');

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals('processing', $content['status']);
        $this->assertArrayNotHasKey('pending_reason', $content);
        $this->assertArrayNotHasKey('internal_status', $content);
    }

    public function testGetPayoutByIdForIciciCa2faPayoutMerchantNotEnabled()
    {
        $this->ba->proxyAuth();

        $this->fixtures->on('test')->create('payout', [
            'id'              => 'FUj82QLoJgRcM0',
            'merchant_id'     => $this->merchant->getId(),
            'fund_account_id' => '100000000000fa',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'amount'          => 100,
            'balance_id'      => $this->bankingBalance->getId(),
            'status'          => 'pending_on_otp',
            'status_code'     => null,
        ]);

        $request = array(
            'url'    => '/payouts/pout_' . 'FUj82QLoJgRcM0',
            'method' => 'GET');

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals('pending', $content['status']);
        $this->assertArrayNotHasKey('pending_reason', $content);
        $this->assertArrayNotHasKey('internal_status', $content);
    }

    public function testGetPayoutByIdForIciciCa2faPayoutPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->fixtures->on('test')->create('payout', [
            'id'              => 'FUj82QLoJgRcM0',
            'merchant_id'     => $this->merchant->getId(),
            'fund_account_id' => '100000000000fa',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'amount'          => 100,
            'balance_id'      => $this->bankingBalance->getId(),
            'status'          => 'pending_on_otp',
            'status_code'     => null,
        ]);

        $request = array(
            'url'    => '/payouts/pout_' . 'FUj82QLoJgRcM0',
            'method' => 'GET');

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals('pending', $content['status']);
        $this->assertArrayNotHasKey('pending_reason', $content);
        $this->assertArrayNotHasKey('internal_status', $content);
    }

    public function testPayoutCreateForIciciCaApiPayoutMerchantEnabled()
    {
        $this->ba->privateAuth();

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->startTest();
    }

    public function testPayoutCreateForIciciCaApiPayoutMerchantNotEnabled()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testBlockBulkPayoutsForIcici2faWhenMerchantEnabled()
    {
        $this->ba->batchAuth();

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBlockBulkPayoutsForIcici2faWhenMerchantDisabled()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'     => 'C0zv9I46W4wiOq',
            'HTTP_X_Creator_Type' => 'user',
            'HTTP_X_Creator_Id'   => 'MerchantUser01'
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testIcici2faPayoutByCapitalCollectionsApp()
    {
        $this->ba->capitalCollectionsAuth();

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $contact = $this->fixtures->create('contact',
            [
                'name' => 'test name',
                'type' => \RZP\Models\Contact\Type::CAPITAL_COLLECTIONS_INTERNAL_CONTACT
            ]);

        $fundAccount = $this->fixtures->fund_account->createBankAccount(
            [
                'source_type' => 'contact',
                'source_id'   => $contact->getId(),
            ],
            [
                'name'           => 'test',
                'ifsc'           => 'SBIN0007105',
                'account_number' => '111000',
            ]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $fundAccount->getPublicId();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertNotNull($payout[Payout\Entity::PRICING_RULE_ID]);
    }

    public function testIcici2faPayoutByVendorPaymentsApp()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertNotNull($payout[Payout\Entity::PRICING_RULE_ID]);
    }

    public function testIcici2faPayoutByPayrollAppWhenFeatureEnabled()
    {
        $this->ba->appAuthTest($this->config['applications.xpayroll.secret']);

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->startTest();
    }

    public function testIcici2faPayoutByPayrollAppWhenFeatureDisabled()
    {
        $this->ba->appAuthTest($this->config['applications.xpayroll.secret']);

        $this->startTest();
    }

    public function testIcici2faPayoutForTaxPayment()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $contact = $this->fixtures->create('contact', ['type' => 'rzp_tax_pay']);

        $fundAccount = $this->fixtures->fund_account->createBankAccount(
            [
                'source_type' => 'contact',
                'source_id'   => $contact->getId(),
            ],
            [
                'name'           => 'test',
                'ifsc'           => 'SBIN0007105',
                'account_number' => '111000',
            ]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $fundAccount->getPublicId();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertNotNull($payout[Payout\Entity::PRICING_RULE_ID]);
    }

    public function testScheduledPayoutCreateForIcici2faEnabledMerchant()
    {
        $this->ba->proxyAuth();

        $timestamp = Carbon::now('Asia/Kolkata')->addDay(1);

        $scheduledTimestamp = $timestamp->setHour(13);

        $this->testData[__FUNCTION__]['request']['content']['scheduled_at'] = (string) $scheduledTimestamp->getTimestamp();

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::ICICI_2FA]);

        $this->startTest();

    }

    protected function mockBASResponseForFetchingBankingCredentialsForAxisGateway($exception = null): void
    {
        $basMock = $this->getMockBuilder(BankingAccountService::class)
                        ->setConstructorArgs([$this->app])
                        ->setMethods(['fetchBankingCredentials'])
                        ->getMock();

        $basMock->method('fetchBankingCredentials')
                ->willReturn([
                                 'crp_id' => 'corp123',
                                 'user_id' => 'user123',
                                 'urn' => 'dummyURN',
                             ]);

        if ($exception !== null)
        {
            $basMock->method('sendMozartRequest')
                    ->willThrowException($exception);
        }

        $this->app->instance('banking_account_service', $basMock);
    }
    public function testBalanceFetchFailureDueToChangeInBASResponseFields()
    {
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                       ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $this->mockBASResponseForFetchingBankingCredentialsForAxisGateway();

        $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsAfterCronRuns->getBalanceLastFetchedAt());
    }

    public function testScheduledPayoutProcessingAutoRejectForIcici()
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

            $accountType = 'ICICI Current Account';

            $this->assertEquals($accountType, $viewData[Balance\Entity::ACCOUNT_TYPE]);

            $mail->hasTo('naruto@gmail.com');
            $mail->hasFrom('no-reply@razorpay.com');
            $mail->hasReplyTo('no-reply@razorpay.com');

            return true;
        });
    }

    public function testProcessGatewayBalanceUpdateFor2FAMerchants()
    {
        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
            ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(500);

        $this->fixtures->create('feature', [
            'name'        => Features::ICICI_2FA,
            'entity_id'   => $basDetailsBeforeCronRuns->getMerchantId(),
            'entity_type' => 'merchant',
        ]);

        $response = $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
            ['account_number' => 2224440041626905]);

        $this->assertEquals(50000, $basDetailsAfterCronRuns->getGatewayBalance());

        $this->assertNotNull($basDetailsAfterCronRuns->getBalanceLastFetchedAt());

        $this->assertNotNull($basDetailsAfterCronRuns->getGatewayBalanceChangeAt());
    }

    public function testProcessGatewayBalanceUpdateForNon2FAMerchantsIfBlockIsEnabled()
    {
        (new AdminService)->setConfigKeys([ConfigKey::RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA => true]);

        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
            ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $mock = Mockery::mock(\RZP\Services\Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        // Assert that mozart call was not made
        $mock->shouldNotHaveBeenCalled([
            'sendMozartRequest'
        ]);

        $this->app->instance('mozart', $mock);

        $response = $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
            ['account_number' => 2224440041626905]);

        $this->assertNull($basDetailsAfterCronRuns->getGatewayBalance());

        $this->assertNull($basDetailsAfterCronRuns->getBalanceLastFetchedAt());

        $this->assertNull($basDetailsAfterCronRuns->getGatewayBalanceChangeAt());
    }

    public function testProcessGatewayBalanceUpdateForBaasMerchantsWhenCredentialsIsReturnedByBas()
    {
        (new AdminService)->setConfigKeys([ConfigKey::RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA => true]);

        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
            ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(500);

        // mock BAS
        $mock = Mockery::mock(BankingAccountService::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive('fetchBankingCredentials')
             ->andReturn([
                             Icici\Fields::CORP_ID   => 'RAZORPAY12345',
                             Icici\Fields::CORP_USER => 'USER12345',
                             Icici\Fields::URN       => 'URN12345',
                             Fields::CREDENTIALS     => [
                                 "AGGR_ID"           => "BAAS0123",
                                 "AGGR_NAME"         => "ACMECORP",
                                 "beneficiaryApikey" => "wfeg34t34t34t3r43t34GG"
                             ],
                         ]);

        $this->app->instance('banking_account_service', $mock);

        $this->fixtures->create('feature', [
            'name'        => Features::ICICI_BAAS,
            'entity_id'   => $basDetailsBeforeCronRuns->getMerchantId(),
            'entity_type' => 'merchant',
        ]);

        $response = $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
            ['account_number' => 2224440041626905]);

        $this->assertEquals(50000, $basDetailsAfterCronRuns->getGatewayBalance());

        $this->assertNotNull($basDetailsAfterCronRuns->getBalanceLastFetchedAt());

        $this->assertNotNull($basDetailsAfterCronRuns->getGatewayBalanceChangeAt());
    }

    public function testProcessGatewayBalanceUpdateForBaasMerchantsWhenCredentialsIsNotReturnedByBas()
    {
        (new AdminService)->setConfigKeys([ConfigKey::RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA => true]);

        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
            ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $mock = Mockery::mock(\RZP\Services\Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        // Assert that mozart call was not made
        $mock->shouldNotHaveBeenCalled([
            'sendMozartRequest' => []
        ]);

        $this->app->instance('mozart', $mock);

        $this->fixtures->create('feature', [
            'name'        => Features::ICICI_BAAS,
            'entity_id'   => $basDetailsBeforeCronRuns->getMerchantId(),
            'entity_type' => 'merchant',
        ]);

        $response = $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
            ['account_number' => 2224440041626905]);

        $this->assertNull($basDetailsAfterCronRuns->getGatewayBalance());

        $this->assertNull($basDetailsAfterCronRuns->getBalanceLastFetchedAt());

        $this->assertNull($basDetailsAfterCronRuns->getGatewayBalanceChangeAt());
    }

    public function testBlockingOfApiPayoutsWhenNeither2faNorBaasFeatureIsEnabledAndRedisKeySet()
    {
        $this->ba->privateAuth();

        (new AdminService)->setConfigKeys([ConfigKey::RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA => 1]);

        $this->startTest();
    }

    public function testSuccessfulApiPayoutCreationWhenBaasFeatureIsEnabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::ICICI_BAAS]);

        $testData = $this->testData['testCreatePayout'];
        $testData['request']['content']['queue_if_low_balance'] = true;

        $this->app['rzp.mode'] = EnvMode::TEST;

        $this->mockFTSFundTransfer();

        $this->mockBASCredentialsFetchForBaaSMerchants();

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(50000);

        /** @var Details\Entity $basDetailsBeforeCronRuns */
        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                       ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $this->testCreatePayout($testData);

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(5000000, $basDetailsAfterCronRuns->getGatewayBalance());

        $this->assertNotNull($basDetailsAfterCronRuns->getBalanceLastFetchedAt());

        $this->assertNotNull($basDetailsAfterCronRuns->getGatewayBalanceChangeAt());
    }

    public function testBlockingOfDashboardPayoutIfBaasFeatureIsNotEnableAndRedisKeySet()
    {
        $this->ba->proxyAuth();

        (new AdminService)->setConfigKeys([ConfigKey::RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA => 1]);

        $testData = $this->testData['testBlockingOfApiPayoutsWhenNeither2faNorBaasFeatureIsEnabledAndRedisKeySet'];

        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $testData['response']['content']['error']['description'] = 'Dashboard payouts are not available for this account';

        $this->startTest($testData);
    }

    public function testSuccessfulDashboardPayoutCreationIfBaasFeatureIsEnabled()
    {
        $this->ba->proxyAuth();

        (new AdminService)->setConfigKeys([ConfigKey::RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA => 1]);

        $this->fixtures->create('feature', [
            'name'        => Features::ICICI_BAAS,
            'entity_id'   => $this->merchant->getId(),
            'entity_type' => 'merchant',
        ]);

        $testData = $this->testData['testPayoutCreateWithIcici2FaSuccess'];

        $testData['request']['url']              = '/payouts_with_otp';
        $testData['request']['content']['token'] = 'BUIj3m2Nx2VvVj';
        $testData['request']['content']['otp']   = '0007';

        $testData['response']['content']['status'] = 'processing';
        $testData['response']['content']['fees'] = 1062;
        $testData['response']['content']['tax'] = 162;

        $this->startTest($testData);
    }

    public function testCorrectRequestPayloadIsSentToMozartForBaaSMerchantsDuringBalanceFetch()
    {
        $this->mockBASCredentialsFetchForBaaSMerchants();

        $this->mockMozartForBaaSMerchants(100);

        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                       ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(10000, $basDetailsAfterCronRuns->getGatewayBalance());

        $this->assertNotNull($basDetailsAfterCronRuns->getBalanceLastFetchedAt());

        $this->assertNotNull($basDetailsAfterCronRuns->getGatewayBalanceChangeAt());
    }

    public function testCorrectRequestPayloadIsSentToMozartForNonBaaSMerchantsDuringBalanceFetch()
    {
        $this->mockMozartForNonBaaSMerchants(2000);

        $basDetailsBeforeCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                       ['account_number' => 2224440041626905]);

        $this->assertEquals(0, $basDetailsBeforeCronRuns->getBalanceLastFetchedAt());

        $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        /** @var Details\Entity $basDetailsAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626905]);

        $this->assertEquals(200000, $basDetailsAfterCronRuns->getGatewayBalance());

        $this->assertNotNull($basDetailsAfterCronRuns->getBalanceLastFetchedAt());

        $this->assertNotNull($basDetailsAfterCronRuns->getGatewayBalanceChangeAt());
    }

    public function mockFTSFundTransfer()
    {
        $ftsMock = Mockery::mock('RZP\Services\FTS\FundTransfer', [$this->app])->makePartial();

        $ftsMock->shouldReceive('shouldAllowTransfersViaFts')
                ->andReturn([true, 'Dummy']);

        $this->app->instance('fts_fund_transfer', $ftsMock);
    }

    public function mockBASCredentialsFetchForBaaSMerchants()
    {
        $basMock = Mockery::mock(BankingAccountService::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $basMock->shouldReceive('fetchBankingCredentials')
                ->andReturn([
                                Icici\Fields::CORP_ID   => 'RAZORPAY12345',
                                Icici\Fields::CORP_USER => 'USER12345',
                                Icici\Fields::URN       => 'URN12345',
                                Fields::CREDENTIALS     => [
                                    "AGGR_ID"           => "BAAS0123",
                                    "AGGR_NAME"         => "ACMECORP",
                                    "beneficiaryApikey" => "wfeg34t34t34t3r43t34GG"
                                ]
                            ]);

        $this->app->instance('banking_account_service', $basMock);
    }

    public function mockMozartForBaaSMerchants($balanceToReturn)
    {
        $mozartServiceMock = Mockery::mock(Mozart::class, [$this->app])->makePartial();

        $partialMozartRequest = [
            Fields::SOURCE_ACCOUNT => [
                Fields::SOURCE_ACCOUNT_NUMBER => '2224440041626905',
                Icici\Fields::CREDENTIALS     => [
                    Icici\Fields::CORP_ID             => 'RAZORPAY12345',
                    Icici\Fields::CORP_USER           => 'USER12345',
                    Icici\Fields::URN                 => 'URN12345',
                    Icici\Fields::AGGR_ID             => 'BAAS0123',
                    Icici\Fields::AGGR_NAME           => 'ACMECORP',
                    Icici\Fields::BENEFICIARY_API_KEY => 'wfeg34t34t34t3r43t34GG',
                ],
            ],
            Icici\Fields::MERCHANT_ID => '10000000000000',
        ];

        $mozartServiceMock->shouldReceive('sendMozartRequest')
                          ->withArgs(function($namespace,
                                              $gateway,
                                              $action,
                                              $input,
                                              $version,
                                              $useMozartMappedInternalErrorCode,
                                              $timeout,
                                              $connectTimeout) use ($partialMozartRequest)
                          {

                              $this->assertArraySelectiveEquals($partialMozartRequest, $input);

                              return true;

                          })->andReturn([
                                            Icici\Fields::DATA => [

                                                Icici\Fields::BALANCE => $balanceToReturn
                                            ]
                                        ]);

        $this->app->instance('mozart', $mozartServiceMock);
    }

    public function mockMozartForNonBaaSMerchants($balanceToReturn)
    {
        $aggrId            = $this->app['config']['banking_account']['icici']['aggr_id'];
        $aggrName          = $this->app['config']['banking_account']['icici']['aggr_name'];
        $beneficiaryApikey = $this->app['config']['banking_account']['icici']['beneficiary_api_key'];

        $mozartServiceMock = Mockery::mock(Mozart::class, [$this->app])->makePartial();

        $partialMozartRequest = [
            Fields::SOURCE_ACCOUNT => [
                Fields::SOURCE_ACCOUNT_NUMBER => '2224440041626905',
                Icici\Fields::CREDENTIALS     => [
                    Icici\Fields::CORP_ID             => 'RAZORPAY12345',
                    Icici\Fields::CORP_USER           => 'USER12345',
                    Icici\Fields::URN                 => 'URN12345',
                    Icici\Fields::AGGR_ID             => $aggrId,
                    Icici\Fields::AGGR_NAME           => $aggrName,
                    Icici\Fields::BENEFICIARY_API_KEY => $beneficiaryApikey,
                ],
            ],
        ];

        $mozartServiceMock->shouldReceive('sendMozartRequest')
                          ->withArgs(function($namespace,
                                              $gateway,
                                              $action,
                                              $input,
                                              $version,
                                              $useMozartMappedInternalErrorCode,
                                              $timeout,
                                              $connectTimeout) use ($partialMozartRequest)
                          {

                              $this->assertArraySelectiveEquals($partialMozartRequest, $input);

                              $this->assertArrayNotHasKey(Icici\Fields::MERCHANT_ID, $input);

                              return true;

                          })->andReturn([
                                            Icici\Fields::DATA => [
                                                Icici\Fields::BALANCE => $balanceToReturn
                                            ]
                                        ]);

        $this->app->instance('mozart', $mozartServiceMock);
    }

}
