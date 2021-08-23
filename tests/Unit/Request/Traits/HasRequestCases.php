<?php

namespace RZP\Tests\Unit\Request\Traits;

use Illuminate\Http\Request;

use RZP\Http\RequestContext;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\User;

/**
 * Contains a list of various request cases and corresponding
 * methods to mock each case behavior for various unit tests.
 */
trait HasRequestCases
{
    use MocksRequest;

    // Some static hard coded keys and configs for tests

    public static $testKey              = 'rzp_test_TheTestAuthKey';
    public static $liveKey              = 'rzp_live_TheLiveAuthKey';
    public static $testSecret           = 'TheKeySecretForTests';
    public static $liveSecret           = 'TheKeySecretForTestsLive';
    public static $testMidKey           = 'rzp_test_10000000000000';
    public static $testUserId           = User::MERCHANT_USER_ID;
    public static $testOrgId            = Org::RZP_ORG_SIGNED;
    public static $testAdminToken       = Org::DEFAULT_TOKEN . Org::DEFAULT_TOKEN_PRINCIPAL;
    public static $testHostname         = 'dashboard.razorpay.in';
    public static $testAdminEmail       = 'test@test.com';
    public static $testDeviceToken      = 'authentication_token';
    public static $testOauthPublicToken = 'rzp_test_oauth_100OAuthPublic';
    public static $testPartnerKey       = 'rzp_test_partner_TheTestAuthKey';
    public static $testPartnerSecret    = 'TheKeySecretForTests';

    /**
     * Invokes mocker method for given request case.
     * @param  string $case
     * @param  mixed  $args,...
     * @return Request
     */
    protected function invokeRequestCase(string $case, ...$args): Request
    {
        $func = 'mock' . ucfirst($case);
        return $this->$func(...$args);
    }

    /**
     * Invokes mocker method for given request case. Additionally, binds new request context object.
     * @param  string $case
     * @param  mixed  $args,...
     * @return Request
     */
    protected function invokeRequestCaseAndBindNewContext(string $case, ...$args): Request
    {
        $mock = $this->invokeRequestCase($case, ...$args);

        $requestContext = new RequestContext($this->app);
        $requestContext->init();
        $this->app->instance('request.ctx', $requestContext);

        return $mock;
    }

    protected function mockPublicRouteWithKeyInHeaders(
        string $name = 'invoice_get_status',
        string $path = 'invoices/inv_1000000invoice/status'): Request
    {
        return $this->mockRouteRequest($name, $path, [], [self::$testKey]);
    }

    protected function mockPublicRouteWithKeyInQuery(
        string $name = 'invoice_get_status',
        string $path = 'invoices/inv_1000000invoice/status'): Request
    {
        return $this->mockRouteRequest($name, $path, [], [], ['key_id' => self::$testKey]);
    }

    protected function mockPublicRouteWithKeyInInput(
        string $name = 'payment_create',
        string $path = 'payments'): Request
    {
        return $this->mockRouteRequest($name, $path, [], [], [], ['key_id' => self::$testKey]);
    }

    protected function mockPublicRouteWithInvalidKeyLength(
        string $name = 'invoice_get_status',
        string $path = 'invoices/inv_1000000invoice/status'): Request
    {
        return $this->mockRouteRequest($name, $path, [], [], ['key_id' => 'rzp_test_INVALID_LEN']);
    }

    protected function mockPublicCallbackRoute(
        string $name = 'payment_callback_with_key_get',
        string $path = 'payments/pay_10000000000000/callback/hash/rzp_test_TheTestAuthKey'): Request
    {
        return $this->mockRouteRequest($name, $path);
    }

    protected function mockPublicRouteWithOAuthPublicToken(
        string $name = 'invoice_get_status',
        string $path = 'invoices/inv_1000000invoice/status'): Request
    {
        return $this->mockRouteRequest($name, $path, [], [self::$testOauthPublicToken]);
    }

