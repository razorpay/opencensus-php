<?php

namespace Tests\Functional\HdfcGateway;

use Config;
use Tests\Functional\TestCase;
use Tests\Functional\Transaction\TransactionAuthFlowTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class HdfcGatewayMprTest extends TestCase
{
    use TransactionAuthFlowTrait;

    public function setUp()
    {
        parent::setUp();

        $gateway = 'mockhdfc';

        $this->setupPublicBasicAuthParams();

        //
        // load test data
        //
        $this->testData = include(__DIR__.'/helpers/MprTestData.php');
    }

    public function testUploadMpr()
    {
        $defaultGateway = Config::get('gateway.default');
        Config::set('gateway.default', 'mockhdfc');

        $txns = array();

        $r = range(1,1);

        foreach ($r as $i)
        {
            $txn = $this->defaultAuthTransaction();

            $txn = $this->captureTransaction($txn['id'], $txn['amount']);

            array_push($txns, $txn);
        }

        $mprFile = (new \Gateway\MockHdfc\Gateway)->generateMpr();

        $mimeType = 'application/vnd.ms-excel';

        $mprUploadedFile = new UploadedFile($mprFile, $mprFile, $mimeType, filesize($mprFile), null, true);

        $this->testData[__FUNCTION__]['request']['files']['mpr'] = $mprUploadedFile;

        $this->setupAppBasicAuthParams();

        $this->startTest();

        Config::set('gateway.default', $defaultGateway);
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