<?php

namespace RZP\Tests\Functional\FundTransfer;

use Mail;
use Redis;

use Carbon\Carbon;
use RZP\Constants\Entity;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;
use RZP\Models\FundTransfer\Mode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Merchant\Preferences;
use RZP\Mail\Settlement\CriticalFailure;
use RZP\Models\FundTransfer\Axis\Reconciliation\Status;
use RZP\Mail\Settlement\Reconciliation as ReconciliationMail;
use RZP\Mail\Merchant\SettlementFailure as SettlementFailureMail;
use RZP\Models\FundTransfer\Axis2\Reconciliation\Status as Axis2ReconStatus;

class AttemptReconcileTest extends TestCase
{
    use AttemptTrait;
    use AttemptReconcileTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/AttemptTestData.php';

        parent::setUp();

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    // -------------------------------------- Settlement Recon-file tests start -------------------------------

    protected function verifySettlementReconFileProcessForKotak()
    {
        $this->markTestSkipped('Kotak is not live');

        $channel = Channel::KOTAK;

        $setlFile = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $this->assertReconFileProcessSuccessForChannel($setlFile, $channel, Attempt\Type::SETTLEMENT);
    }

    protected function verifySettlementReconFileProcessForIcici($copyFile = false)
    {
        $channel = Channel::ICICI;

        $content = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $setlFile = $content[$channel]['file']['local_file_path'];

        $duplicateFile = $setlFile;

        if ($copyFile === true)
        {
            $pathInfo = pathinfo($setlFile);

            $duplicateFile = $pathInfo['dirname']
                . DIRECTORY_SEPARATOR
                . $pathInfo['filename']
                . 'copy.'
                . $pathInfo['extension'];

            copy($setlFile, $duplicateFile);
        }

        $fileName = basename($setlFile);

        $this->assertStringStartsWith('NRPSS_NRPSSUPLDNEW_', $fileName);

        $this->assertReconFileProcessSuccessForChannel($setlFile, $channel, Attempt\Type::SETTLEMENT, false);

        return $duplicateFile;
    }

    protected function verifySettlementReconFileProcessForHdfc()
    {
        $channel = Channel::HDFC;

        $content = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $setlFile = $content[$channel]['file']['local_file_path'];

        $this->assertReconFileProcessSuccessForChannel($setlFile, $channel, Attempt\Type::SETTLEMENT);
    }

    protected function verifySettlementReconFileProcessForAxis()
    {
        $channel = Channel::AXIS;

        $content = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $setlFile = $content[$channel]['file']['local_file_path'];

        $this->assertReconFileProcessSuccessForChannel($setlFile, $channel, Attempt\Type::SETTLEMENT);
    }

    protected function verifySettlementReconFileProcessForAxis2($copyFile = false)
    {
        $channel = Channel::AXIS2;

        $content = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $setlFile = $content[$channel]['file']['local_file_path'];

        $duplicateFile = $setlFile;

        if ($copyFile === true)
        {
            $pathInfo = pathinfo($setlFile);

            $duplicateFile = $pathInfo['dirname']
                . DIRECTORY_SEPARATOR
                . $pathInfo['filename']
                . 'copy.'
                . $pathInfo['extension'];

            copy($setlFile, $duplicateFile);
        }

        $this->assertReconFileProcessSuccessForChannel($setlFile, $channel, Attempt\Type::SETTLEMENT);

        return $duplicateFile;
    }

    protected function verifySettlementReconProcessForRbl($failureTest = false)
    {
        $channel = Channel::RBL;

        $this->createDataAndAssertInitiateOnlineTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT, $failureTest);

