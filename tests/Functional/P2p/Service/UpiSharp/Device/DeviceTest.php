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

        $helper->initiateVerification([
            'sdk' => [
                'capability'       => '52000002000100040006',
                'challenge'        => 'AUnhIkGYnGBK=='
            ]
        ]);
    }

    public function testVerification()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateVerification([
            'sdk' => [
                'capability'       => '52000002000100040006',
                'challenge'        => 'AUnhIkGYnGBK=='
            ]
        ]);

        $helper->withSchemaValidated();

        $helper->verification($initiate['callback'], [
            'sdk' => [
                'capability'       => '52000002000100040006',
                'challenge'        => 'AUnhIkGYnGBK=='
            ]
        ]);
    }

    public function testInitiateGetToken()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->initiateGetToken([
            'sdk' => [
                'capability'       => '52000002000100040006',
                'challenge'        => 'AUnhIkGYnGBK=='
            ]
        ]);
    }

    public function testGetToken()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateGetToken();

        $helper->withSchemaValidated();

        $helper->getToken($initiate['callback'], [
            'sdk' => [
                'capability'       => '52000002000100040006',
                'challenge'        => 'AUnhIkGYnGBK=='
            ]
        ]);
    }

    public function testDeviceDeregister()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->deregisterDevice();

        $this->assertTrue($this->fixtures->currentDeviceToken()->isExpired());
    }
}
