<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Device;

use RZP\Models\P2p\Device;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class DeviceTest extends TestCase
{
    public function testInitiateVerification()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->initiateVerification([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);
    }

    public function testVerification()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateVerification([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $helper->withSchemaValidated();

        $helper->verification($initiate['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ]);
    }

    public function testInitiateGetToken()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->initiateGetToken([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);
    }

    public function testGetToken()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateGetToken([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $helper->withSchemaValidated();

        $helper->getToken($initiate['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ]);
    }

    public function testVerificationWithBinding()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateVerification([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $helper->withSchemaValidated();

        $request = $helper->verification($initiate['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'false',
                Fields::IS_DEVICE_ACTIVATED       => 'false',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ]);

         $helper->verification($request['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ]);
    }

    public function testDeregister()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $this->mockActionContentFunction([
            Device\Action::DEREGISTER => function(& $content)
            {
                //$content['status'] = 'FAILURE';
            }]);

        $deviceToken = $this->fixtures->deviceToken(self::DEVICE_1);
        $bankAccount = $this->fixtures->bankAccount(self::DEVICE_1);
        $vpa         = $this->fixtures->vpa(self::DEVICE_1);

        $helper->deregisterDevice();

        $this->assertTrue($deviceToken->refresh()->trashed());
        $this->assertTrue($bankAccount->refresh()->trashed());
        $this->assertTrue($vpa->refresh()->trashed());
    }
}
