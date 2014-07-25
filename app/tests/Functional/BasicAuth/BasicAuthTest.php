<?php

namespace Tests\Functional\BasicAuth;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class BasicAuthTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->markTestIncomplete('Under construction');

        parent::setUp();

        //
        // load test data
        //
        //$this->testData = include(__DIR__.'/helpers/MerchantData.php');
    }

    protected function setupBasicAuthParams()
    {
        $this->auth = array(
               'PHP_AUTH_USER' => 'd9c6bf091a1a64cb5678d8c1',
               'PHP_AUTH_PW' => 'thisissupersecret');
    }

    public function testAuthWithoutKeyOrPwd()
    {
        ;
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

}
