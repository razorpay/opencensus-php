<?php

namespace RZP\Tests\Unit\P2p\Upi\Axis;

use RZP\Constants\Mode;
use RZP\Gateway\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\Axis\Library\Action;
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
            'cl'               => [
                'capability'       => '52000002000100040006',
                'challenge'        => 'AUnhIkGYnGBK=='
            ]
        ];

        $input = [
            RegisterToken\Entity::DEVICE_DATA => $request,
            RegisterToken\Entity::DEVICE_ID   => $this->fixtures->device(Fixtures::DEVICE_1)->getId(),
        ];

        $register_token = $this->fixtures->createRegisterToken($input);

        $this->gatewayInput = new ArrayBag();

        $this->gatewayInput->put('register_token', $register_token->toArrayBag());

        $this->context->setGatewayData($this->gateway, Device\Action::INITIATE_VERIFICATION, $this->gatewayInput);

        $response = $this->app['gateway']->call($this->gateway, 'device', $this->context, $this->mode);

        $this->assertNotNull($response->data()->get('request'));

        $this->assertEquals($response->data()->get('request')['payload']['simId'], '0');
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
