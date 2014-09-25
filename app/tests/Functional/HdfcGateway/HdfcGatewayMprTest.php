<?php

namespace Tests\Functional\HdfcGateway;

use Carbon\Carbon;
use Config;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Functional\Payment\PaymentAuthFlowTrait;
use Tests\Functional\RequestResponseFlowTrait;
use Tests\Functional\TestCase;

class HdfcGatewayMprTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        parent::setUp();

        $this->setupPublicBasicAuthParams();

        //
        // load test data
        //
        $this->testData = include(__DIR__.'/helpers/MprTestData.php');
    }

    public function testUploadMpr()
    {
        $payments = $this->createPaymentEntities();

        $mprFile = $this->generateMpr();

        $this->uploadMpr($mprFile);

        $txns = $this->matchTransactions($payments);

        $this->generateSettlements($txns);
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
                    'data' => [
                        array(
                            'entity' => 'settlement',
                        ),
                    ]
                ]
            ]
        ];

        $this->setupAppBasicAuthParams();

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
                    'data' => [],
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
                'entity_type' => 'payment');

            array_push($txns, $txn);
        }

        $testData['response']['data'] = $txns;

        $this->setupProxyBasicAuthParams();

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

        $this->setupAppBasicAuthParams();

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

        $mprUploadedFile = new UploadedFile(
                                $mprFile,
                                $mprFile,
                                $mimeType,
                                filesize($mprFile), null, true);

        $request = &$this->testData['testUploadMpr']['request'];
        $request['content']['recipient'] = 'hdfc_mpr_testing_test@mg.razorpay.com';
        $request['content']['attachment-count'] = '1';

        $request['files']['attachment-1'] = $mprUploadedFile;

        $this->setupAppBasicAuthParams();

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