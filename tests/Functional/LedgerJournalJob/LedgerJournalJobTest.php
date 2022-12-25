<?php

namespace RZP\Tests\Functional\LedgerJournalJob;

use Mail;
use Queue;
use RZP\Models\Feature;
use RZP\Jobs\LedgerJournalTest;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\PayoutServiceDataMigration;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Invoice\InvoiceTestTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Payout\DataMigration as PayoutDataMigration;

class LedgerJournalJobTest extends TestCase
{
    use InvoiceTestTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;


    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/LedgerJournalJobTestData.php';
        parent::setUp();

        $this->setUpMerchantForBusinessBanking(false, 10000000);
    }

    public function testPayoutTransactionCreation()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fundAccount = $this->createVpaFundAccount();

        $this->fixtures->payout->createPayoutWithoutTransaction([
            'id'              => 'SamplePoutId12',
            'status'          => 'processed',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'balance_id'      => $balance->getId(),
            'fund_account_id' => $this->fundAccount->getId(),
        ]);

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $testData = &$this->testData[__FUNCTION__];
        $ledgerJournalJob = new LedgerJournalTest($testData['payload']);
        $ledgerJournalJob->handle();

        $payout = $this->getDbLastEntity('payout');
        $transaction = $this->getDbLastEntity('transaction');

        // assert payout
        $this->assertEquals('HNjsypA96SgJKJ', $payout->getTransactionId());

        // assert transaction
        $this->assertEquals('HNjsypA96SgJKJ', $transaction->getId());
        $this->assertEquals('SamplePoutId12', $transaction->getEntityId());
        $this->assertEquals('payout', $transaction->getType());
        $this->assertEquals('24500', $transaction->getBalance());
    }

    public function testPayoutTransactionCreationForPSPayout()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fundAccount = $this->createVpaFundAccount();

        $this->fixtures->payout->createPayoutWithoutTransaction([
                                                                    'id'              => 'SamplePoutId12',
                                                                    'status'          => 'processed',
                                                                    'pricing_rule_id' => '1nvp2XPMmaRLxb',
                                                                    'balance_id'      => $balance->getId(),
                                                                    'fund_account_id' => $this->fundAccount->getId(),
                                                                    'is_payout_service' => 1
                                                                ]);

        $payout = $this->getLastEntity('payout', true, 'test');

        (new PayoutServiceDataMigration('test', [
            PayoutDataMigration\Processor::FROM => $payout[PayoutEntity::CREATED_AT],
            PayoutDataMigration\Processor::TO   => $payout[PayoutEntity::CREATED_AT],
            PayoutEntity::BALANCE_ID            => $payout[PayoutEntity::BALANCE_ID]
        ]))->handle();

        $id = $payout[PayoutEntity::ID];

        PayoutEntity::stripSignWithoutValidation($id);

        $migratedPayout = \DB::connection('live')->select("select * from ps_payouts where id = '$id'")[0];

        $this->assertEquals($payout[PayoutEntity::ID], 'pout_' .$migratedPayout->id);

        $this->fixtures->edit('payout', $id, ['id' => 'Gg7sgBZgvYjlSC']);

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::PAYOUT_SERVICE_ENABLED]);

        $featuresArray = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ])->pluck('name')->toArray();

        $this->assertNotContains('ledger_reverse_shadow', $featuresArray);
        $this->assertContains('payout_service_enabled', $featuresArray);

        $testData = &$this->testData['testPayoutTransactionCreation'];
        $ledgerJournalJob = new LedgerJournalTest($testData['payload']);
        $ledgerJournalJob->handle();

        $transaction = $this->getDbLastEntity('transaction');

        // assert transaction
        $this->assertEquals('HNjsypA96SgJKJ', $transaction->getId());
        $this->assertEquals('SamplePoutId12', $transaction->getEntityId());
        $this->assertEquals('payout', $transaction->getType());
        $this->assertEquals('24500', $transaction->getBalance());
    }

    public function testReversalTransactionCreation()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fundAccount = $this->createVpaFundAccount();

        $this->fixtures->create('payout', [
            'id'              => 'SamplePoutId15',
            'status'          => 'processed',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'balance_id'      => $balance->getId(),
            'fund_account_id' => $this->fundAccount->getId(),
        ]);

        $this->fixtures->reversal->createReversalWithoutTransaction([
            'id'              => 'SampleRvrslId2',
            'entity_id'       => 'SamplePoutId15',
            'entity_type'     => 'payout',
            'balance_id'      => $balance->getId(),
        ]);

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $testData = &$this->testData[__FUNCTION__];
        $ledgerJournalJob = new LedgerJournalTest($testData['payload']);
        $ledgerJournalJob->handle();

        $reversal = $this->getDbLastEntity('reversal');
        $transaction = $this->getDbEntityById('transaction', $reversal['transaction_id']);

        // assert reversal
        $this->assertEquals('HNjsypA96SgJKJ', $reversal->getTransactionId());

        // assert transaction
        $this->assertEquals('HNjsypA96SgJKJ', $transaction->getId());
        $this->assertEquals('SampleRvrslId2', $transaction->getEntityId());
        $this->assertEquals('reversal', $transaction->getType());
        $this->assertEquals('21200', $transaction->getBalance());
    }

    public function testReversalTransactionCreationForPSReversal()
    {
        $this->app->instance('rzp.mode', "test");

        $balance = $this->getDbLastEntity('balance');

        $this->fundAccount = $this->createVpaFundAccount();

        $this->fixtures->create('payout', [
            'id'                => 'SamplePoutId15',
            'status'            => 'processed',
            'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            'balance_id'        => $balance->getId(),
            'fund_account_id'   => $this->fundAccount->getId(),
            'is_payout_service' => 1
        ]);

        $payout = $this->getLastEntity('payout', true, 'test');

        $this->fixtures->reversal->createReversalWithoutTransaction([
            'id'              => 'SampleRvrslId2',
            'entity_id'       => 'SamplePoutId15',
            'entity_type'     => 'payout',
            'balance_id'      => $balance->getId(),
        ]);

        $reversal = $this->getLastEntity('reversal', true, 'test');

        (new PayoutServiceDataMigration('test', [
            PayoutDataMigration\Processor::FROM => $payout[PayoutEntity::CREATED_AT],
            PayoutDataMigration\Processor::TO   => $payout[PayoutEntity::CREATED_AT],
            PayoutEntity::BALANCE_ID            => $payout[PayoutEntity::BALANCE_ID]
        ]))->handle();

        $payoutId = $payout[PayoutEntity::ID];

        PayoutEntity::stripSignWithoutValidation($payoutId);

        $migratedPayout = \DB::connection('live')->select("select * from ps_payouts where id = '$payoutId'")[0];

        $this->assertEquals($payout[PayoutEntity::ID], 'pout_' .$migratedPayout->id);

        $this->fixtures->edit('payout', $payoutId, ['id' => 'Gg7sgBZgvYjlSC']);

        $reversalId = $reversal['id'];

        PayoutEntity::stripSignWithoutValidation($reversalId);

        $migratedReversal = \DB::connection('live')->select("select * from ps_reversals where id = '$reversalId'")[0];

        $this->assertEquals($reversal['id'], 'rvrsl_' .$migratedReversal->id);

        $this->fixtures->edit('reversal', $reversalId, ['id' => 'Gg7sgBZgvYjlSD']);

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $testData = &$this->testData['testReversalTransactionCreation'];
        $ledgerJournalJob = new LedgerJournalTest($testData['payload']);
        $ledgerJournalJob->handle();

        $transaction = $this->getDbEntityById('transaction', 'HNjsypA96SgJKJ');

        // assert transaction
        $this->assertEquals('HNjsypA96SgJKJ', $transaction->getId());
        $this->assertEquals('SampleRvrslId2', $transaction->getEntityId());
        $this->assertEquals('reversal', $transaction->getType());
        $this->assertEquals('21200', $transaction->getBalance());
    }

    public function testBankTransferTransactionCreation()
    {
        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->create('bank_transfer', [
            'id'             => "SampleBnkTId12",
            'utr'            => "2222",
            'balance_id'     => $balance->getId(),
            'merchant_id'    => '10000000000000'
        ]);

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $testData = &$this->testData[__FUNCTION__];
        $ledgerJournalJob = new LedgerJournalTest($testData['payload']);
        $ledgerJournalJob->handle();

        $bankTransfer = $this->getDbLastEntity('bank_transfer');
        $transaction = $this->getDbLastEntity('transaction');

        // assert bank transfer
        $this->assertEquals('HNjsypA96SgJKJ', $bankTransfer->getTransactionId());

        // assert transaction
        $this->assertEquals('HNjsypA96SgJKJ', $transaction->getId());
        $this->assertEquals('SampleBnkTId12', $transaction->getEntityId());
        $this->assertEquals('bank_transfer', $transaction->getType());
        $this->assertEquals('24500', $transaction->getBalance());
    }

    public function testAdjustmentTransactionCreation()
    {
        $balance = $this->getDbLastEntity('balance');

        $txn = $this->fixtures->create('transaction', ['merchant_id' => '10000000000000']);
        $adj = $this->fixtures->create('adjustment', [
            'id'             => 'SampleAdjId123',
            'balance_id'     => $balance->getId(),
            'merchant_id'    => '10000000000000',
            'amount'         => 100 ,
            'description'    => 'test adjustment',
            'transaction_id'    => $txn->getId()
        ]);
        $this->fixtures->edit('adjustment', $adj['id'], ['transaction_id' => null]);

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $testData = &$this->testData[__FUNCTION__];
        $ledgerJournalJob = new LedgerJournalTest($testData['payload']);
        $ledgerJournalJob->handle();

        $adjustment = $this->getDbLastEntity('adjustment');
        $transaction = $this->getDbEntityById('transaction', $adjustment->getTransactionId());

        // assert adjustment
        $this->assertEquals('HNjsypA96SgJKJ', $adjustment->getTransactionId());

        // assert transaction
        $this->assertEquals('HNjsypA96SgJKJ', $transaction->getId());
        $this->assertEquals('SampleAdjId123', $transaction->getEntityId());
        $this->assertEquals('adjustment', $transaction->getType());
        $this->assertEquals('24500', $transaction->getBalance());
    }

    public function testCreditTransferTransactionCreation()
    {
        $balance = $this->getDbLastEntity('balance');

        $ct = $this->fixtures->create('credit_transfer', [
            'id'             => 'SampleCtTrfId2',
            'balance_id'     => $balance->getId(),
            'merchant_id'    => '10000000000000',
            'amount'         => 100 ,
            'description'    => 'test credit transfer',
            'entity_id'      => 'JGSxG6xVOuzDcp',
            'entity_type'    => 'payout'
        ]);

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $testData = &$this->testData[__FUNCTION__];
        $ledgerJournalJob = new LedgerJournalTest($testData['payload']);
        $ledgerJournalJob->handle();

        $creditTransfer = $this->getDbLastEntity('credit_transfer');
        $transaction = $this->getDbEntityById('transaction', $creditTransfer->getTransactionId());

        // assert adjustment
        $this->assertEquals('HNjsypA96SgJKJ', $creditTransfer->getTransactionId());

        // assert transaction
        $this->assertEquals('HNjsypA96SgJKJ', $transaction->getId());
        $this->assertEquals('SampleCtTrfId2', $transaction->getEntityId());
        $this->assertEquals('credit_transfer', $transaction->getType());
        $this->assertEquals('21200', $transaction->getBalance());
    }
}
