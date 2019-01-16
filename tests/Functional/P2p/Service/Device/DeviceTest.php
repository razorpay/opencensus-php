<?php

namespace RZP\Tests\P2p\Service\Device;

use RZP\Tests\P2p\Service\TestCase;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class DeviceTest extends TestCase
{
    public function testStartVerification()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->startVerification();
    }

    public function testVerificationStatus()
    {
        $helper = $this->getDeviceHelper();

        $registerResponse = $helper->startVerification();

        $helper->withSchemaValidated();

        $helper->fetchVerificationStatus($registerResponse['token']);
    }

    public function testDeviceRefreshToken()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->refreshClToken();
    }

    public function testDeviceDeregister()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->deregisterDevice();

        $this->assertTrue($this->fixtures->currentDeviceToken()->isExpired());
    }
}
