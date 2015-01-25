<?php

namespace Tests\Functional\HdfcGateway;

use Carbon\Carbon;
use Config;
use Mockery;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Functional\Payment\PaymentAuthFlowTrait;
use Tests\Functional\RequestResponseFlowTrait;
use Tests\Functional\TestCase;

class HdfcGatewayMprTest extends TestCase
{
    use RequestResponseFlowTrait;

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
        $setlFile = $this->generateSettlements($txns);

        // Generate settlement reconciliation file
        $setlReconciliationFile = $this->generateSetlReconciliationFile($setlFile);

        // Reconcile settlements
        $data = $this->reconcileSettlements($setlReconciliationFile);

        // Generate settlement return file
        $setlReturnFile = $this->generateSetlReturnFile($data);

        // Reconcile settlement return file
        $this->processSetlReturns($setlReturnFile);
    }

    protected function reconcileSettlements($setlReconciliationFile)
    {
        $uploadedFile = $this->createUploadedFile($setlReconciliationFile);

        $request = [
            'url' => '/settlements/reconcile',
            'files' => [
                'setlReconciliationFile' => $uploadedFile
            ],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->unlinkFile($setlReconciliationFile);

        return $content;
    }

    protected function generateSettlements($txns)
    {
        $request = [
            'url' => '/settlements/initiate/kotak',
            'method' => 'POST',
            'content' => ['all' => 1],
        ];

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('kotak', $content);
        $this->assertArrayHasKey('setlFile', $content['kotak']);

        return $content['kotak']['setlFile'];
    }

    protected function generateSetlReconciliationFile($setlFile)
    {
        $uploadedFile = $this->createUploadedFile($setlFile);

        $request = [
            'url' => '/settlements/reconcile/generate',
            'files' => [
                'setlFile' => $uploadedFile
            ],
        ];

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('setlReconciliationFile', $content);

        $this->unlinkFile($setlFile);

        return $content['setlReconciliationFile'];
    }

    protected function generateSetlReturnFile($setlData)
    {
        $items = $setlData['items'];

        $content = [];

        foreach ($items as $item)
        {
            $content[] = [
                'id' => $item['id'],
                'refer_utr' => '1'
            ];
        }

        $request = [
            'url' => '/settlements/return/generate',
            'content' => $content,
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('setlReturnFile', $content);

        return $content['setlReturnFile'];
    }

    protected function processSetlReturns($setlReturnFile)
    {
        $uploadedFile = $this->createUploadedFile($setlReturnFile);

        $request = [
            'url' => '/settlements/return',
            'files' => [
                'setlReturnFile' => $uploadedFile
            ],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->unlinkFile($setlReturnFile);

        return $content;
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

    protected function generateMpr()
    {
        \Config::set('mail.pretend', true);

        $this->ba->appAuth();

        $request = array(
            'url' => '/gateway/mpr/generate');

        $mprFile = $this->makeRequestAndGetContent($request);

        return $mprFile;
    }

    protected function reconcileMpr($mprFile)
    {
        $uploadedFile = $this->createUploadedFile($mprFile, 'application/vnd.ms-excel');

        $request = &$this->testData['testUploadMpr']['request'];
        $request['content']['recipient'] = 'hdfc_mpr_testing_test@mg.razorpay.com';
        $request['content']['attachment-count'] = '1';

        $request['files']['attachment-1'] = $uploadedFile;

        $this->ba->appAuth();

        $this->runRequestResponseFlow($this->testData['testUploadMpr']);

        $this->unlinkFile($mprFile);
    }

    protected function unlinkFile($file)
    {
         // $this->assertTrue(
         //    unlink($file),
         //    'Could not delete file generated during testing. Filename: ' . $file);
    }

    protected function createUploadedFile($file, $mimeType = 'text/plain')
    {
        $this->assertFileExists($file);

        $uploadedFile = new UploadedFile(
                                $file,
                                $file,
                                $mimeType,
                                filesize($file),
                                null,
                                true);

        return $uploadedFile;
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