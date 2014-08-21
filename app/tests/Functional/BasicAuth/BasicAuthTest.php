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

    public function testBasicAuthRealm()
    {
        ;
    }

    public function testAppRoutesWithPrivateAuth()
    {
        $this->setupBasicAuthParams();

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
}