        $this->assertReconProcessSuccessForChannel($channel, Attempt\Type::SETTLEMENT, $failureTest);
    }

    protected function verifySettlementReconProcessForYesbank($failureTest = false)
    {
        $channel = Channel::YESBANK;

        $this->createDataAndAssertInitiateOnlineTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT, $failureTest);

        $this->assertReconProcessSuccessForChannel($channel, Attempt\Type::SETTLEMENT, $failureTest);
    }

    protected function verifyPayoutReconProcessForYesbankVpa($failureTest = false)
    {
        $channel = Channel::YESBANK;

        $this->createDataAndAssertInitiateOnlineTransferSuccessForVpa($channel, 1, Attempt\Type::PAYOUT, $failureTest);

        $this->assertReconProcessSuccessForChannelVpa($channel, Attempt\Type::PAYOUT, $failureTest);
    }

    protected function verifySettlementReconFileProcessFailureKotak()
    {
        $this->markTestSkipped('Kotak is not live.');

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

        $setlFile = $setlFile[$channel]['file']['local_file_path'];

        $this->assertReconFileProcessFailureForChannel($setlFile, $channel);
    }

    protected function verifySettlementReconFileProcessFailureHdfc()
    {
        $channel = Channel::HDFC;

        $setlFile = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $setlFile = $setlFile[$channel]['file']['local_file_path'];

        $this->assertReconFileProcessFailureForChannel($setlFile, $channel);
    }

    protected function verifySettlementReconFileProcessFailureAxis()
    {
        $channel = Channel::AXIS;

        $setlFile = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $setlFile = $setlFile[$channel]['file']['local_file_path'];

        $this->assertReconFileProcessFailureForChannel($setlFile, $channel);
    }

    // -------------------------------------- Settlement Recon-file tests end -----------------------------

    // -------------------------------------- Payout Recon-file tests start -------------------------------

    protected function verifyPayoutReconFileProcessForKotak()
    {
        $this->markTestSkipped('Kotak is not live.');

        $channel = Channel::KOTAK;

        $setlFile = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::PAYOUT);

        $setlFile = $setlFile[$channel]['file']['local_file_path'];

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
        $this->markTestSkipped('Kotak is not live.');

        $this->verifySettlementReconFileProcessForKotak();

        $this->reconcileEntitiesForChannel(Channel::KOTAK);

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::SETTLEMENT);
    }

    public function testSettlementReconcileEntitiesSuccessForIcici()
    {
        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->verifySettlementReconFileProcessForIcici();

        $this->reconcileEntitiesForChannel(Channel::ICICI);

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::SETTLEMENT);
    }

    public function testSettlementReconcileFlipStatusForIcici()
    {
        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $setlFile = $this->verifySettlementReconFileProcessForIcici(true);

//        $this->reconcileEntitiesForChannel(Channel::ICICI);

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::SETTLEMENT);

        $this->assertReconFileProcessFlipStatusForChannel($setlFile, Channel::ICICI, Attempt\Type::SETTLEMENT, true);

        $this->reconcileEntitiesForChannel(Channel::ICICI);

        // Validate settlement attempt entity
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($attempt['status'], Attempt\Status::FAILED);
    }

    public function testSettlementReconcileFlipStatusForAxis2()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $setlFile = $this->verifySettlementReconFileProcessForAxis2(true);

        $this->reconcileEntitiesForChannel(Channel::AXIS2);

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::SETTLEMENT);

        $this->assertReconFileProcessFlipStatusForChannel($setlFile, Channel::AXIS2, Attempt\Type::SETTLEMENT, true);

        $this->reconcileEntitiesForChannel(Channel::AXIS2);

        // Validate settlement attempt entity
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($attempt['status'], Attempt\Status::FAILED);
    }

    public function testSettlementReconcileEntitiesSuccessForHdfc()
    {
        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->verifySettlementReconFileProcessForHdfc();

        $this->reconcileEntitiesForChannel(Channel::HDFC);

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::SETTLEMENT);
    }

    public function testSettlementReconcileEntitiesSuccessForAxis()
    {
        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->verifySettlementReconFileProcessForAxis();

        $this->reconcileEntitiesForChannel(Channel::AXIS);

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::SETTLEMENT);
    }

    public function testSettlementReconcileEntitiesSuccessForAxis2()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->verifySettlementReconFileProcessForAxis2();

        $this->reconcileEntitiesForChannel(Channel::AXIS2);
    }

    public function testFundTransferInitiateEnableForAxis()
    {
        $this->createDataForChannel(Channel::AXIS, Attempt\Type::SETTLEMENT, 1, Attempt\Type::SETTLEMENT);

        $redisMock = \Mockery::mock('Illuminate\Redis\RedisManager', [$this->app, 'driver', []]);

        $redisConnmock = \Mockery::mock('Illuminate\Redis\Connections\PredisConnection', [null]);

        $this->app->instance('redis', $redisMock);

        $redisMock->shouldReceive('connection')
            ->andReturn($redisConnmock);

        $redisConnmock->shouldReceive('hGetAll')
            ->andReturn(['axis' => 'enable']);

        $content = $this->initiateTransfer(Channel::AXIS,
            Attempt\Purpose::SETTLEMENT,
            Attempt\Type::SETTLEMENT);

        $this->assertEquals(Channel::AXIS,$content[Channel::AXIS]['channel']);

        return $content;
    }

    public function testFundTransferInitiateDisableForAxis()
    {
        $this->createDataForChannel(Channel::AXIS, Attempt\Type::SETTLEMENT, 1, Attempt\Type::SETTLEMENT);

        $redisMock = \Mockery::mock('Illuminate\Redis\RedisManager', [$this->app, 'driver', []]);

        $redisConnmock = \Mockery::mock('Illuminate\Redis\Connections\PredisConnection', [null]);

        $this->app->instance('redis', $redisMock);

        $redisMock->shouldReceive('connection')
            ->andReturn($redisConnmock);

        $redisConnmock->shouldReceive('hGetAll')
            ->andReturn(['axis' => 'disable']);

        $content = $this->initiateTransfer(Channel::AXIS,
            Attempt\Purpose::SETTLEMENT,
            Attempt\Type::SETTLEMENT);

        $this->assertEquals('failed',$content['status']);

        return $content;
    }

    public function testSettlementReconcileEntitiesSuccessForRbl()
    {
        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->verifySettlementReconProcessForRbl();

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::SETTLEMENT);
    }

    public function testSettlementReconcileEntitiesSuccessForYesbank()
    {
        $this->verifySettlementReconProcessForYesbank();

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::SETTLEMENT);
    }

    public function testPayoutReconcileEntitiesSuccessForYesbankVpa()
    {
        $this->fixtures->create(
            'terminal',
            [
                'gateway' => Gateway::UPI_YESBANK,
            ]);

        $this->verifyPayoutReconProcessForYesbankVpa();

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::PAYOUT);
    }

    public function testSettlementReconcileEntitiesFailureForRbl()
    {
        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->verifySettlementReconProcessForRbl(true);
    }

    public function testSettlementReconcileEntitiesFailureForYesbank()
    {
        $this->verifySettlementReconProcessForYesbank(true);
    }

    public function testPayoutReconcileEntitiesForKotak()
    {
        $this->markTestSkipped('Kotak is not live.');

        $this->verifyPayoutReconFileProcessForKotak();
    }

    public function verifyReconcileEntitiesFailureForKotak()
    {
        Mail::fake();

        $this->verifySettlementReconFileProcessFailureKotak();

        $merchant = $this->getEntityById('merchant', '10000000000000', true);
        $this->assertEquals(true, $merchant['hold_funds']);

        Mail::assertQueued(SettlementFailureMail::class);
    }

    protected function verifyReconcileEntitiesFailureForIcici()
    {
        $channel = Channel::ICICI;

        $this->verifySettlementReconFileProcessFailureIcici();
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

        $this->assertTestResponse($settlement, 'fetchAndMatchSettlementsForReconFailure');
        $this->assertEquals(
            $batch['id'], $settlement[Settlement\Entity::BATCH_FUND_TRANSFER_ID]);

        $this->assertNotNull($settlement[Settlement\Entity::UTR]);

        // Validate settlement attempt entities
        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $testKey = 'matchSettlementAttemptForReconFailure' . ucfirst($channel);

        $this->assertTestResponse($settlementAttempt, $testKey);
        $this->assertNotNull($settlementAttempt['utr']);

        // Validate settlement-transaction entity
        $setlTxn = $this->getLastEntity('transaction', true);

        $this->assertEquals('settlement', $setlTxn['type']);
        $this->assertNotNull($setlTxn['reconciled_at']);
        $this->assertNotNull($setlTxn['reconciled_type']);
    }

    protected function assertOnlineReconcileEntitiesFailure(array $content, string $channel)
    {
        $this->assertTestResponse($content, 'matchSummaryForReconFailure');

        // Validate batch fund transfer entity
        $batch = $this->getLastEntity('batch_fund_transfer', true);
        $this->assertEquals(0, $batch['processed_count']);
        $this->assertEquals(0, $batch['processed_amount']);

        //Validate settlement entities
        $settlement = $this->getLastEntity('settlement', true);

        if ($channel === Channel::YESBANK)
        {
            $this->assertTestResponse($settlement, 'fetchAndMatchSettlementsForReconFailureYesbank');
        }
        else
        {
            $this->assertTestResponse($settlement, 'fetchAndMatchSettlementsForReconFailure');
        }

        $this->assertEquals(
            $batch['id'], $settlement[Settlement\Entity::BATCH_FUND_TRANSFER_ID]);

        $this->assertNull($settlement[Settlement\Entity::UTR]);

        // Validate settlement attempt entities
        $settlementAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $testKey = 'matchSettlementAttemptForReconFailure' . ucfirst($channel);

        $this->assertTestResponse($settlementAttempt, $testKey);
        $this->assertNull($settlementAttempt['utr']);

        // Validate settlement-transaction entity
        $setlTxn = $this->getLastEntity('transaction', true);

        $this->assertEquals('settlement', $setlTxn['type']);
        $this->assertNotNull($setlTxn['reconciled_at']);
        $this->assertNotNull($setlTxn['reconciled_type']);
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
        $this->assertNotNull($txn['reconciled_type']);
    }

    public function testRetrySettlementKotak()
    {
        $this->markTestSkipped('Kotak is not live.');

        $this->reinitiateSettlementAndAssertSuccessForChannel(Channel::KOTAK);
    }

    public function testRetrySettlementIcici()
    {
        $this->markTestSkipped('this is skipped till the test case is fixed');

        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->reinitiateSettlementAndAssertSuccessForChannel(Channel::ICICI);

        $setl = $this->getLastEntity('settlement', true);

        $bta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->validationSettlementDestination($setl['id'], Entity::FUND_TRANSFER_ATTEMPT, $bta['id']);
    }

    /**
     * This test asserts that the Settlement Failure email is sent in case of marketplace accounts as well.
     */
    public function testRetrySettlementKotakMarketplace()
    {
        $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $this->fixtures->merchant->edit('10000000000000', ['parent_id' => '10000000000002']);

        $this->reinitiateSettlementAndAssertSuccessForChannel(Channel::KOTAK);
    }

    protected function reinitiateSettlementAndAssertSuccessForChannel(string $channel)
    {
        $verifyReconEntitiesFunc = 'verifyReconcileEntitiesFailureFor' . ucfirst($channel);

        $this->$verifyReconEntitiesFunc();

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $settlementId = $attempt['source'];

//        $this->fixtures->edit('merchant', $attempt['merchant_id'], ['hold_funds' => 1]);

        $content = $this->retryIntiateSettlements([$settlementId]);

        // No settlements retried as merchants funds on hold
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
                'failed_recons'    => (int) $failure,
                'internal_failure' => (int) $internalFailure
            ]
        ];

        $this->ba->cronAuth();

        $this->makeRequestAndGetContent($request);

