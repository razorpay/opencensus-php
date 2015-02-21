<?php

namespace Tests\Functional\Merchant;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class TerminalTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/TerminalData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testAssignTerminal()
    {
        $merchant = $this->fixtures->create('merchant');

        $url = '/merchants/'.$merchant->getKey().'/terminals';
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testReassignTerminalForSameGateway()
    {
        $this->startTest();
    }

    public function testAssignTerminalForDifferentGateway()
    {
        $this->startTest();
    }

    public function testDeleteTerminal()
    {
        $merchant = $this->fixtures
                         ->create('merchant_fluid:entity', ['id' => '10abcdefghsdfs'])
                         ->addTerminal('atom', ['id' => 'testatomrandom'])
                         ->get();

        $content = $this->startTest();
    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        return $this->runRequestResponseFlow($testData);
    }
}
