<?php

namespace Tests\Functional\HdfcGateway;

use Carbon\Carbon;
use Config;
use Mockery;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Functional\Payment\PaymentAuthFlowTrait;
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
        $this->mockSlack();

        $this->mockDashboardRequest();

        // Create payments and refunds
        $prEntities = $this->createPaymentAndRefundEntities();

        // Generate the mpr file for above payments and refunds
        $mprFile = $this->generateMpr();

        // Upload the generate mpr file for reconciliation
        $this->reconcileMpr($mprFile);

        // Check the txns corresponding to above payments after
        // reconciliation
        $txns = $this->matchTransactions($prEntities);

        // Generate settlements for above transactions
        $setlFile = $this->initiateSettlements($txns);

        // Generate settlement reconciliation file
        $setlReconciliationFile = $this->generateSetlReconciliationFile($setlFile);

        // Reconcile settlements
        $data = $this->reconcileSettlements($setlReconciliationFile);

        // Generate settlement return file
        $setlReturnFile = $this->generateSetlReturnFile($data);

        // Reconcile settlement return file
        $this->processSetlReturns($setlReturnFile);
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

        $createdAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 5;
        $capturedAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 10;

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

    protected function mockSlack()
    {
        $slackPretend = $this->config->get('slack.mock');

        if ($slackPretend === false)
        {
            return;
        }

        $slack = Mockery::mock('Services\Slack');

        $this->app->instance('slack', $slack);

        $slack->shouldReceive('send')
              ->times(5)
              ->with(Mockery::type('string'), '#settlements', 'settlements');
    }

    protected function mockDashboardRequest()
    {
        $config = $this->config->get('applications.dashboard');

        if ($config['pretend'] === false)
        {
            return;
        }

        $dashboard = Mockery::mock('Dashboard\DashboardServiceProvider');

        $this->app->instance('dashboard', $dashboard);

        $dashboard->shouldReceive('queueRecord')
              ->times(1)
              ->with('settlement', Mockery::type('Models\\Base\\PublicEntity'));
    }
}