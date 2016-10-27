<?php

namespace RZP\Tests\Functional\BasicAuth;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class BasicAuthTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/BasicAuthData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testNoAuth()
    {
        $this->ba->noAuth();

        $this->startTest();

        $this->assertEquals('Basic realm="Razorpay"', $this->response->headers->get('WWW-Authenticate'));
    }

    public function testNoAuthOnJsonpRoute()
    {
        $this->ba->noAuth();

        $this->startTest();

        $this->assertEquals('Basic realm="Razorpay"', $this->response->headers->get('WWW-Authenticate'));
    }

    public function testWrongKeyOnPublicJsonpRoute()
    {
        $this->ba->publicAuth('rzp_test_TheTstWrongKey');

        $this->startTest();
    }

    /**
     * This also checks the effect of providing secret on
     * public route
     */
    public function testPrivateAuthOnPublicRoute()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testUnauthorizedOnJsonpRoute()
    {
        $this->ba->publicAuth('rzp_test_TheTestAusdKey');

        $this->startTest();
    }

    public function testNoSecretOnPrivateRoute()
    {
        $this->ba->privateAuth(null, '');

        $this->startTest();
    }

    public function testPublicAuthWithWrongKeyId()
    {
        $this->ba->publicAuth('abcdefgh820b0c06208ccd99');

        $this->startTest();
    }

    public function testPrivateAuthWithWrongKeyId()
    {
        $this->ba->privateAuth('abcdefgh820b0c06208ccd99');

        $this->startTest();
    }

    public function testPrivateAuthWithWrongSecret()
    {
        $this->ba->privateAuth(null, 'somerandomsecre');

        $this->startTest();
    }

    public function testAppAuthWithNoSecret()
    {
        $this->ba->appAuth('rzp_test', null);

        $this->startTest();
    }

    public function testPrivateAuthOnAppRoute()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testProxyAuthOnPrivateRouteInCloud()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testProxyAuthOnPrivateRouteNotInCloud()
    {
        $this->ba->proxyAuth();

        $this->cloud = false;

        $this->startTest();
    }

    public function testBasicAuthRealm()
    {
        ;
    }

    public function testAppRoutesWithPrivateAuth()
    {
        $this->ba->privateAuth();

        $internalRoutes = $this->app['api.route']->getApiRouteInCategory('internal');

        foreach ($internalRoutes as $routeName => $routeInfo)
        {
            $testData['request']['method'] = $routeInfo[0];
            $testData['request']['url'] = $routeInfo[1];

            $this->startTest($testData);
        }
    }

    public function testAppRoutesWithInvalidPrivateAuth()
    {
        $this->ba->privateAuth(null, '=');

        $internalRoutes = $this->app['api.route']->getApiRouteInCategory('internal');

        foreach ($internalRoutes as $routeName => $routeInfo)
        {
            $testData['request']['method'] = $routeInfo[0];
            $testData['request']['url'] = $routeInfo[1];

            $this->startTest($testData);
        }
    }

    public function testInvalidMerchantKeyForAppRouteAndNotExistentRoute()
    {
        ;
    }

    public function testValidMerchantKeyForAppRouteAndNonExistentRoute()
    {
        ;
    }

    public function testPublicQueryAuth()
    {
        $this->markTestIncomplete();

        $request = [
            'url' => '/payments/create/jsonp?keyid=rzp_test_TheTestAuthKey',
            'method' => 'GET',
        ];

        $this->ba->noAuth();

        $content = $this->makeRequestAndGetContent($request);
    }

    public function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }
}
