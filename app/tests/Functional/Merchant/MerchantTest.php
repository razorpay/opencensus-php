<?php

namespace Tests\Functional\Merchant;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class MerchantTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantData.php';

        parent::setUp();

        $this->setupAppBasicAuthParams();
    }

    /**
     * @group merchant
     * @return array Data return from merchant creation
     */
    public function testCreateMerchant()
    {
        $content = $this->startTest();
    }

    public function testGetMerchant()
    {
        $this->createMerchant();

        $this->setupAppBasicAuthParams('rzp_test');
        $this->startTest();

        $this->setupAppBasicAuthParams('rzp_live');
        $this->startTest();
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
        $data = $this->testData[__FUNCTION__];

        $content = $this->startTest();

        $expired = time() + 10;

        $this->assertGreaterThan($expired, $content['old']['expired_at']);
    }

    public function testUpdateKeyTwice()
    {
        $data = $this->testData[__FUNCTION__];

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
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        return $this->runRequestResponseFlow($testData);
    }

    protected function createMerchant()
    {
        $merchant = array(
                'id'    => '41ce4abda390575910cba897',
                'name'  => 'Tester',
                'email' => 'liveAndTest@localhost.com'
            );

        $request = array(
            'content' => $merchant,
            'url' => '/merchants',
            'method' => 'POST'
        );

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals($merchant, $content);

        return $content;
    }
}
