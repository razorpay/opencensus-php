<?php

namespace RZP\Tests\Functional\Gateway\Kotak;

use App;
use Mail;
use Config;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Mail\Settlement\Reconciliation as ReconciliationMail;
use RZP\Mail\Merchant\SettlementFailure as SettlementFailureMail;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Account;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Settlement;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class ReconciliationTest extends TestCase
{
    use RequestResponseFlowTrait;
    use SettlementTrait;
    use ReconciliationTrait;
    use FileHandlerTrait;
    use HeimdallTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/ReconciliationTestData.php';

        parent::setUp();
    }

    public function testReconFileProcessForKotak()
    {
        Mail::fake();

        // Create payments and refunds with timestamps two days back
        $prEntities = $this->createPaymentAndRefundEntities();

        // delete Existing files
        $this->deleteSetlFiles();

        // reconciliation
        $this->matchTransactions($prEntities);

        // Generate settlements for above transactions
        $setlFile = $this->initiateSettlementsAndAssertSuccess(Settlement\Channel::KOTAK);

        // Generate settlement reconciliation file
        $setlReconciliationFile = $this->generateSetlReconciliationFile($setlFile, Settlement\Channel::KOTAK);

        // Reconcile settlements
        $data = $this->reconcileSettlements($setlReconciliationFile);

        // Match data returned by reconciliation
        $this->assertTestResponse($data, 'matchSummaryForReconFile');

        // Validate settlement attempt entity
        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertTestResponse($settlementAttempt, 'matchSettlementAttemptForReconSuccess');
        $this->assertNotNull($settlementAttempt['utr']);

        $setl = $this->getLastEntity('settlement', true);
        $this->assertNotNull($setl['utr']);

        Mail::assertSent(ReconciliationMail::class);
    }

    public function testReconEntityForKotak()
    {
        $this->markTestSkipped();

        Mail::fake();

        $this->testReconFileProcessForKotak();

        $this->ba->appAuth();

        $request = [
            'url'       => '/fund_transfer_attempts/kotak',
            'method'    => 'POST',
            'content'   => [],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $setl = $this->getLastEntity('settlement', true);
        $this->assertTestResponse($setl, 'fetchAndMatchSettlementsForReconSuccess');

        $this->assertNotNull(Settlement\Entity::UTR);

        $merchant = $this->getEntityById('merchant','10000000000000', true);
        $this->assertEquals(false, $merchant['hold_funds']);

        $batch = $this->getLastEntity('batch_fund_transfer', true);

        $this->assertEquals(1, $batch['processed_count']);
        $this->assertEquals(4382000, $batch['processed_amount']);

        // Validate settlement-transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals('settlement', $txn['type']);
        $this->assertNotNull($txn['reconciled_at']);

        Mail::assertSent(ReconciliationMail::class);
    }

    public function testReconFileProcessFailureForKotak()
    {
        Mail::fake();

        // Create payments and refunds with timestamps two days back
        $prEntities = $this->createPaymentAndRefundEntities();

        // delete Existing files
        $this->deleteSetlFiles();

        // reconciliation
        $this->matchTransactions($prEntities);

        // Generate settlements for above transactions
        $setlFile = $this->initiateSettlementsAndAssertSuccess(Settlement\Channel::KOTAK);

        // Generate settlement reconciliation file
        $generateFailedReconciliations = true;
        $setlReconciliationFile = $this->generateSetlReconciliationFile(
            $setlFile,
            Settlement\Channel::KOTAK,
            $generateFailedReconciliations);

        // Reconcile settlements
        $data = $this->reconcileSettlements($setlReconciliationFile);

        // Match data returned by reconciliation
        $this->assertTestResponse($data, 'matchSummaryForReconFile');

        Mail::assertSent(ReconciliationMail::class);
    }

    public function testReconEntiyFailureForKotak()
    {
        Mail::fake();

        $this->testReconFileProcessFailureForKotak();

        $this->ba->appAuth();

        $request = [
            'url'       => '/fund_transfer_attempts/kotak',
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

        $merchant = $this->getEntityById('merchant','10000000000000', true);
        $this->assertEquals(true, $merchant['hold_funds']);

        // Validate settlement attempt entity
        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertTestResponse($settlementAttempt, 'matchSettlementAttemptForReconFailure');
        $this->assertNotNull($settlementAttempt['utr']);

        // Validate batch fund transfer entity
        $batch = $this->getLastEntity('batch_fund_transfer', true);
        $this->assertEquals(0, $batch['processed_count']);
        $this->assertEquals(0, $batch['processed_amount']);

        // Validate settlement-transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals('settlement', $txn['type']);
        $this->assertNotNull($txn['reconciled_at']);

        Mail::assertSent(SettlementFailureMail::class);

        return $settlement;
    }

    public function testRetryReconForHoldedFunds()
    {
        $settlement = $this->testReconEntiyFailureForKotak();

        $content = $this->retryIntiateSettlements([$settlement['id']]);

        $this->assertEquals('No settlements found!', $content['kotak']['message']);

        $this->assertNotNull($content['kotak']['retry_skipped_settlements']);

        $this->assertEquals(1, $content['kotak']['retry_skipped_count']);

        // Validate no files were created
        $content = $this->getEntities('file_store', [], true);
        $this->assertSame($content['count'], 2);

        $this->fixtures->merchant->holdFunds(Account::TEST_ACCOUNT, false);
    }

    public function testRetryRecon()
    {
        $settlement = $this->testReconEntiyFailureForKotak();

        $firstAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $oldBatchFundTransferId = $settlement['batch_fund_transfer_id'];

        // Resetting merchant
        $this->fixtures->merchant->holdFunds(Account::TEST_ACCOUNT, false);

        $content = $this->retryIntiateSettlements([$settlement['id']]);

        // Check settlement entities
        $setlAttempts = $this->getEntities('fund_transfer_attempt', [], true);
        $this->assertEquals(2, $setlAttempts['count']);

        //Validate settlement entity
        $settlement = $this->getLastEntity('settlement', true);
        $this->assertNotNull($settlement['batch_fund_transfer_id']);
        $this->assertNotEquals($oldBatchFundTransferId, $settlement['batch_fund_transfer_id']);

        $this->assertNotNull($content['kotak']['settlement_text_file']);

        // Check reconciliation
        $setlFile = $content['kotak']['settlement_text_file']['local_file_path'];

        // Validate 4 files we created in all
        $content = $this->getEntities('file_store', [], true);
        $this->assertSame($content['count'], 4);

        $setlReconciliationFile = $this->generateSetlReconciliationFile(
            $setlFile, Settlement\Channel::KOTAK, false, $firstAttempt['id']);

        // Reconcile settlements
        $this->reconcileSettlements($setlReconciliationFile);

        // Validate batch settlement entity
        $this->fetchAndMatchBatchData('settlement');

        //Validate settlement entity
        $settlement = $this->getLastEntity('settlement', true);
        $this->assertTestResponse($settlement, 'fetchAndMatchSettlementsForRetryReconSuccess');

        // Validate settlement attempt entity
        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertTestResponse($settlementAttempt, 'matchSettlementAttemptForReconSuccess');
        $this->assertNotNull($settlementAttempt['utr']);

        $this->assertEquals($settlementAttempt['utr'], $settlement['utr']);

        // Validate settlement-transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals('settlement', $txn['type']);
        $this->assertNotNull($txn['reconciled_at']);
    }

    public function testAdjustmentCreationAgainstSettlement()
    {
        // Create payments and refunds with timestamps two days back
        $prEntities = $this->createPaymentAndRefundEntities();

        // delete Existing files
        $this->deleteSetlFiles();

        // reconciliation
        $txns = $this->matchTransactions($prEntities);

        // Generate settlements for above transactions
        $setlFile = $this->initiateSettlementsAndAssertSuccess(Settlement\Channel::KOTAK);

        $setl = $this->getLastEntity('settlement', true);

        $setlId = $setl['id'];

        $adjustmentData =[
            'merchant_id'   => '10000000000000',
            'amount'        => 100,
            'currency'      => 'INR',
            'description'   => 'random desc',
            'settlement_id' => $setlId
        ];

        $request = [
            'method'    => 'POST',
            'url'       => '/adjustments',
            'content'   => $adjustmentData
        ];

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->setAdminForInternalAuth();
        $this->ba->addAdminAuthHeaders('org_'.$this->org->id, $this->authToken);

        $content = $this->makeRequestAndGetContent($request);

        $this->ba->addAdminAuthHeaders(null, null);

        $data = $this->getLastEntity('adjustment', true);

        $this->assertArraySelectiveEquals($content, $data);
    }

    public function testReconciliationInTestMode()
    {
        $this->createSettlementsAndSettlementFile(3);

        // Added so that a new file name is created for next settlement
        $currentTime = Carbon::now(Timezone::IST);
        $currentTime->addSecond();
        Carbon::setTestNow($currentTime);

        $this->createSettlementsAndSettlementFile(
            2, Carbon::today(Timezone::IST)->subDays(5)->timestamp);

        $request = [
            'url' => '/settlements/reconcile/test/kotak',
            'method' => 'POST',
            'content' => []
        ];

        $this->ba->appAuth();

        $this->makeRequestAndGetContent($request);

        $ftas = $this->getEntities('fund_transfer_attempt', [], true);

        $attemptsWithUtr = $attemptsWithoutUtr = 0;

        foreach ($ftas['items'] as $attempt)
        {
            if ($attempt['utr'] === null)
            {
                $attemptsWithoutUtr++;
            }
            else
            {
                $attemptsWithUtr++;
            }
        }

        $this->assertEquals(2, $attemptsWithoutUtr);
        $this->assertEquals(3, $attemptsWithUtr);
    }

    protected function setAdminForInternalAuth()
    {
        $this->org = $this->fixtures->create('org');

        $this->authToken = $this->getAuthTokenForOrg($this->org);
    }
}
