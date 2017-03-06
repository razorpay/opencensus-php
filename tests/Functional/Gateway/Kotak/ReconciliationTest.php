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

class ReconciliationTest extends TestCase
{
    use RequestResponseFlowTrait;
    use SettlementTrait;
    use PayoutTrait;

    public function setUp()
    {
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

        $batchSetlData = $this->getBatchSettlementData('settlement');

        // Validate batch settlement entity
        $this->fetchAndMatchBatchSettlement($batchSetlData);

        //Validate settlement entity
        $this->fetchAndMatchSettlements();
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

        $batchSetlData = $this->getBatchSettlementData('settlement');

        // Validate batch settlement entity
        $this->fetchAndMatchBatchSettlement($batchSetlData);

        //Validate settlement entity
        $this->fetchAndMatchSettlements(true);

        // Resetting time
        Carbon::setTestNow();
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

        $batchSetlData = $this->getBatchSettlementData('payout');

        // Validate batch settlement entity
        $this->fetchAndMatchBatchSettlement($batchSetlData);
    }

    protected function initiatePayoutsAndAssertSuccess()
    {
        $content = $this->initiatePayouts();

        $this->assertArrayHasKey('kotak', $content);
        $this->assertArrayHasKey('payout_text_file', $content['kotak']);

        return $content['kotak']['payout_text_file'];
    }

    protected function initiateSettlementsAndAssertSuccess()
    {
        $content = $this->initiateSettlements();

        $this->assertArrayHasKey('kotak', $content);
        $this->assertArrayHasKey('settlement_text_file', $content['kotak']);
        $this->assertArrayHasKey('settlement_excel_file', $content['kotak']);

        return $content['kotak']['settlement_text_file'];
    }

    protected function matchTransactions($prEntities)
    {
        $count = count($prEntities);

        $testData = [
            'request' => [
                'url' => '/transactions',
                'method' => 'GET',
            ],
            'response' => [
                'content' => [
                    'entity' => 'collection',
                    'count' => $count,
                    'items' => [],
                ]
            ]
        ];

        $txns = array();
        foreach ($prEntities as $prEntity)
        {
            $txn = array(
                'entity' => 'transaction',
                'amount' => $prEntity->getAmount(),
                'currency' => 'INR',
                'debit' => 0,
                'entity_id' => $prEntity->getPublicId(),
                'type' => $prEntity->getEntity());

            array_push($txns, $txn);
        }

        $testData['response']['items'] = $txns;

        $this->ba->proxyAuth();

        $content = $this->runRequestResponseFlow($testData);

        return $content;
    }

    protected function createPaymentAndRefundEntities()
    {
        $prEntities = [];

        $r = range(1,5);

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(20)->timestamp + 5;
        $capturedAt = Carbon::today('Asia/Kolkata')->subDays(20)->timestamp + 10;

        foreach ($r as $i)
        {
            $payment = $this->fixtures->create('payment:captured',
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt + 10]);

            $attrs = [
                'payment' => $payment,
                'amount' => '100000',
                'created_at' => $createdAt + 20,
                'updated_at' => $createdAt + 20];

            $refund = $this->fixtures->create('refund:from_payment', $attrs);

            array_push($prEntities, $payment);
            array_push($prEntities, $refund);
        }

        return $prEntities;
    }

    protected function createPayoutEntities()
    {
        $prEntities = array();

        $r = range(1,5);

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(4)->timestamp + 5;

        foreach ($r as $i)
        {
            $payout = $this->fixtures->create('payout',
                [
                    'amount' => 1000,
                    'created_at' => $createdAt
                ]);

            array_push($prEntities, $payout);
        }

        return $prEntities;
    }

    protected function getBatchSettlementData(string $type)
    {
        switch ($type) {
            case 'settlement':
                $data = [
                    'type' => 'settlement',
                    'entity' => 'batch_settlement',
                    'date' => Carbon::today('Asia/Kolkata')->timestamp,
                    'channel' => 'kotak',
                    'amount' => 4385000,
                    'fees' => 115000,
                    'service_tax' => 15000,
                    'api_fee' => 0,
                    'gateway_fee' => 0,
                    'total_count' => 1,
                    'transaction_count' => 10,
                ];
                break;

            case 'payout':
                $data = [
                    'type' => 'payout',
                    'entity' => 'batch_settlement',
                    'date' => Carbon::today('Asia/Kolkata')->timestamp,
                    'channel' => 'kotak',
                    'amount' => 5000,
                    'fees' => 2935,
                    'service_tax' => 385,
                    'api_fee' => 0,
                    'gateway_fee' => 0,
                    'total_count' => 5,
                    'transaction_count' => 5,
                ];
                break;

            default:
                $data = [];
        }

        return $data;
    }

    protected function fetchAndMatchBatchSettlement(array $data)
    {
        $content = $this->getEntities('batch_settlement', [], true);

        $data = array(
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                $data,
            ]
        );

        $this->assertArraySelectiveEquals($data, $content);

        $time = time();
        $item = $content['items'][0];
        $this->assertGreaterThanOrEqual($item['initiated_at'], $time);
        $this->assertGreaterThanOrEqual($item['reconciled_at'], $time);
        $this->assertGreaterThanOrEqual($item['returned_at'], $time);
    }

    protected function fetchAndMatchSettlements($failed = false)
    {
        $content = $this->getEntities('settlement', array(), true);

        $data = array(
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'merchant_id' => '10000000000000',
                    'channel' => 'kotak',
                    'amount' => 4385000,
                    'fees' => 115000,
                    'service_tax' => 15000,
                    'channel'     => 'kotak',
                ],
            ]
        );

        if ($failed == true)
        {
            $data['items'][0]['failure_reason'] = 'Reconciliation';
            $data['items'][0]['remarks'] =
                'This is a string which test characters count limit.' .
                ' This is a string which test characters count limit. This is a string which' .
                ' test characters count limit. This is a string which test characters count limit.' .
                ' This is a string which test characters count li';
            $data['items'][0]['status'] = 'failed';
        }
        else
        {
            $data['items'][0]['failure_reason'] = null;
            $data['items'][0]['remarks'] = '';
        }

        $this->assertArraySelectiveEquals($data, $content);
    }

    protected function checkAdjustmentCreated()
    {
        $setl = $this->getLastEntity('settlement', true);
        $settlementSign = 'setl_';
        $setlId = substr($setl['id'], strlen($settlementSign));

        $data = [
            'merchant_id' => "10000000000000",
            'amount' => 4385000,
            'currency' => "INR",
            'channel' => "kotak",
            'description' => "Adjustment for failed settlement",
            'settlement_id' => $setlId
        ];

        $content = $this->getLastEntity('adjustment', true);

        $this->assertArraySelectiveEquals($data, $content);
    }
}
