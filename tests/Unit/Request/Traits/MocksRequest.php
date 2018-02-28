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
     * @param  string      $name Name of the route (Ref Route.php)
     * @param  string|null $path Actual url to be accessed(without placeholders)
     * @return Request
     */
    protected function mockRouteRequest(
        string $name,
        string $path = null,
        array $methods = []): Request
    {
        $params = Route::getApiRoute($name);

        $requestMock = $this->mockRequest(array_merge($methods, ['path', 'ip']));
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
     * @param  array  $withMethods
     * @return Request
     */
    protected function mockRequest(array $withMethods = []): Request
    {
        $requestMock = $this->getMockBuilder(Request::class)
                            ->setMethods($withMethods)
                            ->getMock();
        $this->app->instance('request', $requestMock);

        return $requestMock;
    }
}
