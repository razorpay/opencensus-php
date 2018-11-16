<?php

namespace RZP\Tests\P2p\Service\Device;

use RZP\Tests\P2p\Service\TestCase;

class DeviceTest extends TestCase
{
    public function testStartVerification()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->startVerification();
    }

    public function testCustomerVerificationStatus()
    {
        $token = str_random(16);

        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->fetchVerificationStatus($token);
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
    }
}
