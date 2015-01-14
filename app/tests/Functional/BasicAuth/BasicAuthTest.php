<?php

namespace Tests\Functional\BasicAuth;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class BasicAuthTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/BasicAuthData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testAuthWithoutKeyOrPwd()
    {
        $this->ba->basicAuth('', '');

        $this->startTest();
    }

    // This also checks the effect of providing secret on
    // public route
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

    public function testPublicAuthOnPrivateRoute()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testPublicAuthWithWrongKeyId()
    {
        $this->ba->publicAuth('abcdefgh820b0c06208ccd99');

        $this->startTest();
    }

    public function testPrivateAuthWithWrongKeyId()
    {
        $this->ba->publicAuth('abcdefgh820b0c06208ccd99');

        $this->startTest();
    }

    public function testPrivateAuthWithWrongSecret()
    {
        $this->ba->privateAuth(null, 'somerandomsecre');

        $this->startTest();
    }

    public function testPublicAuthOnAppRoute()
    {
        $this->ba->publicAuth();

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

        $internalRoutes = \Http\Route::getApiRouteInCategory('internal');

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

        $internalRoutes = \Http\Route::getApiRouteInCategory('internal');

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

    public function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }

    protected function fetchPaymentSuccess()
    {
        $payment = $this->fixtures->createEntity('payment', ['merchant_id' => '10000000000000']);

        $request = array(
            'method' => 'GET',
            'url' => '/payments');

        $payment = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('entity', $payment);
        $this->assertEquals($payment['entity'], 'payment');
    }
}
