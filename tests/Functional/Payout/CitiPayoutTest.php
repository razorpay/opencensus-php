<?php

namespace RZP\Tests\Functional\Payout;

use RZP\Models\Payout;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt\Status;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class CitiPayoutTest extends TestCase
{
    use PayoutTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutTestData.php';

        parent::setUp();

        $this->mockRazorxTreatment('citi');

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

        $this->flushCache();

        $this->ba->privateAuth();

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function testCreatePayoutForCitiToCardViaNEFTWithMultipleCredits()
    {
        $this->markTestSkipped();

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000002fa',
                'account_type' => 'card',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_id'   => '100000000lcard',
                'active'       => 1,
            ]);

        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $balance = $this->getLastEntity('balance', true);

        $balanceBefore = $balance['balance'];

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals($payout['channel'], 'citi');
        $this->assertNull($payout['user_id']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $txn = $this->getLastEntity('transaction', true);

        $txnId = str_after($txn['id'], 'txn_');
        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(500, $txn['fee']);
        $this->assertEquals('reward_fee', $txn['credit_type']);
        $this->assertEquals(500, $txn['fee_credits']);

        $balance = $this->getLastEntity('balance', true);

        $this->assertNull($balance['channel']);
        $this->assertEquals('shared', $balance['account_type']);
        $this->assertEquals($balanceBefore - 1000, $balance['balance']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'], 'pout_' . $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'], 'pout_' . $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Test Merchant Fund Transfer', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals(Channel::CITI, $payoutAttempt['channel']);
        $this->assertEquals(Status::INITIATED, $payoutAttempt['status']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => 'payout',
            'transaction_id'  => $txnId,
            'pricing_rule_id' => 'Bbg7cl6t6I3XA5',
            'percentage'      => null,
            'amount'          => 500,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][0]);

        $this->flushCache();
    }

    public function testCreatePayoutForCitiToCardViaNEFTWithMultipleCreditsWithNewCreditsFlow()
    {
        $this->markTestSkipped();

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000002fa',
                'account_type' => 'card',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_id'   => '100000000lcard',
                'active'       => 1,
            ]);

        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $balance = $this->getLastEntity('balance', true);

        $balanceBefore = $balance['balance'];

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals($payout['channel'], 'citi');
        $this->assertNull($payout['user_id']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $txn = $this->getLastEntity('transaction', true);

        $txnId = str_after($txn['id'], 'txn_');
        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(500, $txn['fee']);
        $this->assertEquals('reward_fee', $txn['credit_type']);
        $this->assertEquals(500, $txn['fee_credits']);

        $balance = $this->getLastEntity('balance', true);

        $this->assertNull($balance['channel']);
        $this->assertEquals('shared', $balance['account_type']);
        $this->assertEquals($balanceBefore - 1000, $balance['balance']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'], 'pout_' . $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'], 'pout_' . $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Test Merchant Fund Transfer', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals(Channel::CITI, $payoutAttempt['channel']);
        $this->assertEquals(Status::INITIATED, $payoutAttempt['status']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => 'payout',
            'transaction_id'  => $txnId,
            'pricing_rule_id' => 'Bbg7cl6t6I3XA5',
            'percentage'      => null,
            'amount'          => 500,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][0]);

        $this->flushCache();
    }

    // this test case will makes two payouts, one will consume credits
    // and the 2nd one will will debitted from banking balance completely
    public function testCreatePayoutForCitiToCardViaNEFTRewardFeeCredits()
    {
        $this->markTestSkipped();

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000002fa',
                'account_type' => 'card',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_id'   => '100000000lcard',
                'active'       => 1,
            ]);

        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 500 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $balance = $this->getLastEntity('balance', true);

        $balanceBefore = $balance['balance'];

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals($payout['channel'], 'citi');
        $this->assertNull($payout['user_id']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $txn = $this->getLastEntity('transaction', true);

        $txnId = str_after($txn['id'], 'txn_');
        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(500, $txn['fee']);
        $this->assertEquals('reward_fee', $txn['credit_type']);
        $this->assertEquals(500, $txn['fee_credits']);

        $balance = $this->getLastEntity('balance', true);

        $this->assertNull($balance['channel']);
        $this->assertEquals('shared', $balance['account_type']);
        $this->assertEquals($balanceBefore - 1000, $balance['balance']);

        $creditEntity = $this->getLastEntity('credits', true);
        $this->assertEquals(500, $creditEntity['used']);

        $creditTxnEntity = $this->getLastEntity('credit_transaction', true);
        $this->assertEquals('payout', $creditTxnEntity['entity_type']);
        $this->assertEquals($payout['id'], 'pout_' . $creditTxnEntity['entity_id']);
        $this->assertEquals(500, $creditTxnEntity['credits_used']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Test Merchant Fund Transfer', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals(Channel::CITI, $payoutAttempt['channel']);
        $this->assertEquals(Status::INITIATED, $payoutAttempt['status']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => 'payout',
            'transaction_id'  => $txnId,
            'pricing_rule_id' => 'Bbg7cl6t6I3XA5',
            'percentage'      => null,
            'amount'          => 500,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][0]);

        // checking route's response for dashboard
        $request = [
            'url' => '/payouts/' . $payout['id'],
            'method' => 'get',
            'content' => []

        ];

        $this->ba->privateAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals(500, $response['fees']);
        $this->assertEquals(0, $response['tax']);
        $this->assertEquals('reward_fee', $response['fee_type']);

        // checking route's response for dashboard
        $request = [
            'url' => '/transactions/' . $txn['id'],
            'method' => 'get',
            'content' => []

        ];
        $this->ba->privateAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals(1000, $response['debit']);
        $this->assertEquals('reward_fee', $response['source']['fee_type']);

        // 2nd payout
        $balance = $this->getLastEntity('balance', true);

        $balanceBefore = $balance['balance'];

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals($payout['channel'], 'citi');
        $this->assertNull($payout['user_id']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals(590, $payout['fees']);
        $this->assertNull($payout['fee_type']);

        $txn = $this->getLastEntity('transaction', true);

        $request = [
            'url' => '/transactions/' . $txn['id'],
            'method' => 'get',
            'content' => []

        ];
        $this->ba->privateAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertNull($response['source']['fee_type']);

        $txnId = str_after($txn['id'], 'txn_');
        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertEquals(90, $txn['tax']);
        $this->assertEquals(590, $txn['fee']);
        $this->assertEquals('default', $txn['credit_type']);
        $this->assertEquals(0, $txn['fee_credits']);

        $balance = $this->getLastEntity('balance', true);

        $this->assertNull($balance['channel']);
        $this->assertEquals('shared', $balance['account_type']);
        $this->assertEquals($balanceBefore - 1590, $balance['balance']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Test Merchant Fund Transfer', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals(Channel::CITI, $payoutAttempt['channel']);
        $this->assertEquals(Status::INITIATED, $payoutAttempt['status']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => 'payout',
            'transaction_id'  => $txnId,
            'pricing_rule_id' => 'Bbg7cl6t6I3XA5',
            'percentage'      => null,
            'amount'          => 500,
        ];

        $this->flushCache();
    }

    // this test case will makes two payouts, one will consume credits
    // and the 2nd one will will debitted from banking balance completely
    public function testCreatePayoutForCitiToCardViaNEFTRewardFeeCreditsWithNewCreditsFlow()
    {
        $this->markTestSkipped();

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000002fa',
                'account_type' => 'card',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_id'   => '100000000lcard',
                'active'       => 1,
            ]);

        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 500 ,'used'=> 0, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 500 ]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditBalanceEntityBalanceBefore = $creditBalanceEntity['balance'];

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $balance = $this->getLastEntity('balance', true);

        $balanceBefore = $balance['balance'];

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals($payout['channel'], 'citi');
        $this->assertNull($payout['user_id']);
        $this->assertNotNull($payout['pricing_rule_id']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $txn = $this->getLastEntity('transaction', true);

        $txnId = str_after($txn['id'], 'txn_');
        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(500, $txn['fee']);
        $this->assertEquals('reward_fee', $txn['credit_type']);
        $this->assertEquals(500, $txn['fee_credits']);

        $balance = $this->getLastEntity('balance', true);

        $this->assertNull($balance['channel']);
        $this->assertEquals('shared', $balance['account_type']);
        $this->assertEquals($balanceBefore - 1000, $balance['balance']);

        $creditBalanceEntity = $this->getLastEntity('credit_balance', true);
        $this->assertEquals($creditBalanceEntityBalanceBefore, $creditBalanceEntity['balance']);

        $creditEntity = $this->getLastEntity('credits', true);
        $this->assertEquals(500, $creditEntity['used']);

        $creditTxnEntity = $this->getLastEntity('credit_transaction', true);
        $this->assertEquals('payout', $creditTxnEntity['entity_type']);
        $this->assertEquals($payout['id'], 'pout_' . $creditTxnEntity['entity_id']);
        $this->assertEquals(500, $creditTxnEntity['credits_used']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Test Merchant Fund Transfer', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals(Channel::CITI, $payoutAttempt['channel']);
        $this->assertEquals(Status::INITIATED, $payoutAttempt['status']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => 'payout',
            'transaction_id'  => $txnId,
            'pricing_rule_id' => 'Bbg7cl6t6I3XA5',
            'percentage'      => null,
            'amount'          => 500,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][0]);

        // checking route's response for dashboard
        $request = [
            'url' => '/payouts/' . $payout['id'],
            'method' => 'get',
            'content' => []

        ];

        $this->ba->privateAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals(500, $response['fees']);
        $this->assertEquals(0, $response['tax']);
        $this->assertEquals('reward_fee', $response['fee_type']);

        // checking route's response for dashboard
        $request = [
            'url' => '/transactions/' . $txn['id'],
            'method' => 'get',
            'content' => []

        ];
        $this->ba->privateAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals(1000, $response['debit']);
        $this->assertEquals('reward_fee', $response['source']['fee_type']);

        // 2nd payout
        $balance = $this->getLastEntity('balance', true);

        $balanceBefore = $balance['balance'];

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals($payout['channel'], 'citi');
        $this->assertNull($payout['user_id']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals(590, $payout['fees']);
        $this->assertNull($payout['fee_type']);

        $txn = $this->getLastEntity('transaction', true);

        $request = [
            'url' => '/transactions/' . $txn['id'],
            'method' => 'get',
            'content' => []

        ];
        $this->ba->privateAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertNull($response['source']['fee_type']);

        $txnId = str_after($txn['id'], 'txn_');
        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertEquals(90, $txn['tax']);
        $this->assertEquals(590, $txn['fee']);
        $this->assertEquals('default', $txn['credit_type']);
        $this->assertEquals(0, $txn['fee_credits']);

        $balance = $this->getLastEntity('balance', true);

        $this->assertNull($balance['channel']);
        $this->assertEquals('shared', $balance['account_type']);
        $this->assertEquals($balanceBefore - 1590, $balance['balance']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Test Merchant Fund Transfer', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals(Channel::CITI, $payoutAttempt['channel']);
        $this->assertEquals(Status::INITIATED, $payoutAttempt['status']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => 'payout',
            'transaction_id'  => $txnId,
            'pricing_rule_id' => 'Bbg7cl6t6I3XA5',
            'percentage'      => null,
            'amount'          => 500,
        ];

        $this->flushCache();
    }

    public function testCreatePayoutToBankAccountViaIMPS()
    {
        $this->startTest();

        $payout = $this->getLastEntity('payout', true);
        $this->assertEquals($payout['channel'], 'citi');
        $this->assertNull($payout['user_id']);

        $txn = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');
        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);

        $balance = $this->getLastEntity('balance', true);
        $this->assertNull($balance['channel']);
        $this->assertEquals('shared', $balance['account_type']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Batman', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals(Channel::CITI, $payoutAttempt['channel']);
        $this->assertEquals(Status::INITIATED, $payoutAttempt['status']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => 'payout',
            'transaction_id'  => $txnId,
            'pricing_rule_id' => 'Bbg7dTcURsOr77',
            'percentage'      => null,
            'amount'          => 900,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $this->flushCache();
    }

    public function testCreatePayoutForVpaFundAccountId()
    {
        $contactId = $this->getDbLastEntity('contact')->getId();

        $this->fixtures->create('fund_account:vpa', [
            'id'            => '100000000003fa',
            'source_type'   => 'contact',
            'source_id'     => $contactId,
        ]);

        $this->startTest();

        $this->flushCache();
    }

    public function testCreateQueuedPayoutWithModeSet()
    {
        $currentBalance = $this->getDbLastEntity('balance');

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $response = $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(0, $payout['fees']);

        $this->assertEquals(0, $payout['tax']);

        $newBalance = $this->getDbLastEntity('balance');

        $this->assertEquals($currentBalance->getBalance(), $newBalance->getBalance());

        $txn = $this->getDbEntity('transaction', ['entity_id' => substr($response['id'], 5)]);

        $this->assertNull($txn);

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => substr($response['id'], 5)]);

        $this->assertNull($fta);

        $this->startTest();

        $summary1 = $this->makePayoutSummaryRequest();

        // Assert that there are 2 payouts in queued state.
        $this->assertEquals(2, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(20000002, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $summary2 = $this->makePayoutSummaryRequest();

        // Assert that there are still 2 payouts in queued state since there wasn't enough balance to process them
        $this->assertEquals(2, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(20000002, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        // Add enough balance to process only one queued payout
        $this->fixtures->balance->edit($newBalance['id'], ['balance' => 11000000]);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $updatedSummary = $this->makePayoutSummaryRequest();

        // Assert that there is only one payout in queued state. The other one got processed.
        $this->assertEquals(1, $updatedSummary[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(10000001, $updatedSummary[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        $this->flushCache();
    }

    public function testCreateQueuedPayoutWithModeSetWithCredits()
    {
        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 500 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $balance = $this->getLastEntity('balance', true);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $balanceBefore = $balance['balance'];

        $currentBalance = $this->getDbLastEntity('balance');

        $this->ba->privateAuth();

        $response = $this->startTest();

        $newBalance = $this->getDbLastEntity('balance');

        $this->assertEquals($currentBalance->getBalance(), $newBalance->getBalance());

        $txn = $this->getDbEntity('transaction', ['entity_id' => substr($response['id'], 5)]);

        $this->assertNull($txn);

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => substr($response['id'], 5)]);

        $this->assertNull($fta);

        $this->startTest();

        $summary1 = $this->makePayoutSummaryRequest();

        // Assert that there are 2 payouts in queued state.
        $this->assertEquals(2, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(20000002, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        $payouts = $this->getDbEntities('payout');
        $this->assertNull($payouts[0]['fee_type']);
        $this->assertNull($payouts[1]['fee_type']);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $summary2 = $this->makePayoutSummaryRequest();

        // Assert that there are still 2 payouts in queued state since there wasn't enough balance to process them
        $this->assertEquals(2, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(20000002, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        // Add enough balance to process only one queued payout
        $this->fixtures->balance->edit($newBalance['id'], ['balance' => 11000000]);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $updatedSummary = $this->makePayoutSummaryRequest();

        // Assert that there is only one payout in queued state. The other one got processed.
        $this->assertEquals(1, $updatedSummary[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(10000001, $updatedSummary[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        $this->flushCache();
    }

    public function testCreateQueuedPayoutWithModeSetWithCreditsWithNewCreditsFlow()
    {
        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 500 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 500 ]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $balance = $this->getLastEntity('balance', true);

        $balanceBefore = $balance['balance'];

        $currentBalance = $this->getDbLastEntity('balance');

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->ba->privateAuth();

        $response = $this->startTest();

        $newBalance = $this->getDbLastEntity('balance');

        $this->assertEquals($currentBalance->getBalance(), $newBalance->getBalance());

        $txn = $this->getDbEntity('transaction', ['entity_id' => substr($response['id'], 5)]);

        $this->assertNull($txn);

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => substr($response['id'], 5)]);

        $this->assertNull($fta);

        $this->startTest();

        $summary1 = $this->makePayoutSummaryRequest();

        // Assert that there are 2 payouts in queued state.
        $this->assertEquals(2, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(20000002, $summary1[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        $balance = $this->getLastEntity('credit_balance', true);
        $this->assertEquals(500, $balance['balance']);
        $payouts = $this->getDbEntities('payout');
        $this->assertNull($payouts[0]['fee_type']);
        $this->assertNull($payouts[1]['fee_type']);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $summary2 = $this->makePayoutSummaryRequest();

        // Assert that there are still 2 payouts in queued state since there wasn't enough balance to process them
        $this->assertEquals(2, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(20000002, $summary2[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        // Add enough balance to process only one queued payout
        $this->fixtures->balance->edit($newBalance['id'], ['balance' => 11000000]);

        $dispatchResponse = $this->dispatchQueuedPayouts();
        $this->assertEquals($dispatchResponse['balance_id_list'][0], $currentBalance['id']);

        $updatedSummary = $this->makePayoutSummaryRequest();

        // Assert that there is only one payout in queued state. The other one got processed.
        $this->assertEquals(1, $updatedSummary[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['count']);
        $this->assertEquals(10000001, $updatedSummary[$bankingAccount->getPublicId()][Payout\Status::QUEUED]['low_balance']['total_amount']);

        $this->flushCache();
    }


    public function testCreatePayoutWithModeNotSet()
    {
        $this->startTest();
    }

    public function testCreateQueuedPayoutUnsupportedModeForCitiIcici()
    {
        $contactId = $this->getDbLastEntity('contact')->getId();

        $this->fixtures->create('fund_account:vpa', [
            'id'            => '100000000003fa',
            'source_type'   => 'contact',
            'source_id'     => $contactId,
        ]);

        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->edit('balance', $balance->getId(), ['balance' => '100000']);

        $this->startTest();
    }

    protected function tearDown(): void
    {
        $this->flushCache();

        parent::tearDown();
    }
}
