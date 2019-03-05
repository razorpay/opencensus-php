<?php

namespace RZP\Tests\Functional\Card;

use RZP\Models\Card\IIN;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class IinTest extends TestCase
{
    use RequestResponseFlowTrait;
    use IinTrait;
    use DbEntityFetchTrait;

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

    public function testEditIinFailedInvalidMessageType()
    {
        $this->testAddIin();

        $this->startTest();
    }

    public function testEditIin()
    {
        $this->testAddIin();

        $this->startTest();
    }

    public function testLockedIin()
    {
        $this->testAddIin();

        $this->startTest();

        $iin = $this->getLastEntity('iin', true);

        $this->assertEquals($iin[IIN\Entity::LOCKED], true);
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

        $this->fixtures->merchant->addFeatures(['atm_pin_auth', 'headless']);

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

        $this->fixtures->merchant->addFeatures(['headless']);

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

    public function testImportIinWithMessageType()
    {
        $this->ba->adminAuth();

        $file = $this->getUploadedIinFile(false, true);

        $testData = &$this->testData['testImportIinWithMessageType'];

        $testData['request']['files']['file'] = $file;

        $this->ba->adminAuth();

        $this->startTest();

        $iin = $this->getDbEntityById('iin', '559300')->toArray();
        $this->assertEquals('DMS', $iin['message_type']);
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

    public function testGetCardPaymentFlowsFailure()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCardPaymentFlowsEmpty()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCardPaymentFlows()
    {
        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['flows' => $flows]);

        $this->fixtures->merchant->addFeatures(['atm_pin_auth', 'axis_express_pay', 'headless']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCardPaymentFlowsFromIin()
    {
        $flows = [
            'pin'          => '1',
            'headless_otp' => '1',
            'otp'          => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['flows' => $flows]);

        $this->fixtures->merchant->addFeatures(['atm_pin_auth', 'axis_express_pay', 'headless']);

        $this->ba->privateAuth();

        $this->startTest();
    }


    public function testGetBulkFlows()
    {
        $flows = [
            'pin' => '1',
            'otp' => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['flows' => $flows]);

        $this->fixtures->merchant->addFeatures(['iin_listing']);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $this->assertEquals([401200], $response['iins']);

        $flows = [
            'pin' => '1',
            'otp' => '1',
            'headless_otp' => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['flows' => $flows]);

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $this->assertEquals([401200], $response['iins']);

         $flows = [
            'pin' => '1',
            'headless_otp' => '1',
        ];

        $this->fixtures->edit('iin', 401200, ['flows' => $flows]);

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $this->assertEquals([401200], $response['iins']);
    }

    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        return $this->runRequestResponseFlow($testData);
    }
}
