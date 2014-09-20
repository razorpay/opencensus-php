<?php

namespace Tests\Functional\HdfcGateway;

use Carbon\Carbon;
use Config;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Functional\TestCase;
use Tests\Functional\Transaction\TransactionAuthFlowTrait;

class SettlementTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setupPublicBasicAuthParams();

        //
        // load test data
        //
        $this->testData = include(__DIR__.'/helpers/MprTestData.php');
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