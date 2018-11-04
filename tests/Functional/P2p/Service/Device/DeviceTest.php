<?php

namespace RZP\Tests\P2p\Service\Device;

use RZP\Tests\P2p\Service\TestCase;

class DeviceTest extends TestCase
{
    public function testDeviceCreate()
    {
        $helper = $this->getDeviceHelper();

        //$helper->withSchemaValidated();

        $helper->postCreateDevice();
    }
}
