<?php

namespace RZP\Tests\Functional\Settlement\Reconciliaton\Axis;

use App;
use Mail;
use Config;

use RZP\Models\Settlement;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Gateway\Kotak\ReconciliationTrait;
use RZP\Mail\Settlement\Reconciliation as ReconciliationMail;

class AxisReconciliationTest extends TestCase
{
    use RequestResponseFlowTrait;
    use SettlementTrait;
    use ReconciliationTrait;
    use FileHandlerTrait;

    protected $channel;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/AxisTestData.php';

        parent::setUp();

        $this->channel = Settlement\Channel::AXIS;

        $this->ba->adminAuth();

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $this->channel]);
    }

    public function createReconFileProcess()
    {
        // Create payments and refunds with timestamps two days back
        $this->createPaymentAndRefundEntities(2);

        $this->ba->appAuth();

        $this->deleteSetlFiles();

        // Generate settlements for above transactions
        $setlFile = $this->initiateSettlementsAndAssertSuccess($this->channel);

        // Generate settlement reconciliation file
        $setlReconciliationFile = $this->generateSetlReconciliationFile(
            $setlFile,
            $this->channel);

        // Reconcile settlements
        $data = $this->reconcileSettlements($setlReconciliationFile, $this->channel);

        // Match data returned by reconciliation
        $this->assertTestResponse($data, 'matchSummaryForReconFile');

        // Validate settlement attempt entity
        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertTestResponse($settlementAttempt, 'matchSettlementAttemptForReconSuccess');
        $this->assertNotNull($settlementAttempt['utr']);

        $setl = $this->getLastEntity('settlement', true);
        $this->assertNotNull($setl['utr']);

        $this->assertEquals($settlementAttempt['utr'], $setl['utr']);
    }

    public function testReconEntityProcess()
    {
        Mail::fake();

        $this->createReconFileProcess();

        $this->ba->appAuth();

        $request = [
            'url'       => '/fund_transfer_attempts/' . $this->channel,
            'method'    => 'POST',
            'content'   => [],
        ];

        $this->makeRequestAndGetContent($request);

        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertTestResponse($settlementAttempt, 'matchSettlementAttemptForReconEntitySuccess');

        $setl = $this->getLastEntity('settlement', true);

        $this->assertTestResponse($setl, 'fetchAndMatchSettlementsForReconSuccess');
        $this->assertNotNull(Settlement\Entity::UTR);

        $merchant = $this->getEntityById('merchant','10000000000000', true);
        $this->assertEquals(false, $merchant['hold_funds']);

        $batch = $this->getLastEntity('batch_fund_transfer', true);

        $this->assertEquals(1, $batch['processed_count']);
        $this->assertEquals(1752800, $batch['processed_amount']);

        // Validate settlement-transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals('settlement', $txn['type']);
        $this->assertNotNull($txn['reconciled_at']);

        Mail::assertSent(ReconciliationMail::class);
    }
    public function createReconFailureFileProcess($internalFailure = false)
    {
        // Create payments and refunds with timestamps two days back
        $this->createPaymentAndRefundEntities(2);

        $this->ba->appAuth();

        $this->deleteSetlFiles();

        // Generate settlements for above transactions
        $setlFile = $this->initiateSettlementsAndAssertSuccess($this->channel);

        // Generate settlement reconciliation file with failure
        $setlReconciliationFile = $this->generateSetlReconciliationFile(
            $setlFile,
            $this->channel,
            true,
            null,
            $internalFailure);

        // Reconcile settlements
        $data = $this->reconcileSettlements($setlReconciliationFile, $this->channel);

        // Match data returned by reconciliation
        $this->assertTestResponse($data, 'matchSummaryForReconFile');

        // Validate settlement attempt entity
        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertTestResponse($settlementAttempt, 'matchSettlementAttemptForReconFileFailure');
    }

    public function testReconEntityFailure()
    {
        $this->createReconFailureFileProcess();

        $this->ba->appAuth();

        $request = [
            'url'       => '/fund_transfer_attempts/' . $this->channel,
            'method'    => 'POST',
            'content'   => [],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertTestResponse($content, 'matchSummaryForReconFailure');

        // Validate batch settlement entity
        $batchFundTransfer = $this->fetchAndMatchBatchData('settlement');

        //Validate settlement entity
        $settlement = $this->getLastEntity('settlement', true);
        $this->assertTestResponse($settlement, 'fetchAndMatchSettlementsForReconFailure');
        $this->assertEquals(
            $batchFundTransfer['id'], $settlement[Settlement\Entity::BATCH_FUND_TRANSFER_ID]);

        $this->assertNotNull($settlement[Settlement\Entity::UTR]);

        // Validate settlement attempt entity
        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertTestResponse($settlementAttempt, 'matchSettlementAttemptForReconEntityFailure');

        // Validate batch fund transfer entity
        $batch = $this->getLastEntity('batch_fund_transfer', true);

        $this->assertEquals(0, $batch['processed_count']);
        $this->assertEquals(0, $batch['processed_amount']);

        // Validate settlement-transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals('settlement', $txn['type']);
        $this->assertNotNull($txn['reconciled_at']);

        $merchant = $this->getEntityById('merchant', $txn['merchant_id'], true);

        $this->assertFalse($merchant['hold_funds']);

        return $settlement;
    }
}
