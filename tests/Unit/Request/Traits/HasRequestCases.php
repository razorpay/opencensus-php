<?php

namespace RZP\Tests\Unit\Request\Traits;

use Illuminate\Http\Request;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\User;

/**
 * Contains a list of various request cases and corresponding
 * methods to mock each case behavior for various unit tests.
 */
trait HasRequestCases
{
    use MocksRequest;

    public static $testKey         = 'rzp_test_TheTestAuthKey';
    public static $liveKey         = 'rzp_live_TheLiveAuthKey';
    public static $testSecret      = 'TheKeySecretForTests';
    public static $liveSecret      = 'TheKeySecretForTestsLive';
    public static $testMidKey      = 'rzp_test_10000000000000';
    public static $testUserId      = User::MERCHANT_USER_ID;
    public static $testOrgId       = Org::RZP_ORG_SIGNED;
    public static $testAdminToken  = Org::DEFAULT_TOKEN . Org::DEFAULT_TOKEN_PRINCIPAL;
    public static $testHostname    = 'dashboard.razorpay.in';
    public static $testAdminEmail  = 'test@test.com';
    public static $testDeviceToken = 'authentication_token';

    public static $requestCases = [
        'publicRouteWhenKeyInHeaders',
        'publicRouteWhenKeyInQuery',
        'publicRouteWhenKeyInInput',
        'publicRouteWhenKeyIsOfInvalidLen',

        'publicCallbackRoute',

        'publicRouteWithOAuthPublicToken',


        'privateRoute',
        'privateRouteWhenLiveMode',
        'privateRouteWhenInvalidKey',
        'privateRouteWithOAuthBearerToken',
        'privateRouteWithProxyAuth',

        'proxyRoute',

        'privilegeRouteWhenInternalAppAuth',
        'privilegeRouteWhenAdminAuth',

        'directRoute',

        'deviceRoute',
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

        $requestMock->expects($this->any())
                    ->method('getUser')
                    ->willReturn(self::$testKey);

        return $requestMock;
    }

    protected function mockPublicRouteWhenKeyInQuery(
        string $name = 'invoice_get_status',
        string $path = 'invoices/inv_1000000invoice/status'): Request
    {
        $requestMock = $this->mockRouteRequest($name, $path, [], ['key_id' => 'rzp_test_TheTestAuthKey']);

        return $requestMock;
    }

    protected function mockPublicRouteWhenKeyInInput(
        string $name = 'payment_create',
        string $path = 'payments'): Request
    {
        $requestMock = $this->mockRouteRequest($name, $path, [], [], ['key_id' => 'rzp_test_TheTestAuthKey']);

        return $requestMock;
    }

    protected function mockPublicRouteWhenKeyIsOfInvalidLen(
        string $name = 'invoice_get_status',
        string $path = 'invoices/inv_1000000invoice/status'): Request
    {
        $requestMock = $this->mockRouteRequest($name, $path, [], ['key_id' => 'rzp_test_INVALID_LEN']);

        return $requestMock;
    }

    protected function mockPublicCallbackRoute(
        string $name = 'payment_callback_with_key_get',
        string $path = 'payments/pay_10000000000000/callback/hash/rzp_test_TheTestAuthKey'): Request
    {
        $requestMock = $this->mockRouteRequest($name, $path);

        return $requestMock;
    }

    protected function mockPublicRouteWithOAuthPublicToken(
        string $name = '',
        string $path = ''): Request
    {
        // TODO
    }

    protected function mockPrivateRoute(
        string $name = 'invoice_fetch_multiple',
        string $path = 'invoices'): Request
    {
        $requestMock = $this->mockRouteRequest($name, $path, ['getUser', 'getPassword']);

        $requestMock->expects($this->any())
                    ->method('getUser')
                    ->willReturn(self::$testKey);
        $requestMock->expects($this->any())
                    ->method('getPassword')
                    ->willReturn(self::$testSecret);

        return $requestMock;
    }

