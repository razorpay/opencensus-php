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
        $this->testDataFilePath = __DIR__.'/ReconciliationTestData.php';

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

        // Validate daily settlement entity
        $this->fetchAndMatchDailySettlement();
    }

    public function testPayoutReconciliation()
    {
        // Create payments and refunds with timestamps two days back
        $payoutEntities = $this->createPayoutEntities();

        // // delete Existing files
        // $this->deleteSetlFiles();

        // reconciliation
        $txns = $this->matchTransactions($payoutEntities);

        // Generate settlements for above transactions
        $payoutFiles = $this->initiatePayoutsAndAssertSuccess();

        // Generate settlement reconciliation file
        $payoutReconciliationFile = $this->generateSetlReconciliationFile($payoutFiles);

        // Reconcile settlements
        $data = $this->reconcileSettlements($payoutReconciliationFile);
    }

    protected function initiatePayoutsAndAssertSuccess()
    {
        $content = $this->initiatePayouts();

        $this->assertArrayHasKey('kotak', $content);
        $this->assertArrayHasKey('payout_text_file', $content['kotak']);

        return $content['kotak']['payout_text_file'];
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
        $prEntities = array();

        $r = range(1,5);

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(4)->timestamp + 5;
        $capturedAt = Carbon::today('Asia/Kolkata')->subDays(4)->timestamp + 10;

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

    protected function fetchAndMatchDailySettlement()
    {
        $content = $this->getEntities('daily_settlement', [], true);

        $data = array(
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'entity' => 'daily_settlement',
                    'date' => Carbon::today('Asia/Kolkata')->timestamp,
                    'channel' => 'kotak',
                    'amount' => 4385000,
                    'fees' => 115000,
                    'service_tax' => 15000,
                    'api_fee' => 0,
                    'gateway_fee' => 0,
                    'settlement_count' => 1,
                    'transaction_count' => 10,
                ],
            ]
        );

        $this->assertArraySelectiveEquals($data, $content);

        $time = time();
        $item = $content['items'][0];
        $this->assertGreaterThanOrEqual($item['initiated_at'], $time);
        $this->assertGreaterThanOrEqual($item['reconciled_at'], $time);
        $this->assertGreaterThanOrEqual($item['returned_at'], $time);

        $content = $this->getEntities('settlement', array(), true);
    }
}
