<?php

namespace Tests\Functional\Transaction;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class TransactionTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/TransactionData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testDummy()
    {
        ;// apparently you need to have a test per test file!
    }

    public function testAddAdjustment()
    {
        $adj = $this->startTest();

        $testData = $this->testData['testGetAdjustment'];
        $testData['request']['url'] = '/adjustments/'.$adj['id'];

        $adj = $this->runRequestResponseFlow($testData);
    }

    protected function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }
}