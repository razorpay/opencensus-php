<?php

namespace RZP\Tests\Functional\FeeRecovery;

use Carbon\Carbon;

use RZP\Jobs\FeeRecoveryLowBalance;
use RZP\Mail\FeeRecovery\LowBalanceAlert;
use RZP\Models\Payout;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Schedule;
use RZP\Constants\Timezone;
use RZP\Models\FeeRecovery;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Merchant\Balance\FreePayout;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\FeeRecovery\Entity as FeeRecoveryEntity;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\BankingAccount\FeeRecoveryTrait;

class FeeRecoveryTest extends TestCase
{
    use PayoutTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;
    use FeeRecoveryTrait;

    private $checkerRoleUser;

    /**
     * @var Entity
     */
    private $bankingAccount;

    /** @var Merchant\Balance\Entity $balance */
    protected $balance;

    protected $contactForPayout;

    /** @var Merchant\Entity $merchant */
    protected $merchant;

    /**
     * @var \RZP\Models\FundAccount\Entity
     */
    private $fundAccount;
    private $ownerRoleUser;
    private $finL3RoleUser;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/FeeRecoveryTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->setUpMerchantForBusinessBanking(
            false,
            10000,
            AccountType::DIRECT,
            Channel::RBL);

        $this->contactForPayout =  $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fundAccount = $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->balance = $this->getDbEntity('balance', ['merchant_id' => '10000000000000', 'type' => 'banking']);

        $bankingAccount = $this->getDbEntity('banking_account', ['balance_id' => $this->balance['id']]);

        // Update the banking account we created to make it a RBL CA Banking Account
        $this->bankingAccount = $this->fixtures->edit('banking_account', $bankingAccount['id'],
        [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'balance_id'            => $this->balance->getId()
        ]);

