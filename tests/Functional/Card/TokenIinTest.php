<?php

namespace RZP\Tests\Functional\Card;

use Event;

use RZP\Models\Card\TokenisedIIN;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class TokenIinTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/TokenIinTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testCreateIin()
    {
        $this->startTest();
    }

    public function testfetchIin()
    {
        $this->testCreateIin();

        $this->startTest();
    }

    public function testfetchbyTokenIin()
    {
        $this->testCreateIin();

        $this->startTest();
    }

    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];
        return $this->runRequestResponseFlow($testData);
    }
}
