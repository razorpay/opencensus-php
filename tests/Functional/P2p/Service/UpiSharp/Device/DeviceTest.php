<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Device;

use RZP\Models\P2p\Device;
use RZP\Http\Controllers\P2p\Requests;
use RZP\Tests\P2p\Service\Base\P2pRequest;
use RZP\Tests\P2p\Service\Base\Scenario;
use RZP\Gateway\P2p\Upi\Sharp\DeviceGateway;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class DeviceTest extends TestCase
{
    public function testInitiateVerification()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $response = $helper->initiateVerification();

        $token = $this->getDbLastEntity('p2p_register_token');
        $queryParams = '?c=919999999999&t=' . $token->getToken();

        $this->assertArraySubset([
            'version'   => 'v1',
            'type'      => 'sms',
            'request'   => [
                'destination'   => DeviceGateway::SMS_VERIFICATION_RECEIVER,
                'content'       => secure_url('v1/upi/callback/p2p_upi_sharp') . $queryParams,
                'action'        => 'send',
            ],
        ], $response);
    }

    public function testVerification()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateVerification();

        $helper->withSchemaValidated();

        $response = $helper->verification($initiate['callback']);

        $device = $this->getDbLastDevice();

        $deviceToken = $device->deviceToken($this->fixtures->handle);

        $this->assertArraySubset([
            'id'            => $device->getPublicId(),
            'status'        => 'verified',
            'auth_token'    => $device->getAuthToken(),
            'vpa'           => null,
        ], $response);

        $this->assertArrayNotHasKey('npci_token', $deviceToken->getGatewayData());
    }

    public function testScenarioVerificationSmsTimedOut()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateVerification();

        $helper->setScenarioInContext(Scenario::DE101);

        $helper->verification($initiate['callback']);
    }

    public function testScenarioVerificationSmsBasedValidation()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateVerification();

        $registerToken = $this->getDbLastEntity('p2p_register_token');

        $this->assertSame(route(Requests::P2P_CUSTOMER_VERIFICATION, [$registerToken->getToken()]) . '?',
                          $initiate['callback']);

        $helper->setScenarioInContext(Scenario::DE102);

        $verification = $helper->verification($initiate['callback']);

        $this->assertArraySubset([
            'version'   => 'v1',
            'type'      => 'poll',
            'request'   => [
                'time'  => ($registerToken->getCreatedAt() + 10),
            ],
            'callback'  => route(Requests::P2P_CUSTOMER_VERIFICATION, [$registerToken->getToken()]) . '?',
        ], $verification);

        $callback = [
            'content' => json_encode([
                // URL would have had different phone number
                'c' => '919876543210',
                't' => $registerToken->getToken(),
            ]),
        ];

        // Callbacks will go to live handle by default, we can disable live handle
        $this->fixtures->disableHandle($this->fixtures->handle->getCode());

        $response = $helper->callback($this->gateway, $callback);

        // The response we send back to gateway
        $this->assertArraySubset([
            'success' => 1,
        ], $response);

        // Asserting as new device is created
        $device = $this->getDbLastDevice();
        $this->assertArraySubset([
            Device\Entity::CUSTOMER_ID          => 'ArzpLocalCust1',
            Device\Entity::MERCHANT_ID          => '10000000000000',
            Device\Entity::CONTACT              => '919876543210',
        ], $device->toArray());

        // Asserting on device_token, It's verified and belonged to the device
        $deviceToken = $device->deviceToken($this->fixtures->handle);

        $this->assertEmpty($deviceToken->getGatewayData());

        $helper->setScenarioInContext(Scenario::DE102);

        $response = $helper->verification($verification['callback']);

        // This time we will receive the device verified
        $this->assertArraySubset([
            'id'            => $device->getPublicId(),
            'status'        => 'verified',
            'auth_token'    => $device->getAuthToken(),
            'vpa'           => null,
        ], $response);
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

    public function testScenarioGetTokenNotRegistered()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateGetToken();

        $helper->setScenarioInContext(Scenario::DE201);

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
        $this->assertFalse($bankAccount->refresh()->trashed());
        $this->assertNull($vpa->refresh()->getBankAccountId());
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
