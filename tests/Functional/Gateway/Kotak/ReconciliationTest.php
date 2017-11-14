<?php

namespace RZP\Tests\Functional\Gateway\Kotak;

use App;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use Config;
use Mail;
use RZP\Constants\Mode;
use RZP\Mail\Settlement\KotakReconciliation as KotakReconciliationMail;
use RZP\Mail\Merchant\SettlementFailure as SettlementFailureMail;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Account;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\FundTransfer\Kotak;
use RZP\Models\Payout\Status as PayoutStatus;
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

    public function testSettlementReconciliation()
    {
        Mail::fake();

        // Create payments and refunds with timestamps two days back
        $prEntities = $this->createPaymentAndRefundEntities();

        // delete Existing files
        $this->deleteSetlFiles();

        // reconciliation
        $txns = $this->matchTransactions($prEntities);

        // Generate settlements for above transactions
        $setlFile = $this->initiateSettlementsAndAssertSuccess();

        // Generate settlement reconciliation file
        $setlReconciliationFile = $this->generateSetlReconciliationFile($setlFile);

        // Reconcile settlements
        $data = $this->reconcileSettlements($setlReconciliationFile);

        // Validate settlement attempt entity
        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertTestResponse($settlementAttempt, 'matchSettlementAttemptForReconSuccess');
        $this->assertNotNull($settlementAttempt['utr']);

        // Validate settlement entity
        $setl = $this->getLastEntity('settlement', true);
        $this->assertTestResponse($setl, 'fetchAndMatchSettlementsForReconSuccess');

        $notNullKeys = [Settlement\Entity::UTR, Settlement\Entity::SETTLED_ON, Settlement\Entity::STATUS];

        foreach ($notNullKeys as $key)
        {
            $this->assertNotNull($setl[$key]);
        }

        $merchant = $this->getEntityById('merchant','10000000000000', true);
        $this->assertEquals(false, $merchant['hold_funds']);

        $batch = $this->getLastEntity('batch_fund_transfer', true);

        $this->assertEquals(1, $batch['processed_count']);
        $this->assertEquals(4382000, $batch['processed_amount']);

        // Validate settlement-transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals('settlement', $txn['type']);
        $this->assertNotNull($txn['reconciled_at']);

        Mail::assertSent(KotakReconciliationMail::class);
    }

    public function testReconciliationFailure()
    {
        Mail::fake();
        // Mocking time to 22:30 for settlements to get processed
        Carbon::setTestNow(Carbon::create(2016, 11, 15, 23, 0, 0, Timezone::IST));

        // Create payments and refunds with timestamps two days back
        $prEntities = $this->createPaymentAndRefundEntities();

        // delete Existing files
        $this->deleteSetlFiles();

        // reconciliation
        $txns = $this->matchTransactions($prEntities);

        // Generate settlements for above transactions
        $setlFile = $this->initiateSettlementsAndAssertSuccess();

        // Generate settlement reconciliation file
        $generateFailedReconciliations = true;
        $setlReconciliationFile = $this->generateSetlReconciliationFile(
            $setlFile,
            $generateFailedReconciliations);

        // Reconcile settlements
        $data = $this->reconcileSettlements($setlReconciliationFile);

        // Validate batch settlement entity
        $batchFundTransfer = $this->fetchAndMatchBatchData('settlement');

        //Validate settlement entity
        $settlement = $this->getLastEntity('settlement', true);
        $this->assertTestResponse($settlement, 'fetchAndMatchSettlementsForReconFailure');
        $this->assertEquals(
            $batchFundTransfer['id'], $settlement[Settlement\Entity::BATCH_FUND_TRANSFER_ID]);

        $notNullKeys = [Settlement\Entity::UTR, Settlement\Entity::STATUS];
        foreach ($notNullKeys as $key)
        {
            $this->assertNotNull($settlement[$key]);
        }

        $this->assertNull($settlement[Settlement\Entity::SETTLED_ON]);

        $merchant = $this->getEntityById('merchant','10000000000000', true);
        $this->assertEquals(true, $merchant['hold_funds']);

        // Validate settlement attempt entity
        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertTestResponse($settlementAttempt, 'matchSettlementAttemptForReconFailure');
        $this->assertNotNull($settlementAttempt['utr']);

        $content = $this->getEntities('file_store', [], true);
        $this->assertSame($content['count'], 2);

        // Validate batch fund transfer entity
        $batch = $this->getLastEntity('batch_fund_transfer', true);
        $this->assertEquals(0, $batch['processed_count']);
        $this->assertEquals(0, $batch['processed_amount']);

        // Validate settlement-transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals('settlement', $txn['type']);
        $this->assertNotNull($txn['reconciled_at']);

        // Resetting time
        Carbon::setTestNow();

        Mail::assertSent(SettlementFailureMail::class);

        return $settlement;
    }

    public function testRetryReconForHoldedFunds()
    {
        $settlement = $this->testReconciliationFailure();

        $this->fixtures->merchant->holdFunds();

        $content = $this->retryIntiateSettlements([$settlement['id']]);

        $this->assertEquals('No settlements found!', $content['kotak']['message']);

        // Validate no files were created
        $content = $this->getEntities('file_store', [], true);
        $this->assertSame($content['count'], 2);

        $this->fixtures->merchant->holdFunds(Account::TEST_ACCOUNT, false);
    }

    public function testRetryRecon()
    {
        $settlement = $this->testReconciliationFailure();

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

        $setlReconciliationFile = $this->generateSetlReconciliationFile($setlFile, false, $firstAttempt['id']);

        // Reconcile settlements
        $data = $this->reconcileSettlements($setlReconciliationFile);

        // Validate batch settlement entity
        $this->fetchAndMatchBatchData('settlement');

        //Validate settlement entity
        $settlement = $this->getLastEntity('settlement', true);
        $this->assertTestResponse($settlement, 'fetchAndMatchSettlementsForRetryReconSuccess');

        // Validate settlement attempt entity
        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertTestResponse($settlementAttempt, 'matchSettlementAttemptForReconSuccess');
        $this->assertNotNull($settlementAttempt['utr']);

        // Validate settlement-transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals('settlement', $txn['type']);
        $this->assertNotNull($txn['reconciled_at']);
    }

    public function testRetryReconWithoutSettlementIds()
    {
        $this->testReconciliationFailure();

        $data = $this->testData[__FUNCTION__];

        $request = [
            'url' => '/settlements/retry',
            'method' => 'POST',
            'content' => []
        ];

        $this->ba->adminAuth();

        $this->runRequestResponseFlow($data, function() use ($request)
        {
            $content = $this->makeRequestAndGetContent($request);
        });
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
        $setlFile = $this->initiateSettlementsAndAssertSuccess();

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
        $txtFile1 = $this->createSettlementsAndSettlementFile(3);

        // Added so that a new file name is created for next settlement
        $currentTime = Carbon::now(Timezone::IST);
        $currentTime->addSecond();
        Carbon::setTestNow($currentTime);

        $txtFile2 = $this->createSettlementsAndSettlementFile(
            2, Carbon::today(Timezone::IST)->subDays(5)->timestamp);

        $request = [
            'url' => '/settlements/reconcile/test',
            'method' => 'POST',
            'content' => []
        ];

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

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
