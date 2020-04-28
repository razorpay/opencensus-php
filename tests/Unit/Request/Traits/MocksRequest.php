<?php

namespace RZP\Tests\Unit\Request\Traits;

use Illuminate\Http\Request;
use Illuminate\Routing\Route as IlluminateRoute;

use RZP\Http\Route;

/**
 * Provides mocking capability of given request route for Unit tests.
 */
trait MocksRequest
{
    /**
     * Headers used for next mockRouteRequest() call and reset after.
     * @var array
     */
    protected $withRequestHeaders = [];

    /**
     * @param  array $headers
     * @return mixed
     */
    protected function withRequestHeaders(array $headers)
    {
        $this->withRequestHeaders = $headers;
        return $this;
    }

    /**
     * Mocks a route request.
     * @param  string      $name    Name of the route (Ref Route.php)
     * @param  string|null $path    Actual url to be accessed(without placeholders)
     * @param  array       $methods Additional methods to mock partially
     * @param  array       $auth    Http basic auth details - user & password
     * @param  array       $query   Query(GET) parameters to mock
     * @param  array       $input   Input(POST) parameters to mock
     * @param  array       $server  Server parameters i.e. headers etc.
     * @return Request
     */
    protected function mockRouteRequest(
        string $name,
        string $path = null,
        array $methods = [],
        array $auth = [],
        array $query = [],
        array $input = [],
        array $server = []): Request
    {
        // Extract route parameters(i.e. method, pattern, alias etc) from Route.php
        $params = Route::getApiRoute($name);

        // Need to set request method headers for Symfony
        $server['REQUEST_METHOD'] = $params[0];
        // Sets request user and password
        $server['PHP_AUTH_USER']  = $auth[0] ?? null;
        $server['PHP_AUTH_PW']    = $auth[1] ?? null;
        // Merges additional headers for this request. See $withRequestHeaders.
        $server = array_merge($server, $this->withRequestHeaders);
        $this->withRequestHeaders = [];

        $requestMock = $this->getMockBuilder(Request::class)
                            ->setConstructorArgs([$query, $input, [], [], [], $server, null])
                            ->setMethods(array_merge($methods, ['path', 'ip']))
                            ->getMock();

        // Sets actual path and ip address expectation for request mock
        $requestMock->expects($this->any())
                    ->method('path')
                    ->willReturn($path ?: $params[1]);
        $requestMock->expects($this->any())
                    ->method('ip')
                    ->willReturn('1.1.1.1');

        // We need to do following just because of the way laravel's request resolution
        // works internally. Sets router resolver which returns a new Route instance
        // binded with this mocked request.
        $requestMock->setRouteResolver(function () use ($requestMock, $name, $params)
        {
            return (new IlluminateRoute($params[0], $params[1], ['as' => $name]))->bind($requestMock);
        });

        // Finally set the mocked request object as app instance
        $this->app->instance('request', $requestMock);

        return $requestMock;
    }
}
