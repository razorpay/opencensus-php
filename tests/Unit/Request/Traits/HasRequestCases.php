<?php

namespace RZP\Tests\Unit\Request\Traits;

use Illuminate\Http\Request;

/**
 * Contains a list of various request cases and corresponding
 * methods to mock each case behavior for various unit tests.
 */
trait HasRequestCases
{
    use MocksRequest;

    public static $testKey    = 'rzp_test_TheTestAuthKey';
    public static $testSecret = 'TheKeySecretForTests';

    public static $requestCases = [
        'publicRouteWhenKeyInHeaders',

        'privateRoute',
    ];

    protected function invokeRequestCase(string $case, ...$args)
    {
        $func = 'mock' . ucfirst($case);
        return $this->$func(...$args);
    }

    protected function mockPublicRouteWhenKeyInHeaders(
        string $name = 'invoice_get_status',
        string $path = 'invoices/inv_1000000invoice/status'): Request
    {
        $requestMock = $this->mockRouteRequest($name, $path, ['getUser']);

        $requestMock->expects($this->atLeastOnce())
                    ->method('getUser')
                    ->willReturn(self::$testKey);

        return $requestMock;
    }

    protected function mockPrivateRoute(
        string $name = 'invoice_fetch_multiple',
        string $path = 'invoices'): Request
    {
        $requestMock = $this->mockRouteRequest($name, $path, ['getUser', 'getPassword']);

        $requestMock->expects($this->atLeastOnce())
                    ->method('getUser')
                    ->willReturn(self::$testKey);
        $requestMock->expects($this->atLeastOnce())
                    ->method('getPassword')
                    ->willReturn(self::$testSecret);

        return $requestMock;
    }
}
