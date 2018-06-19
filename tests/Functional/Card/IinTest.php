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

        $this->ba->adminAuth();
    }

    public function testAddIin()
    {
        $this->startTest();
    }

    public function testAddIinFailed()
    {
        $this->startTest();
    }

    public function testEditIinFailed()
    {
        $this->testAddIin();

        $this->startTest();
    }

    public function testEditIin()
    {
        $this->testAddIin();

        $this->startTest();
    }

    public function testGetIin()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetPaymentFlows()
    {
        $this->testAddIin();

        $this->ba->publicAuth();

        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
            ];

        $this->fixtures->edit('iin', 112333, ['flows' => $flows]);

        $this->fixtures->merchant->addFeatures(['atm_pin_auth', 'otpelf']);

        $this->startTest();
    }

    public function testGetPaymentFlowsEmptyResponse()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetPaymentOtpFlow()
    {
        $this->testAddIin();

        $this->ba->publicAuth();

        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
        ];

        $this->fixtures->edit('iin', 112333, ['flows' => $flows]);

        $this->fixtures->merchant->addFeatures(['otpelf']);

        $response = $this->startTest();

        $this->assertArrayNotHasKey('atm_pin_auth', $response);
    }

    public function testGetIins()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testImportIin()
    {
        $this->ba->adminAuth();

        $file = $this->getUploadedIinFile();

        $testData = &$this->testData['testImportIin'];

        $testData['request']['files']['file'] = $file;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testImportIinWithIssuer()
    {
        $this->ba->adminAuth();

        $file = $this->getUploadedIinFile(true);

        $testData = &$this->testData['testImportIinWithIssuer'];

        $testData['request']['files']['file'] = $file;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testIinRangeUploadWithType()
    {
        $this->ba->adminAuth();

        $this->startTest();

        $iin = $this->getEntityById('iin', 652851, true);

        $this->assertEquals('credit', $iin['type']);
        $this->assertEquals('RuPay', $iin['network']);
        $this->assertEquals(null, $iin['issuer']);
    }

    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        return $this->runRequestResponseFlow($testData);
    }
}
