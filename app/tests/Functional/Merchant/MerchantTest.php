<?php

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class MerchantTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $merchantId = null;

    protected $keyId = null;

    protected $keySecret = null;

    public function setUp()
    {
        parent::setUp();

        $this->setupAppBasicAuthParams(1);

        //
        // load test data
        //
        $this->testData = include(__DIR__.'/helpers/MerchantData.php');
    }

    /**
     */
    protected function setupAppBasicAuthParams($user, $pwd = 'DASHBOARD_AUTH_PASS')
    {
        // Auth
        $_SERVER['PHP_AUTH_USER'] = $user;
        $_SERVER['PHP_AUTH_PW'] = $pwd;
    }

    protected function setupMerchantAsBaiscAuthUser($merchantId)
    {
        $_SERVER['PHP_AUTH_USER'] = $merchantId;
    }

    protected function setupMerchantSecretAsBasicAuthSecret($secret)
    {
        $_SERVER['PHP_AUTH_PW'] = $secret;
    }

    /**
     * @group merchant
     * @return array Data return from merchant creation
     */
    public function testCreateMerchant()
    {
        $this->setupAppBasicAuthParams(1000);

        $content = $this->startTest();

        $this->merchantId = $content['id'];

        $this->keyId = $content['key']['id'];

        $this->keySecret = $content['key']['secret'];
    }

    public function testMerchantFetchKeys()
    {
        $this->startTest();
    }

    /**
     * Updates a key
     */
    public function testUpdateKeyExpireNow()
    {
        $content = $this->startTest();

        $expired = time() + 1;

        $this->assertLessThan($expired, $content['old']['expired_at']);
    }

    public function testUpdateKeyExpireInFuture()
    {
        $key = $this->getTestDataIndexKey();

        $data = $this->testData[$key];

        $content = $this->startTest();

        $expired = time() + 10;

        $this->assertGreaterThan($expired, $content['old']['expired_at']);
    }

    public function testUpdateKeyTwice()
    {
        $key = $this->getTestDataIndexKey();

        $data = $this->testData[$key];

        //
        // Update key once
        //
        $response = $this->makeRequest($data['request']);
        $content = json_decode($response->getContent(), true);

        $expired = time() + 1;

        $this->assertLessThan($expired, $content['old']['expired_at']);

        //
        // Update the same key second time
        //
        $content = $this->startTest();
    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $name = lcfirst(substr($func, 4));

        $testData = $this->testData[$name];

        //$this->replaceDefualtValues($testData['request']['content']);

        return $this->runRequestResponseFlow($testData);
    }

    protected function getTestDataIndexKey()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $name = lcfirst(substr($func, 4));

        return $name;
    }
}
