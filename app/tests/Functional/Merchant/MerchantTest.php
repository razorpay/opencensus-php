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

    public function testCreateKeyForNonActivatedMerchant()
    {
        $this->createMerchant();

        $this->ba->appAuthLive();
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

    public function testMerchantFetchCardEnabled()
    {
        $merchants = $this->getEntities(
                'merchant', ['methods' => "{'card':true}"], true);

        $this->assertEquals($merchants['entity'], 'collection');
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

    public function testEditMerchant()
    {
        $this->createMerchant();

        $this->startTest();
    }

    public function testEditMerchantEmail()
    {
        $this->createMerchant();

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

        $activated_at = time();

        $content = $this->startTest();
        $this->assertLessThanOrEqual($content['activated_at'], $activated_at);

        $testData = $this->testData['testGetBalance'];
        $testData['request']['url'] = '/merchants/1cXSLlUU8V9sXl/balance';
        $testData['response']['content']['id'] = '1cXSLlUU8V9sXl';
        $testData['response']['content']['balance'] = 0;

        $this->runRequestResponseFlow($testData);
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

    public function testAttemptPaymentOnNonLiveMerchant()
    {
        $this->testMerchantDisableLive();

        $key = $this->fixtures->create('key', ['merchant_id' => '1cXSLlUU8V9sXl']);
        $key = $key->getKey();

        $this->ba->publicAuth('rzp_live_'.$key);

        $this->startTest();
    }

    public function testAddBankAccount()
    {
        $this->startTest();
    }

    public function testGetBankAccount()
    {
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

    public function testGetPaymentMethodsRoute()
    {
        $this->ba->publicLiveAuth();

        $this->fixtures->links['merchant']->activate('10000000000000');

        $attributes = array(
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'axis_genius',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay axis_genius',
            'gateway_terminal_id'       => 'nodal account axis_genius',
            'gateway_terminal_password' => 'razorpay_password',
        );

        $terminal = $this->fixtures->on('live')->create('terminal', $attributes);

        $content = $this->startTest();
    }

    public function testGetCheckoutRoute()
    {
        $this->ba->publicLiveAuth();

        $this->fixtures->links['merchant']->activate('10000000000000');

        $request = array(
            'url' => '/checkout',
            'method' => 'get',
            'content' => [],
        );

        $response = $this->makeRequest($request);

        $headers = $response->headers->all();
        $this->assertArrayNotHasKey('x-frame-options', $headers);
    }

    public function testGetCheckoutRouteWithWrongKey()
    {
        $this->ba->publicLiveAuth('random');

        $this->fixtures->links['merchant']->activate('10000000000000');

        $request = array(
            'url' => '/checkout',
            'method' => 'get',
            'content' => [],
        );

        $response = $this->makeRequest($request);

        $headers = $response->headers->all();
        $this->assertArrayNotHasKey('x-frame-options', $headers);
    }

    public function testPutPaytmMethod()
    {
        $this->ba->appAuth();

        $content = $this->startTest();
    }

    public function testGetKeySecret()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testGetMercantBeneficiaryFile()
    {
        $this->ba->appAuth();

        $request = array(
            'url' => '/merchants/beneficiary/file',
            'method' => 'get',
            'content' => [],
        );

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('url', $content);
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
                'name'  => 'Tester 2',
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
