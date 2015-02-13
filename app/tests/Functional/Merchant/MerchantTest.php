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

        $this->ba->appAuth();
    }

    public function testCreateKey()
    {
        $this->createMerchant();

        $this->startTest();
    }

    public function testGetMerchant()
    {
        $this->createMerchant();

        $this->ba->appAuthTest();
        $this->startTest();

        $this->ba->appAuthLive();
        $this->startTest();
    }

    public function testGetBalance()
    {
        // The merchant and balances have been created in
        // fixtures already
        $this->ba->appAuthTest();
        $this->startTest();

        $this->ba->appAuthLive();
        $this->testData[__FUNCTION__]['response']['content']['balance'] = 0;
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

    public function testRollDemoKey()
    {
        $this->createMerchant();

        $this->fixtures->create(
            'key',
            ['merchant_id' => '1cXSLlUU8V9sXl',
             'id' => '1DP5mmOlF5G5ag']);

        $this->startTest();
    }

    public function testActivateMerhantWithoutBankAccount()
    {
        $this->ba->appAuthLive();

        $this->startTest();
    }

    public function testActivateMerchant()
    {
        $this->ba->appAuthLive();

        $ba = $this->fixtures
                   ->on('live')
                   ->create(
                        'merchant:bank_account',
                        ['merchant_id' => '1cXSLlUU8V9sXl']);

        $this->startTest();

        $testData = $this->testData['testGetBalance'];
        $testData['request']['url'] = '/merchants/1cXSLlUU8V9sXl/balance';
        $testData['response']['content']['balance'] = 0;
    }

    public function testMerchantEnableLive()
    {
        $this->testMerchantDisableLive();
        $this->startTest();
    }

    public function testMerchantDisableLive()
    {
        $this->testActivateMerchant();
        $this->startTest();
    }

    public function testAddBankAccount()
    {
        $this->startTest();
    }

    public function testGetBankAccount()
    {
//        $this->markTestSkipped();
        $this->testAddBankAccount();

        $content = $this->startTest();
    }

    public function testSetBanks()
    {
        $this->ba->appAuth();

        $content = $this->startTest();
    }

    public function testSetEmptyBanks()
    {
        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertSame([], $content['enabled']);
    }

    public function testGetBanksByMerchantAuth()
    {
        $this->ba->publicTestAuth();

        $this->startTest();

        $this->fixtures->links['merchant']->activate('10000000000000');

        $this->ba->publicLiveAuth();

        $this->startTest();
    }

    public function testGetBanksByAppAuth()
    {
        $this->testSetBanks();

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testGetKeySecret()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    protected function startTest()
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
