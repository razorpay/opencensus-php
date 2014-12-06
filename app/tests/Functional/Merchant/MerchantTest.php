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

    public function testCreateKey()
    {
        $this->createMerchant();

        $this->startTest();
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
        $content = $this->makeRequestAndGetContent($data['request']);

        $expired = time() + 1;

        $this->assertLessThan($expired, $content['old']['expired_at']);

        //
        // Update the same key second time
        //
        $content = $this->startTest();
    }

    /**
     * Checks that new key id generated is not
     * associated with time like other entity ids
     * The way to do this is to take a normal generated id
     * and compare it with new key generated. Comparison is
     * done for first few letters. If it's time dependant,
     * then those will be same
     */
    public function testNewKeyIdRandom()
    {
        $content = $this->startTest();

        $id = $this->fixtures->generateUniqueId();
        $newKeyId = $content['new']['id'];
        // strip prefix
        $newKeyId = substr($newKeyId, 9);

        $str1 = substr($id, 0, 3);
        $str2 = substr($newKeyId, 0, 3);

        $this->assertNotEquals($str1, $str2);
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
                'id'    => '1X4hRFHFx4UiXt',
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
