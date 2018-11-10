<?php

namespace RZP\Tests\P2p\Service\Device;

use RZP\Tests\P2p\Service\TestCase;

class DeviceTest extends TestCase
{
    public function testDeviceCreate()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->postCreateDevice();
    }

    public function testDeviceFetch()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->fetchDevice();
    }

    public function testDeviceRefreshToken()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->refreshClToken();
    }

    public function testDeviceDelete()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->deleteDevice();
    }
}
