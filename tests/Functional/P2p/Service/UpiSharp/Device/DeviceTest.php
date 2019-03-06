<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Device;

use RZP\Tests\P2p\Service\UpiSharp\TestCase;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class DeviceTest extends TestCase
{
    public function testInitiateVerification()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->initiateVerification();
    }

    public function testVerification()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateVerification();

        $helper->withSchemaValidated();

        $helper->verification($initiate['callback']);
    }

    public function testInitiateGetToken()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->initiateGetToken();
    }

    public function testGetToken()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateGetToken();

        $helper->withSchemaValidated();

        $helper->getToken($initiate['callback']);
    }

    public function testDeviceDeregister()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->deregisterDevice();

        $this->assertTrue($this->fixtures->currentDeviceToken()->isExpired());
    }
}
