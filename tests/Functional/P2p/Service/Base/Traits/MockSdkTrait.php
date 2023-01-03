<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

use Mockery;
use RZP\Gateway\P2p\Base\Factory;

trait MockSdkTrait
{
    protected $mockedSdk;
    protected $mockedCallback;

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

    protected function mockCallback($gateway = null)
    {
        if ($this->mockedCallback === null)
        {
            $gateway = $gateway ?: $this->gateway;

            $class = Factory::getGatewayClass($gateway, 'Mock\\Callback');

            $this->mockedCallback = Mockery::mock($class, [])->makePartial();
        }

        return $this->mockedCallback;
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

    protected function expectedCallback(string $route, array $params = [], array $callback = [])
    {
        $url = route($route, $params);

        $query = http_build_query([
            'callback'  => $callback,
        ]);

        if (empty($query) === false)
        {
            $url .= ('?' . $query);
        }

        return $url;
    }

    protected function assertRequestResponse(string $type, array $request, string $callback, array $response)
    {
        // TODO: This check is added to avoid JSON Schema change, Fix schema and remove this
        if (isset($request['time']) === true)
        {
            $request['time'] = (string) $request['time'];
        }
        $expected = [
            'version'   => 'v1',
            'type'      => $type,
            'request'   => $request,
            'callback'  => $callback,
        ];

        $this->assertArraySubset($expected, $response, true);
    }
}
