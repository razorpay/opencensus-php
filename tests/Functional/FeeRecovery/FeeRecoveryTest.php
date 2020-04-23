<?php

namespace RZP\Tests\Functional\FeeRecovery;

use Carbon\Carbon;

use RZP\Models\Payout;
use RZP\Models\Schedule;
use RZP\Constants\Timezone;
use RZP\Models\FeeRecovery;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;

class FeeRecoveryTest extends TestCase
{
    use PayoutTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    private $checkerRoleUser;

    /**
     * @var Entity
     */
    private $bankingAccount;

    /**
     * @var \RZP\Models\FundAccount\Entity
     */
    private $fundAccount;

    public function setUp()
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

        $this->bankingAccount = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'balance_id'            => $this->balance->getId()
        ]);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(100);

        $this->merchant = $this->getDbEntityById('merchant', '10000000000000');
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

        $workflow = $this->setupWorkflowForLiveMode();

        $balance = $this->getDbLastEntity('balance', 'live')->toArray();

        $this->assertEquals($balance['account_type'], AccountType::DIRECT);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->createPayoutWithWorkflow($workflow, [], 'rzp_live_TheLiveAuthKey');

        $payout = $this->getDbLastEntity('payout', 'live')->toArray();

        $this->assertEquals(Payout\Status::PENDING, $payout['status']);
        $this->assertEquals($balance['id'], $payout['balance_id']);
        $this->assertEquals($payout['channel'], Channel::RBL);

        $feeRecovery = $this->getDbLastEntity('fee_recovery', 'live');

        $this->assertNull($feeRecovery);
    }

    public function testApprovePendingPayoutFeeRecoveryCreated()
    {
        $this->markTestSkipped("Need to fix due to live/test stuff");

        $this->testCreateRBLPendingPayoutNoFeeRecoveryCreated();

        $payout = $this->getDbLastEntity('payout', 'live');

        $this->assertEquals($payout['status'], Payout\Status::PENDING);

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->checkerRoleUser->getId());

        $data = & $this->testData[__FUNCTION__];

        $data['request']['url'] = '/payouts/pout_' . $payout['id'] . '/approve';

        $this->startTest();

        // Create Checker Role User for 2nd level of approval
        $secondLevelRole = $this->getDbEntityById('role', Org::MAKER_ROLE, 'live');
        $secondUser = $this->fixtures->on('live')
                            ->user->createUserForMerchant('10000000000000', [], Org::MAKER_ROLE);

        $this->app['config']->set('database.default', 'live');

        $secondUser->roles()->attach($secondLevelRole);

        // Make Request to Approve pending payout for second level
        $this->ba->proxyAuth('rzp_live_10000000000000', $secondUser->getId());

        $secondApprovalResponse = $this->startTest();

        $this->app['config']->set('database.default', 'live');

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

    public function testCreateFeeRecoveryPayout()
    {
        $oldTime = Carbon::create(2020, 1,3);

        Carbon::setTestNow($oldTime);

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

        $balanceId = $this->balance->getId();

        $startTime = Carbon::create(2020,1,1)->getTimestamp();
        $endTime   = Carbon::create(2020,1,8)->getTimestamp();

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

    public function testFeeRecoveryPayoutCron()
    {
        $oldTime = Carbon::create(2020, 1,3);

        Carbon::setTestNow($oldTime);

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

        $newTime = Carbon::create(2020, 1,10);

        Carbon::setTestNow($newTime);

        $this->ba->cronAuth();

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
        $this->assertEquals($feeRecovery['type'], FeeRecovery\Type::DEBIT);
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

        $feeRecoveryEntity3 = $this->getDbLastEntity('fee_recovery')->toArray();

        // Assert that we are creating a new fee recovery entity for the failed payout
        $this->assertEquals(FeeRecovery\Entity::PAYOUT, $feeRecoveryEntity3['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $feeRecoveryEntity3['status']);
        $this->assertEquals(0, $feeRecoveryEntity3['attempt_number']);
        $this->assertNull($feeRecoveryEntity3['recovery_payout_id']);
        $this->assertEquals($feeRecoveryEntity3['type'], FeeRecovery\Type::CREDIT);

        // Assert that the status of fee recovery entity corresponding to this
        // recovery payout has been updated back to unrecovered
        $this->assertEquals($feeRecoveryEntity1['recovery_payout_id'], $feeRecoveryPayout['id']);
        $this->assertEquals($feeRecoveryEntity1['status'], FeeRecovery\Status::UNRECOVERED);

        // Assert that last 2 fee_recovery entities correspond to the same payout (1 debit and 1 credit)
        $this->assertEquals($feeRecoveryEntity3['entity_id'], $feeRecoveryEntity2['entity_id']);
        $this->assertEquals($feeRecoveryEntity2['type'], FeeRecovery\Type::DEBIT);
        $this->assertEquals($feeRecoveryEntity3['type'], FeeRecovery\Type::CREDIT);
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

        $latestFeeRecoveryEntity = $this->getDbLastEntity('fee_recovery')->toArray();

        // Assert that we are creating a new fee recovery entity for the reversal
        $this->assertEquals(FeeRecovery\Entity::REVERSAL, $latestFeeRecoveryEntity['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $latestFeeRecoveryEntity['status']);
        $this->assertEquals(0, $latestFeeRecoveryEntity['attempt_number']);
        $this->assertNull($latestFeeRecoveryEntity['recovery_payout_id']);
        $this->assertEquals($latestFeeRecoveryEntity['type'], FeeRecovery\Type::CREDIT);

        // Assert that the status of fee recovery entity corresponding to this
        // recovery payout has been updated back to unrecovered
        $this->assertEquals($feeRecoveryEntity['recovery_payout_id'], $feeRecoveryPayout['id']);
        $this->assertEquals($feeRecoveryEntity['status'], FeeRecovery\Status::UNRECOVERED);
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

        $latestFeeRecoveryEntity = $this->getDbLastEntity('fee_recovery')->toArray();

        // Assert that we are creating a new fee recovery entity for the reversal
        $this->assertEquals(FeeRecovery\Entity::REVERSAL, $latestFeeRecoveryEntity['entity_type']);
        $this->assertEquals(FeeRecovery\Status::UNRECOVERED, $latestFeeRecoveryEntity['status']);
        $this->assertEquals(0, $latestFeeRecoveryEntity['attempt_number']);
        $this->assertNull($latestFeeRecoveryEntity['recovery_payout_id']);
        $this->assertEquals($latestFeeRecoveryEntity['type'], FeeRecovery\Type::CREDIT);

        // Assert that the status of fee recovery entity corresponding to this
        // recovery payout has been updated back to unrecovered
        $this->assertEquals($feeRecoveryEntityUpdated['recovery_payout_id'], $feeRecoveryPayout['id']);
        $this->assertEquals($feeRecoveryEntityUpdated['status'], FeeRecovery\Status::UNRECOVERED);
    }

    protected function updateFtaAndSource($payout, $status, $utr = '933815233814')
    {
        $this->ba->appAuth();

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

    protected function createVirtualBankingAccount()
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
                'ifsc'           => 'SBIN0007105',
                'account_number' => '111000111000',
            ]);

        return $fundAccount;
    }

    // tests outstanding fees to be recovered when fee recovery payout is initiated for 3 payouts
    // out of which one is failed and other is reversed
    public function testOutstandingFeesToBeRecovered()
    {
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

    // tests outstanding fees to be recovered when fee recovery payout is initiated for 3 payouts
    // out of which one is failed and other is reversed. In this case Fee recovery payouts is processed.
    // hence no outstanding amount. Last deducted at will also be equal to processed_at of fee recovery_payout
    public function testOutstandingFeesToBeRecoveredWhenFeeRecoveryPayoutsAreProcessed()
    {
        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbLastEntity('payout');

        $oldTime =  Carbon::create(2020, 9,3);

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
        $this->testCreateFeeRecoveryPayout();

        $feeRecoveryPayout = $this->getDbLastEntity('payout');

        $oldTime =  Carbon::create(2020, 9, 3, 12, 23, 45);

        Carbon::setTestNow($oldTime);

        // update fee_recovery payout to processed
        $this->updateFtaAndSource($feeRecoveryPayout, Payout\Status::PROCESSED, '933818903814');

        // create a new payout
        $newTime =  Carbon::create(2020, 10,3);

        Carbon::setTestNow($newTime);

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

    protected function setupScheduleAndScheduleTaskForMerchant()
    {
        $createScheduleRequest = [
            'method'  => 'POST',
            'url'     => '/schedules',
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

        (new Schedule\Task\Core)->createOrUpdate($this->merchant, $this->balance , $scheduleTaskInput);

        $scheduleTask = $this->getDbLastEntity('schedule_task')->toArray();

        $pastTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $this->fixtures->edit('schedule_task', $scheduleTask['id'], [
            'next_run_at'  => $pastTimeStamp
        ]);

        $this->fixtures->edit('balance', $this->balance->getId(), [
            'created_at'   => $pastTimeStamp
        ]);
    }
}
