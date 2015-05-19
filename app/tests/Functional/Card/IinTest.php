<?php

namespace Tests\Functional\Card;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class IinTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/IinTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testAddIin()
    {
        $this->startTest();
    }

    public function testGetIin()
    {
        $this->startTest();
    }

    public function testGetIins()
    {
        $this->startTest();
    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->runRequestResponseFlow($testData);
    }
}
