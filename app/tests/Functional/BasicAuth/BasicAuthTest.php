<?php

namespace Tests\Functional\BasicAuth;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class BasicAuthTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        // $this->markTestIncomplete('Under construction');

        parent::setUp();

        //
        // load test data
        //
        $this->testData = include(__DIR__.'/helpers/BasicAuthData.php');

        $this->setupPrivateBasicAuthParams();
    }

    public function testAuthWithoutKeyOrPwd()
    {
        $this->setupBasicAuthParams('', '');

        $this->startTest();
    }

    public function testAuthWithoutPwd()
    {
        ;
    }

    public function testAuthWithoutKey()
    {
        ;
    }

    public function testAuthWithKeyAndPwd()
    {
        ;
    }

    public function testPublicAuth()
    {

    }

    public function testPrivateAuth()
    {
        ;
    }

    public function testAppAuth()
    {
        ;
    }

    public function testPublicAuthOnPrivateRoute()
    {
        ;
    }

    public function testPrivateAuthOnPublicRoute()
    {
        ;
    }

    public function testPrivateAuthOnAppRoute()
    {
        ;
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

    public function testAppRoutesWithAppAuth()
    {
        $this->markTestIncomplete();
        $this->setupAppBasicAuthParams();

        $internalRoutes = \Http\Route::getApiRouteInCategory('internal');

        foreach ($internalRoutes as $routeName => $routeInfo)
        {
            $testData['request']['method'] = $routeInfo[0];
            $testData['request']['url'] = $routeInfo[1];

            $this->startTest($testData);
        }
    }

    public function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }

    protected function fetchTransactionSuccess()
    {
        $transaction = $this->createEntity('transaction', ['merchant_id' => '363e4efa820b0c06208ccd99']);

        $request = array(
            'method' => 'GET',
            'url' => '/transactions');

        $txn = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('entity', $txn);
        $this->assertEquals($txn['entity'], 'transaction');
    }
}
