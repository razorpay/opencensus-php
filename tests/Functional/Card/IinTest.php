<?php

namespace RZP\Tests\Functional\Card;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class IinTest extends TestCase
{
    use RequestResponseFlowTrait;
    use IinTrait;

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

    public function testEditIin()
    {
        $this->testAddIin();

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

    public function testImportIin()
    {
        $file = $this->getUploadedIinFile();

        $testData = &$this->testData['testImportIin'];

        $testData['request']['files']['file'] = $file;

        $this->startTest();
    }

    public function testImportIinWithIssuer()
    {
        $file = $this->getUploadedIinFile(true);

        $testData = &$this->testData['testImportIinWithIssuer'];

        $testData['request']['files']['file'] = $file;

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
