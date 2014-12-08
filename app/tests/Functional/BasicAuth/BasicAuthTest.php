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

        $this->setupPrivateBasicAuthParams();
    }

    public function testAuthWithoutKeyOrPwd()
    {
        $this->setupBasicAuthParams('', '');

        $this->startTest();
    }

    // This also checks the effect of providing secret on
    // public route
    public function testPrivateAuthOnPublicRoute()
    {
        $this->setupPrivateBasicAuthParams();

        $this->startTest();
    }

    public function testPublicAuthOnPrivateRoute()
    {
        $this->setupPublicBasicAuthParams();

        $this->startTest();
    }

    public function testPublicAuthWithWrongKeyId()
    {
        $this->setupPublicBasicAuthParams('abcdefgh820b0c06208ccd99');

        $this->startTest();
    }

    public function testPrivateAuthWithWrongKeyId()
    {
        $this->setupPublicBasicAuthParams('abcdefgh820b0c06208ccd99');

        $this->startTest();
    }

    public function testPrivateAuthWithWrongSecret()
    {
        $this->setupPrivateBasicAuthParams(null, 'somerandomsecre');

        $this->startTest();
    }

    public function testPublicAuthOnAppRoute()
    {
        $this->setupPublicBasicAuthParams();

        $this->startTest();
    }

    public function testPrivateAuthOnAppRoute()
    {
        $this->setupPrivateBasicAuthParams();

        $this->startTest();
    }

    public function testProxyAuthOnPrivateRouteInCloud()
    {
        $this->setupProxyBasicAuthParams();

        $this->startTest();
    }

    public function testProxyAuthOnPrivateRouteNotInCloud()
    {
        $this->setupProxyBasicAuthParams();

        $this->cloud = false;

        $this->startTest();
    }

    public function testBasicAuthRealm()
    {
        ;
    }

    public function testAppRoutesWithPrivateAuth()
    {
        $this->setupPrivateBasicAuthParams();

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
        $this->setupPrivateBasicAuthParams(null, '=');

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
