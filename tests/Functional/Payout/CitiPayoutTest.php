<?php

namespace RZP\Tests\Functional\Payout;

use RZP\Models\Admin\ConfigKey;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt\Status;
use RZP\Models\Admin\Service as AdminService;
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

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $merchantId = '10000000000000';

        $this->app['cache']->flush();

        (new AdminService)->setConfigKeys([ConfigKey::CITI_CHANNEL_PAYOUT_MIDS => [$merchantId]]);

        $this->ba->privateAuth();
    }

    public function testCreatePayout()
    {
        $this->startTest();

        $payout = $this->getLastEntity('payout', true);
        $this->assertEquals($payout['channel'], Channel::CITI);
        $this->assertNull($payout['user_id']);

        $txn = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');
        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);

        $balance = $this->getLastEntity('balance', true);
        $this->assertEquals('yesbank', $balance['channel']);
        $this->assertEquals('shared', $balance['account_type']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Batman', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals(Channel::CITI, $payoutAttempt['channel']);
        $this->assertEquals(Status::CREATED, $payoutAttempt['status']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => 'payout',
            'transaction_id'  => $txnId,
            'pricing_rule_id' => 'Bbg7dTcURsOr77',
            'percentage'      => null,
            'amount'          => 900,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $this->app['cache']->flush();
    }

    public function testCreatePayoutForVpaFundAccountId()
    {
        $contactId = $this->getDbLastEntity('contact')->getId();

        $this->fixtures->create('fund_account:vpa', [
            'id'            => '100000000003fa',
            'source_type'   => 'contact',
            'source_id'     => $contactId,
        ]);

        $vpaId = $this->getDbEntityById('fund_account', '100000000003fa')->getAccountId();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);
        $this->assertEquals($payout['channel'], 'citi');
        $this->assertNull($payout['user_id']);

        $txn = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');
        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);

        $balance = $this->getLastEntity('balance', true);
        $this->assertEquals('yesbank', $balance['channel']);
        $this->assertEquals('shared', $balance['account_type']);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals('Batman', $payoutAttempt['narration']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('fa_' . $vpaId, 'fa_' . $payoutAttempt['vpa_id']);
        $this->assertEquals(Channel::CITI, $payoutAttempt['channel']);
        $this->assertEquals(Status::CREATED, $payoutAttempt['status']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => 'payout',
            'transaction_id'  => $txnId,
            'pricing_rule_id' => 'Bbg7f0FaUJQOvj',
            'percentage'      => null,
            'amount'          => 900,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        $this->app['cache']->flush();
    }

    public function testCreateQueuedPayout()
    {
        $currentBalance = $this->getDbLastEntity('balance');

        $response = $this->startTest();

        $newBalance = $this->getDbLastEntity('balance');

        $this->assertEquals($currentBalance->getBalance(), $newBalance->getBalance());

        $txn = $this->getDbEntity('transaction', ['entity_id' => substr($response['id'], 5)]);

        $this->assertNull($txn);

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => substr($response['id'], 5)]);

        $this->assertNull($fta);

        $this->startTest();

        $summary = $this->makePayoutQueueSummaryRequest();

        $this->assertEquals(2, $summary['count']);
        $this->assertEquals(20000002, $summary['total_amount']);

        $dispatchResponse = $this->dispatchQueuedPayouts();

        $this->assertEquals(2, $dispatchResponse[$newBalance['id']]['total_payout_count']);
        $this->assertEquals(10000000, $dispatchResponse[$newBalance['id']]['balance_remaining']);
        $this->assertEquals(10000000, $dispatchResponse[$newBalance['id']]['original_balance']);
        $this->assertEquals(0, $dispatchResponse[$newBalance['id']]['dispatched_payout_count']);
        $this->assertEquals(0, $dispatchResponse[$newBalance['id']]['dispatched_payout_amount']);

        $this->fixtures->balance->edit($newBalance['id'], ['balance' => 11000000]);

        $dispatchResponse = $this->dispatchQueuedPayouts();

        $this->assertEquals(2, $dispatchResponse[$newBalance['id']]['total_payout_count']);
        $this->assertEquals(998229, $dispatchResponse[$newBalance['id']]['balance_remaining']);
        $this->assertEquals(11000000, $dispatchResponse[$newBalance['id']]['original_balance']);
        $this->assertEquals(1, $dispatchResponse[$newBalance['id']]['dispatched_payout_count']);
        $this->assertEquals(10001771, $dispatchResponse[$newBalance['id']]['dispatched_payout_amount']);

        $txn = $this->getDbEntity('transaction', ['entity_id' => substr($response['id'], 5)]);

        $this->assertNotNull($txn);

        $fta = $this->getDbEntity('fund_transfer_attempt', ['source_id' => substr($response['id'], 5)]);

        $this->assertNotNull($fta);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals(Channel::CITI, $payoutAttempt['channel']);
        $this->assertEquals(Status::CREATED, $payoutAttempt['status']);

        $this->app['cache']->flush();
    }
}
