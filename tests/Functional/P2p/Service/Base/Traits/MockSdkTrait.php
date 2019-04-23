<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

use Mockery;
use RZP\Gateway\P2p\Base\Factory;

trait MockSdkTrait
{
    protected $mockedSdk;

    protected function mockSdk($gateway = null)
    {
        if ($this->mockedSdk === null)
        {
            $gateway = $gateway ?: $this->gateway;

            $class = Factory::getGatewayClass($gateway, 'Mock\\Sdk');

            $this->mockedSdk = Mockery::mock($class, [])->makePartial();
        }

        return $this->mockedSdk;
    }

    /*
     * This method makes a call to Mock Sdk class, which returns the mocked response for sdk calls.
     * This response is then used to make subsequent call to api for next action
     */
    protected function handleSdkRequest(array $request)
    {
        $this->assertSame('sdk', $request['type']);

        $this->mockSdk()->setMockedRequest($request['request']);

        return [
            'sdk' => $this->mockSdk()->call()
        ];
    }

    protected function mockSdkContentFunction(callable $closure)
    {
        return $this->mockSdk()
            ->shouldReceive('content')
            ->andReturnUsing($closure)
            ->mock();
    }
}
