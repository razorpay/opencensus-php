<?php

namespace RZP\Tests\Functional\LedgerJournalJob;

use Mail;
use Mockery;
use Queue;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use RZP\Jobs\LedgerJournalTest;
use RZP\Constants\Entity as E;
use RZP\Jobs\LedgerJournalLive;
use RZP\Tests\Functional\TestCase;
use RZP\Services\BatchMicroService;
use RZP\Jobs\PayoutServiceDataMigration;
use RZP\Jobs\TokenRegistrationAutoCharge;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Invoice\InvoiceTestTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
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

        $this->fixtures->on('test')->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

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

}
