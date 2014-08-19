<?php

namespace Tests\Functional\BasicAuth;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class BasicAuthTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $appRoutes = array(
        ['POST', '/merchants'],
        ['GET',  '/merchants/363e4efa820b0c06208ccd99/keys'],
        ['PUT',  '/merchants/363e4efa820b0c06208ccd99/keys/363e4efa820b0c06208ccd99'],
        ['POST', '/merchants/363e4efa820b0c06208ccd99/pricing'],
        ['GET',  '/merchants/363e4efa820b0c06208ccd99/pricing'],
        ['POST', '/merchants/363e4efa820b0c06208ccd99/terminal'],
        ['GET',  '/merchants/363e4efa820b0c06208ccd99/terminal'],
        ['POST', '/merchants/363e4efa820b0c06208ccd99/activate'],
        ['POST', '/pricing'],
        ['GET',  '/pricing'],
        ['GET',  '/pricing/merchants'],
        ['GET',  '/pricing/gateways'],
        ['POST', '/pricing/501f6ad55b9a845fe509e09e/rule'],
        ['GET',  '/pricing/501f6ad55b9a845fe509e09e'],
        ['GET',  '/pricing/501f6ad55b9a845fe509e09e/rule/c5484d12aafacc2023608c79'],
        );

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

        foreach ($this->appRoutes as $route)
        {
            $testData['request']['method'] = $route[0];
            $testData['request']['url'] = $route[1];

            $this->startTest($testData);
        }
    }

    public function testAppRoutesWithAppAuth()
    {$this->markTestIncomplete();
        $this->setupAppBasicAuthParams();

        foreach ($this->appRoutes as $route)
        {
            $testData['request']['method'] = $route[0];
            $testData['request']['url'] = $route[1];

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
