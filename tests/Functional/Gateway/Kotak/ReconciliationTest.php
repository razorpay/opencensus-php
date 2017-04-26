<?php

namespace RZP\Tests\Functional\Gateway\Kotak;

use Carbon\Carbon;
use Config;
use Mockery;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Payout\PayoutTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FileStore;
use RZP\Models\Merchant\Account;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\FundTransfer\Kotak;

class ReconciliationTest extends TestCase
{
    use RequestResponseFlowTrait;
    use SettlementTrait;
    use PayoutTrait;
    use ReconciliationTrait;
    use FileHandlerTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/ReconciliationTestData.php';

        parent::setUp();
    }

    public function testSettlementReconciliation()
    {
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
        $this->assertNotNull($setl['utr']);

        $batch = $this->getLastEntity('batch_fund_transfer', true);

        $this->assertEquals(1, $batch['processed_count']);
        $this->assertEquals(4385000, $batch['processed_amount']);

        // Validate settlement-transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals('settlement', $txn['type']);
        $this->assertNotNull($txn['reconciled_at']);
    }

    public function testReconciliationFailure()
    {
        // Mocking time to 22:30 for settlements to get processed
        Carbon::setTestNow(Carbon::create(2016, 11, 15, 23, 0, 0, 'Asia/Kolkata'));

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
        $this->assertEquals($batchFundTransfer['id'], $settlement['batch_fund_transfer_id']);

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
        $oldBatchFundTransferId = $settlement['batch_fund_transfer_id'];

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

        $setlReconciliationFile = $this->generateSetlReconciliationFile($setlFile);

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

        $this->ba->appAuthMode();

        $this->runRequestResponseFlow($data, function() use ($request)
        {
            $content = $this->makeRequestAndGetContent($request);
        });
    }

    public function testAsjustmentCreationAgainstSettlement()
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

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        $data = $this->getLastEntity('adjustment', true);

        $this->assertArraySelectiveEquals($content, $data);
    }

    public function testPayoutReconciliation()
    {
        // Create payments and refunds with timestamps two days back
        $payoutEntities = $this->createPayoutEntities();

        // reconciliation
        $txns = $this->matchTransactions($payoutEntities);

        // Generate settlements for above transactions
        $payoutFiles = $this->initiatePayoutsAndAssertSuccess();

        // Generate reconciliation file, settlement and payout have common implementation
        $payoutReconciliationFile = $this->generateSetlReconciliationFile($payoutFiles);

        // Reconcile settlements, same route is being used as both are h2h
        $data = $this->reconcileSettlements($payoutReconciliationFile);

        // Validate batch settlement entity
        $this->fetchAndMatchBatchData('payout');
    }

    public function testReconciliationInTestMode()
    {
        $txtFile1 = $this->createSettlementsAndSettlementFile(3);

        // Added so that a new file name is created for next settlement
        $currentTime = Carbon::now('Asia/Kolkata');
        $currentTime->addSecond();
        Carbon::setTestNow($currentTime);

        $txtFile2 = $this->createSettlementsAndSettlementFile(
            2, Carbon::today("Asia/Kolkata")->subDays(5)->timestamp);

        $request = [
            'url' => '/settlements/reconcile/test',
            'method' => 'POST',
            'content' => []
        ];

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        $ftas = $this->getEntities('fund_transfer_attempt', [], true);

        $attemptsWithUtr = $attemptsWithoutUtr = 0;

        // s($ftas);
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

    protected function createSettlementsAndSettlementFile(
        $settlementCount = 2,
        $setlAttemptTimestamp = null): FileStore\Creator
    {
        $timestamp = $setlAttemptTimestamp ?: Carbon::today("Asia/Kolkata")->timestamp;

        // Create merchant
        $merchant = $this->fixtures->create('merchant');
        $merchantId = $merchant->getId();

        $bankAccount = $this->fixtures->create('bank_account', ['entity_id' => $merchantId]);

        // Create settlements
        $settlements = $this->fixtures->times($settlementCount)->create(
            'settlement',
            [
                'merchant_id' => $merchantId,
                'utr' => null,
                'created_at' => $timestamp,
            ]);

        // Create batch of settlement
        $batchTransferEntity = $this->fixtures->create(
            'batch_fund_transfer',
            [
                'total_count' => 1,
                'transaction_count' => $settlementCount,
                'created_at' => $timestamp
            ]);

        // Create fund transfer attempts
        $textData = $allAttempts = [];

        foreach ($settlements as $settlement)
        {
            // Create transaction
            $transaction = $this->fixtures->create(
                                'transaction',
                                [
                                    'merchant_id' => $merchantId,
                                    'type' => 'settlement',
                                    'entity_id' => $settlement->getId()
                                ]);

            $this->fixtures->edit('settlement', $settlement->getId(), ['transaction_id' => $transaction->getId()]);

            $fta = $this->fixtures->create(
                'fund_transfer_attempt',
                [
                    'source_id' => $settlement->getId(),
                    'created_at' => $timestamp,
                    'batch_fund_transfer_id' => $batchTransferEntity->getId(),
                ]
            );

            $allAttempts[] = $fta;
        }

        list($textFile, $excelFile) = (new Kotak\NodalAccount)->generateSettlementFile(
                                                                        $allAttempts, false);

        // Update batch with generated settlement file id
        $batchTransferEntity->setTxtFileId(($textFile->get())['id']);
        $this->fixtures->edit(
            'batch_fund_transfer',
            $batchTransferEntity->getId(),
            ['txt_file_id' => ($textFile->get())['id']]
        );

        return $textFile;
    }
}
