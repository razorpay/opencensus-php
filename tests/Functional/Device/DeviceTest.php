<?php

namespace RZP\Tests\Functional\Device;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class DeviceTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->markTestSkipped();

        $this->testDataFilePath = __DIR__.'/DeviceTestData.php';

        parent::setUp();
    }

    public function testCreateDevice()
    {
        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertNotNull($response['auth_token']);
        $this->assertNotNull($response['verification_token']);
    }

    public function testFetchCreatedDevice()
    {
        $this->ba->deviceAuth();

        $response = $this->startTest();

        $this->assertNotNull($response['auth_token']);
        $this->assertNotNull($response['verification_token']);
    }

    public function testFetchVerifiedDevice()
    {
        $this->ba->deviceAuth();

        $device = $this->fixtures->create('device:verified');

        $this->testData[__FUNCTION__]['request']['url'] = '/upi/devices/' . $device->getPublicId();

        $response = $this->startTest();

        $this->assertFalse(isset($response['auth_token']));
        $this->assertFalse(isset($response['verification_token']));
    }

    public function testDeviceVerification()
    {
        $this->ba->publicAuth();

        $createRequest = $this->testData['testCreateDevice'];

        $response = $this->startTest($createRequest);

        $this->testData[__FUNCTION__]['request']['url'] = '/upi/devices/verify';
        $this->testData[__FUNCTION__]['request']['content']['message'] = $response['verification_token'];

        $this->ba->directAuth();

        $this->startTest();
    }

    protected function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = [];
        if (isset($this->testData[$name]))
        {
            $testData = $this->testData[$name];
        }

        if ($testDataToReplace !== [])
        {
            $testData = $testDataToReplace;
        }

        return $this->runRequestResponseFlow($testData);
    }
}
