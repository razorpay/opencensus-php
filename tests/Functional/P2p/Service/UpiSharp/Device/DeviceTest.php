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

        $helper->initiateVerification($this->getMockedSdkData());
    }

    public function testVerification()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateVerification($this->getMockedSdkData());

        $helper->withSchemaValidated();

        $helper->verification($initiate['callback'], $this->getMockedSdkData());
    }

    public function testInitiateGetToken()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->initiateGetToken($this->getMockedSdkData());
    }

    public function testGetToken()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateGetToken();

        $helper->withSchemaValidated();

        $helper->getToken($initiate['callback'], $this->getMockedSdkData());
    }

    public function testDeviceDeregister()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $deviceToken = $this->fixtures->deviceToken(self::DEVICE_1);
        $bankAccount = $this->fixtures->bankAccount(self::DEVICE_1);
        $vpa         = $this->fixtures->vpa(self::DEVICE_1);

        $helper->deregisterDevice();

        $this->assertTrue($deviceToken->refresh()->trashed());
        $this->assertTrue($bankAccount->refresh()->trashed());
        $this->assertTrue($vpa->refresh()->trashed());
    }

    public function testEditSameDevide()
    {
        $device = $this->fixtures->device;

        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateVerification();

        $authToken = $device->getAuthToken();

        $helper->verification($initiate['callback'], $this->getMockedSdkData([
            'contact' => $device->getContact(),
        ]));

        $device->reload();

        $this->assertNotSame($authToken, $device->getAuthToken());
    }

    private function getMockedSdkData(array $override = [])
    {
        return [
            'sdk' => array_filter(array_merge([
                'capability'       => '52000002000100040006',
                'challenge'        => 'AUnhIkGYnGBK=='
            ], $override))
        ];
    }
}
