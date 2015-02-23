<?php

namespace Tests\Unit\Gateway;

use Mockery;
use Models\Card;
use Tests\TestCase;

class GatewayDriverTest extends TestCase
{
    public function testGatewayDriverCreation()
    {
        $class = $this->mockGatewayManagerFunctions(true, 'test', 'hdfc');
        $this->assertEquals('Gateway\MockHdfc\Gateway', $class);

        $class = $this->mockGatewayManagerFunctions(true, 'live', 'hdfc');
        $this->assertEquals('Gateway\Hdfc\Gateway', $class);

        $class = $this->mockGatewayManagerFunctions(false, 'test', 'hdfc');
        $this->assertEquals('Gateway\Hdfc\Gateway', $class);

        $class = $this->mockGatewayManagerFunctions(false, 'live', 'atom');
        $this->assertEquals('Gateway\Atom\Gateway', $class);

        $class = $this->mockGatewayManagerFunctions(true, 'live', 'atom');
        $this->assertEquals('Gateway\Atom\Gateway', $class);
    }

    protected function mockGatewayManagerFunctions($mockgateway = true, $mode = 'test', $gateway = 'hdfc')
    {
        $mock = Mockery::mock('Gateway\GatewayManager')->makePartial()->shouldAllowMockingProtectedMethods();

        $mock->shouldReceive('getGateways')->withNoArgs()->andReturn(['atom', 'hdfc']);

        $mock->shouldReceive('getMode')->andReturn($mode);

        $gateways = [];
        if ($mockgateway)
            $gateways = ['atom', 'hdfc'];
        else
            $gateways = [];

        $mock->shouldReceive('getMockDrivers')->andReturn($gateways);

        $gateway = $mock->createDriver($gateway);

        return get_class($gateway);
    }
}
