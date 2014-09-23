<?php

namespace Tests\Functional\HdfcGateway;

use Carbon\Carbon;
use Config;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Functional\TestCase;
use Tests\Functional\Transaction\TransactionAuthFlowTrait;

class HdfcGatewayMprTest extends TestCase
{
    use TransactionAuthFlowTrait;

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
        $txns = array();

        $r = range(1,1);

        $createdAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 5;
        $capturedAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 10;

        foreach ($r as $i)
        {
            $txn = $this->fixtures->createTransactionCapturedEntity(
                ['captured_at' => $capturedAt, 'created_at' => $createdAt]);

            array_push($txns, $txn);
        }

        \Config::set('mail.pretend', true);

        $this->setupAppBasicAuthParams();

        $request = array(
            'method' => 'POST',
            'url' => '/gateway/mpr/generate',
            'content' => array());

        $mprFile = $this->makeRequestAndGetContent($request);

        $mimeType = 'application/vnd.ms-excel';

        $mprUploadedFile = new UploadedFile($mprFile, $mprFile, $mimeType, filesize($mprFile), null, true);

        $request = &$this->testData[__FUNCTION__]['request'];
        $request['content']['recipient'] = 'hdfc_mpr_testing_test@mg.razorpay.com';
        $request['content']['attachment-count'] = '1';

        $request['files']['attachment-1'] = $mprUploadedFile;

        $this->setupAppBasicAuthParams();

        $this->startTest();

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