//        $this->reconcileEntitiesForChannel($channel);

        $ftas = $this->getEntities('fund_transfer_attempt', [], true);

        $success = $failed = 0;

        $statusClass = $this->getReconStatusClass($channel);

        foreach ($ftas['items'] as $attempt)
        {
            $status = $attempt['bank_status_code'];

            $isSuccess = $statusClass::inStatus($statusClass::getSuccessfulStatus(), $status, $attempt['bank_response_code']);

            if ($isSuccess === true)
            {
                $success++;
            }
            else
            {
                $failed++;
            }
        }

        if (($failure === true) or ($internalFailure === true))
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
        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->verifyReconciliationInTestMode(Channel::AXIS);
    }

    public function testReconciliationInTestModeForFailure()
    {
        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        // This test wont work for kotak.
        // because kotak failure transactions can not be determined by the status.
        $this->verifyReconciliationInTestMode(Channel::AXIS, true);
    }

    public function testReconciliationInTestModeForInternalFailure()
    {
        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->verifyReconciliationInTestMode(Channel::AXIS, true, true);
    }

    public function testReconciliationInTestModeForFailureForHdfc()
    {
        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->verifyReconciliationInTestMode(Channel::HDFC, false, true);
    }

    protected function getReconStatusClass(string $channel)
    {
        return 'RZP\\Models\\FundTransfer\\' . ucwords($channel). '\\Reconciliation\\Status';
    }

    public function testSettlementReconcileEntitiesFailureForAxis()
    {
        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->verifySettlementReconForAxisReturnSettled();
    }

    // this test is to support the new lambda which has bucket and region in payload
    public function testSettlementReconcileEntitiesForAxis2ViaNewLambda()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->verifySettlementReconForAxis2(true);
    }

    // this test is to support the old lambda which do not have bucket and region in payload
    public function testSettlementReconcileEntitiesForAxis2ViaOldLambda()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->verifySettlementReconForAxis2(false);
    }

    protected function verifySettlementReconForAxisReturnSettled()
    {
        $channel = Channel::AXIS;

        $content = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $setlFile = $content[$channel]['file']['local_file_path'];

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $setlReconciliationFile = $this->generateReconciliationFileForChannel(
            $setlFile, $channel, false, null, true);

        $this->fixtures->edit(
            'fund_transfer_attempt', $fta['id'],
            [
                'status'           => Attempt\Status::PROCESSED,
                'bank_status_code' => Status::EXECUTED
            ]
        );

        $this->reconcileSettlements($setlReconciliationFile, $channel);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(Status::RETURNSETTLED, $fta['bank_status_code']);

        $this->assertEquals(Attempt\Status::FAILED, $fta['status']);

        $this->reconcileEntitiesForChannel(Channel::AXIS);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(Attempt\Status::FAILED, $fta['status']);
    }

    protected function verifySettlementReconForAxis2($newLambda = true)
    {
        $channel = Channel::AXIS2;

        // create and initiate the settlement
        $content = $this->createDataAndAssertInitiateTransferSuccess(
            $channel, 1, Attempt\Type::SETTLEMENT);

        $setlFile = $content[$channel]['file']['local_file_path'];

        // this is the reversefeed file which is being generated
        $setlFile =  $this->generateReconciliationFileForChannel($setlFile, $channel);

        // lambda trigger along with the reverse feed file
        $res = $this->reconcileSettlementsUsingLambda($setlFile, $channel, $newLambda);

        $this->assertEquals(0, $res['unprocessed_count']);
        $this->assertEquals(1, $res['total_count']);
        $this->assertEquals(Channel::AXIS2, $res['channel']);

        $this->reconcileEntitiesForChannel(Channel::AXIS2);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(Axis2ReconStatus::SUCCESS, $fta['bank_status_code']);

        $this->assertEquals(Attempt\Status::PROCESSED, $fta['status']);
    }

    public function testSettlementVerificationForYesbank()
    {
        $this->verifySettlementReconProcessForYesbank();

        $this->reconcileEntitiesForChannel(Channel::YESBANK);

        $this->assertReconcileEntitiesSuccessForSource(Attempt\Type::SETTLEMENT);

        $content = $this->verifyProcessedSettlements(Channel::YESBANK, true);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($content['unprocessed_count'], 0);

        $this->assertEquals(Attempt\Status::INITIATED, $fta['status']);

        $this->assertEquals('FAILED', $fta['bank_status_code']);
    }

    public function testSettlementMerchantFailureForYesbank()
    {
        $channel = Channel::YESBANK;

        $failure = 'merchant_error';

        $this->createDataAndAssertInitiateOnlineTransferResponse(
            $channel,
            Attempt\Purpose::SETTLEMENT,
            1,
            Attempt\Type::SETTLEMENT,
            $failure);

        $this->reconcileOnlineSettlements($channel, $failure);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(Attempt\Status::FAILED, $fta['status']);
        $this->assertEquals('npci:E449', $fta['bank_response_code']);

        $settlement = $this->getLastEntity('settlement', true);

        $this->assertEquals(Settlement\Status::FAILED, $settlement['status']);

        $merchant = $this->getEntityById('merchant', '10000000000000', true);

        $this->assertEquals(true, $merchant['hold_funds']);
        $this->assertEquals('bank account/transaction was rejected from bank', $merchant['hold_funds_reason']);
    }

    // It will verify weather the fund transfer attempt has
    // transfer mode as NEFT once we add MID in ONLY_NEFT_MIDs array
    public function testFundTransferInitiateForNEFTOnlyMIDs()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        // have previous time for creating payments so as to consider these in settlements creation
        $now = Carbon::create(2018, 8, 14, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $merchantId = Preferences::MID_BHARTI_AIRTEL;

        $this->fixtures->merchant->createAccount($merchantId);

        // this is to make sure full fill all the conditions to get the settlement
        $this->fixtures->merchant->edit(
            $merchantId,
            [
                'channel'      => Channel::AXIS2,
                'suspended_at' => null,
                'activated'    => true
            ]);

        $payments = $this->createPaymentEntities(2, $merchantId, $now, 100000000);

        $this->createRefundFromPayments($payments);

        // setting the time stamp later so as to consider the previous dated transactions into the settlements
        $now = Carbon::create(2018, 8, 20, 15, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->initiateSettlements(Channel::AXIS2, null, true, [$merchantId] );

        $settlement = $this->getLastEntity('settlement', true);

        // validate the amount of settlement should be grater than 2lakh
        // which should set the mode as RTGS if MID is not part od ONLY_NEFT_MID array
        $this->assertGreaterThan(20000000, $settlement['amount']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        // validate the mode set as NEFT for the given MID
        $this->assertEquals(Mode::NEFT, $fta['mode']);
    }
}
