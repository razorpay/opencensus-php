<?php

namespace RZP\Tests\Functional\FundTransfer;

use Mail;

use RZP\Models\Settlement;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Mail\Settlement\CriticalFailure;
use RZP\Mail\Settlement\Reconciliation as ReconciliationMail;
use RZP\Mail\Merchant\SettlementFailure as SettlementFailureMail;

class AttemptReconcileTest extends TestCase
{
    use AttemptTrait;
    use AttemptReconcileTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/AttemptTestData.php';

        parent::setUp();
    }

    // -------------------------------------- Settlement Recon-file tests start -------------------------------

    protected function verifySettlementReconFileProcessForKotak()
    {
        $channel = Channel::KOTAK;

        $setlFile = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $this->assertReconFileProcessSuccessForChannel($setlFile, $channel, Attempt\Type::SETTLEMENT);
    }

    protected function verifySettlementReconFileProcessForIcici()
    {
        $channel = Channel::ICICI;

        $setlFile = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $this->assertReconFileProcessSuccessForChannel($setlFile, $channel, Attempt\Type::SETTLEMENT);
    }

    protected function verifySettlementReconFileProcessForHdfc()
    {
        $channel = Channel::HDFC;

        $setlFile = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $this->assertReconFileProcessSuccessForChannel($setlFile, $channel, Attempt\Type::SETTLEMENT);
    }

    protected function verifySettlementReconFileProcessForAxis()
    {
        $channel = Channel::AXIS;

        $setlFile = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $this->assertReconFileProcessSuccessForChannel($setlFile, $channel, Attempt\Type::SETTLEMENT);
    }

    protected function verifySettlementReconFileProcessFailureKotak()
    {
        $channel = Channel::KOTAK;

        $setlFile = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $this->assertReconFileProcessFailureForChannel($setlFile, $channel);
    }

    protected function verifySettlementReconFileProcessFailureIcici()
    {
        $channel = Channel::ICICI;

        $setlFile = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $this->assertReconFileProcessFailureForChannel($setlFile, $channel);
    }

    protected function verifySettlementReconFileProcessFailureHdfc()
    {
        $channel = Channel::HDFC;

        $setlFile = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $this->assertReconFileProcessFailureForChannel($setlFile, $channel);
    }

    protected function verifySettlementReconFileProcessFailureAxis()
    {
        $channel = Channel::AXIS;

        $setlFile = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $this->assertReconFileProcessFailureForChannel($setlFile, $channel);
    }

    // -------------------------------------- Settlement Recon-file tests end -----------------------------

    // -------------------------------------- Payout Recon-file tests start -------------------------------

    protected function verifyPayoutReconFileProcessForKotak()
    {
        $channel = Channel::KOTAK;

        $setlFile = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::PAYOUT);

        $this->assertReconFileProcessSuccessForChannel($setlFile, $channel, Attempt\Type::PAYOUT);
    }

    protected function assertReconFileProcessFailureForChannel($setlFile, string $channel)
    {
        Mail::fake();

        $data = $this->reconcileSettlementsForChannel($setlFile, $channel, true);

        // Match data returned by reconciliation
        $this->assertTestResponse($data, 'matchSummaryForReconFile');
        $this->assertEquals($channel, $data['channel']);

        // Validate settlement attempt entity
        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $dataKey = 'matchAttemptForReconFailure' . ucfirst($channel);
        $this->assertTestResponse($settlementAttempt, $dataKey);

        Mail::assertQueued(ReconciliationMail::class);
    }

    public function testSettlementReconcileEntitiesSuccessForKotak()
    {
        $this->verifySettlementReconFileProcessForKotak();

        $this->reconcileEntitiesForChannel(Channel::KOTAK);

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::SETTLEMENT);
    }

    public function testSettlementReconcileEntitiesSuccessForIcici()
    {
        $this->verifySettlementReconFileProcessForIcici();

        $this->reconcileEntitiesForChannel(Channel::ICICI);

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::SETTLEMENT);
    }

    public function testSettlementReconcileEntitiesSuccessForHdfc()
    {
        $this->verifySettlementReconFileProcessForHdfc();

        $this->reconcileEntitiesForChannel(Channel::HDFC);

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::SETTLEMENT);
    }

    public function testSettlementReconcileEntitiesSuccessForAxis()
    {
        $this->verifySettlementReconFileProcessForAxis();

        $this->reconcileEntitiesForChannel(Channel::AXIS);

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::SETTLEMENT);
    }

    public function testPayoutReconcileEntitiesForKotak()
    {
        $this->verifyPayoutReconFileProcessForKotak();

        $this->reconcileEntitiesForChannel(Channel::KOTAK);

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::PAYOUT);
    }

    public function verifyReconcileEntitiesFailureForKotak()
    {
        Mail::fake();

        $channel = Channel::KOTAK;

        $this->verifySettlementReconFileProcessFailureKotak();

        $content = $this->reconcileEntitiesForChannel($channel);

        $this->assertReconcileEntitiesFailure($content, $channel);

        $merchant = $this->getEntityById('merchant', '10000000000000', true);
        $this->assertEquals(true, $merchant['hold_funds']);

        Mail::assertQueued(SettlementFailureMail::class);
    }

    protected function verifyReconcileEntitiesFailureForIcici()
    {
        $channel = Channel::ICICI;

        $this->verifySettlementReconFileProcessFailureIcici();

        $content = $this->reconcileEntitiesForChannel($channel);

        $this->assertReconcileEntitiesFailure($content, $channel);
    }

    protected function assertReconcileEntitiesFailure(array $content, string $channel)
    {
        $this->assertTestResponse($content, 'matchSummaryForReconFailure');

        // Validate batch fund transfer entity
        $batch = $this->getLastEntity('batch_fund_transfer', true);
        $this->assertEquals(0, $batch['processed_count']);
        $this->assertEquals(0, $batch['processed_amount']);

        //Validate settlement entities
        $settlement = $this->getLastEntity('settlement', true);

//        foreach ($settlements['items'] as $settlement)
//        {
            $this->assertTestResponse($settlement, 'fetchAndMatchSettlementsForReconFailure');
            $this->assertEquals(
                $batch['id'], $settlement[Settlement\Entity::BATCH_FUND_TRANSFER_ID]);

            $this->assertNotNull($settlement[Settlement\Entity::UTR]);
//        }

        // Validate settlement attempt entities
        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $testKey = 'matchSettlementAttemptForReconFailure' . ucfirst($channel);

//        foreach ($ftas['items'] as $settlementAttempt)
//        {
            $this->assertTestResponse($settlementAttempt, $testKey);
            $this->assertNotNull($settlementAttempt['utr']);
//        }

        // Validate settlement-transaction entity
        $setlTxn = $this->getLastEntity('transaction', true);
//s($setlTxns['count']);
//        foreach ($setlTxns['items'] as $txn)
//        {
            $this->assertEquals('settlement', $setlTxn['type']);
            $this->assertNotNull($setlTxn['reconciled_at']);
//        }
    }

    protected function assertReconcileEntitiesSuccessForSource(string $sourceType)
    {
        $attempts = $this->getEntities('fund_transfer_attempt', [], true);

        foreach ($attempts['items'] as $attempt)
        {
            $this->assertNull($attempt['failure_reason']);
            $this->assertEquals(Attempt\Status::PROCESSED, $attempt[Attempt\Entity::STATUS]);
        }

        $sources = $this->getEntities($sourceType, [], true);
        $sourceTestData = 'fetchAndMatchReconSuccessFor' . ucfirst($sourceType);
        foreach ($sources['items'] as $source)
        {
            $this->assertTestResponse($source, $sourceTestData);
            $this->assertNotNull($source['utr']);

            $merchantId = $source['merchant_id'];

            $merchant = $this->getEntityById('merchant',$merchantId, true);
            $this->assertEquals(false, $merchant['hold_funds']);
        }

        $batch = $this->getLastEntity('batch_fund_transfer', true);
        $batchTestData = 'matchBatchReconcileDataFor' . ucfirst($sourceType);
        $this->assertTestResponse($batch, $batchTestData);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($sourceType, $txn['type']);
        $this->assertNotNull($txn['reconciled_at']);
    }

    public function testRetrySettlementKotak()
    {
        $this->reinitiateSettlementAndAssertSuccessForChannel(Channel::KOTAK);
    }

    public function testRetrySettlementIcici()
    {
        $this->reinitiateSettlementAndAssertSuccessForChannel(Channel::ICICI);
    }

    protected function reinitiateSettlementAndAssertSuccessForChannel(string $channel)
    {
        $verifyReconEntitiesFunc = 'verifyReconcileEntitiesFailureFor' . ucfirst($channel);

        $this->$verifyReconEntitiesFunc();

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $settlementId = $attempt['source'];

        $content = $this->retryIntiateSettlements([$settlementId]);

        // No settlements retried as merhcants funds on hold
        $this->assertEquals(1, $content['retry_skipped_count']);

        $settlement = $this->getLastEntity('settlement', true);
        $this->assertEquals(1, $settlement['attempts']);

        $merchantId = $attempt['merchant_id'];

        // Release merchant funds
        $this->fixtures->merchant->holdFunds($merchantId, false);

        $content = $this->retryIntiateSettlements([$settlementId]);

        $this->assertNotNull($content['retried_settlements']);
        $this->assertEquals($settlementId, 'setl_' . $content['retried_settlements'][0]);

        // Check settlement entities
        // 1 earlier + 1 of the reinitiated
        $setlAttempts = $this->getEntities('fund_transfer_attempt', [], true);
        $this->assertEquals(2, $setlAttempts['count']);

        //Validate settlement entity
        $settlement = $this->getLastEntity('settlement', true);
        $this->assertTestResponse($settlement, 'testRetrySettlement');
    }


    public function verifyReconciliationInTestMode(
        string $channel,
        bool $failure = false,
        bool $internalFailure = false)
    {
        Mail::fake();

        $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $request = [
            'url' => '/settlements/reconcile/test/all',
            'method' => 'POST',
            'content' => [
                'failed_recons' => (int) $failure,
                'internal_failure' => (int) $internalFailure
            ]
        ];

        $this->ba->appAuth();

        $this->makeRequestAndGetContent($request);

        $this->reconcileEntitiesForChannel($channel);

        $ftas = $this->getEntities('fund_transfer_attempt', [], true);

        $success = $failed = 0;

        $statusClass = $this->getReconStatusClass($channel);

        foreach ($ftas['items'] as $attempt)
        {
            $status = $attempt['bank_status_code'];

            if (in_array($status, $statusClass::getSuccessfulStatus(), true) === true)
            {
                $success++;
            }
            else
            {
                $failed++;
            }
        }

        if ($failure === true)
        {
            $this->assertEquals(1, $failed);
        }
        else
        {
            $this->assertEquals(1, $success);
        }

        if ($internalFailure === true)
        {
            Mail::assertQueued(CriticalFailure::class);
        }
    }

    public function testReconciliationInTestModeForSuccess()
    {
        $this->verifyReconciliationInTestMode(Channel::AXIS);
    }

    public function testReconciliationInTestModeForFailure()
    {
        // This test wont work for kotak.
        // because kotak failure transactions can not be determined by the status.
        $this->verifyReconciliationInTestMode(Channel::AXIS, true);
    }

    public function testReconciliationInTestModeForInternalFailure()
    {
        $this->verifyReconciliationInTestMode(Channel::AXIS, true, true);
    }

    protected function getReconStatusClass(string $channel)
    {
        return 'RZP\\Models\\FundTransfer\\' . ucwords($channel). '\\Reconciliation\\Status';
    }
}
