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

        // Create payments
        $payments = $this->createPaymentEntities();

        // Generate the mpr file for above payments
        $mprFile = $this->generateMpr();
        $this->markTestSkipped();
        // Upload the generate mpr file for reconciliation
        $this->uploadMpr($mprFile);

        // Check the txns corresponding to above payments after
        // reconciliation
        $txns = $this->matchTransactions($payments);

        // Generate settlements for above transactions
        $this->generateSettlements($txns);
    }

    protected function mockSlack()
    {
        $slackPretend = $this->config->get('slack.pretend');

        if ($slackPretend === false)
        {
            return;
        }

        $slack = Mockery::mock('Services\Slack');

        $this->app->instance('slack', $slack);

        $slack->shouldReceive('send')
              ->times(3)
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
              ->with('settlement', Mockery::type('Models\\Base\\PublicCollection'));
    }

    protected function generateSettlements($txns)
    {
        $testData = [
            'request' => [
                'url' => '/settlements/initiate',
                'method' => 'POST',
                'content' => ['all' => 1],
            ],
            'response' => [
                'content' => [
                    'entity' => 'collection',
                    'count' => 1,
                    'items' => [
                        array(
                            'entity' => 'settlement',
                        ),
                    ]
                ]
            ]
        ];

        $this->ba->appAuth();

        $content = $this->runRequestResponseFlow($testData);
    }

    protected function matchTransactions($payments)
    {
        $count = count($payments);

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
        foreach ($payments as $payment)
        {
            $txn = array(
                'entity' => 'transaction',
                'amount' => $payment->getAmount(),
                'currency' => 'INR',
                'debit' => 0,
                'entity_id' => $payment->getPublicId(),
                'type' => 'payment');

            array_push($txns, $txn);
        }

        $testData['response']['items'] = $txns;

        $this->ba->proxyAuth();

        $content = $this->runRequestResponseFlow($testData);

        return $content;
    }

    protected function createPaymentEntities()
    {
        $payments = array();

        $r = range(1,2);

        $createdAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 5;
        $capturedAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 10;

        foreach ($r as $i)
        {
            $payment = $this->fixtures->createPaymentCapturedEntity(
                ['captured_at' => $capturedAt,
                 'created_at' => $createdAt,
                 'updated_at' => $createdAt + 10]);

            array_push($payments, $payment);
        }

        return $payments;
    }

    protected function generateMpr()
    {
        \Config::set('mail.pretend', true);

        $this->ba->appAuth();

        $request = array(
            'method' => 'POST',
            'url' => '/gateway/mpr/generate',
            'content' => array());

        $mprFile = $this->makeRequestAndGetContent($request);

        return $mprFile;
    }

    protected function uploadMpr($mprFile)
    {
        $mimeType = 'application/vnd.ms-excel';

        $this->assertFileExists($mprFile);

        $mprUploadedFile = new UploadedFile(
                                $mprFile,
                                $mprFile,
                                $mimeType,
                                filesize($mprFile), null, true);

        $request = &$this->testData['testUploadMpr']['request'];
        $request['content']['recipient'] = 'hdfc_mpr_testing_test@mg.razorpay.com';
        $request['content']['attachment-count'] = '1';

        $request['files']['attachment-1'] = $mprUploadedFile;

        $this->ba->appAuth();

        $this->runRequestResponseFlow($this->testData['testUploadMpr']);

        $this->assertTrue(
            unlink($mprFile),
            'Could not delete hdfc mpr file generated during testing');
    }

    public function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }
}