    protected function mockPublicCallbackRouteWithOAuthPublicToken(
        string $name = 'payment_callback_with_key_get',
        string $path = 'payments/pay_10000000000000/callback/hash/rzp_test_oauth_100OAuthPublic'): Request
    {
        return $this->mockRouteRequest($name, $path);
    }

    protected function mockPrivateRoute(
        string $name = 'invoice_fetch_multiple',
        string $path = 'invoices'): Request
    {
        return $this->mockRouteRequest($name, $path, [], [self::$testKey, self::$testSecret]);
    }

    protected function mockPrivateRouteWithLiveMode(
        string $name = 'invoice_fetch_multiple',
        string $path = 'invoices'): Request
    {
        return $this->mockRouteRequest($name, $path, [], [self::$liveKey, self::$liveSecret]);
    }

    protected function mockPrivateRouteWithInvalidKey(
        string $name = 'invoice_fetch_multiple',
        string $path = 'invoices'): Request
    {
        return $this->mockRouteRequest($name, $path, [], ['invalidkey', self::$testSecret]);
    }

    protected function mockPrivateRouteWithInvalidSecret(
        string $name = 'invoice_fetch_multiple',
        string $path = 'invoices'): Request
    {
        return $this->mockRouteRequest($name, $path, [], [self::$testKey, 'invalidsecret']);
    }

    protected function mockPrivateRouteWithOAuthBearerToken(
        string $name = 'invoice_fetch_multiple',
        string $path = 'invoices'): Request
    {
        $token = file_get_contents(__DIR__ . '/../Helpers/test_oauth_bearer_token.txt');

        return $this->mockRouteRequest($name, $path, [], [], [], [], ['HTTP_Authorization' => 'Bearer ' . $token]);
    }

    protected function mockPrivateRouteWithPartnerAuthToken(
        string $name = 'invoice_fetch_multiple',
        string $path = 'invoices'): Request
    {
        return $this->mockRouteRequest($name, $path, [], [self::$testPartnerKey, self::$testPartnerSecret], [], [], ['X-Razorpay-Account' => 'acc_100000Razorpay']);
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
        $secret = \Config::get('applications.merchant_dashboard.secret');
        $server = [
            'HTTP_X-Dashboard-User-Id'   => self::$testUserId,
            // TODO: What is role of default user created in fixtures?
            'HTTP_X-Dashboard-User-Role' => null,
        ];

        return $this->mockRouteRequest($name, $path, [], [self::$testMidKey, $secret], [], [], $server);
    }

    protected function mockPrivilegeRouteWithInternalAppAuth(
        string $name = 'invoice_expire_bulk',
        string $path = 'invoices/expire'): Request
    {
        $secret = \Config::get('applications.merchant_dashboard.secret');
        return $this->mockRouteRequest($name, $path, [], ['rzp_test', $secret]);
    }

    protected function mockPrivilegeRouteWithAdminAuth(
        string $name = 'dummy_route',
        string $path = 'dummy/route'): Request
    {
        $secret = \Config::get('applications.admin_dashboard.secret');
        $server = [
            'HTTP_X-Org-Id'                => self::$testOrgId,
            'HTTP_X-Admin-Token'           => self::$testAdminToken,
            'HTTP_X-Org-Hostname'          => self::$testHostname,
            'HTTP_X-Dashboard-Admin-Email' => self::$testAdminEmail,
        ];

        return $this->mockRouteRequest($name, $path, [], ['rzp_test', $secret], [], [], $server);
    }

    protected function mockDirectRoute(
        string $name = 'checkout_public',
        string $path = 'checkout/public'): Request
    {
        return $this->mockRouteRequest($name, $path);
    }

    // protected function mockDeviceRoute(
    //     string $name = 'vpa_create',
    //     string $path = 'vpa/create'): Request
    // {
    //     return $this->mockRouteRequest($name, $path, [], [self::$testKey, self::$testDeviceToken]);
    // }
}
