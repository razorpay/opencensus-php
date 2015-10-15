<?php

namespace Tests\Functional\Gateway\Hdfc;

use Carbon\Carbon;
use Config;
use Mockery;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Functional\RequestResponseFlowTrait;
use Tests\Functional\Settlement\SettlementTrait;
use Tests\Functional\TestCase;

class HdfcGatewayMprTest extends TestCase
{
    use RequestResponseFlowTrait;
    use SettlementTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MprTestData.php';

        parent::setUp();

        $this->ba->publicAuth();
    }

    public function testUploadMpr()
    {
        //$this->mockSlack(5);

        $this->mockDashboardRequest(2);

        // Create payments and refunds with timestamps two days back
        $prEntities = $this->createPaymentAndRefundEntities();

        $this->deleteSetlFiles();

        // Generate the mpr file for above payments and refunds
        $from = Carbon::yesterday('Asia/Kolkata')->subDay(1)->timestamp;
        $to = Carbon::yesterday('Asia/Kolkata')->timestamp;
        $mprFile = $this->generateMpr($from, $to);

        // Upload the generate mpr file for reconciliation
        $settledAt = Carbon::today('Asia/Kolkata')->timestamp;
        $this->reconcileMpr($mprFile, $settledAt);

        // Check the txns corresponding to above payments after
        // reconciliation
        $txns = $this->matchTransactions($prEntities);

        // Generate settlements for above transactions
        $setlFile = $this->initiateSettlementsAndAssertSuccess();

        // Generate settlement reconciliation file
        $setlReconciliationFile = $this->generateSetlReconciliationFile($setlFile);

        // Reconcile settlements
        $data = $this->reconcileSettlements($setlReconciliationFile);

        // Generate settlement return file
        $setlReturnFile = $this->generateSetlReturnFile($data);

        // Reconcile settlement return file
        $this->processSetlReturns($setlReturnFile);

//        $this->matchSetlEntities();

        $this->fetchAndMatchDailySettlement();
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
                'type' => 'payment');

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

        $createdAt = Carbon::today('Asia/Kolkata')->subDays(2)->timestamp + 5;
        $capturedAt = Carbon::today('Asia/Kolkata')->subDays(2)->timestamp + 10;

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

    protected function fetchAndMatchDailySettlement()
    {
        $content = $this->getEntities('dailysettlement', [], true);

        $data = array(
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'entity' => 'daily_settlement',
                    'date' => Carbon::today('Asia/Kolkata')->timestamp,
                    'channel' => 'kotak',
                    'amount' => 4414500,
//                    'amount' => 4387640,
                    'api_fee' => 28500,
                    'gateway_fee' => 85500,
                    'settlement_count' => 2,
                    'transaction_count' => 11,
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

        $this->assertArraySelectiveEquals($this->testData['testUploadMprSettlementData'], $content);
    }

    protected function mockSlack($times)
    {
        $slackPretend = $this->config->get('slack.mock');

        if ($slackPretend === false)
        {
            return;
        }

        $slack = Mockery::mock('Services\Slack');

        $this->app->instance('slack', $slack);

        $slack->shouldReceive('send')
              ->times($times)
              ->with(Mockery::type('string'), '#settlements', 'settlements');
    }

    protected function mockDashboardRequest($times)
    {
        $config = $this->config->get('applications.dashboard');

        if ($config['pretend'] === false)
        {
            return;
        }

        $dashboard = Mockery::mock('Dashboard\DashboardServiceProvider');

        $this->app->instance('dashboard', $dashboard);

        $dashboard->shouldReceive('queueRecord')
              ->times($times)
              ->with('settlement', Mockery::type('Models\\Base\\PublicEntity'));
    }
}
