<?php

namespace RZP\Tests\Unit\Gateway;

use Mockery;
use RZP\Tests\TestCase;

class GatewayDriverTest extends TestCase
{
    public function testGatewayDriverCreation()
    {
        $class = $this->mockGatewayManagerFunctions(true, 'test', 'hdfc');
        $this->assertEquals('RZP\Gateway\Hdfc\Mock\Gateway', $class);

        $class = $this->mockGatewayManagerFunctions(true, 'live', 'hdfc');
        $this->assertEquals('RZP\Gateway\Hdfc\Gateway', $class);

        $class = $this->mockGatewayManagerFunctions(false, 'test', 'hdfc');
        $this->assertEquals('RZP\Gateway\Hdfc\Gateway', $class);

        $class = $this->mockGatewayManagerFunctions(false, 'live', 'atom');
        $this->assertEquals('RZP\Gateway\Atom\Gateway', $class);

        $class = $this->mockGatewayManagerFunctions(true, 'live', 'atom');
        $this->assertEquals('RZP\Gateway\Atom\Gateway', $class);
    }

    protected function mockGatewayManagerFunctions($mockgateway = true, $mode = 'test', $gateway = 'hdfc')
    {
        $mock = Mockery::mock('RZP\Gateway\GatewayManager')->makePartial()->shouldAllowMockingProtectedMethods();

        $mock->shouldReceive('getGateways')->withNoArgs()->andReturn(['atom', 'hdfc']);

        $mock->shouldReceive('getMode')->andReturn($mode);

        $gateways = [];

        if ($mockgateway)
        {
            $gateways = ['atom', 'hdfc'];
        }

        $mock->shouldReceive('getMockDrivers')->andReturn($gateways);

        $gateway = $mock->createDriver($gateway);

        return get_class($gateway);
    }
}
