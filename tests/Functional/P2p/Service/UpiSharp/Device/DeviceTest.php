<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Device;

use Carbon\Carbon;
use phpseclib\Crypt\AES;
use RZP\Models\P2p\Device;
use RZP\Exception\RuntimeException;
use RZP\Models\P2p\Device\DeviceToken;
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

        $response = $helper->verification($initiate['callback'], ['sms' => ['success' => true]]);

        $device = $this->getDbLastDevice();

        $deviceToken = $device->deviceToken($this->fixtures->handle);

        $this->assertArraySubset([
            'id'            => $device->getPublicId(),
            'status'        => 'verified',
            'auth_token'    => $device->getAuthToken(),
            'vpa'           => null,
        ], $response);

        // Gateway data should only have device_id and nothing else
        $this->assertArraySubset([
            'device_id' => 'GDID919999999999',
            'cl_token'  => null,
            'cl_expiry' => null,
        ], $deviceToken->getGatewayData());
    }

    public function testVerificationOnSameDevice()
    {
        $device = $this->fixtures->device;

        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateVerification();

        $authToken = $device->getAuthToken();

        // This will not initiate any scenario but will force phone number same
        $helper->setScenarioInContext(Scenario::N0000, '000', $device->getContact());

        $helper->verification($initiate['callback']);

        $device->reload();

        $this->assertNotSame($authToken, $device->getAuthToken());
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

        $expectedCallback = $this->expectedCallback(Requests::P2P_CUSTOMER_VERIFICATION, [
            $registerToken->getToken()
        ]);

        $this->assertSame($expectedCallback, $initiate['callback']);

        $helper->setScenarioInContext(Scenario::DE102);

        $verification = $helper->verification($initiate['callback']);

        $this->assertArraySubset([
            'version'   => 'v1',
            'type'      => 'redirect',
            'request'   => [
                'time'  => ($registerToken->getCreatedAt() + 10),
            ],
            'callback'  => $expectedCallback,
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

    public function initiateGetToken()
    {
        $cases = $this->getCommonCases(Scenario::DE201, Scenario::DE202, Scenario::DE203);

        return array_except($cases, []);
    }

    /**
     * @dataProvider initiateGetToken
     */
    public function testInitiateGetToken($scenario, $gatewayData, $type, $message)
    {
        $helper = $this->getDeviceHelper();

        // No problem to run schema validation, driver is caching the schema
        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenario[0]);

        if (empty($gatewayData['cl_expiry']) === false)
        {
            // We can not use time based value in data provider, since there will always be unknown lag
            $gatewayData['cl_expiry'] = Carbon::now()->addSeconds($gatewayData['cl_expiry'])->getTimestamp();
        }

        $this->fixtures->deviceToken(self::DEVICE_1)->setGatewayData($gatewayData)->saveOrFail();

        $response = $helper->initiateGetToken();

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            // The validation for error is kept separately
            return;
        }

        // If the
        if (empty($type) === false)
        {
            $this->npciClAssertionMessage = $message;

            $this->handleNpciClRequest(
                $response,
                'getChallenge',
                $this->expectedCallback(Requests::P2P_CUSTOMER_GET_TOKEN, [], ['type' => $type]),
                [
                    $type,
                    $this->fixtures->device->getUuid(),
                ]);

            return;
        }

        $token = $this->fixtures->deviceToken(self::DEVICE_1);

        $this->assertArraySubset([
            'version'   => 'v1',
            'type'      => 'redirect',
            'request'   => [
                'time'  => $token->getCreatedAt(),
            ],
            'callback'  => route(Requests::P2P_CUSTOMER_GET_TOKEN),
        ], $response);
    }

    public function getToken()
    {
        $cases = $this->getCommonCases(Scenario::DE301, Scenario::DE302, Scenario::DE303);

        // Get Token specific cases where post action we send npci response
        // Initial Register App with valid token
        $cases['noClDataInGatewayData'][4]              = true;
        $cases['noClDataInGatewayData'][0][1]           = Scenario::N0000;
        $cases['noClDataInGatewayData'][0][2]           = Scenario::N0000;

        // Case where Register app works with
        $cases['normalRotateCall'][4]                   = true;
        $cases['normalRotateCall'][0][1]                = Scenario::N0000;
        $cases['normalRotateCall'][0][2]                = Scenario::N0000;

        // Add new test where fetch token with initial
        $cases['fetchTokenFailedOnInitial']             = $cases['noClDataInGatewayData'];
        $cases['fetchTokenFailedOnInitial'][0][1]       = Scenario::DE304;
        $cases['fetchTokenFailedOnInitial'][0][2]       = Scenario::N0000;

        // Add new test where fetch token with rotate
        $cases['fetchTokenFailedOnRotate']              = $cases['normalRotateCall'];
        $cases['fetchTokenFailedOnRotate'][0][1]        = Scenario::DE304;
        $cases['fetchTokenFailedOnRotate'][0][2]        = Scenario::N0000;

        // Add new test where hmac is invalid
        $cases['invalidHmacOnRegisterApp']              = $cases['noClDataInGatewayData'];
        $cases['invalidHmacOnRegisterApp'][0][1]        = Scenario::DE305;
        $cases['invalidHmacOnRegisterApp'][0][2]        = Scenario::N0000;

        // Add new test where hmac is invalid
        $cases['registerAppFailed']                     = $cases['noClDataInGatewayData'];
        $cases['registerAppFailed'][0][1]               = Scenario::N0000;
        $cases['registerAppFailed'][0][2]               = Scenario::DE306;

        return array_except($cases, []);
    }

    /**
     * @dataProvider getToken
     */
    public function testGetToken($scenario, $gatewayData, $type, $message, $token = false)
    {
        $helper = $this->getDeviceHelper();

        // No problem to run schema validation, driver is caching the schema
        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenario[0]);

        if (empty($gatewayData['cl_expiry']) === false)
        {
            // We can not use time based value in data provider, since there will always be unknown lag
            $gatewayData['cl_expiry'] = Carbon::now()->addSeconds($gatewayData['cl_expiry'])->getTimestamp();
        }

        $this->fixtures->deviceToken(self::DEVICE_1)->setGatewayData($gatewayData)->saveOrFail();

        // This is the callback which will be sent in initiateGetToken
        $response = $helper->getToken($this->expectedCallback(Requests::P2P_CUSTOMER_GET_TOKEN));

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            // The validation for error is kept separately
            return;
        }

        // If the actions not defined and
        if (empty($type) === false)
        {
            $this->npciClAssertionMessage = $message;

            $function = $this->handleNpciClRequest(
                $response,
                'getChallenge',
                $this->expectedCallback(Requests::P2P_CUSTOMER_GET_TOKEN, [], ['type' => $type]),
                [
                    $type,
                    $this->fixtures->device->getUuid(),
                ]);

            // In these cases no additional calls are expected
            if ($token === false)
            {
                return;
            }

            if ($token === true)
            {
                $token = 'Bu1DCDFS3udZuKyJosfi+yget5o/mfXrvlXKyn+arAw=';
            }

            $sdkData = $function(['token' => $token]);

            // This is important for test case, we are rewinding time to beginning of the minute
            // Thus the api call time and current time are within that second itself
            // If this does not work, we will take 10 sec window for Sharp
            $this->now(Carbon::now()->second(0));

            $current  = Carbon::now()->getTimestamp();
            $expiry   = Carbon::now()->addDays(45)->getTimestamp();

            $callbackParams = ['token' => $token, 'expiry' => $expiry];

            $helper->setScenarioInContext($scenario[1]);

            $response = $helper->getToken($response['callback'], ['sdk' => $sdkData]);

            if ($helper->getScenarioInContext()->isSuccess() === false)
            {
                // The validation for error is kept separately
                return;
            }

            $function = $this->handleNpciClRequest(
                $response,
                'registerApp',
                $this->expectedCallback(Requests::P2P_CUSTOMER_GET_TOKEN, [], $callbackParams),
                [
                    $this->fixtures->device->getAppName(),
                    substr($this->fixtures->device->getContact(), -10),
                    $this->fixtures->device->getUuid(),
                    $this->calculateHmac('registerApp', $token),
                ]);

            $sdkData = $function();

            $helper->setScenarioInContext($scenario[2]);

            $response = $helper->getToken($response['callback'], ['sdk' => $sdkData]);

            $deviceToken = $this->fixtures->deviceToken(self::DEVICE_1)->refresh();

            if ($helper->getScenarioInContext()->isSuccess() === false)
            {
                // The validation for error is kept separately
                return;
            }

            // Check that the device token is updated properly in
            $this->assertArraySubset([
                'gateway_data' => [
                    'cl_token'  => $token,
                    'cl_expiry' => $expiry,
                ],
                'status'        => 'verified',
                'refreshed_at'  => $current,
            ], $deviceToken->toArray());
        }

        // Where token is sent to device
        $this->assertArraySubset([
            'id'                => $this->fixtures->device->getPublicId(),
            'auth_token'        => 'ALC01device001',
            'status'            => 'verified',
//            'expire_at'         => '2324234',

            'vpa'               => [
                'entity'        => 'vpa',
                'id'            => $this->fixtures->vpa->getPublicId(),
                'handle'        => $this->fixtures->handle->getCode(),
                'bank_account'  => [
                    'entity'    => 'bank_account',
                    'id'        => $this->fixtures->bank_account->getPublicId(),
                ],
                'default'       => true,
            ],

        ], $response);
    }

    public function deregister()
    {
        $cases = [];

        $message = 'dergisterSuccessfully';
        $cases[$message] = [[Scenario::N0000],];

        $message = 'dergisterRequestTimedOut';
        $cases[$message] = [[Scenario::DE401],];

        return $cases;
    }

    /**
     * @dataProvider deregister
     */
    public function testDeviceDeregister($scenarios)
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenarios[0]);

        $deviceToken = $this->fixtures->deviceToken(self::DEVICE_1);
        $bankAccount = $this->fixtures->bankAccount(self::DEVICE_1);
        $vpa         = $this->fixtures->vpa(self::DEVICE_1);

        $helper->deregisterDevice();

        if ($helper->getScenarioInContext()->isSuccess() === true)
        {
            $this->assertTrue($deviceToken->refresh()->trashed());
            $this->assertFalse($bankAccount->refresh()->trashed());
            $this->assertNull($vpa->refresh()->getBankAccountId());
        }
        else
        {
            $this->assertFalse($deviceToken->refresh()->trashed());
            $this->assertFalse($bankAccount->refresh()->trashed());
            $this->assertSame($bankAccount->getId(), $vpa->refresh()->getBankAccountId());
        }
    }

    private function getCommonCases($failure, $registration, $rotation)
    {
        // Cases-> Scenarios, gateway data ,token action, message
        $cases = [];

        $message = 'noClDataInGatewayData';
        $gatewayData = [];
        $cases[$message] = [[Scenario::N0000], $gatewayData, 'initial', $message,];

        $message = 'clExpiryIsMissing';
        $gatewayData = [
            'cl_token'  => 'some_token',
        ];
        $cases[$message] = [[Scenario::N0000], $gatewayData, 'initial', $message,];

        $message = 'clExpiryIsNull';
        $gatewayData = [
            'cl_token'  => 'some_token',
            'cl_expiry' => null,
        ];
        $cases[$message] = [[Scenario::N0000], $gatewayData, 'initial', $message,];

        $message = 'normalRotateCall';
        $gatewayData = [
            'cl_token'  => 'some_token',
            'cl_expiry' => 59, // Number of seconds adding to current time
        ];
        $cases[$message] = [[Scenario::N0000], $gatewayData, 'rotate', $message,];

        // Final response
        $message = 'noRegistrationOrRotationIsNeeded';
        $gatewayData = [
            'cl_token'  => 'some_token',
            'cl_expiry' => 65, // Number of seconds adding to current time
        ];
        $cases[$message] = [[Scenario::N0000], $gatewayData, null, $message,];

        // Scenarios
        $message = 'scenario#ForcedError';
        $gatewayData = [];
        $cases[$message] = [[$failure], $gatewayData, null, $message,];

        $message = 'scenario#ForcedRegistration';
        $gatewayData = [
            'cl_token'  => 'some_token',
            'cl_expiry' => 61, // Number of seconds adding to current time
        ];
        $cases[$message] = [[$registration], $gatewayData, 'initial', $message,];

        $message = 'scenario#ForcedRotation';
        $gatewayData = [
            'cl_token'  => 'some_token',
            'cl_expiry' => 65, // Number of seconds adding to current time
        ];
        $cases[$message] = [[$rotation], $gatewayData, 'rotate', $message,];

        return $cases;
    }
}
