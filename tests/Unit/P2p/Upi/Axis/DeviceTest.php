<?php

namespace RZP\Tests\Unit\P2p\Upi\Axis;

use RZP\Constants\Mode;
use RZP\Gateway\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Models\P2p\Upi\Service;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Tests\Functional\Partner\Commission\Action;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\Axis\Actions\DeviceAction;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class DeviceTest extends TestCase
{
    use EventsTrait;
    /**
     * @var Context
     */
    protected $context;

    protected $action;

    protected $mode = Mode::TEST;

    protected $gateway = 'p2p_upi_axis';

    protected $entity = 'device';

    protected $gatewayInput;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setContext();

        $this->gatewayInput = new ArrayBag();
    }

    public function testInitiateVerification()
    {
        $registerToken = $this->fixtures->createRegisterToken([
            'sdk' => ['simId' => '0']
        ]);

        $this->gatewayInput->put('sdk', new ArrayBag(['simId' => '0']));

        $this->gatewayInput->put('register_token', $registerToken->toArrayBag());

        $response = $this->makeGatewayCall(Device\Action::INITIATE_VERIFICATION);

        $this->assertTrue($response->hasRequest());

        $this->assertSame('sdk', $response->requestType());
    }

    public function testVerification()
    {
        $registerToken = $this->fixtures->createRegisterToken([
            'sdk' => ['simId' => '0']
        ]);

        $this->gatewayInput->put('register_token', $registerToken->toArrayBag());

        $sdk = new ArrayBag([
            Fields::STATUS                    => 'SUCCESS',
            Fields::IS_DEVICE_BOUND           => 'true',
            Fields::IS_DEVICE_ACTIVATED       => 'false',
            Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
            Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA'
        ]);

        $this->gatewayInput->put(Fields::SDK, $sdk);

        $this->gatewayInput->put(Fields::CALLBACK,  new ArrayBag([
            Fields::ACTION => DeviceAction::BIND_DEVICE,
        ]));

        $response = $this->makeGatewayCall(Device\Action::VERIFICATION);

        $this->assertTrue($response->hasRequest());

        $this->assertEquals($response->request()['action'], DeviceAction::ACTIVATE_DEVICE_BINDING);
    }

    public function testVerificationAfterDeviceBinding()
    {
        $registerToken = $this->fixtures->createRegisterToken([
            'sdk' => ['simId' => '0']
        ]);

        $this->gatewayInput->put('register_token', $registerToken->toArrayBag());

        $sdk = new ArrayBag([
            Fields::STATUS                    => 'SUCCESS',
            Fields::IS_DEVICE_BOUND           => 'true',
            Fields::IS_DEVICE_ACTIVATED       => 'true',
            Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
            Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
            Fields::VPA_ACCOUNTS              => [],
            Fields::UDF_PARAMETERS            => [],
        ]);

        $this->gatewayInput->put(Fields::SDK, $sdk);

        $this->gatewayInput->put(Fields::CALLBACK,  new ArrayBag([
            Fields::ACTION => DeviceAction::BIND_DEVICE,
        ]));

        $response = $this->makeGatewayCall(Device\Action::VERIFICATION);

        $this->assertFalse($response->hasRequest());
    }

    public function testInitiateGetToken()
    {
        $response = $this->makeGatewayCall(Device\Action::INITIATE_GET_TOKEN);

        $this->assertTrue($response->hasRequest());

        $this->assertEquals($response->request()['sdk'], 'axis');

        $this->assertEquals($response->request()['content']['merchantId'], 'BAJAJUATTEST');
    }

    public function testGetToken()
    {
        $sdk = new ArrayBag([
            Fields::STATUS                    => 'SUCCESS',
            Fields::IS_DEVICE_BOUND           => 'true',
            Fields::IS_DEVICE_ACTIVATED       => 'true',
            Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
            Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
            Fields::VPA_ACCOUNTS              => [],
            Fields::UDF_PARAMETERS            => [],
        ]);

        $this->gatewayInput->put(Fields::SDK, $sdk);

        $this->gatewayInput->put(Fields::CALLBACK,  new ArrayBag([
            Fields::ACTION => DeviceAction::GET_SESSION_TOKEN,
        ]));

        $response = $this->makeGatewayCall(Device\Action::GET_TOKEN);

        $this->assertFalse($response->hasRequest());
    }

    public function testReminder()
    {
        $this->mockRaven();

        $response = (new Service())->reminderCallback([
            'handle' => $this->fixtures->handle->getCode(),
            'entity' => 'device',
            'id'     => $this->fixtures->device->getId(),
            'action' => Device\Action::DEVICE_COOLDOWN_COMPLETED
        ]);

        $this->assertArraySubset([
            'success' => true
        ], $response);

        $this->assertRavenRequest(function ($input) {
            $this->assertArraySubset([
                'receiver' => $this->fixtures->device->getContact(),
                'source'   => 'api.test.p2p',
                'template' => 'sms.p2p.cooldown_completed',
                'sender'   => 'SENDER',
                'params'   => [
                    'app_name'      => 'APPLICATION NAME',
                    'sms_signature' => 'SMS SIGNATURE',
                ]
            ], $input);
        });
    }

    protected function setContext()
    {
        $context = new Context();

        $context->setHandle($this->fixtures->handle(self::DEVICE_1));

        $context->setMerchant($this->fixtures->merchant(self::DEVICE_1));

        $context->setDevice($this->fixtures->device(self::DEVICE_1));

        $context->setDeviceToken($this->fixtures->deviceToken(self::DEVICE_1));

        $context->registerServices();

        $this->context = $context;
    }

    protected function makeGatewayCall($action): Base\Response
    {
        $this->context->setGatewayData($this->gateway, $action, $this->gatewayInput);

        return $this->app['gateway']->call($this->gateway, $this->entity, $this->context, $this->mode);
    }
}