    protected function mockPrivateRouteWhenLiveMode(
        string $name = 'invoice_fetch_multiple',
        string $path = 'invoices'): Request
    {
        $requestMock = $this->mockRouteRequest($name, $path, ['getUser', 'getPassword']);

        $requestMock->expects($this->any())
                    ->method('getUser')
                    ->willReturn(self::$liveKey);
        $requestMock->expects($this->any())
                    ->method('getPassword')
                    ->willReturn(self::$liveSecret);

        return $requestMock;
    }

    protected function mockPrivateRouteWhenInvalidKey(
        string $name = 'invoice_fetch_multiple',
        string $path = 'invoices'): Request
    {
        $requestMock = $this->mockRouteRequest($name, $path, ['getUser', 'getPassword']);

        $requestMock->expects($this->any())
                    ->method('getUser')
                    ->willReturn('invalidkey');
        $requestMock->expects($this->any())
                    ->method('getPassword')
                    ->willReturn(self::$testSecret);

        return $requestMock;
    }

    protected function mockPrivateRouteWithOAuthBearerToken(
        string $name = '',
        string $path = ''): Request
    {
        // TODO
    }

    protected function mockPrivateRouteWithProxyAuth(
        string $name = 'invoice_create',
        string $path = 'invoices'): Request
    {
        return $this->mockProxyRoute($name, $path);
    }

    protected function mockProxyRoute(
        string $name = 'batch_create',
        string $path = 'batches'): Request
    {
        $server = [
            'HTTP_X-Dashboard-User-Id'   => self::$testUserId,
            // TODO: What is role of default user created in fixtures?
            'HTTP_X-Dashboard-User-Role' => null,
        ];

        $requestMock = $this->mockRouteRequest($name, $path, ['getUser', 'getPassword'], [], [], $server);

        $requestMock->expects($this->any())
                    ->method('getUser')
                    ->willReturn(self::$testMidKey);
        $requestMock->expects($this->any())
                    ->method('getPassword')
                    ->willReturn(\Config::get('applications.dashboard.secret'));

        return $requestMock;
    }

    protected function mockPrivilegeRouteWhenInternalAppAuth(
        string $name = 'invoice_expire_bulk',
        string $path = 'invoices/expire'): Request
    {
        $requestMock = $this->mockRouteRequest($name, $path, ['getUser', 'getPassword']);

        $requestMock->expects($this->any())
                    ->method('getUser')
                    ->willReturn('rzp_test');
        $requestMock->expects($this->any())
                    ->method('getPassword')
                    ->willReturn(\Config::get('applications.dashboard.secret'));

        return $requestMock;
    }

    protected function mockPrivilegeRouteWhenAdminAuth(
        string $name = 'dummy_route',
        string $path = 'dummy/route'): Request
    {
        $server = [
            'HTTP_X-Org-Id'                => self::$testOrgId,
            'HTTP_X-Admin-Token'           => self::$testAdminToken,
            'HTTP_X-Org-Hostname'          => self::$testHostname,
            'HTTP_X-Dashboard-Admin-Email' => self::$testAdminEmail,
        ];

        $requestMock = $this->mockRouteRequest($name, $path, ['getUser', 'getPassword'], [], [], $server);

        $requestMock->expects($this->any())
                    ->method('getUser')
                    ->willReturn('rzp_test');
        $requestMock->expects($this->any())
                    ->method('getPassword')
                    ->willReturn(\Config::get('applications.dashboard.secret'));

        return $requestMock;
    }

    protected function mockDirectRoute(
        string $name = 'checkout_public',
        string $path = 'checkout/public'): Request
    {
        return $this->mockRouteRequest($name, $path);
    }

    protected function mockDeviceRoute(
        string $name = 'vpa_create',
        string $path = 'upi/vpa'): Request
    {
        $requestMock = $this->mockRouteRequest($name, $path, ['getUser', 'getPassword']);

        $requestMock->expects($this->any())
                    ->method('getUser')
                    ->willReturn(self::$testKey);
        $requestMock->expects($this->any())
                    ->method('getPassword')
                    ->willReturn(self::$testDeviceToken);

        return $requestMock;
    }
}
