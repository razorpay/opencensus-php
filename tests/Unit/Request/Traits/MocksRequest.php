<?php

namespace RZP\Tests\Unit\Request\Traits;

use Illuminate\Http\Request;
use Illuminate\Routing\Route as IlluminateRoute;

use RZP\Http\Route;

/**
 * Includes methods to mock a route request.
 */
trait MocksRequest
{
    /**
     * Mocks a route request.
     * @param  string      $name    Name of the route (Ref Route.php)
     * @param  string|null $path    Actual url to be accessed(without placeholders)
     * @param  array       $methods Methods to mock partially
     * @param  array       $query   Query(GET) parameters to mock
     * @param  array       $input   Input(POST) parameters to mock
     * @param  array       $server  Server parameters i.e. headers etc.
     * @return Request
     */
    protected function mockRouteRequest(
        string $name,
        string $path = null,
        array $methods = [],
        array $query = [],
        array $input = [],
        array $server = []): Request
    {
        $params = Route::getApiRoute($name);

        $server['REQUEST_METHOD'] = $params[0];
        $requestMock = $this->mockRequest(array_merge($methods, ['path', 'ip']), $query, $input, $server);
        $requestMock->expects($this->any())
                    ->method('path')
                    ->willReturn($path ?: $params[1]);
        $requestMock->expects($this->any())
                    ->method('ip')
                    ->willReturn('1.1.1.1');

        $requestMock->setRouteResolver(function () use ($requestMock, $name, $params)
        {
            return (new IlluminateRoute($params[0], $params[1], ['as' => $name]))->bind($requestMock);
        });

        return $requestMock;
    }

    /**
     * Mocks request.
     * @param  array  $methods Methods to mock partially
     * @param  array  $query   Query(GET) parameters to mock
     * @param  array  $input   Input(POST) parameters to mock
     * @param  array  $server  Server parameters i.e. headers etc.
     * @return Request
     */
    protected function mockRequest(
        array $methods = [],
        array $query = [],
        array $input = [],
        array $server = []): Request
    {
        $requestMock = $this->getMockBuilder(Request::class)
                            ->setConstructorArgs([$query, $input, [], [], [], $server, null])
                            ->setMethods($methods)
                            ->getMock();
        $this->app->instance('request', $requestMock);

        return $requestMock;
    }
}
