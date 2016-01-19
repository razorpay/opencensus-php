<?php

namespace Tests\Functional\Merchant;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class TerminalTest extends TestCase
{
    use PaymentTrait;

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
                         ->create('merchant_fluid', ['id' => '10abcdefghsdfs'])
                         ->addTerminal('atom', ['id' => 'testatomrandom'])
                         ->get();

        $content = $this->startTest();
    }

    public function testRestoreTerminal()
    {
        $merchant = $this->fixtures
                         ->create('merchant_fluid', ['id' => '10abcdefghsdfs'])
                         ->addTerminal('atom', ['id' => 'testatomrandom', 'used_count' => 5])
                         ->get();

        $t = $this->deleteTerminal2('testatomrandom');
        $this->assertNotNull($t['deleted_at']);

        $t = $this->restoreTerminal('testatomrandom');
        $this->assertNull($t['deleted_at']);
    }

    public function testEditAxisMigsTerminal()
    {
        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', ['used_count' => 2]);

        $tid = $terminal['id'];
        $data = array('gateway_terminal_id' => 'random', 'gateway_terminal_password' => 'random');

        $content = $this->editTerminal($tid, $data);
        $this->assertEquals($content['gateway_terminal_id'], 'random');
    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        return $this->runRequestResponseFlow($testData);
    }
}
