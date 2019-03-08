<?php

namespace RZP\Tests\Unit\P2p\Upi\Axis;

use RZP\Constants\Mode;
use RZP\Gateway\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Http\Response\Response;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\Axis\Actions\DeviceAction;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class DeviceTest extends TestCase
{
    protected $context;

    protected $fixtures;

    protected $action;

    protected $mode = Mode::TEST;

    protected $gateway = 'p2p_upi_axis';

    public function setUp()
    {
        parent::setUp();

        $this->fixtures = new Fixtures($this->deviceSetMap);

        $this->context = $this->context();

        $this->setContext();
    }

    public function testInitiateVerification()
    {
        $request = [
            'customer_id'      => $this->fixtures->customer->getPublicId(),
            'ip'               => '179.0.0.1',
            'os'               => 'android',
            'os_version'       => '5.0.1',
            'simSlot'          => '0',
            'simid'            => '0',
            'uuid'             => '5637293534543',
            'type'             => 'mobile',
            'geocode'          => '12.971599,77.594566',
            'app_name'         => 'com.razorpay',
        ];

        $register_token = $this->fixtures->createRegisterToken([
            RegisterToken\Entity::DEVICE_DATA => $request,
        ]);

        $this->gatewayInput = new ArrayBag();

        $this->gatewayInput->put('sdk', new ArrayBag(['simId' => '0']));

        $this->gatewayInput->put('register_token', $register_token->toArrayBag());

        $this->context->setGatewayData($this->gateway, Device\Action::INITIATE_VERIFICATION, $this->gatewayInput);

        $response = $this->app['gateway']->call($this->gateway, 'device', $this->context, $this->mode);

        $this->assertTrue($response->hasRequest());

        $this->assertEquals($response->request()['content']['simId'], '0');
    }

    public function testVerification()
    {
        $request = [
            'customer_id' => $this->fixtures->customer->getPublicId(),
        ];

        $register_token = $this->fixtures->createRegisterToken([
            RegisterToken\Entity::DEVICE_DATA => $request,
        ]);

        $request = [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'false',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA'
            ]
        ];

        $this->gatewayInput = new ArrayBag();

        $this->gatewayInput->put('register_token', $register_token->toArrayBag());

        $this->gatewayInput->put(Fields::SDK, $request[Fields::SDK]);

        $this->context->setGatewayData($this->gateway, Device\Action::VERIFICATION, $this->gatewayInput);

        $response = $this->app['gateway']->call($this->gateway, 'device', $this->context, $this->mode);

        $this->assertTrue($response->hasRequest());

        $this->assertEquals($response->request()['action'], DeviceAction::ACTIVATE_DEVICE_BINDING);
    }

    public function testVerificationAfterDeviceBinding()
    {
        $request = [
            'customer_id' => $this->fixtures->customer->getPublicId(),
        ];

        $register_token = $this->fixtures->createRegisterToken([
            RegisterToken\Entity::DEVICE_DATA => $request,
        ]);

        $request = [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ];

        $this->gatewayInput = new ArrayBag();

        $this->gatewayInput->put('register_token', $register_token->toArrayBag());

        $this->gatewayInput->put(Fields::SDK, new ArrayBag($request[Fields::SDK]));

        $this->context->setGatewayData($this->gateway, Device\Action::VERIFICATION, $this->gatewayInput);

        $response = $this->app['gateway']->call($this->gateway, 'device', $this->context, $this->mode);

        $this->assertFalse($response->hasRequest());
    }

    public function testInitiateGetToken()
    {
        $request = [
            'ip'               => '179.0.0.1',
            'os'               => 'android',
            'os_version'       => '5.0.1',
            'simid'            => 'SIMID267506921',
            'uuid'             => '5637293534543',
            'type'             => 'mobile',
            'geocode'          => '12.971599,77.594566',
            'app_name'         => 'com.razorpay',
        ];

        $this->gatewayInput = new ArrayBag();

        $this->context->setGatewayData($this->gateway, Device\Action::INITIATE_GET_TOKEN, $this->gatewayInput);

        $response = $this->app['gateway']->call($this->gateway, 'device', $this->context, $this->mode);

        $this->assertTrue($response->hasRequest());

        $this->assertEquals($response->request()['sdk'], 'axis');

        $this->assertEquals($response->request()['content']['merchantId'], '123456');
    }

    public function testGetToken()
    {
        $request = [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ];

        $this->gatewayInput = new ArrayBag();

        $this->gatewayInput->put(Fields::SDK, $request[Fields::SDK]);

        $this->context->setGatewayData($this->gateway, Device\Action::GET_TOKEN, $this->gatewayInput);

        $response = $this->app['gateway']->call($this->gateway, 'device', $this->context, $this->mode);

        $this->assertFalse($response->hasRequest());
    }

    protected function context()
    {
        return $this->app['p2p.ctx'];
    }

    protected function setContext()
    {
        $this->context->setHandle($this->fixtures->handle(Fixtures::DEVICE_1));

        $this->context->setMerchant($this->fixtures->merchant(Fixtures::DEVICE_1));

        $this->context->setDevice($this->fixtures->device(Fixtures::DEVICE_1));
    }
}