        $this->fixtures->create('banking_account_statement_details',[
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $this->bankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626905',
            Details\Entity::CHANNEL        => Details\Channel::RBL,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(100);

        $this->merchant = $this->getDbEntityById('merchant', '10000000000000');
        $this->setupUserRoles();
    }

    private function setupUserRoles()
    {
        $this->ownerRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'owner', 'live');

        $this->finL3RoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'finance_l3', 'live');
    }

    protected function setupScheduleAndScheduleTaskForMerchant()
    {
        $this->setupDefaultScheduleForFeeRecovery();
        $this->createScheduleTaskForMerchantAndBalance($this->merchant->getId(), $this->bankingBalance->getId());
    }

    public function testCreateFeeRecoveryAtPayoutCreationForRBLPayouts()
    {
        $this->ba->privateAuth();

        $this->createPayout($this->balance);

        $payout = $this->getDbLastEntity('payout')->toArray();

        $feeRecovery = $this->getDbLastEntity('fee_recovery')->toArray();

        $this->assertEquals($payout['id'], $feeRecovery['entity_id']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecovery['status']);
        $this->assertEquals(0, $feeRecovery['attempt_number']);
        $this->assertNull($feeRecovery['recovery_payout_id']);
    }

    public function testCreateFeeRecoveryAtPayoutCreationForRBLPayoutsWithRewards()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->ba->privateAuth();

        $this->createPayout($this->balance);

        $payout = $this->getDbLastEntity('payout')->toArray();

        $feeRecovery = $this->getDbLastEntity('fee_recovery');

        $this->assertNull($feeRecovery);
        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);
    }

    public function testCreateFeeRecoveryAtPayoutCreationWithFeeCredits()
    {
        Carbon::setTestNow(Carbon::now());

        $timeStamp = Carbon::now()->getTimestamp();

        $this->fixtures->create('feature', [
            'name'          => 'rzpx_fee_credit',
            'entity_id'     => '10000000000000',
            'entity_type'   => 'merchant',
        ]);

        $this->ba->privateAuth();

        $contact = $this->createContact();

        $fundAccount = $this->createFundAccountForContact($contact);

        // Create a payout
        $this->createPayoutForFundAccount($fundAccount, $this->balance);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout['id'], ['initiated_at' => $timeStamp]);

        // Updating FTA and Payout status to initiated to allow transition to reversed
        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $payout->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::INITIATED]);

        $this->fixtures->edit('payout', $payout->getId(), ['status' => Payout\Status::INITIATED]);

        $payout = $this->getDbLastEntity('payout')->toArray();

        $feeRecovery = $this->getDbLastEntity('fee_recovery')->toArray();

        $feeCredits = $this->getDbLastEntity('credits')->toArray();

        $currentFeeCredits = (new \RZP\Models\Merchant\Credits\Repository())->getMerchantCreditsOfType($this->merchant->getId(), 'fee_credit');

        $this->assertEquals(-590, $currentFeeCredits);

        $this->assertEquals('fee_credit', $feeCredits['type']);
        $this->assertEquals(0, $feeCredits['value']);
        $this->assertEquals($payout['fees'], $feeCredits['used']);

        $this->assertEquals($feeRecovery['entity_id'], $payout['id']);
        $this->assertEquals(FeeRecovery\Status::RECOVERED, $feeRecovery['status']);
        $this->assertEquals('debit', $feeRecovery['type']);

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
    }

    public function testCreateFeeRecoveryAtPayoutCreationFailedWithFeeCredits()
    {
        $this->testCreateFeeRecoveryAtPayoutCreationWithFeeCredits();

        $payout = $this->getDbLastEntity('payout');

        // fail the payout
        $this->updateFtaAndSource($payout, Payout\Status::FAILED,'944926344925');

        $feeRecovery = $this->getDbLastEntity('fee_recovery')->toArray();

        $feeCredits = $this->getDbLastEntity('credits')->toArray();

        $currentFeeCredits = (new \RZP\Models\Merchant\Credits\Repository())->getMerchantCreditsOfType($this->merchant->getId(), 'fee_credit');

        $this->assertEquals(0, $currentFeeCredits);

        $this->assertEquals('fee_credit', $feeCredits['type']);
        $this->assertEquals(0, $feeCredits['value']);
        $this->assertEquals(0, $feeCredits['used']);

        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecovery['type']);
        $this->assertEquals($payout['id'], $feeRecovery['entity_id']);
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecovery['entity_type']);
        $this->assertEquals(FeeRecovery\Status::RECOVERED, $feeRecovery['status']);
    }

    public function testCreateFeeRecoveryAtPayoutCreationReversedWithFeeCredits()
    {
        $this->testCreateFeeRecoveryAtPayoutCreationWithFeeCredits();

        $payout = $this->getDbLastEntity('payout');

        // Reverse the payout
        $this->updateFtaAndSource($payout, Payout\Status::REVERSED,'944926344925');

        $reversal = $this->getDbLastEntity('reversal')->toArray();

        $feeRecovery = $this->getDbLastEntity('fee_recovery')->toArray();

        $feeCredits = $this->getDbLastEntity('credits')->toArray();

        $currentFeeCredits = (new \RZP\Models\Merchant\Credits\Repository())->getMerchantCreditsOfType($this->merchant->getId(), 'fee_credit');

        $this->assertEquals(0, $currentFeeCredits);

        $this->assertEquals('fee_credit', $feeCredits['type']);
        $this->assertEquals(0, $feeCredits['value']);
        $this->assertEquals(0, $feeCredits['used']);

        $this->assertEquals($payout->getId(), $reversal['entity_id']);

        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecovery['type']);
        $this->assertEquals($reversal['id'], $feeRecovery['entity_id']);
        $this->assertEquals(FeeRecovery\Entity::REVERSAL, $feeRecovery['entity_type']);
        $this->assertEquals(FeeRecovery\Status::RECOVERED, $feeRecovery['status']);
    }

    public function testCreateFeeRecoveryAtPayoutCreationForRBLPayoutsReversedWithRewards()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee' , 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 700 ]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->ba->privateAuth();

        $this->createPayout($this->balance);

        $payout = $this->getDbLastEntity('payout');

        // Reverse the  payout
        $this->updateFtaAndSource($payout, Payout\Status::REVERSED,'944926344925');

        $feeRecovery = $this->getDbLastEntity('fee_recovery');

        $this->assertNull($feeRecovery);
        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);
    }

    public function testCreateVAPayoutNoFeeRecoveryCreated()
    {
        $balance = $this->createVirtualBankingAccount();

        $this->createPayout($balance);

        $feeRecovery = $this->getDbLastEntity('fee_recovery');

        $this->assertNull($feeRecovery);
    }

    public function testCreateRBLQueuedPayoutNoFeeRecoveryCreated()
    {
        $this->mockMozartResponseForFetchingBalanceFromRblGateway(0);

        $this->fixtures->edit('balance', $this->balance->getId(), ['balance' => 1000]);

        $this->createPayout($this->balance);

        $payout = $this->getDbLastEntity('payout')->toArray();

        $this->assertEquals(Payout\Status::QUEUED, $payout['status']);
        $this->assertEquals($this->balance->getId(), $payout['balance_id']);

        $feeRecovery = $this->getDbLastEntity('fee_recovery');

        $this->assertNull($feeRecovery);
    }

    public function testProcessQueuedPayoutFeeRecoveryCreated()
    {
        $this->markTestSkipped('Currently skipped since mocking gateway balance is an issue');

        $this->testCreateRBLQueuedPayoutNoFeeRecoveryCreated();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals($payout['status'], Payout\Status::QUEUED);

        $this->fixtures->edit('balance', $this->balance->getId(), ['balance' => 1000000]);

        $this->ba->cronAuth();

        $this->startTest();

        // Moving this payout to initiated
        $payout->reload();
        $payout->setStatus(Payout\Status::INITIATED);
        $payout->saveOrFail();

        $feeRecovery = $this->getDbLastEntity('fee_recovery');

        $this->assertEquals($payout['id'], $feeRecovery['entity_id']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecovery['status']);
        $this->assertEquals($feeRecovery['type'], FeeRecovery\Type::DEBIT);
        $this->assertEquals(0, $feeRecovery['attempt_number']);
        $this->assertEquals($feeRecovery['recovery_payout_id'], null);
    }

    public function testCreateRBLPendingPayoutNoFeeRecoveryCreated()
    {
        $this->liveSetUpForRbl();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $balance = $this->getDbLastEntity('balance', 'live')->toArray();

        $this->assertEquals($balance['account_type'], AccountType::DIRECT);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->createPayoutWithWorkflow([], 'rzp_live_TheLiveAuthKey');

        $payout = $this->getDbLastEntity('payout', 'live')->toArray();

        $this->assertEquals(Payout\Status::PENDING, $payout['status']);
        $this->assertEquals($balance['id'], $payout['balance_id']);
        $this->assertEquals($payout['channel'], Channel::RBL);

        $feeRecovery = $this->getDbLastEntity('fee_recovery', 'live');

        $this->assertNull($feeRecovery);
    }

    public function testApprovePendingPayoutFeeRecoveryCreated()
    {
        $this->testCreateRBLPendingPayoutNoFeeRecoveryCreated();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->assertEquals($payout['status'], Payout\Status::PENDING);

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->ownerRoleUser->getId());

        $data = & $this->testData[__FUNCTION__];

        $data['request']['url'] = '/payouts/pout_' . $payout['id'] . '/approve';

        $this->startTest();

        $this->app['config']->set('database.default', 'live');

        // Make Request to Approve pending payout for second level
        $this->ba->proxyAuth('rzp_live_10000000000000', $this->finL3RoleUser->getId());

        $this->startTest();

        $this->app['config']->set('database.default', 'live');

        // Moving this payout to initiated
        $payout->reload();
        $payout->setStatus(Payout\Status::INITIATED);
        $payout->saveOrFail();

        $payout = $this->getDbLastEntity('payout', 'live');

        $feeRecovery = $this->getDbLastEntity('fee_recovery','live');

        $this->assertEquals($payout['id'], $feeRecovery['entity_id']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecovery['status']);
        $this->assertEquals($feeRecovery['type'], FeeRecovery\Type::DEBIT);
        $this->assertEquals(0, $feeRecovery['attempt_number']);
        $this->assertEquals($feeRecovery['recovery_payout_id'], null);
    }

    public function testCreateFeeRecoveryPayout()
    {
        $oldTime = Carbon::create(2020, 1, 3, null, null, null);

        Carbon::setTestNow($oldTime);

        $oldTimeStamp = $oldTime->getTimestamp();

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->balance);

        // Create first payout
        $this->testCreateFeeRecoveryAtPayoutCreationForRBLPayouts();

        $payout = $this->getDbLastEntity('payout');

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->fixtures->edit('payout', $payout['id'], ['initiated_at' => $oldTimeStamp]);

        // Create second payout
        $this->createPayoutForFundAccount($fundAccount, $this->balance);

        $payout2 = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout2['id'], ['initiated_at' => $oldTimeStamp]);

        // Fail the second payout
        $this->updateFtaAndSource($payout2, Payout\Status::FAILED);

        // Create a third payout
        $this->createPayoutForFundAccount($fundAccount, $this->balance);

        $payout3 = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout3['id'], ['initiated_at' => $oldTimeStamp]);

        // Updating FTA and Payout status to initiated to allow transition to reversed
        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $payout3->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::INITIATED]);

        $this->fixtures->edit('payout', $payout3->getId(), ['status' => Payout\Status::INITIATED]);

        // Reverse the third payout
        $this->updateFtaAndSource($payout3, Payout\Status::REVERSED,'944926344925');

        $this->fixtures->edit('contact', '1010101contact', ['type' => 'rzp_fees']);

        $balanceId = $this->balance->getId();

        $startTime = Carbon::create(2020, 1, 1, null, null, null)->getTimestamp();
        $endTime   = Carbon::create(2020, 1, 8, null, null, null)->getTimestamp();

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content'] = [
            'balance_id'    => $balanceId,
            'from'          => $startTime,
            'to'            => $endTime,
        ];

        $this->ba->adminAuth();

        $this->startTest();

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
    }
    public function testCreatePayoutServiceFeeRecoveryPayout()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_SERVICE_ENABLED]);

        $this->setMockRazorxTreatment(['enable_ca_rzp_fees_flow_via_payouts_service' => 'off']);

        $oldTime = Carbon::create(2020, 1, 3, null, null, null);

        Carbon::setTestNow($oldTime);

        $oldTimeStamp = $oldTime->getTimestamp();

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->balance);

        // Create first payout
        $this->testCreateFeeRecoveryAtPayoutCreationForRBLPayouts();

        $payout = $this->getDbLastEntity('payout');

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->fixtures->edit('payout', $payout['id'], ['initiated_at' => $oldTimeStamp]);

        // Create second payout
        $this->createPayoutForFundAccount($fundAccount, $this->balance);

        $payout2 = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout2['id'], ['initiated_at' => $oldTimeStamp]);

        // Fail the second payout
        $this->updateFtaAndSource($payout2, Payout\Status::FAILED);

        // Create a third payout
        $this->createPayoutForFundAccount($fundAccount, $this->balance);

        $payout3 = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout3['id'], ['initiated_at' => $oldTimeStamp]);

        // Updating FTA and Payout status to initiated to allow transition to reversed
        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $payout3->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::INITIATED]);

        $this->fixtures->edit('payout', $payout3->getId(), ['status' => Payout\Status::INITIATED]);

        // Reverse the third payout
        $this->updateFtaAndSource($payout3, Payout\Status::REVERSED,'944926344925');

        $this->fixtures->edit('contact', '1010101contact', ['type' => 'rzp_fees']);

        $balanceId = $this->balance->getId();

        $startTime = Carbon::create(2020, 1, 1, null, null, null)->getTimestamp();
        $endTime   = Carbon::create(2020, 1, 8, null, null, null)->getTimestamp();

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content'] = [
            'balance_id'    => $balanceId,
            'from'          => $startTime,
            'to'            => $endTime,
        ];

        $this->ba->adminAuth();

        $this->startTest();

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
    }

    public function testCreateFeeRecoveryPayoutLowBalance()
    {
        $this->markTestSkipped('Currently skipped since mocking gateway balance is an issue');

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(100);

        $this->testCreateFeeRecoveryAtPayoutCreationForRBLPayouts();

        $payout = $this->getDbLastEntity('payout')->toArray();

        $payoutId = $payout['id'];

        $initiatedAt = $payout['initiated_at'];

        $previousWeekTimeStamp = $initiatedAt - 604800;

        $this->fixtures->edit('payout', $payoutId, ['initiated_at' => $previousWeekTimeStamp]);

        $this->fixtures->edit('contact', '1010101contact', ['type' => 'rzp_fees']);

        $balanceId = $this->balance->getId();

        $this->fixtures->edit('balance', $balanceId, ['balance' => 0]);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(0);

        // Start time and end time are start of last week and end of last week
        $startTime = Carbon::now(Timezone::IST)->subWeek()->startOfWeek()->getTimestamp();
        $endTime   = Carbon::now(Timezone::IST)->subWeek()->endOfWeek()->getTimestamp();

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content'] = [
            'balance_id'    => $balanceId,
            'from'          => $startTime,
            'to'            => $endTime,
        ];

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreateFeeRecoveryPayoutSkipWorkflow()
    {
        $this->markTestSkipped('Need to fix live/test stuff');

        $this->liveSetUpForRbl();

        $fundAccount = $this->getDbLastEntity('fund_account', 'live');

        $balance = $this->getDbLastEntity('balance', 'live');

        $payoutParams = [
            'purpose'           => 'refund',
            'fund_account_id'   => $fundAccount['id'],
            'notes'             => [
                'abc' => 'xyz',
            ],
            'amount'            => 1000,
            'currency'          => 'INR',
            'balance_id'        => $balance['id'],
            'initiated_at'      => Carbon::now(Timezone::IST)->getTimestamp() - 604800,
            'pricing_rule_id'   => 'Bbg7cl6t6I3XA6'
        ];

        $this->fixtures->on('live')->create('payout', $payoutParams);

        $this->setupWorkflowForLiveMode();

        // Start time and end time are start of last week and end of last week
        $startTime = Carbon::now(Timezone::IST)->subWeek()->startOfWeek()->getTimestamp();
        $endTime   = Carbon::now(Timezone::IST)->subWeek()->endOfWeek()->getTimestamp();

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content'] = [
            'balance_id'    => $balance['id'],
            'from'          => $startTime,
            'to'            => $endTime,
        ];

        $this->ba->adminAuth('live');

        $this->startTest();
    }

    private function createPayoutsForFeeRecoveryTests()
    {
        $oldTime = Carbon::create(2020, 1, 4, 8, null, null, Timezone::IST);

        Carbon::setTestNow($oldTime);

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->balance);

        $this->setupScheduleAndScheduleTaskForMerchant();

        $oldTimeStamp = $oldTime->getTimestamp();

        // Create first payout
        $this->testCreateFeeRecoveryAtPayoutCreationForRBLPayouts();

        $payout = $this->getDbLastEntity('payout');

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->fixtures->edit('payout', $payout['id'], ['initiated_at' => $oldTimeStamp]);

        // Create second payout
        $this->createPayoutForFundAccount($fundAccount, $this->balance);

        $payout2 = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout2['id'], ['initiated_at' => $oldTimeStamp]);

        // Fail the second payout
        $this->updateFtaAndSource($payout2, Payout\Status::FAILED);

        // Create a third payout
        $this->createPayoutForFundAccount($fundAccount, $this->balance);

        $payout3 = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout3['id'], ['initiated_at' => $oldTimeStamp]);

        // Updating FTA and Payout status to initiated to allow transition to reversed
        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $payout3->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::INITIATED]);

        $this->fixtures->edit('payout', $payout3->getId(), ['status' => Payout\Status::INITIATED]);

        // Reverse the third payout
        $this->updateFtaAndSource($payout3, Payout\Status::REVERSED,'944926344925');

        $this->fixtures->edit('contact', '1010101contact', ['type' => 'rzp_fees']);

        $newTime = Carbon::create(2020, 1, 7, 7, 0, null);

        Carbon::setTestNow($newTime);

        return [$payout, $payout2, $payout3];
    }

    private function assertFeeRecoveryEntriesAfterFeeRecoveryJob($payout, $payout2, $payout3)
    {
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

    public function testFeeRecoveryPayoutCron()
    {
        [$payout, $payout2, $payout3] = $this->createPayoutsForFeeRecoveryTests();

        $this->ba->cronAuth();

        $this->startTest();

        $this->assertFeeRecoveryEntriesAfterFeeRecoveryJob($payout, $payout2, $payout3);
    }

    public function testCreateFeeRecoveryPayoutForAsyncPayoutProcessingEnabledMerchant()
    {
        $oldTime = Carbon::create(2020, 1, 3, null, null, null);

        Carbon::setTestNow($oldTime);

        $oldTimeStamp = $oldTime->getTimestamp();

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->balance);

        // Create first payout
        $this->testCreateFeeRecoveryAtPayoutCreationForRBLPayouts();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout['id'], ['initiated_at' => $oldTimeStamp]);

        $this->fixtures->edit('contact', '1010101contact', ['type' => 'rzp_fees']);

        $balanceId = $this->balance->getId();

        $startTime = Carbon::create(2020, 1, 1, null, null, null)->getTimestamp();

        $endTime   = Carbon::create(2020, 1, 8, null, null, null)->getTimestamp();

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content'] = [
            'balance_id'    => $balanceId,
            'from'          => $startTime,
            'to'            => $endTime,
        ];

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_PROCESS_ASYNC_LP]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT]);

        $this->ba->adminAuth();

        $this->startTest();

        $feeRecoveryPayout = $this->getDbLastEntity('payout');

        $this->assertNotEquals('create_request_submitted', $feeRecoveryPayout['internal_status']);

        $this->assertNull($feeRecoveryPayout['create_request_submitted_at']);

        $this->assertEquals('rzp_fees', $feeRecoveryPayout['purpose']);

        $this->assertEquals('created', $feeRecoveryPayout['internal_status']);
    }

    public function testFeeRecoveryPayoutCronNextAndLastRunUpdate()
    {
        Queue::fake();

        $oldTime = Carbon::create(2020, 1, 4, 8, null, null);

        Carbon::setTestNow($oldTime);

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->balance);

        $this->setupScheduleAndScheduleTaskForMerchant();

        $oldTimeStamp = $oldTime->getTimestamp();

        $this->testCreateFeeRecoveryAtPayoutCreationForRBLPayouts();

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout['id'], ['initiated_at' => $oldTimeStamp]);

        $this->fixtures->edit('contact', '1010101contact', ['type' => 'rzp_fees']);

        $newTime = Carbon::create(2020, 1, 5, 7, 0, null);

        $task = $this->getDbLastEntity('schedule_task');

        $initialNextRunAt = $task['next_run_at'];

        Carbon::setTestNow($newTime);

        $this->ba->cronAuth();

        $this->startTest();

        $task->reload();

        // Assert that last run and next run is not updated when job is not invoked
        $this->assertEquals(null, $task['last_run_at']);

        $this->assertEquals($initialNextRunAt, $task['next_run_at']);

        $balance = $payout->balance;

        $lastRunAt = ($task->getLastRunAt() + 1) ?? $balance->getCreatedAt();

        $nextRunAt = $task->getNextRunAt();

        $job = new \RZP\Jobs\FeeRecovery('test', null, $balance->getId(), $lastRunAt, $nextRunAt, $task);

        $job->handle();

        $lastRun = Carbon::createFromTimestamp($initialNextRunAt, Timezone::IST);

        $currentTime = Carbon::now(Timezone::IST);

        [$start, $nextRunTime] = (new FeeRecovery\Core)->getNextInterval($currentTime);

        $task->reload();

        // Assert that last run and next run is updated when job is run
        $this->assertEquals($nextRunAt, $task['last_run_at']);

        $this->assertEquals($nextRunTime->getTimestamp(), $task['next_run_at']);
    }

    public function testFeeRecoveryPayoutCronNextRunUpdateForNegativeAmount()
    {
        $oldTime = Carbon::create(2020, 1, 4, 8, null, null, Timezone::IST);

        Carbon::setTestNow($oldTime);

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->balance);

        $this->setupScheduleAndScheduleTaskForMerchant();

        $oldTimeStamp = $oldTime->getTimestamp();

        $contact = $this->createContact();

        $fundAccount = $this->createFundAccountForContact($contact);

        // Create a payout
        $this->createPayoutForFundAccount($fundAccount, $this->balance);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout['id'], ['initiated_at' => $oldTimeStamp]);

        // Updating FTA and Payout status to initiated to allow transition to reversed
        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $payout->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::INITIATED]);

        $this->fixtures->edit('payout', $payout->getId(), ['status' => Payout\Status::INITIATED]);

        $this->fixtures->edit('contact', '1010101contact', ['type' => 'rzp_fees']);

        // Recovering fees for the payout
        $newTime = Carbon::create(2020, 1, 5, 7, 0, null);

        Carbon::setTestNow($newTime);

        $this->ba->cronAuth();

        $this->startTest();

        $feeRecoveryPayout = $this->getDbLastEntity('payout');

        // Moving this payout to initiated
        $feeRecoveryPayout->setStatus(Payout\Status::INITIATED);
        $feeRecoveryPayout->saveOrFail();

        // Fee Recovery entity for payout
        $feeRecovery = $this->getDbEntity('fee_recovery', [
            'entity_id' => $payout['id'],
            'type'      => FeeRecovery\Type::DEBIT
        ])->toArray();

        $this->assertEquals($payout['id'], $feeRecovery['entity_id']);
        $this->assertEquals(FeeRecovery\Status::PROCESSING, $feeRecovery['status']);
        $this->assertEquals(1, $feeRecovery['attempt_number']);
        $this->assertEquals($feeRecovery['recovery_payout_id'], $feeRecoveryPayout['id']);

        $this->updateFtaAndSource($payout, Payout\Status::REVERSED,'944926344925');

        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals($reversal['entity_id'], $payout['id']);

        $task = $this->getDbLastEntity('schedule_task')->toArray();

        // Recovering fees for this balance in next cycle
        $newTime = Carbon::create(2020, 1, 6, 7, 0, null);

        Carbon::setTestNow($newTime);

        $this->ba->cronAuth();

        $this->startTest();

        $updatedTask = $this->getDbLastEntity('schedule_task')->toArray();

        $this->assertEquals($updatedTask['last_run_at'], $task['last_run_at']);
        $this->assertEquals($updatedTask['next_run_at'], $task['next_run_at'] + 86400);
    }

    public function testFeeRecoveryPayoutCronNextAndLastRunUpdateForZeroAmount()
    {
        $oldTime = Carbon::create(2020, 1, 4, 8, null, null, Timezone::IST);

        Carbon::setTestNow($oldTime);

        $this->setupScheduleAndScheduleTaskForMerchant();

        $newTime = Carbon::create(2020, 1, 5, 7, 0, null, Timezone::IST);

        $task = $this->getDbLastEntity('schedule_task');

        $initialNextRunAt = $task['next_run_at'];

        Carbon::setTestNow($newTime);

        $nextRunAt = $task->getNextRunAt();

        $this->mockRazorxFeeRecoveryRollout();

        $job = new \RZP\Jobs\FeeRecovery('test', null, $this->balance->getId(), 1, $nextRunAt, $task);

        $job->handle();

        $lastRun = Carbon::createFromTimestamp($initialNextRunAt, Timezone::IST);

        $currentTime = Carbon::now(Timezone::IST);

        [$start, $nextRunTime] = (new FeeRecovery\Core)->getNextInterval($currentTime);

        // Assert that last run and next run is updated when job is run
        $this->assertEquals($nextRunAt, $task['last_run_at']);

        $this->assertEquals($nextRunTime->getTimestamp(), $task['next_run_at']);
    }

    public function testUpdateFeeRecoveryAfterPayoutFTAReconSuccess()
    {
        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbEntity('payout', ['purpose' => 'rzp_fees']);

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::PROCESSED, '933815383814');

        $feeRecoveryEntity = $this->getDbEntity('fee_recovery',
                                                [
                                                    'recovery_payout_id' => $feeRecoveryPayout->getId()
                                                ]);

        $this->assertEquals($feeRecoveryEntity['recovery_payout_id'], $feeRecoveryPayout['id']);
        $this->assertEquals($feeRecoveryEntity['status'], FeeRecovery\Status::RECOVERED);
    }

    public function testUpdateFeeRecoveryAfterPayoutFTAReconFailed()
    {
        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbEntity('payout', ['purpose' => 'rzp_fees']);

        $feeRecoveryEntity1 = $this->getDbEntity('fee_recovery',
                                                [
                                                    'recovery_payout_id' => $feeRecoveryPayout->getId()
                                                ]);

        $feeRecoveryEntity2 = $this->getDbEntity('fee_recovery',
                                                [
                                                    'entity_id' => $feeRecoveryPayout->getId()
                                                ]);

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::FAILED, '933815383818');

        $feeRecoveryEntity1->reload();

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryEntity3 = $this->getDbEntity('fee_recovery',
                                                [
                                                    FeeRecoveryEntity::ENTITY_ID => $feeRecoveryEntity2['entity_id'],
                                                    FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
                                                ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryEntity3['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryEntity3['status']);
        $this->assertEquals(0, $feeRecoveryEntity3['attempt_number']);
        $this->assertNull($feeRecoveryEntity3['recovery_payout_id']);

        // Assert that the status of fee recovery entity corresponding to this
        // recovery payout has been updated back to unrecovered
        $this->assertEquals($feeRecoveryEntity1['recovery_payout_id'], $feeRecoveryPayout['id']);
        $this->assertEquals(FeeRecovery\Status::FAILED, $feeRecoveryEntity1['status']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryEntity2['type']);
        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryEntity3['type']);
    }

    public function testUpdateFeeRecoveryAfterPayoutFTAReconReversed()
    {
        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbEntity('payout', ['purpose' => 'rzp_fees']);

        // Updating FTA and Payout status to initiated to allow transition to reversed

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $feeRecoveryPayout->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::INITIATED]);

        $this->fixtures->edit('payout', $feeRecoveryPayout->getId(), ['status' => Payout\Status::INITIATED]);

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::REVERSED, '939915383814');

        $feeRecoveryEntity = $this->getDbEntity('fee_recovery',
                                                [
                                                    'recovery_payout_id' => $feeRecoveryPayout->getId()
                                                ]);

        $feeRecoveryPayoutReversal = $this->getDbLastEntity('reversal');

        $latestFeeRecoveryEntity = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryPayoutReversal->getId()
            ])->toArray();

        // Assert that we are creating a new fee recovery entity for the reversal
        $this->assertEquals(FeeRecovery\Entity::REVERSAL, $latestFeeRecoveryEntity['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $latestFeeRecoveryEntity['status']);
        $this->assertEquals(0, $latestFeeRecoveryEntity['attempt_number']);
        $this->assertNull($latestFeeRecoveryEntity['recovery_payout_id']);
        $this->assertEquals($latestFeeRecoveryEntity['type'], FeeRecovery\Type::CREDIT);

        // Assert that the status of fee recovery entity corresponding to this
        // recovery payout has been updated back to unrecovered
        $this->assertEquals($feeRecoveryEntity['recovery_payout_id'], $feeRecoveryPayout['id']);
        $this->assertEquals($feeRecoveryEntity['status'], FeeRecovery\Status::FAILED);
    }

    public function testUpdateFeeRecoveryAfterPayoutFTAReconSuccessFollowedByReversed()
    {
        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbEntity('payout', ['purpose' => 'rzp_fees']);

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::PROCESSED, '933815383814');

        $feeRecoveryEntity = $this->getDbEntity('fee_recovery',
                                                [
                                                    'recovery_payout_id' => $feeRecoveryPayout->getId()
                                                ]);

        // Assertions for fee_recovery payout marked as successful
        $this->assertEquals($feeRecoveryEntity['recovery_payout_id'], $feeRecoveryPayout['id']);
        $this->assertEquals($feeRecoveryEntity['status'], FeeRecovery\Status::RECOVERED);

        // Updating FTA status to processed to allow transition to reversed

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $feeRecoveryPayout->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::PROCESSED]);

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::REVERSED, '939915383814');

        $feeRecoveryEntityUpdated = $this->getDbEntity('fee_recovery',
                                                [
                                                    'recovery_payout_id' => $feeRecoveryPayout->getId()
                                                ]);

        $latestFeeRecoveryPayoutReversal = $this->getDbLastEntity('reversal');

        $latestFeeRecoveryEntity = $this->getDbEntity('fee_recovery',
                                                [
                                                    'entity_id'      => $latestFeeRecoveryPayoutReversal->getId(),
                                                    'attempt_number' => 0
                                                ])->toArray();

        // Assert that we are creating a new fee recovery entity for the reversal
        $this->assertEquals(FeeRecovery\Entity::REVERSAL, $latestFeeRecoveryEntity['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $latestFeeRecoveryEntity['status']);
        $this->assertNull($latestFeeRecoveryEntity['recovery_payout_id']);
        $this->assertEquals($latestFeeRecoveryEntity['type'], FeeRecovery\Type::CREDIT);

        // Assert that the status of fee recovery entity corresponding to this
        // recovery payout has been updated back to unrecovered
        $this->assertEquals($feeRecoveryEntityUpdated['recovery_payout_id'], $feeRecoveryPayout['id']);
        $this->assertEquals($feeRecoveryEntityUpdated['status'], FeeRecovery\Status::FAILED);
    }

    public function testCreateFeeRecoveryScheduleTaskForMerchant()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $currentTimeStamp = Carbon::now()->getTimestamp();
        $threeDaysLaterTimeStamp = Carbon::now()->addDays(3)->getTimestamp();

        // Create a schedule
        $createScheduleRequest = [
            'method'  => 'POST',
            'url'     => '/schedules',
            'content'   => [
                'type'      => 'fee_recovery',
                'name'      => 'Basic T+7',
                'period'    => 'daily',
                'interval'  => 3,
            ],
        ];

        $this->ba->adminAuth();

        $schedule = $this->makeRequestAndGetContent($createScheduleRequest);

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content'] = [
            'balance_id'    => $this->balance->getId(),
            'schedule_id'   => $schedule['id'],
        ];

        $this->startTest();

        $task = $this->getDbLastEntity('schedule_task')->toArray();

        // Assert next run at and last run at (keeping 1 sec leeway for both timestamps because test takes time to run)
        // Cannot use Carbon::setTestNow() here since Carbon is used during the flow and then returns erroneous time.
        $this->assertGreaterThanOrEqual($currentTimeStamp, $task['last_run_at']);
        $this->assertLessThanOrEqual($currentTimeStamp + 1, $task['last_run_at']);
        $this->assertGreaterThanOrEqual($threeDaysLaterTimeStamp, $task['next_run_at']);
        $this->assertLessThanOrEqual($threeDaysLaterTimeStamp + 1, $task['next_run_at']);

        // Assert schedule Id and Entity Id
        $this->assertEquals($schedule['id'], $task['schedule_id']);
        $this->assertEquals($this->balance['id'], $task['entity_id']);
    }

    // This test will create a new schedule task for a recently activated merchant
    // (where fee recovery hasn't happened even once)
    public function testCreateFeeRecoveryScheduleTaskForRecentlyActivatedMerchant()
    {
        // This will create a default schedule for a merchant
        $this->setupScheduleAndScheduleTaskForMerchant();

        $oldTask = $this->getDbLastEntity('schedule_task');

        // The default task would have last_run_at as null since fee has never been been recovered for this balance
        $this->assertNull($oldTask['last_run_at']);

        // Create a schedule
        $createScheduleRequest = [
            'method'  => 'POST',
            'url'     => '/schedules',
            'content'   => [
                'type'      => 'fee_recovery',
                'name'      => 'Basic T+7',
                'period'    => 'daily',
                'interval'  => 3,
            ],
        ];

        $this->ba->adminAuth();

        $schedule = $this->makeRequestAndGetContent($createScheduleRequest);

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content'] = [
            'balance_id'    => $this->balance->getId(),
            'schedule_id'   => $schedule['id'],
        ];

        $this->startTest();

        $newTask = $this->getDbLastEntity('schedule_task')->toArray();

        // Assert schedule Id and Entity Id
        $this->assertEquals($schedule['id'], $newTask['schedule_id']);
        $this->assertEquals($this->balance['id'], $newTask['entity_id']);

        // Assert that timestamps are same
        $this->assertEquals($oldTask['last_run_at'], $newTask['last_run_at']);
        $this->assertEquals($oldTask['next_run_at'], $newTask['next_run_at']);
        $this->assertNull($newTask['last_run_at']);
    }

    public function testUpdateFeeRecoveryScheduleTaskForMerchant()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $currentTimeStamp       = Carbon::now()->getTimestamp();
        $threeDaysLaterTimeStamp  = Carbon::now()->addDays(3)->getTimestamp();

        // Create a schedule
        $createScheduleRequest = [
            'method'  => 'POST',
            'url'     => '/schedules',
            'content'   => [
                'type'      => 'fee_recovery',
                'name'      => 'Original Schedule',
                'period'    => 'daily',
                'interval'  => 3,
            ],
        ];

        $this->ba->adminAuth();

        $schedule = $this->makeRequestAndGetContent($createScheduleRequest);

        // Create a second schedule
        $createScheduleRequest2 = [
            'method'  => 'POST',
            'url'     => '/schedules',
            'content'   => [
                'type'      => 'fee_recovery',
                'name'      => 'New Schedule',
                'period'    => 'daily',
                'interval'  => 7,
            ],
        ];

        $this->ba->adminAuth();

        $schedule2 = $this->makeRequestAndGetContent($createScheduleRequest2);

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content'] = [
            'balance_id'    => $this->balance->getId(),
            'schedule_id'   => $schedule['id'],
        ];

        //This will create a schedule task for schedule-1
        $this->startTest();

        $task = $this->getDbLastEntity('schedule_task')->toArray();

        $taskCountBeforeUpdation = $this->getDbEntities('schedule_task')->count();

        // Assert next run at and last run at (keeping 1 sec leeway for both timestamps because test takes time to run)
        // Cannot use Carbon::setTestNow() here since Carbon is used during the flow and then returns erroneous time.
        $this->assertGreaterThanOrEqual($currentTimeStamp, $task['last_run_at']);
        $this->assertLessThanOrEqual($currentTimeStamp + 1, $task['last_run_at']);
        $this->assertGreaterThanOrEqual($threeDaysLaterTimeStamp, $task['next_run_at']);
        $this->assertLessThanOrEqual($threeDaysLaterTimeStamp + 1, $task['next_run_at']);

        // Assert Schedule Id and Entity Id
        $this->assertEquals($schedule['id'], $task['schedule_id']);
        $this->assertEquals($this->balance['id'], $task['entity_id']);

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content'] = [
            'balance_id'    => $this->balance->getId(),
            'schedule_id'   => $schedule2['id'],
        ];

        //This will create a schedule task for schedule-2
        $this->startTest();

        $newTask = $this->getDbLastEntity('schedule_task')->toArray();

        $this->assertEquals($newTask['last_run_at'], $task['last_run_at']);
        $this->assertEquals($newTask['next_run_at'], $task['next_run_at']);
        $this->assertEquals($schedule2['id'], $newTask['schedule_id']);
        $this->assertEquals($this->balance['id'], $newTask['entity_id']);

        // Assert that old task was deleted

        $taskCountAfterUpdation = $this->getDbEntities('schedule_task')->count();

        $this->assertEquals($taskCountBeforeUpdation, $taskCountAfterUpdation);
    }

    protected function updateFtaAndSource($payout, $status, $utr = '933815233814')
    {
        $this->ba->ftsAuth();

        $request = [
            'method'  => 'POST',
            'url'     =>  '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1234567,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => $payout->getId(),
                'source_type'         => 'payout',
                'status'              => $status,
                'utr'                 => $utr
            ],
        ];

        $this->makeRequestAndGetContent($request);
    }

    public function testCreateManualRecoveryAfterFiveRetryFail()
    {
        $previousRecoveryRetryPayout = $this->testFeeRecoveryNoRetryAfterThreePayoutFTAReconFailed();

        $fifthAttemptFeeRecoveryEntities = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $previousRecoveryRetryPayout->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::UNRECOVERED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 3
            ])->toArray();

        $processedPayoutIds = [];

        $failedPayoutIds = [];

        $reversedPayoutIds = [];

        foreach ($fifthAttemptFeeRecoveryEntities as $fifthAttemptFeeRecoveryEntity)
        {
            if($fifthAttemptFeeRecoveryEntity['entity_type'] === 'payout')
            {
                if($fifthAttemptFeeRecoveryEntity['type'] === 'debit')
                {
                    $processedPayoutIds[] = $fifthAttemptFeeRecoveryEntity['entity_id'];
                }
                else
                {
                    $failedPayoutIds[] = $fifthAttemptFeeRecoveryEntity['entity_id'];
                }
            }
            else
            {
                $reversedPayoutIds[] = $fifthAttemptFeeRecoveryEntity['entity_id'];
            }
        }

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content'] = [
            'payout_ids'        => $processedPayoutIds,
            'failed_payout_ids' => $failedPayoutIds,
            'reversal_ids'      => $reversedPayoutIds,
            'balance_id'        => $previousRecoveryRetryPayout->getBalanceId(),
            'merchant_id'       => '10000000000000',
            'description'       => 'desc',
            'amount'            => 590,
            'reference_number'  => 'abcd'
        ];

        $this->ba->adminAuth();

        $this->startTest();

        //
        // Asserting that new fee recovery entities were created with status = manually_recovered
        //
        $manualFeeRecoveryList = $this->getDbEntities('fee_recovery',
                                                      [
                                                          'status' => FeeRecovery\Status::MANUALLY_RECOVERED,
                                                          FeeRecovery\Entity::ATTEMPT_NUMBER => 4,
                                                          FeeRecovery\Entity::RECOVERY_PAYOUT_ID => null
                                                      ])->toArray();

        $this->assertCount(count($fifthAttemptFeeRecoveryEntities), $manualFeeRecoveryList );

        foreach ($manualFeeRecoveryList as $manualFeeRecovery)
        {
            $this->assertContains($manualFeeRecovery['entity_id'], array_merge($failedPayoutIds, $processedPayoutIds, $reversedPayoutIds));
        }

        // Asserting that status of original entities was changed to failed
        $originalFeeRecoveryList = $this->getDbEntities('fee_recovery',
                                                    [
                                                        'status' => FeeRecovery\Status::FAILED,
                                                        FeeRecovery\Entity::ATTEMPT_NUMBER => 3,
                                                        FeeRecovery\Entity::RECOVERY_PAYOUT_ID => $previousRecoveryRetryPayout->getId()
                                                    ]);

        $this->assertCount(count($fifthAttemptFeeRecoveryEntities), $originalFeeRecoveryList );

        foreach ($originalFeeRecoveryList as $originalFeeRecovery)
        {
            $this->assertContains($originalFeeRecovery['entity_id'], array_merge($failedPayoutIds, $processedPayoutIds, $reversedPayoutIds));
        }
    }

    public function testCreateManualRecoveryIncorrectAmount()
    {
        $this->testCreateFeeRecoveryAtPayoutCreationForRBLPayouts();

        $payout = $this->getDbLastEntity('payout')->toArray();

        $payoutId = $payout['id'];

        $this->fixtures->edit('payout', $payoutId, ['status' => 'processed']);

        $this->fixtures->create('fee_recovery', [
            'entity_id'         => $payoutId,
            'entity_type'       => 'payout',
            'status'            => 'unrecovered',
            'attempt_number'    => 3,
            'type'              => 'debit',
        ]);

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content'] = [
            'payout_ids'        => [$payoutId],
            'balance_id'        => $this->balance['id'],
            'merchant_id'       => '10000000000000',
            'description'       => 'desc',
            'amount'            => 591,
            'reference_number'  => 'abcd'
        ];

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreateManualRecoveryWhereRecoveryAlreadyInProgress()
    {
        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbEntity('payout', ['purpose' => 'rzp_fees']);

        $feeRecoveryEntityRzpFees1 = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryPayout->getId()
            ]);

        // -----------------------------------------------------
        // normal attempt at 1 fail and start retry for Attempt 2
        // -----------------------------------------------------

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::FAILED, '933815383818');

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryEntityRzpFees2 = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryEntityRzpFees1['entity_id'],
                FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
            ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryEntityRzpFees2['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryEntityRzpFees2['status']);
        $this->assertEquals(0, $feeRecoveryEntityRzpFees2['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryEntityRzpFees1['type']);
        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryEntityRzpFees2['type']);

        $this->assertNull($feeRecoveryEntityRzpFees2['recovery_payout_id']);

        $feeRecoveryRetryPayout = $this->getDbLastEntity('payout');

        $feeRecoveryRetryPayout->setStatus(Payout\Status::INITIATED);
        $feeRecoveryRetryPayout->saveOrFail();

        $feeRecoveryEntities = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryPayout->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 1
            ])->toArray();

        $feeRecoveryRetryEntities = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayout->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::PROCESSING,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
            ])->toArray();

        $this->assertSameSize($feeRecoveryEntities, $feeRecoveryRetryEntities);

        $feeRecoveryRetryEntityRzpFeesDebit = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayout->getId()
            ])->toArray();

        // Assert that we are creating a new fee recovery entity
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryRetryEntityRzpFeesDebit['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryRetryEntityRzpFeesDebit['status']);
        $this->assertEquals(0, $feeRecoveryRetryEntityRzpFeesDebit['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryRetryEntityRzpFeesDebit['type']);

        $processedPayoutIds = [];

        $failedPayoutIds = [];

        $reversedPayoutIds = [];

        foreach ($feeRecoveryRetryEntities as $feeRecoveryRetryEntity)
        {
            if($feeRecoveryRetryEntity['entity_type'] === 'payout')
            {
                if($feeRecoveryRetryEntity['type'] === 'debit')
                {
                    $processedPayoutIds[] = $feeRecoveryRetryEntity['entity_id'];
                }
                else
                {
                    $failedPayoutIds[] = $feeRecoveryRetryEntity['entity_id'];
                }
            }
            else
            {
                $reversedPayoutIds[] = $feeRecoveryRetryEntity['entity_id'];
            }
        }

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content'] = [
            'payout_ids'        => $processedPayoutIds,
            'failed_payout_ids' => $failedPayoutIds,
            'reversal_ids'      => $reversedPayoutIds,
            'balance_id'        => $feeRecoveryRetryPayout->getBalanceId(),
            'merchant_id'       => '10000000000000',
            'description'       => 'desc',
            'amount'            => 590,
            'reference_number'  => 'abcd'
        ];

        $this->ba->adminAuth();

        $this->startTest();
    }

    // This test covers what happens if we pass wrong IDs in the input. As an example
    public function testCreateManualRecoveryWhenWrongIdsPassedInInput()
    {
        // Random old timestamp. Does not matter what since manual recovery happens based on Ids rather than timestamps
        $oldTimeStamp = Carbon::now()->subWeek()->getTimestamp();

        $this->testCreateFeeRecoveryAtPayoutCreationForRBLPayouts();

        $payout = $this->getDbLastEntity('payout')->toArray();

        $fundAccount = $this->getDbLastEntity('fund_account');

        // Create second payout
        $this->createPayoutForFundAccount($fundAccount, $this->balance);

        $payout2 = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout2['id'], ['initiated_at' => $oldTimeStamp]);

        // Fail the second payout
        $this->updateFtaAndSource($payout2, Payout\Status::FAILED);

        // Create reversal for the second payout
        $this->fixtures->reversal->createPayoutReversal([
            'merchant_id'   => '10000000000000',
            'entity_id'     => $payout2['id'],
            'entity_type'   => 'payout',
            'balance_id'    => $this->balance->getId(),
            'amount'        => $payout2['amount'],
            'fee'           => 0,
            'tax'           => 0,
            'channel'       => 'rbl',
        ]);

        $reversalForPayout2 = $this->getDbLastEntity('reversal');

        // Create a third payout
        $this->createPayoutForFundAccount($fundAccount, $this->balance);

        $payout3 = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout3['id'], ['initiated_at' => $oldTimeStamp]);

        // Updating FTA and Payout status to initiated to allow transition to reversed
        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $payout3->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::INITIATED]);

        $this->fixtures->edit('payout', $payout3->getId(), ['status' => Payout\Status::INITIATED]);

        // Reverse the third payout
        $this->updateFtaAndSource($payout3, Payout\Status::REVERSED,'944926344925');

        $reversalForPayout3 = $this->getDbLastEntity('reversal');

        $data = & $this->testData[__FUNCTION__];

        // payout2 is in failed and reversed both which should not be the case.
        $data['request']['content'] = [
            'payout_ids'        => [$payout['id'], $payout2['id'], $payout3['id']],
            'failed_payout_ids' => [$payout2['id']],
            'reversal_ids'      => [$reversalForPayout2['id'], $reversalForPayout3['id']],
            'balance_id'        => $this->balance['id'],
            'merchant_id'       => '10000000000000',
            'description'       => 'desc',
            'amount'            => 590,
            'reference_number'  => 'abcd'
        ];

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreateFeeRecoveryPayoutWithFailedToReversedCase()
    {
        $oldTime = Carbon::create(2020, 1, 3, null, null, null);

        Carbon::setTestNow($oldTime);

        $oldTimeStamp = $oldTime->getTimestamp();

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->bankingBalance);

        // Create first payout
        $this->testCreateFeeRecoveryAtPayoutCreationForRBLPayouts();

        $payout = $this->getDbLastEntity('payout');

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->fixtures->edit('payout', $payout['id'], ['initiated_at' => $oldTimeStamp]);

        // Create second payout
        $this->createPayoutForFundAccount($fundAccount, $this->balance);

        $payout2 = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout2['id'], ['initiated_at' => $oldTimeStamp]);

        // Create a third payout
        $this->createPayoutForFundAccount($fundAccount, $this->balance);

        $payout3 = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout3['id'], ['initiated_at' => $oldTimeStamp]);

        // Fail the third payout
        $this->updateFtaAndSource($payout3, Payout\Status::FAILED);

        // Reverse the third payout (This happens at BAS fetch, so we shall mimic it via fixtures)
        $this->fixtures->edit('payout', $payout3['id'], [
            'reversed_at' => $oldTimeStamp,
            'status'      => Payout\Status::REVERSED
        ]);

        // Create reversal for the third payout
        // We shall not create any fee_recovery entry since that gets skipped during the failed->reversed status change
        $this->fixtures->reversal->createPayoutReversal(
            [
                'merchant_id'   => '10000000000000',
                'entity_id'     => $payout3['id'],
                'entity_type'   => 'payout',
                'balance_id'    => $this->balance->getId(),
                'amount'        => $payout3['amount'],
                'fee'           => 0,
                'tax'           => 0,
                'channel'       => 'rbl',
            ]);

        $this->fixtures->edit('contact', '1010101contact', ['type' => 'rzp_fees']);

        $balanceId = $this->balance->getId();

        $startTime = Carbon::create(2020, 1, 1, null, null, null)->getTimestamp();
        $endTime   = Carbon::create(2020, 1, 8, null, null, null)->getTimestamp();

        $data = & $this->testData[__FUNCTION__];

        $data['request']['content'] = [
            'balance_id'    => $balanceId,
            'from'          => $startTime,
            'to'            => $endTime,
        ];

        $this->ba->adminAuth();

        $this->startTest();

        $feeRecoveryPayout = $this->getDbLastEntity('payout');

        // Moving this payout to initiated
        $feeRecoveryPayout->setStatus(Payout\Status::INITIATED);
        $feeRecoveryPayout->saveOrFail();

        $feeRecoveryList = $this->getDbEntities('fee_recovery')->toArray();

        // Assert count of fee Recovery Entities
        $this->assertEquals(5, count($feeRecoveryList));

        // Fee Recovery entity for initiated payout 1
        $this->assertEquals($payout['id'], $feeRecoveryList[0]['entity_id']);
        $this->assertEquals(FeeRecovery\Status::PROCESSING, $feeRecoveryList[0]['status']);
        $this->assertEquals($feeRecoveryList[0]['type'], FeeRecovery\Type::DEBIT);
        $this->assertEquals(1, $feeRecoveryList[0]['attempt_number']);
        $this->assertEquals($feeRecoveryList[0]['recovery_payout_id'], $feeRecoveryPayout['id']);

        // Fee Recovery entity for initiated payout 2
        $this->assertEquals($payout2['id'], $feeRecoveryList[1]['entity_id']);
        $this->assertEquals(FeeRecovery\Status::PROCESSING, $feeRecoveryList[1]['status']);
        $this->assertEquals($feeRecoveryList[1]['type'], FeeRecovery\Type::DEBIT);
        $this->assertEquals(1, $feeRecoveryList[1]['attempt_number']);
        $this->assertEquals($feeRecoveryList[1]['recovery_payout_id'], $feeRecoveryPayout['id']);

        // Fee Recovery entity for initiated payout 3
        $this->assertEquals($payout3['id'], $feeRecoveryList[2]['entity_id']);
        $this->assertEquals(FeeRecovery\Status::PROCESSING, $feeRecoveryList[2]['status']);
        $this->assertEquals($feeRecoveryList[2]['type'], FeeRecovery\Type::DEBIT);
        $this->assertEquals(1, $feeRecoveryList[2]['attempt_number']);
        $this->assertEquals($feeRecoveryList[2]['recovery_payout_id'], $feeRecoveryPayout['id']);

        // Fee Recovery entity for failed payout 3
        $this->assertEquals($payout3['id'], $feeRecoveryList[3]['entity_id']);
        $this->assertEquals(FeeRecovery\Status::PROCESSING, $feeRecoveryList[3]['status']);
        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryList[3]['type']);
        $this->assertEquals(1, $feeRecoveryList[3]['attempt_number']);
        $this->assertEquals($feeRecoveryList[3]['recovery_payout_id'], $feeRecoveryPayout['id']);

        // Fee Recovery entity for recovery payout (Corresponding to the rzp_fees payout)
        $this->assertEquals($feeRecoveryPayout['id'], $feeRecoveryList[4]['entity_id']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryList[4]['status']);
        $this->assertEquals(0, $feeRecoveryList[4]['attempt_number']);
        $this->assertNull($feeRecoveryList[4]['recovery_payout_id']);
    }


    public function createVirtualBankingAccount()
    {
        $balanceAttributes = [
            'balance' => 10000000,
            'balanceType' => 'shared',
            'channel' => null,
        ];

        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balanceAttributes["balance"],
            '10000000000000',
            $balanceAttributes["balanceType"] ,
            $balanceAttributes["channel"]
        );

        $virtualAccount = $this->fixtures->create('virtual_account');
        $bankAccount    = $this->fixtures->create(
            'bank_account',
            [
                'type'           => 'virtual_account',
                'entity_id'      => $virtualAccount->getId(),
                'account_number' => '2224440041626906',
                'ifsc_code'      => 'RAZRB000000',
            ]);

        $virtualAccount->bankAccount()->associate($bankAccount);
        $virtualAccount->balance()->associate($bankingBalance);
        $virtualAccount->save();

        $bankingBalance->setAccountNumber($virtualAccount->bankAccount->getAccountNumber());
        $bankingBalance->save();

        $defaultFreePayoutsCountConstantName = 'DEFAULT_FREE_SHARED_ACCOUNT_PAYOUTS_COUNT_SLAB1';

        $defaultFreePayoutsCount = constant(FreePayout::class . '::' . $defaultFreePayoutsCountConstantName);

        $this->fixtures->create('counter', [
            'account_type'          => 'shared',
            'balance_id'            => $bankingBalance->getId(),
            'free_payouts_consumed' => $defaultFreePayoutsCount,
        ]);

        return $bankingBalance;
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
            'content'   => $content
        ];

        $this->ba->privateAuth();

        $this->makeRequestAndGetContent($request);

        // Adding this here so that all payouts that get created go to initiated state automatically
        $this->initiatePayoutFromCreated();
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

    protected function createContact()
    {
        $contact = $this->fixtures->create(
            'contact',
            [
                'id'        => '1010101contact',
                'email'     => 'rzp@rzp.com',
                'contact'   => '8989898989',
                'name'      => 'wckd',
            ]
        );

        return $contact;
    }

    protected function createFundAccountForContact($contact)
    {
        $fundAccount = $this->fixtures->fund_account->createBankAccount(
            [
                'source_type' => 'contact',
                'source_id'   => $contact->getId(),
            ],
            [
                'name'           => "test",
                'ifsc'           => 'RATN0000011',
                'account_number' => '111000111000',
            ]);

        return $fundAccount;
    }

    // tests outstanding fees to be recovered when fee recovery payout is initiated for 3 payouts
    // out of which one is failed and other is reversed
    public function testOutstandingFeesToBeRecovered()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->testCreateFeeRecoveryPayout();

        $this->ba->proxyAuth();

        $request = [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
            'content' => [],
        ];

        $observedResponse = $this->makeRequestAndGetContent($request);

        $observedResponse = array_filter($observedResponse['items'],function($item){
            return $item['channel'] === 'rbl';
        });

        $observedResponse = reset($observedResponse);

        $expectedResponse = [
            'id'             => 'bacc_'. $this->bankingAccount->getId(),
            'channel'        => "rbl",
            'merchant_id'    => "10000000000000",
            'account_number' => "2224440041626905",
            'balance'        => [
                'id'             => $this->bankingBalance->getId(),
                'balance'        => 10000,
                'currency'       => "INR",
                'locked_balance' => 0,
            ],
            'fee_recovery_details' => [
                'outstanding_amount' => 590,
                'last_deducted_at'   => null,
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $observedResponse);
    }

    //tests if fee recovery outstanding amount is present and feature is on
    // then don't expose fee_recovery_details in banking_accounts get api
    public function testOutstandingFeesPresentButNotExposedInBankingAccountsGet()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->fixtures->merchant->addFeatures([Feature\Constants::SKIP_EXPOSE_FEE_RECOVERY]);

        $this->testCreateFeeRecoveryPayout();

        $this->ba->proxyAuth();

        $request = [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
            'content' => [],
        ];

        $observedResponse = $this->makeRequestAndGetContent($request);

        $observedResponse = array_filter($observedResponse['items'],function($item){
            return $item['channel'] === 'rbl';
        });

        $observedResponse = reset($observedResponse);

        $expectedResponse = [
            'id'             => 'bacc_'. $this->bankingAccount->getId(),
            'channel'        => "rbl",
            'merchant_id'    => "10000000000000",
            'account_number' => "2224440041626905",
            'balance'        => [
                'id'             => $this->bankingBalance->getId(),
                'balance'        => 10000,
                'currency'       => "INR",
                'locked_balance' => 0,
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $observedResponse);
        $this->assertArrayNotHasKey('fee_recovery_details', $observedResponse);
    }

    //tests if fee recovery outstanding amount is present and ui sends fee_recovery flag in input as 0
    // then don't expose fee_recovery_details in banking_accounts get api
    public function testOutstandingFeesPresentButNotExposedInBankingAccountsGet2()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->testCreateFeeRecoveryPayout();

        $this->ba->proxyAuth();

        $request = [
            'url'     => '/banking_accounts?fee_recovery=0',
            'method'  => 'GET',
            'content' => [],
        ];

        $observedResponse = $this->makeRequestAndGetContent($request);

        $observedResponse = array_filter($observedResponse['items'],function($item){
            return $item['channel'] === 'rbl';
        });

        $observedResponse = reset($observedResponse);

        $expectedResponse = [
            'id'             => 'bacc_'. $this->bankingAccount->getId(),
            'channel'        => "rbl",
            'merchant_id'    => "10000000000000",
            'account_number' => "2224440041626905",
            'balance'        => [
                'id'             => $this->bankingBalance->getId(),
                'balance'        => 10000,
                'currency'       => "INR",
                'locked_balance' => 0,
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $observedResponse);
        $this->assertArrayNotHasKey('fee_recovery_details', $observedResponse);
    }

    // tests outstanding fees to be recovered when fee recovery payout is initiated for 3 payouts
    // out of which one is failed and other is reversed. In this case Fee recovery payouts is processed.
    // hence no outstanding amount. Last deducted at will also be equal to processed_at of fee recovery_payout
    public function testOutstandingFeesToBeRecoveredWhenFeeRecoveryPayoutsAreProcessed()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbLastEntity('payout');

        $oldTime =  Carbon::create(2020, 9, 3, null, null, null);

        Carbon::setTestNow($oldTime);

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::PROCESSED, '933818903814');

        $this->ba->proxyAuth();

        $request = [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
            'content' => [],
        ];

        $observedResponse = $this->makeRequestAndGetContent($request);

        $observedResponse = array_filter($observedResponse['items'],function($item){
            return $item['channel'] === 'rbl';
        });

        $observedResponse = reset($observedResponse);

        $expectedResponse = [
            'id'             => 'bacc_'. $this->bankingAccount->getId(),
            'channel'        => "rbl",
            'merchant_id'    => "10000000000000",
            'account_number' => "2224440041626905",
            'balance'        => [
                'id'             => $this->bankingBalance->getId(),
                'balance'        => 10000,
                'currency'       => "INR",
                'locked_balance' => 0,
            ],
            'fee_recovery_details' => [
                'outstanding_amount' => 0,
                'last_deducted_at'   => $oldTime->getTimestamp(),
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $observedResponse);
    }

    // tests outstanding fees to be recovered when fee recovery payout is initiated for 3 payouts
    // out of which one is failed and other is reversed. In this case first Fee recovery payouts is processed.
    // so outstanding amount. Last deducted at will also be equal to processed_at of fee recovery_payout
    // Later two new payouts are made then hence some outstanding amount to be recovered
    public function testFeeRecoveryDetails()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbLastEntity('payout');

        $oldTime =  Carbon::create(2020, 9, 3, 12, 23, 45);

        Carbon::setTestNow($oldTime);

        // update fee_recovery payout to processed
        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::PROCESSED, '933818903814');

        // create a new payout
        $newTime =  Carbon::create(2020, 10, 3, null, null, null);

        Carbon::setTestNow($newTime);

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->bankingBalance);

        $this->createPayoutForFundAccount($this->fundAccount, $this->bankingBalance);

        $newPayout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $newPayout['id'], ['initiated_at' => $newTime->getTimestamp()]);

        // create another payout

        $this->createPayoutForFundAccount($this->fundAccount, $this->bankingBalance);

        $newPayout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $newPayout['id'], ['initiated_at' => $newTime->getTimestamp()]);

        // get banking_accounts
        $this->ba->proxyAuth();

        $request = [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
            'content' => [],
        ];

        $observedResponse = $this->makeRequestAndGetContent($request);

        $observedResponse = array_filter($observedResponse['items'],function($item){
            return $item['channel'] === 'rbl';
        });

        $observedResponse = reset($observedResponse);

        $expectedResponse = [
            'id'             => 'bacc_'. $this->bankingAccount->getId(),
            'channel'        => "rbl",
            'merchant_id'    => "10000000000000",
            'account_number' => "2224440041626905",
            'balance'        => [
                'id'             => $this->bankingBalance->getId(),
                'balance'        => 10000,
                'currency'       => "INR",
                'locked_balance' => 0,
            ],
            'fee_recovery_details' => [
                'outstanding_amount' => 1180,
                'last_deducted_at'   => $oldTime->getTimestamp(),
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $observedResponse);
    }

    public function testFeeRecoveryRetryAfterOnePayoutFTAReconFailedSecondSuccess()
    {
        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbEntity('payout', ['purpose' => 'rzp_fees']);

        $feeRecoveryEntityRzpFees1 = $this->getDbEntity('fee_recovery',
                                                [
                                                    FeeRecoveryEntity::ENTITY_ID => $feeRecoveryPayout->getId()
                                                ]);

        // -----------------------------------------------------
        // normal attempt at 1 fail and start retry for Attempt 2
        // -----------------------------------------------------

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::FAILED, '933815383818');

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryEntityRzpFees2 = $this->getDbEntity('fee_recovery',
                                                [
                                                    FeeRecoveryEntity::ENTITY_ID => $feeRecoveryEntityRzpFees1['entity_id'],
                                                    FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
                                                ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryEntityRzpFees2['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryEntityRzpFees2['status']);
        $this->assertEquals(0, $feeRecoveryEntityRzpFees2['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryEntityRzpFees1['type']);
        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryEntityRzpFees2['type']);

        $this->assertNull($feeRecoveryEntityRzpFees2['recovery_payout_id']);

        $feeRecoveryRetryPayout = $this->getDbLastEntity('payout');

        $feeRecoveryRetryPayout->setStatus(Payout\Status::INITIATED);
        $feeRecoveryRetryPayout->saveOrFail();

        $feeRecoveryEntities = $this->getDbEntities('fee_recovery',
                                                   [
                                                       FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryPayout->getId(),
                                                       FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                                                       FeeRecoveryEntity::ATTEMPT_NUMBER     => 1
                                                   ])->toArray();

        $feeRecoveryRetryEntities = $this->getDbEntities('fee_recovery',
                                                        [
                                                            FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayout->getId(),
                                                            FeeRecoveryEntity::STATUS             => FeeRecovery\Status::PROCESSING,
                                                            FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
                                                        ])->toArray();

        $this->assertSameSize($feeRecoveryEntities, $feeRecoveryRetryEntities);

        $feeRecoveryRetryEntityRzpFeesDebit = $this->getDbEntity('fee_recovery',
                                                                [
                                                                    FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayout->getId()
                                                                ])->toArray();

        // Assert that we are creating a new fee recovery entity
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryRetryEntityRzpFeesDebit['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryRetryEntityRzpFeesDebit['status']);
        $this->assertEquals(0, $feeRecoveryRetryEntityRzpFeesDebit['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryRetryEntityRzpFeesDebit['type']);

        $this->updateFtaAndSource($feeRecoveryRetryPayout, Payout\Status::PROCESSED, '933815383814');

        $feeRecoveryRetryEntitiesAfterSuccess = $this->getDbEntities('fee_recovery',
                                                        [
                                                            FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayout->getId(),
                                                            FeeRecoveryEntity::STATUS             => FeeRecovery\Status::RECOVERED,
                                                            FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
                                                        ])->toArray();

        $this->assertSameSize($feeRecoveryRetryEntities, $feeRecoveryRetryEntitiesAfterSuccess);
    }

    public function testFeeRecoveryRetryAfterTwoPayoutFTAReconFailedThirdSuccess()
    {
        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbEntity('payout', ['purpose' => 'rzp_fees']);

        $feeRecoveryEntityRzpFees1 = $this->getDbEntity('fee_recovery',
                                                       [
                                                           FeeRecoveryEntity::ENTITY_ID => $feeRecoveryPayout->getId()
                                                       ]);

        // -----------------------------------------------------
        // normal attempt at 1 fail and start retry for Attempt 2
        // -----------------------------------------------------

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::FAILED, '933815383818');

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryEntityRzpFees2 = $this->getDbEntity('fee_recovery',
                                                       [
                                                           FeeRecoveryEntity::ENTITY_ID => $feeRecoveryEntityRzpFees1['entity_id'],
                                                           FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
                                                       ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryEntityRzpFees2['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryEntityRzpFees2['status']);
        $this->assertEquals(0, $feeRecoveryEntityRzpFees2['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryEntityRzpFees1['type']);
        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryEntityRzpFees2['type']);

        $this->assertNull($feeRecoveryEntityRzpFees2['recovery_payout_id']);

        $feeRecoveryRetryPayoutAttemptOne = $this->getDbLastEntity('payout');

        $feeRecoveryRetryPayoutAttemptOne->setStatus(Payout\Status::INITIATED);
        $feeRecoveryRetryPayoutAttemptOne->saveOrFail();

        $feeRecoveryEntities = $this->getDbEntities('fee_recovery',
                                                   [
                                                       FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryPayout->getId(),
                                                       FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                                                       FeeRecoveryEntity::ATTEMPT_NUMBER     => 1
                                                   ])->toArray();

        $feeRecoveryRetryEntitiesAttemptOne = $this->getDbEntities('fee_recovery',
                                                        [
                                                            FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                                                            FeeRecoveryEntity::STATUS             => FeeRecovery\Status::PROCESSING,
                                                            FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
                                                        ])->toArray();

        $this->assertSameSize($feeRecoveryEntities, $feeRecoveryRetryEntitiesAttemptOne);

        $feeRecoveryRetryEntityRzpFeesAttemptOneDebit = $this->getDbEntity('fee_recovery',
                                                                          [
                                                                              FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptOne->getId()
                                                                          ])->toArray();

        // -----------------------------------------------------
        // retry attempt at 2 fail and start retry for Attempt 3
        // -----------------------------------------------------

        $this->updateFtaAndSource($feeRecoveryRetryPayoutAttemptOne, Payout\Status::FAILED, '933815383814');

        $feeRecoveryRetryEntitiesAfterFail = $this->getDbEntities('fee_recovery',
                                                                    [
                                                                        FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                                                                        FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                                                                        FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
                                                                    ])->toArray();

        $this->assertSameSize($feeRecoveryRetryEntitiesAttemptOne, $feeRecoveryRetryEntitiesAfterFail);

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryRetryEntityRzpFeesAttemptOneCredit = $this->getDbEntity('fee_recovery',
                                                                           [
                                                                               FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                                                                               FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
                                                                           ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['status']);
        $this->assertEquals(0, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryRetryEntityRzpFeesAttemptOneDebit['type']);
        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['type']);

        $this->assertNull($feeRecoveryEntityRzpFees2['recovery_payout_id']);

        $feeRecoveryRetryPayoutAttemptTwo = $this->getDbLastEntity('payout');

        $feeRecoveryRetryPayoutAttemptTwo->setStatus(Payout\Status::INITIATED);
        $feeRecoveryRetryPayoutAttemptTwo->saveOrFail();

        $feeRecoveryEntitiesAttemptOne = $this->getDbEntities('fee_recovery',
                                                             [
                                                                 FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                                                                 FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                                                                 FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
                                                             ])->toArray();

        $feeRecoveryRetryEntitiesAttemptTwo = $this->getDbEntities('fee_recovery',
                                                                  [
                                                                      FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptTwo->getId(),
                                                                      FeeRecoveryEntity::STATUS             => FeeRecovery\Status::PROCESSING,
                                                                      FeeRecoveryEntity::ATTEMPT_NUMBER     => 3
                                                                  ])->toArray();

        $this->assertSameSize($feeRecoveryEntitiesAttemptOne, $feeRecoveryRetryEntitiesAttemptTwo);

        $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit = $this->getDbEntity('fee_recovery',
                                                                          [
                                                                              FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptTwo->getId()
                                                                          ])->toArray();

        // Assert that we are creating a new fee recovery entity
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['status']);
        $this->assertEquals(0, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['type']);

        $this->updateFtaAndSource($feeRecoveryRetryPayoutAttemptTwo, Payout\Status::PROCESSED, '933815383819');

        $feeRecoveryRetryEntitiesAfterSuccessAttemptTwo = $this->getDbEntities('fee_recovery',
                                                                              [
                                                                                  FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptTwo->getId(),
                                                                                  FeeRecoveryEntity::STATUS             => FeeRecovery\Status::RECOVERED,
                                                                                  FeeRecoveryEntity::ATTEMPT_NUMBER     => 3
                                                                              ])->toArray();

        $this->assertSameSize($feeRecoveryRetryEntitiesAttemptTwo, $feeRecoveryRetryEntitiesAfterSuccessAttemptTwo);

        return $feeRecoveryRetryPayoutAttemptTwo;
    }

    public function testFeeRecoveryNoRetryAfterThreePayoutFTAReconFailed()
    {
        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbEntity('payout', ['purpose' => 'rzp_fees']);

        $feeRecoveryEntityRzpFees1 = $this->getDbEntity('fee_recovery',
                                                       [
                                                           FeeRecoveryEntity::ENTITY_ID => $feeRecoveryPayout->getId()
                                                       ]);

        // -----------------------------------------------------
        // normal attempt at 1 fail and start retry for Attempt 2
        // -----------------------------------------------------

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::FAILED, '933815383818');

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryEntityRzpFees2 = $this->getDbEntity('fee_recovery',
                                                       [
                                                           FeeRecoveryEntity::ENTITY_ID => $feeRecoveryEntityRzpFees1['entity_id'],
                                                           FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
                                                       ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryEntityRzpFees2['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryEntityRzpFees2['status']);
        $this->assertEquals(0, $feeRecoveryEntityRzpFees2['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryEntityRzpFees1['type']);
        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryEntityRzpFees2['type']);

        $this->assertNull($feeRecoveryEntityRzpFees2['recovery_payout_id']);

        $feeRecoveryRetryPayoutAttemptOne = $this->getDbLastEntity('payout');

        $feeRecoveryRetryPayoutAttemptOne->setStatus(Payout\Status::INITIATED);
        $feeRecoveryRetryPayoutAttemptOne->saveOrFail();

        $feeRecoveryEntities = $this->getDbEntities('fee_recovery',
                                                   [
                                                       FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryPayout->getId(),
                                                       FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                                                       FeeRecoveryEntity::ATTEMPT_NUMBER     => 1
                                                   ])->toArray();

        $feeRecoveryRetryEntitiesAttemptOne = $this->getDbEntities('fee_recovery',
                                                                  [
                                                                      FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                                                                      FeeRecoveryEntity::STATUS             => FeeRecovery\Status::PROCESSING,
                                                                      FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
                                                                  ])->toArray();

        $this->assertSameSize($feeRecoveryEntities, $feeRecoveryRetryEntitiesAttemptOne);

        $feeRecoveryRetryEntityRzpFeesAttemptOneDebit = $this->getDbEntity('fee_recovery',
                                                                          [
                                                                              FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                                                                              FeeRecoveryEntity::TYPE      => FeeRecovery\Type::DEBIT
                                                                          ])->toArray();

        // -----------------------------------------------------
        // retry attempt at 2 fail and start retry for Attempt 3
        // -----------------------------------------------------

        $this->updateFtaAndSource($feeRecoveryRetryPayoutAttemptOne, Payout\Status::FAILED, '933815383814');

        $feeRecoveryRetryEntitiesAfterFail = $this->getDbEntities('fee_recovery',
                                                                 [
                                                                     FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                                                                     FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                                                                     FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
                                                                 ])->toArray();

        $this->assertSameSize($feeRecoveryRetryEntitiesAttemptOne, $feeRecoveryRetryEntitiesAfterFail);

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryRetryEntityRzpFeesAttemptOneCredit = $this->getDbEntity('fee_recovery',
                                                                           [
                                                                               FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                                                                               FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
                                                                           ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['status']);
        $this->assertEquals(0, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryRetryEntityRzpFeesAttemptOneDebit['type']);
        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['type']);

        $this->assertNull($feeRecoveryRetryEntityRzpFeesAttemptOneCredit['recovery_payout_id']);

        $feeRecoveryRetryPayoutAttemptTwo = $this->getDbLastEntity('payout');

        $feeRecoveryRetryPayoutAttemptTwo->setStatus(Payout\Status::INITIATED);
        $feeRecoveryRetryPayoutAttemptTwo->saveOrFail();

        $feeRecoveryEntitiesAttemptOne = $this->getDbEntities('fee_recovery',
                                                             [
                                                                 FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                                                                 FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                                                                 FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
                                                             ])->toArray();

        $feeRecoveryRetryEntitiesAttemptTwo = $this->getDbEntities('fee_recovery',
                                                                  [
                                                                      FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptTwo->getId(),
                                                                      FeeRecoveryEntity::STATUS             => FeeRecovery\Status::PROCESSING,
                                                                      FeeRecoveryEntity::ATTEMPT_NUMBER     => 3
                                                                  ])->toArray();

        $this->assertSameSize($feeRecoveryEntitiesAttemptOne, $feeRecoveryRetryEntitiesAttemptTwo);

        $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit = $this->getDbEntity('fee_recovery',
                                                                          [
                                                                              FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptTwo->getId(),
                                                                              FeeRecoveryEntity::TYPE      => FeeRecovery\Type::DEBIT
                                                                          ])->toArray();

        // Assert that we are creating a new fee recovery entity
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['status']);
        $this->assertEquals(0, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['type']);

        $payoutsBeforeFinalFail = $this->getDbEntities('payout')->toArray();

        $this->updateFtaAndSource($feeRecoveryRetryPayoutAttemptTwo, Payout\Status::FAILED, '933815383819');

        $feeRecoveryRetryEntitiesAfterSuccessAttemptTwo = $this->getDbEntities('fee_recovery',
                                                                              [
                                                                                  FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptTwo->getId(),
                                                                                  FeeRecoveryEntity::STATUS             => FeeRecovery\Status::UNRECOVERED,
                                                                                  FeeRecoveryEntity::ATTEMPT_NUMBER     => 3
                                                                              ])->toArray();

        $this->assertSameSize($feeRecoveryRetryEntitiesAttemptTwo, $feeRecoveryRetryEntitiesAfterSuccessAttemptTwo);

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryRetryEntityRzpFeesAttemptTwoCredit = $this->getDbEntity('fee_recovery',
                                                                           [
                                                                               FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptTwo->getId(),
                                                                               FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
                                                                           ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryRetryEntityRzpFeesAttemptTwoCredit['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryRetryEntityRzpFeesAttemptTwoCredit['status']);
        $this->assertEquals(0, $feeRecoveryRetryEntityRzpFeesAttemptTwoCredit['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryRetryEntityRzpFeesAttemptTwoCredit['type']);

        $this->assertNull($feeRecoveryRetryEntityRzpFeesAttemptTwoCredit['recovery_payout_id']);

        $payoutsAfterFinalFail = $this->getDbEntities('payout')->toArray();

        $this->assertSameSize($payoutsBeforeFinalFail, $payoutsAfterFinalFail);

        return $feeRecoveryRetryPayoutAttemptTwo;
    }

    public function testFeeRecoveryNoRetryAfterThreePayoutFTAReconReversed()
    {
        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbEntity('payout', ['purpose' => 'rzp_fees']);

        $feeRecoveryEntityRzpFees1 = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryPayout->getId()
            ]);

        // -----------------------------------------------------
        // normal attempt at 1 fail and start retry for Attempt 2
        // -----------------------------------------------------

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $feeRecoveryPayout->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::INITIATED]);

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::REVERSED, '933815383818');

        $feeRecoveryPayoutReversal = $this->getDbLastEntity('reversal');

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryEntityRzpFees2 = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryPayoutReversal->getId(),
                FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
            ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::REVERSAL, $feeRecoveryEntityRzpFees2['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryEntityRzpFees2['status']);
        $this->assertEquals(0, $feeRecoveryEntityRzpFees2['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryEntityRzpFees1['type']);
        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryEntityRzpFees2['type']);

        $this->assertNull($feeRecoveryEntityRzpFees2['recovery_payout_id']);

        $feeRecoveryRetryPayoutAttemptOne = $this->getDbLastEntity('payout');

        $feeRecoveryRetryPayoutAttemptOne->setStatus(Payout\Status::INITIATED);
        $feeRecoveryRetryPayoutAttemptOne->saveOrFail();

        $feeRecoveryEntities = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryPayout->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 1
            ])->toArray();

        $feeRecoveryRetryEntitiesAttemptOne = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::PROCESSING,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
            ])->toArray();

        $this->assertSameSize($feeRecoveryEntities, $feeRecoveryRetryEntitiesAttemptOne);

        $feeRecoveryRetryEntityRzpFeesAttemptOneDebit = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptOne->getId()
            ])->toArray();

        // -----------------------------------------------------
        // retry attempt at 2 fail and start retry for Attempt 3
        // -----------------------------------------------------

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $feeRecoveryRetryPayoutAttemptOne->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::INITIATED]);

        $this->updateFtaAndSource($feeRecoveryRetryPayoutAttemptOne, Payout\Status::REVERSED, '933815383814');

        $feeRecoveryRetryEntitiesAfterFail = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
            ])->toArray();

        $this->assertSameSize($feeRecoveryRetryEntitiesAttemptOne, $feeRecoveryRetryEntitiesAfterFail);

        $feeRecoveryRetryPayoutAttemptOneReversal = $this->getDbLastEntity('reversal');

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryRetryEntityRzpFeesAttemptOneCredit = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptOneReversal->getId(),
                FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
            ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::REVERSAL, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['status']);
        $this->assertEquals(0, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryRetryEntityRzpFeesAttemptOneDebit['type']);
        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['type']);

        $this->assertNull($feeRecoveryRetryEntityRzpFeesAttemptOneCredit['recovery_payout_id']);

        $feeRecoveryRetryPayoutAttemptTwo = $this->getDbLastEntity('payout');

        $feeRecoveryRetryPayoutAttemptTwo->setStatus(Payout\Status::INITIATED);
        $feeRecoveryRetryPayoutAttemptTwo->saveOrFail();

        $feeRecoveryEntitiesAttemptOne = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
            ])->toArray();

        $feeRecoveryRetryEntitiesAttemptTwo = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptTwo->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::PROCESSING,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 3
            ])->toArray();

        $this->assertSameSize($feeRecoveryEntitiesAttemptOne, $feeRecoveryRetryEntitiesAttemptTwo);

        $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptTwo->getId()
            ])->toArray();

        // Assert that we are creating a new fee recovery entity
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['status']);
        $this->assertEquals(0, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['type']);

        $payoutsBeforeFinalFail = $this->getDbEntities('payout')->toArray();

        $this->updateFtaAndSource($feeRecoveryRetryPayoutAttemptTwo, Payout\Status::FAILED, '933815383819');

        $feeRecoveryRetryEntitiesAfterSuccessAttemptTwo = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptTwo->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::UNRECOVERED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 3
            ])->toArray();

        $this->assertSameSize($feeRecoveryRetryEntitiesAttemptTwo, $feeRecoveryRetryEntitiesAfterSuccessAttemptTwo);

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryRetryEntityRzpFeesAttemptTwoCredit = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptTwo->getId(),
                FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
            ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryRetryEntityRzpFeesAttemptTwoCredit['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryRetryEntityRzpFeesAttemptTwoCredit['status']);
        $this->assertEquals(0, $feeRecoveryRetryEntityRzpFeesAttemptTwoCredit['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryRetryEntityRzpFeesAttemptTwoCredit['type']);

        $this->assertNull($feeRecoveryRetryEntityRzpFeesAttemptTwoCredit['recovery_payout_id']);

        $payoutsAfterFinalFail = $this->getDbEntities('payout')->toArray();

        $this->assertSameSize($payoutsBeforeFinalFail, $payoutsAfterFinalFail);
    }

    public function testFeeRecoveryRetryAfterTwoPayoutFTAReconReversedThirdSuccess()
    {
        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbEntity('payout', ['purpose' => 'rzp_fees']);

        $feeRecoveryEntityRzpFees1 = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryPayout->getId()
            ]);

        // -----------------------------------------------------
        // normal attempt at 1 fail and start retry for Attempt 2
        // -----------------------------------------------------

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $feeRecoveryPayout->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::INITIATED]);

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::REVERSED, '933815383818');

        $feeRecoveryPayoutReversal = $this->getDbLastEntity('reversal');

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryEntityRzpFees2 = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryPayoutReversal->getId(),
                FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
            ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::REVERSAL, $feeRecoveryEntityRzpFees2['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryEntityRzpFees2['status']);
        $this->assertEquals(0, $feeRecoveryEntityRzpFees2['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryEntityRzpFees1['type']);
        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryEntityRzpFees2['type']);

        $this->assertNull($feeRecoveryEntityRzpFees2['recovery_payout_id']);

        $feeRecoveryRetryPayoutAttemptOne = $this->getDbLastEntity('payout');

        $feeRecoveryRetryPayoutAttemptOne->setStatus(Payout\Status::INITIATED);
        $feeRecoveryRetryPayoutAttemptOne->saveOrFail();

        $feeRecoveryEntities = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryPayout->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 1
            ])->toArray();

        $feeRecoveryRetryEntitiesAttemptOne = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::PROCESSING,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
            ])->toArray();

        $this->assertSameSize($feeRecoveryEntities, $feeRecoveryRetryEntitiesAttemptOne);

        $feeRecoveryRetryEntityRzpFeesAttemptOneDebit = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptOne->getId()
            ])->toArray();

        // -----------------------------------------------------
        // retry attempt at 2 fail and start retry for Attempt 3
        // -----------------------------------------------------

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $feeRecoveryRetryPayoutAttemptOne->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::INITIATED]);

        $this->updateFtaAndSource($feeRecoveryRetryPayoutAttemptOne, Payout\Status::REVERSED, '933815383814');

        $feeRecoveryRetryEntitiesAfterFail = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
            ])->toArray();

        $this->assertSameSize($feeRecoveryRetryEntitiesAttemptOne, $feeRecoveryRetryEntitiesAfterFail);

        $feeRecoveryRetryPayoutAttemptOneReversal = $this->getDbLastEntity('reversal');

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryRetryEntityRzpFeesAttemptOneCredit = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptOneReversal->getId(),
                FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
            ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::REVERSAL, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['status']);
        $this->assertEquals(0, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryRetryEntityRzpFeesAttemptOneDebit['type']);
        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['type']);

        $this->assertNull($feeRecoveryEntityRzpFees2['recovery_payout_id']);

        $feeRecoveryRetryPayoutAttemptTwo = $this->getDbLastEntity('payout');

        $feeRecoveryRetryPayoutAttemptTwo->setStatus(Payout\Status::INITIATED);
        $feeRecoveryRetryPayoutAttemptTwo->saveOrFail();

        $feeRecoveryEntitiesAttemptOne = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
            ])->toArray();

        $feeRecoveryRetryEntitiesAttemptTwo = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptTwo->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::PROCESSING,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 3
            ])->toArray();

        $this->assertSameSize($feeRecoveryEntitiesAttemptOne, $feeRecoveryRetryEntitiesAttemptTwo);

        $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptTwo->getId()
            ])->toArray();

        // Assert that we are creating a new fee recovery entity
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['status']);
        $this->assertEquals(0, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['type']);

        $this->updateFtaAndSource($feeRecoveryRetryPayoutAttemptTwo, Payout\Status::PROCESSED, '933815383819');

        $feeRecoveryRetryEntitiesAfterSuccessAttemptTwo = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptTwo->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::RECOVERED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 3
            ])->toArray();

        $this->assertSameSize($feeRecoveryRetryEntitiesAttemptTwo, $feeRecoveryRetryEntitiesAfterSuccessAttemptTwo);
    }

    public function testFeeRecoveryRetryAfterTwoPayoutFTAReconProcessedThanReversedThirdSuccess()
    {
        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbEntity('payout', ['purpose' => 'rzp_fees']);

        $feeRecoveryEntityRzpFees1 = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryPayout->getId()
            ]);

        // -----------------------------------------------------
        // normal attempt at 1 fail and start retry for Attempt 2
        // -----------------------------------------------------

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::PROCESSED, '933815383818');

        $feeRecoveryEntity = $this->getDbEntity('fee_recovery',
            [
                'recovery_payout_id' => $feeRecoveryPayout->getId()
            ]);

        $this->assertEquals($feeRecoveryEntity['recovery_payout_id'], $feeRecoveryPayout['id']);
        $this->assertEquals(FeeRecovery\Status::RECOVERED, $feeRecoveryEntity['status']);

        // Updating FTA status to processed to allow transition to reversed
        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $feeRecoveryPayout->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::PROCESSED]);

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::REVERSED, '933815383818');

        $feeRecoveryPayoutReversal = $this->getDbLastEntity('reversal');

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryEntityRzpFees2 = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryPayoutReversal->getId(),
                FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
            ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::REVERSAL, $feeRecoveryEntityRzpFees2['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryEntityRzpFees2['status']);
        $this->assertEquals(0, $feeRecoveryEntityRzpFees2['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryEntityRzpFees1['type']);
        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryEntityRzpFees2['type']);

        $this->assertNull($feeRecoveryEntityRzpFees2['recovery_payout_id']);

        $feeRecoveryRetryPayoutAttemptOne = $this->getDbLastEntity('payout');

        $feeRecoveryRetryPayoutAttemptOne->setStatus(Payout\Status::INITIATED);
        $feeRecoveryRetryPayoutAttemptOne->saveOrFail();

        $feeRecoveryEntities = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryPayout->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 1
            ])->toArray();

        $feeRecoveryRetryEntitiesAttemptOne = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::PROCESSING,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
            ])->toArray();

        $this->assertSameSize($feeRecoveryEntities, $feeRecoveryRetryEntitiesAttemptOne);

        $feeRecoveryRetryEntityRzpFeesAttemptOneDebit = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptOne->getId()
            ])->toArray();

        // -----------------------------------------------------
        // retry attempt at 2 fail and start retry for Attempt 3
        // -----------------------------------------------------


        $this->updateFtaAndSource($feeRecoveryRetryPayoutAttemptOne, Payout\Status::PROCESSED, '933815383814');

        $feeRecoveryRetryEntityAttemptOne = $this->getDbEntity('fee_recovery',
            [
                'recovery_payout_id' => $feeRecoveryRetryPayoutAttemptOne->getId()
            ]);

        $this->assertEquals($feeRecoveryRetryEntityAttemptOne['recovery_payout_id'], $feeRecoveryRetryPayoutAttemptOne['id']);
        $this->assertEquals(FeeRecovery\Status::RECOVERED, $feeRecoveryRetryEntityAttemptOne['status']);

        // Updating FTA status to processed to allow transition to reversed
        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => $feeRecoveryRetryPayoutAttemptOne->getId()]);

        $this->fixtures->edit('fund_transfer_attempt', $fta->getId(), ['status' => Attempt\Status::PROCESSED]);

        $this->updateFtaAndSource($feeRecoveryRetryPayoutAttemptOne, Payout\Status::REVERSED, '933815383814');

        $feeRecoveryRetryEntitiesAfterFail = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
            ])->toArray();

        $this->assertSameSize($feeRecoveryRetryEntitiesAttemptOne, $feeRecoveryRetryEntitiesAfterFail);

        $feeRecoveryRetryPayoutAttemptOneReversal = $this->getDbLastEntity('reversal');

        // 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit) are created
        $feeRecoveryRetryEntityRzpFeesAttemptOneCredit = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptOneReversal->getId(),
                FeeRecoveryEntity::TYPE      => FeeRecovery\Type::CREDIT
            ])->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::REVERSAL, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['status']);
        $this->assertEquals(0, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryRetryEntityRzpFeesAttemptOneDebit['type']);
        $this->assertEquals(FeeRecovery\Type::CREDIT, $feeRecoveryRetryEntityRzpFeesAttemptOneCredit['type']);

        $this->assertNull($feeRecoveryEntityRzpFees2['recovery_payout_id']);

        $feeRecoveryRetryPayoutAttemptTwo = $this->getDbLastEntity('payout');

        $feeRecoveryRetryPayoutAttemptTwo->setStatus(Payout\Status::INITIATED);
        $feeRecoveryRetryPayoutAttemptTwo->saveOrFail();

        $feeRecoveryEntitiesAttemptOne = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptOne->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::FAILED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 2
            ])->toArray();

        $feeRecoveryRetryEntitiesAttemptTwo = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptTwo->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::PROCESSING,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 3
            ])->toArray();

        $this->assertSameSize($feeRecoveryEntitiesAttemptOne, $feeRecoveryRetryEntitiesAttemptTwo);

        $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit = $this->getDbEntity('fee_recovery',
            [
                FeeRecoveryEntity::ENTITY_ID => $feeRecoveryRetryPayoutAttemptTwo->getId()
            ])->toArray();

        // Assert that we are creating a new fee recovery entity
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['status']);
        $this->assertEquals(0, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['attempt_number']);

        $this->assertEquals(FeeRecovery\Type::DEBIT, $feeRecoveryRetryEntityRzpFeesAttemptTwoDebit['type']);

        $this->updateFtaAndSource($feeRecoveryRetryPayoutAttemptTwo, Payout\Status::PROCESSED, '933815383819');

        $feeRecoveryRetryEntitiesAfterSuccessAttemptTwo = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $feeRecoveryRetryPayoutAttemptTwo->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::RECOVERED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 3
            ])->toArray();

        $this->assertSameSize($feeRecoveryRetryEntitiesAttemptTwo, $feeRecoveryRetryEntitiesAfterSuccessAttemptTwo);
    }

    public function testUpdateFeeRecoveryAfterPayoutFTAReconSuccessAndExcludeRewardFeePayout()
    {
        $oldTime = Carbon::create(2020, 1, 3, null, null, null);

        Carbon::setTestNow($oldTime);

        $oldTimeStamp = $oldTime->getTimestamp();

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->balance);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 500 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        // Create Reward fee payout
        $this->createPayoutForFundAccount($fundAccount, $this->balance);

        $payout1 = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout1['id'], ['initiated_at' => $oldTimeStamp]);

        // Process the payout
        $this->updateFtaAndSource($payout1, Payout\Status::PROCESSED, '933815383818');

        $creditEntity = $this->getLastEntity('credits', true);
        $this->assertEquals(500, $creditEntity['used']);

        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbEntity('payout', ['purpose' => 'rzp_fees']);

        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::PROCESSED, '933815383819');

        $feeRecoveryEntity = $this->getDbEntity('fee_recovery',
            [
                'recovery_payout_id' => $feeRecoveryPayout->getId()
            ]);

        $this->assertEquals($feeRecoveryEntity['recovery_payout_id'], $feeRecoveryPayout['id']);
        $this->assertEquals($feeRecoveryEntity['status'], FeeRecovery\Status::RECOVERED);
    }

    public function testCreateFeeRecoveryRetryManualAfterThreeFailures() {

        $previousRecoveryRetryPayout = $this->testFeeRecoveryNoRetryAfterThreePayoutFTAReconFailed();

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['previous_recovery_payout_id'] = $previousRecoveryRetryPayout->getId();

        $this->ba->adminAuth();

        $response = $this->startTest($data);

        $fourthFeeRecoveryPayout = $this->getDbEntityById('payout', $response['id']);

        $this->updateFtaAndSource($fourthFeeRecoveryPayout, Payout\Status::PROCESSED, '933815383830');

        $fourthAttempt = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $fourthFeeRecoveryPayout->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::RECOVERED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 4
            ])->toArray();

        $this->assertGreaterThan(0, $fourthAttempt);
    }

    public function testCreateFeeRecoveryRetryManualByAdminAfterThreeFailures() {

        $previousRecoveryRetryPayout = $this->testFeeRecoveryNoRetryAfterThreePayoutFTAReconFailed();

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['previous_recovery_payout_id'] = $previousRecoveryRetryPayout->getId();

        $this->ba->adminAuth();

        $response = $this->startTest($data);

        $fourthFeeRecoveryPayout = $this->getDbEntityById('payout', $response['id']);

        $this->updateFtaAndSource($fourthFeeRecoveryPayout, Payout\Status::PROCESSED, '933815383830');

        $fourthAttempt = $this->getDbEntities('fee_recovery',
            [
                FeeRecoveryEntity::RECOVERY_PAYOUT_ID => $fourthFeeRecoveryPayout->getId(),
                FeeRecoveryEntity::STATUS             => FeeRecovery\Status::RECOVERED,
                FeeRecoveryEntity::ATTEMPT_NUMBER     => 4
            ])->toArray();

        $this->assertGreaterThan(0, $fourthAttempt);
    }

    public function testCreateFeeRecoveryRetryManualWrongRecoveryPayoutIdByAdminAfterThreeFailures() {

        $this->testFeeRecoveryNoRetryAfterThreePayoutFTAReconFailed();

        $data = $this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest($data);
    }

    private function createBalanceAndScheduleTaskForFeeRecovery()
    {
        $merchant = $this->fixtures->create('merchant', [
            'name'  => 'merchantName',
            'email' => 'merchant@merchantMail.com'
        ]);

        $balance = $this->fixtures->create('balance', [
            'merchant_id'       => $merchant->getId(),
            'type'              => 'banking',
            'account_type'      => 'direct',
        ]);

        $feeRecoverySchedule = $this->fixtures->create('schedule_task', [
            'entity_id'         => $balance->getId(),
            'entity_type'       => 'balance',
            'type'              => 'fee_recovery',
            'last_run_at'       => 1707244199,
            'next_run_at'       => 1707330599,
        ]);

        return [$merchant, $balance, $feeRecoverySchedule];
    }

    public function testGetFeeRecoveryScheduleForBalanceViaAdminAction()
    {
        [$merchant, $balance, $feeRecoverySchedule] = $this->createBalanceAndScheduleTaskForFeeRecovery();

        $data = $this->testData[__FUNCTION__];
        $data['request']['content']['balance_id'] = $balance->getId();
        $data['response']['content']['balance_id'] = $balance->getId();

        $this->ba->adminAuth();

        $this->startTest($data);
    }

    public function testUpdateFeeRecoveryScheduleForBalanceViaAdminAction()
    {
        [$merchant, $balance, $feeRecoverySchedule] = $this->createBalanceAndScheduleTaskForFeeRecovery();

        $data = $this->testData[__FUNCTION__];
        $data['request']['content']['balance_id'] = $balance->getId();
        $data['response']['content']['balance_id'] = $balance->getId();

        $this->ba->adminAuth();

        $this->startTest($data);
    }

    public function testUpdateFeeRecoveryScheduleValidationFailureViaAdminAction()
    {
        [$merchant, $balance, $feeRecoverySchedule] = $this->createBalanceAndScheduleTaskForFeeRecovery();

        $data = $this->testData[__FUNCTION__];
        $data['request']['content']['balance_id'] = $balance->getId();

        $this->ba->adminAuth();

        $this->startTest($data);
    }

    public function testGetFeeRecoveryAmount()
    {
        $this->testCreateFeeRecoveryPayout();

        $data = $this->testData[__FUNCTION__];
        $data['request']['content']['balance_id'] = $this->balance->getId();

        $this->ba->adminAuth();

        $this->startTest($data);
    }

    public function testCreateFeeRecoveryPayoutJobViaAdminAction()
    {
        [$payout, $payout2, $payout3] = $this->createPayoutsForFeeRecoveryTests();

        $data = $this->testData[__FUNCTION__];
        $data['request']['content']['balance_id'] = $this->balance->getId();

        $scheduleTaskId = $this->getDbLastEntity('schedule_task')->getId();

        $scheduleTask = $this->fixtures->edit('schedule_task', $scheduleTaskId, [
            'entity_id'     => $this->balance->getId(),
            'entity_type'   => 'balance',
            'merchant_id'   => $this->balance->getMerchantId(),
            'type'          => 'fee_recovery',
            'last_run_at'   => 1577864736,
            'next_run_at'   => 1578469536,
        ]);

        $oldNextRunAt = $scheduleTask->getNextRunAt();

        $this->ba->adminAuth();

        $dateTime = Carbon::create(2023, 12, 07, 12, 00, 00, Timezone::IST);

        Carbon::setTestNow($dateTime);

        $this->startTest($data);

        $this->assertFeeRecoveryEntriesAfterFeeRecoveryJob($payout, $payout2, $payout3);

        $scheduleTask = $this->getDbEntity('schedule_task', [
            'entity_id'     => $this->balance->getId(),
            'entity_type'   => 'balance',
            'merchant_id'   => $this->balance->getMerchantId(),
            'type'          => 'fee_recovery',
        ]);

        $newLastRunAt = $scheduleTask->getLastRunAt();
        $newNextRunAt = $scheduleTask->getNextRunAt();

        $this->assertEquals($oldNextRunAt, $newLastRunAt);
        $this->assertEquals($newNextRunAt, 1578508199);
    }

    public function testCreateFeeRecoveryPayoutCustomAmountByAdminAction() {

        $this->ba->adminAuth();

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['balance_id'] = $this->balance->getId();

        $response = $this->makeRequestAndGetContent($data['request']);

        $contact = $this->getDbEntity('contact', [
            'merchant_id' => $this->balance->getMerchantId(),
        ])->toArray();

        $this->fixtures->edit('contact', $contact['id'], [
            'type' => 'rzp_fees',
        ]);

        $fundAccount = $this->getDbEntity('fund_account', [
            'source_id' => $contact['id'],
        ]);

        $data['response']['content']['fund_account_id'] = 'fa_' . $fundAccount['id'];

        $this->assertNotNull($response['id']);
        $this->assertEquals('fa_' . $fundAccount['id'], $response['fund_account']['id']);

        unset($response['id']);
        unset($response['fund_account']);
        unset($response['initiated_at']);
        unset($response['created_at']);

        $this->assertEquals($data['response']['content'], $response);
    }

    public function testCreateFeeRecoveryRetryManualFailAfterSuccess()
    {
        $previousRecoveryRetryPayout = $this->testFeeRecoveryRetryAfterTwoPayoutFTAReconFailedThirdSuccess();

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['previous_recovery_payout_id'] = $previousRecoveryRetryPayout->getId();

        $this->ba->adminAuth();

        $this->startTest($data);
    }

    public function prepareEntitiesForFeeRecoveryLowBalanceAlert(string $accountNumber,
                                                                 string $bankingAccountId,
                                                                 int $balanceAmount,
                                                                 int $payoutAmount,
                                                                 array $features)
    {
        $merchant = $this->fixtures->create('merchant', [
            'name'  => 'merchantName',
            'email' => 'merchant@merchantMail.com'
        ]);

        $balance = $this->fixtures->create('balance', [
            'merchant_id'       => $merchant->getId(),
            'type'              => 'banking',
            'account_type'      => 'direct',
            'account_number'    => $accountNumber,
            'balance'           => $balanceAmount,
        ]);

        $this->fixtures->create('banking_account', [
            'account_type'      => 'current',
            'merchant_id'       => $merchant->getId(),
            'channel'           => 'rbl',
            'status'            => 'activated',
            'balance_id'        => $balance->getId(),
            'account_number'    => $balance->getAccountNumber(),
            'id'                => $bankingAccountId
        ]);

        $this->fixtures->create('banking_account_statement_details', [
            'merchant_id'       => $merchant->getId(),
            'account_number'    => $balance->getAccountNumber(),
            'balance_id'        => $balance->getId(),
            'channel'           => 'rbl',
            'status'            => 'active',
        ]);

        foreach ($features as $feature)
        {
            $this->fixtures->create('feature', [
                'name'          => $feature,
                'entity_id'     => $merchant->getId(),
                'entity_type'   => 'merchant',
            ]);
        }

        $payout = $this->fixtures->payout->createPayoutWithoutTransaction([
            'merchant_id'   => $merchant->getId(),
            'balance_id'    => $balance->getId(),
            'purpose'       => 'salary',
            'amount'        => $payoutAmount,
            'fees'          => $payoutAmount,
            'status'        => 'processed',
        ]);

        $this->fixtures->create('fee_recovery', [
            'entity_id'         => $payout->getId(),
            'entity_type'       => 'payout',
            'status'            => 'processing',
            'attempt_number'    => 0,
            'type'              => 'debit',
        ]);

        $this->fixtures->payout->createPayoutWithoutTransaction([
            'merchant_id'   => $merchant->getId(),
            'balance_id'    => $balance->getId(),
            'purpose'       => 'rzp_fees',
            'amount'        => $payoutAmount,
            'status'        => 'queued',
        ]);

        return $merchant;
    }

    public function verifyFeeRecoveryLowBalanceAlert()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->app['config']->set('applications.dcs.mock', true);

        $dateTime = Carbon::create(2023, 12, 07, 12, 00, 00, Timezone::IST);

        Carbon::setTestNow($dateTime);

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__] = $this->testData['testFeeRecoveryLowBalanceAlert'];

        $this->startTest();
    }

    public function testFeeRecoveryLowBalanceAlertMailQueued()
    {
        Mail::fake();

        /** @var Merchant\Entity $merchant */
        $merchant = $this->prepareEntitiesForFeeRecoveryLowBalanceAlert('5224440041626905','randomBaAccId1', 5, 500001, [
            Feature\Constants::PAYOUT
        ]);

        $this->verifyFeeRecoveryLowBalanceAlert();

        Mail::assertQueued(LowBalanceAlert::class, function($mail) use ($merchant)
        {
            $mail->build();

            $from = [
                0   => [
                    'name'    => 'Team RazorpayX',
                    'address' => 'x.support@razorpay.com'
                ],
            ];

            $to = [
                0   => [
                    'name'      => null,
                    'address'   => $merchant->getEmail(),
                ],
            ];

            $viewData = $mail->viewData;

            $this->assertEquals('Urgent: Low balance in your RazorpayX account could lead to Service Disruption', $mail->subject);

            $this->assertEquals($from, $mail->from);

            $this->assertEquals($from, $mail->replyTo);

            $this->assertEquals($to, $mail->to);

            $this->assertSame('emails.fee_recovery.low_balance_alert', $mail->view);

            $this->assertEquals($merchant->getName(), $viewData['name']);

            $this->assertEquals('5224440041626905', $viewData['account_number']);

            $this->assertEquals($merchant->getId(), $viewData['merchant_id']);

            return true;
        });
    }

    public function testFeeRecoveryLowBalanceAlertSufficientBalanceOrExcluded()
    {
        Queue::fake();

        Mail::fake();

        $this->prepareEntitiesForFeeRecoveryLowBalanceAlert('5224440041626905', 'randomBaAccId1', 500001, 500001, [
            Feature\Constants::PAYOUT
        ]);

        $this->prepareEntitiesForFeeRecoveryLowBalanceAlert('5224440041626906', 'randomBaAccId2', 5, 500001, [
            Feature\Constants::PAYOUT, Feature\Constants::EXCLUDE_FROM_CA_BILLING
        ]);

        $this->verifyFeeRecoveryLowBalanceAlert();

        Queue::assertNothingPushed();

        Mail::assertNothingQueued();
    }

    public function testFeeRecoveryLowBalanceAlertInsufficientFeesAndBalance()
    {
        Queue::fake();

        Mail::fake();

        $this->prepareEntitiesForFeeRecoveryLowBalanceAlert('5224440041626906', 'randomBaAccId1', 499999, 500000, [
            Feature\Constants::PAYOUT
        ]);

        $this->verifyFeeRecoveryLowBalanceAlert();

        Queue::assertPushed(FeeRecoveryLowBalance::class);

        Mail::assertNothingQueued();
    }

    public function verifyFeeRecoveryLowBalanceAutomatedBlocking()
    {
        Mail::fake();

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->app['config']->set('applications.dcs.mock', true);

        $this->app['config']->set('applications.slack.mock', true);

        $this->testData[__FUNCTION__] = $this->testData['testFeeRecoveryLowBalanceAlert'];

        $this->ba->cronAuth();

        $this->startTest();

        Mail::assertNothingQueued();
    }

    public function testFeeRecoveryLowBalanceAutomatedBlocking()
    {
        $dateTime = Carbon::create(2023, 12, 1, 12, 15, 00, Timezone::IST);

        Carbon::setTestNow($dateTime);

        /** @var Merchant\Entity $merchant */
        $merchant = $this->prepareEntitiesForFeeRecoveryLowBalanceAlert('5224440041626905', 'randomBaAccId1', 500001, 500001, [
            Feature\Constants::PAYOUT, Feature\Constants::AUTO_DISABLE_PAYOUTS,
        ]);

        $this->verifyFeeRecoveryLowBalanceAutomatedBlocking();

        $merchant->reload();

        $features = $merchant->getEnabledFeatures();

        $this->assertContains(Feature\Constants::PAYOUT_LOW_BALANCE, $features);

        $this->assertNotContains(Feature\Constants::PAYOUT, $features);
    }

    public function testFeeRecoveryLowBalanceNoAutomatedBlockingMissingFeatureOrLowFee()
    {
        $dateTime = Carbon::create(2023, 12, 1, 12, 15, 00, Timezone::IST);

        Carbon::setTestNow($dateTime);

        /** @var Merchant\Entity $merchant1 */
        $merchant1 = $this->prepareEntitiesForFeeRecoveryLowBalanceAlert('5224440041626905', 'randomBaAccId1', 500001, 500001, [
            Feature\Constants::PAYOUT,
        ]);

        /** @var Merchant\Entity $merchant2 */
        $merchant2 = $this->prepareEntitiesForFeeRecoveryLowBalanceAlert('5224440041626906', 'randomBaAccId2', 500001, 499999, [
            Feature\Constants::PAYOUT,
        ]);

        $this->verifyFeeRecoveryLowBalanceAutomatedBlocking();

        $merchant1->reload();

        $merchant2->reload();

        $features = $merchant1->getEnabledFeatures();

        $this->assertContains(Feature\Constants::PAYOUT, $features);

        $this->assertNotContains(Feature\Constants::PAYOUT_LOW_BALANCE, $features);

        $features = $merchant2->getEnabledFeatures();

        $this->assertContains(Feature\Constants::PAYOUT, $features);

        $this->assertNotContains(Feature\Constants::PAYOUT_LOW_BALANCE, $features);
    }

    public function testFeeRecoveryLowBalanceNoAutomatedBlockingInvalidDate()
    {
        $dateTime = Carbon::create(2023, 12, 1, 12, 31, 00, Timezone::IST);

        Carbon::setTestNow($dateTime);

        /** @var Merchant\Entity $merchant */
        $merchant = $this->prepareEntitiesForFeeRecoveryLowBalanceAlert('5224440041626905', 'randomBaAccId1', 500001, 500001, [
            Feature\Constants::PAYOUT,
        ]);

        $this->verifyFeeRecoveryLowBalanceAutomatedBlocking();

        $merchant->reload();

        $features = $merchant->getEnabledFeatures();

        $this->assertContains(Feature\Constants::PAYOUT, $features);

        $this->assertNotContains(Feature\Constants::PAYOUT_LOW_BALANCE, $features);
    }

    public function verifyFeeRecoveryLowBalanceAutomatedUnblocking()
    {
        $dateTime = Carbon::create(2023, 12, 7, 12, 15, 00, Timezone::IST);

        Carbon::setTestNow($dateTime);

        Mail::fake();

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->app['config']->set('applications.dcs.mock', true);

        $this->testData[__FUNCTION__] = $this->testData['testFeeRecoveryLowBalanceAlert'];

        $this->ba->cronAuth();

        $this->startTest();

        Mail::assertNothingQueued();
    }

    public function testFeeRecoveryLowBalanceAutomatedUnblocking()
    {
        /** @var Merchant\Entity $merchant */
        $merchant = $this->prepareEntitiesForFeeRecoveryLowBalanceAlert('5224440041626905', 'randomBaAccId1', 500001, 499999, [
            Feature\Constants::PAYOUT_LOW_BALANCE,
        ]);

        $this->verifyFeeRecoveryLowBalanceAutomatedUnblocking();

        $merchant->reload();

        $features = $merchant->getEnabledFeatures();

        $this->assertContains(Feature\Constants::PAYOUT, $features);

        $this->assertNotContains(Feature\Constants::PAYOUT_LOW_BALANCE, $features);
    }

    public function testFeeRecoveryLowBalanceNoAutomatedUnblockingHighFee()
    {
        /** @var Merchant\Entity $merchant */
        $merchant = $this->prepareEntitiesForFeeRecoveryLowBalanceAlert('5224440041626905', 'randomBaAccId1', 500001, 500001, [
            Feature\Constants::PAYOUT_LOW_BALANCE,
        ]);

        $this->verifyFeeRecoveryLowBalanceAutomatedUnblocking();

        $merchant->reload();

        $features = $merchant->getEnabledFeatures();

        $this->assertContains(Feature\Constants::PAYOUT_LOW_BALANCE, $features);

        $this->assertNotContains(Feature\Constants::PAYOUT, $features);
    }

    protected function mockRazorxFeeRecoveryRollout()
    {
        $this->mockRazorxTreatment('off', 'on');
    }

    protected function mockRazorxTreatment(string $defaultBehaviour = 'off',
                                           string $feeRecoveryRolloutControl = 'control')

    {
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment', 'getCachedTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode) use (
                    $defaultBehaviour,
                    $feeRecoveryRolloutControl)
                {
                    if ($feature === 'rx_fee_recovery_control_roll_out')
                    {
                        return strtolower($feeRecoveryRolloutControl);
                    }

                    return strtolower($defaultBehaviour);
                }));

    }

    public function testGetNextInterval()
    {
        $core = new FeeRecovery\Core();

        // End of month
        $refTime = Carbon::create(2022, 12, 31, 23, 59, 59, Timezone::IST);
        [$startTime, $endTime] = $core->getNextInterval($refTime);

        $expectedStartTime  = Carbon::create(2023, 1, 1, 0, 0, 0, Timezone::IST);
        $expectedEndTime    = Carbon::create(2023, 1, 1, 23, 59, 59, Timezone::IST);

        $this->assertEquals($expectedStartTime->timestamp, $startTime->timestamp);
        $this->assertEquals($expectedEndTime->timestamp, $endTime->timestamp);

        // Mid of interval - cases when merchant gets activated, existing intervals need to be updated
        $refTime = Carbon::create(2023, 8, 17, 14, 36, 34, Timezone::IST);
        [$startTime, $endTime] = $core->getNextInterval($refTime);

        $expectedStartTime  = Carbon::create(2023, 8, 17, 14, 36, 35, Timezone::IST);
        $expectedEndTime    = Carbon::create(2023, 8, 17, 23, 59, 59, Timezone::IST);

        $this->assertEquals($expectedStartTime->timestamp, $startTime->timestamp);
        $this->assertEquals($expectedEndTime->timestamp, $endTime->timestamp);

        // End of Month Feb
        $refTime = Carbon::create(2023, 2, 27, 23, 59, 59, Timezone::IST);
        [$startTime, $endTime] = $core->getNextInterval($refTime);

        $expectedStartTime  = Carbon::create(2023, 2, 28, 0, 0, 0, Timezone::IST);
        $expectedEndTime    = Carbon::create(2023, 2, 28, 23, 59, 59, Timezone::IST);

        $this->assertEquals($expectedStartTime->timestamp, $startTime->timestamp);
        $this->assertEquals($expectedEndTime->timestamp, $endTime->timestamp);
    }
}
