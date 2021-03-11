<?php

namespace RZP\Tests\Functional\Offline\Device;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class DeviceTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/DeviceTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testDeviceActivateInit()
    {
        $this->ba->directAuth();

        $this->fixtures->create('offline_device:registered');

        $this->startTest();
    }
}
