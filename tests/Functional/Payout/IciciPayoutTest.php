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

class IciciPayoutTest extends TestCase
{
    use PayoutTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutTestData.php';

        parent::setUp();

        $this->mockRazorxTreatment('icici');

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

    public function testCreatePayoutForIciciToBankAccountViaNEFT()
    {
        $this->markTestSkipped('Skipping because we are allowing this now');

        $this->startTest();

        $this->flushCache();
    }

    public function testCreatePayoutForIciciToCardViaNEFT()
    {
        $this->markTestSkipped('Skipping because we are allowing this now');

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

        $this->startTest();

        $this->flushCache();
    }


    public function testCreatePayoutToBankAccountViaIMPS()
    {
        $this->startTest();

        $payout = $this->getLastEntity('payout', true);
        $this->assertEquals($payout['channel'], 'icici');
        $this->assertNull($payout['user_id']);
        $this->assertEquals(1062, $payout['fees']);
        $this->assertEquals(162, $payout['tax']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(1062, $txn['fee']);
        $this->assertEquals(162, $txn['tax']);
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
        $this->assertEquals(Channel::ICICI, $payoutAttempt['channel']);
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

    public function testCreatePayoutWithModeNotSet()
    {
        $this->startTest();
    }

    public function testCreatePayoutForVpaFundAccountId()
    {
        $this->markTestSkipped('Skipping because we are allowing this now');

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

    public function testCreateQueuedPayoutUnsupportedModeForCitiIcici()
    {
        $this->markTestSkipped('Skipping because we are allowing this now');

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
