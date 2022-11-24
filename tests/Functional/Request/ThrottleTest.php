<?php

namespace RZP\Tests\Functional\Request;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Facades\Trace;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsThrottle;
use RZP\Http\Throttle\Constant as K;
use Redis;
use RZP\Tests\Functional\RequestResponseFlowTrait;

/**
 * A few end to end functional test to assert rate limiting is working fine.
 */
class ThrottleTest extends TestCase
{
    use TestsThrottle { setUp as baseSetUp; }
    use RequestResponseFlowTrait;

    const API_PRODUCTION_HOST = 'api.razorpay.com';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/ThrottleTestData.php';

        $this->baseSetUp();

        $this->ba->privateAuth();
    }

    public function testNonexistentRoute()
    {
        $this->mockTraceAndExpectNoError();

        $this->startTest();
    }

    public function testFetchOrdersWhenNotThrottled()
    {
        $this->mockTraceAndExpectNoError();

        $this->startTest();
    }

    public function testGetOrderWhenThrottled()
    {
        $this->mockTraceAndExpectNoError();

        // Sets max bucket size for specific test mid and for private auth to 0 and expects request to be throttled.
        $this->setRedisIdLevelSettings('10000000000000', ['test:private:0:order_fetch:mbs' => 0]);

        $this->startTest();
    }

    public function testGetOrderWhenThrottledWithoutMockForSpecificMerchant()
    {
        // Makes global settings mock to true, Also makes max bucket size as 0 for making
        // all request throttle for test purposes
        $this->setRedisGlobalSettings([K::SKIP => 0, K::MOCK => 1, K::MAX_BUCKET_SIZE => 0]);

        // This first request would get throttled but will be mocked
        $this->startTest($this->testData[__FUNCTION__.'1']);

        // Now makes a specific route UN-mocked for specific merchant
        $this->setRedisIdLevelSettings('10000000000000', ['test:private:0:order_fetch:mock' => 0]);

        // This second request would get throttled for real
        $this->startTest($this->testData[__FUNCTION__.'2']);
    }

    /**
     * If redis setting is missing, no throttle happens and an alert is raised.
     */
    public function testGetOrderWhenRedisSettingsMissing()
    {
        $this->mockTraceAndExpectCriticalError(TraceCode::THROTTLE_SETTINGS_MISSING);

        $this->setRedisGlobalSettings([]);

        $this->startTest();
    }

//    TODO: commenting temporarily to escape this error- Cannot make static method Illuminate\Support\Facades\Facade::expects()
//          non static in class PHPUnit\Framework\MockObject\Api
//    public function testMigrateThrottleKeysFromRedisLabs()
//    {
//        $this->ba->adminAuth();
//
//        $redisMockThrottle = $this->getMockBuilder(Redis::class)->setMethods(['hmset', 'srem'])
//            ->getMock();
//
//        $configRedis = $this->getMockBuilder(Redis::class)->setMethods(['set'])
//            ->getMock();
//
//
//        $redisMockOld = $this->getMockBuilder(Redis::class)->setMethods(['smembers', 'hgetall', 'get'])
//            ->getMock();
//
//        Redis::shouldReceive('connection')
//            ->with('query_cache_redis')
//            ->andReturn($configRedis);
//
//        Redis::shouldReceive('connection')
//            ->with('throttle')
//            ->andReturn($redisMockThrottle);
//
//        Redis::shouldReceive('connection')
//            ->andReturn($redisMockOld);
//
//        $redisMockOld->method('smembers')
//            ->will($this->returnValue(array("MID1")));
//
//        $redisMockOld->method('hgetall')
//            ->will($this->returnValue(array("abc")));
//
//        $map = array(
//            array('throttle:{merchant}:MID1', array("abc")),
//            array('throttle:{merchant}:MID1', array("abc"))
//        );
//
//        $redisMockThrottle->method('hmset')->will($this->returnValueMap($map));
//
//
//        $this->makeRequestAndGetContent($this->testData['testMigrateThrottleKeysFromRedisLabs']['request']);
//    }

    public function testGetOrderWhenBlockedForTestMerchant()
    {
        // Case 1: Blocks GET /invoice route for test mid, so GET /orders should pass
        $this->setRedisIdLevelSettings('10000000000000', ['test:private:0:invoice_fetch_multiple:block' => 1]);
        $this->startTest($this->testData[__FUNCTION__.'1']);

        // Case 2: Blocks GET /orders route too for test mid, so GET /orders should error
        $this->setRedisIdLevelSettings('10000000000000', ['test:private:0:order_fetch:block' => 1]);
        $this->startTest($this->testData[__FUNCTION__.'2']);
    }

    public function testGetOrderWhenIPBlocked()
    {
        // Case 1: Test request ip is 10.0.123.123 and following request should NOT be blocked
        $this->setRedisGlobalSettings([K::BLOCKED_IPS => '10.0.123.124' . K::LIST_DELIMITER . '10.0.123.125']);
        $this->startTest($this->testData[__FUNCTION__ . 'Success']);

        // Case 2: Test request ip is 10.0.123.123 and following request should be blocked
        $this->setRedisGlobalSettings([K::BLOCKED_IPS => '10.0.123.123' . K::LIST_DELIMITER . '10.0.123.124']);
        $this->startTest($this->testData[__FUNCTION__ . 'Failure']);
    }

    public function testGetOrderWhenUABlocked()
    {
        // Case 1: User agent does not exists in blocked list, so should not be blocked
        $this->setRedisGlobalSettings([K::BLOCKED_USER_AGENTS => 'Razorpay UA' . K::LIST_DELIMITER . 'CurlBOT']);
        $this->startTest($this->testData[__FUNCTION__ . 'Success']);

        // Case 2: User agent does exists in blocked list, so should be blocked
        $this->setRedisGlobalSettings([K::BLOCKED_USER_AGENTS => 'Razorpay UA' . K::LIST_DELIMITER . 'CurlBOT']);
        $this->startTest($this->testData[__FUNCTION__ .  'Failure1']);

        // Case 2: User agent does exists in blocked list with partial prefixed match, so should be blocked
        $this->setRedisGlobalSettings([K::BLOCKED_USER_AGENTS => 'Razorpay UA' . K::LIST_DELIMITER . 'CurlBOT']);
        $this->startTest($this->testData[__FUNCTION__ .  'Failure2']);
    }

    /**
     * Checks that throttle settings can be set and get by api
     */
    public function testFetchThrottleSettings()
    {
        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($this->testData['testCreateThrottleSettings']['request']);

        // verify that custom settings are added in the set
        $members = $this->redis->smembers(K::CUSTOM_SETTINGS_SET);

        $this->assertArraySelectiveEquals(['{throttle:t}:i:10000000000000'], $members);

        $this->startTest();
    }

    /**
     * Checks that the created throttle settings can block and allow requests
     */
    public function testThrottleCreateSettings()
    {
        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'1']['request']);
        $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'2']['request']);

        $this->ba->privateAuth();

        $this->startTest($this->testData['testGetOrderWhenIPBlockedSuccess']);
        $this->startTest($this->testData['testGetOrderWhenBlockedForTestMerchant1']);
    }

    public function testThrottleConfigCreateMerchant()
    {
        $this->ba->adminAuth();

        // create config for order create
        $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'1']['request']);

        $expected = [
            'order_create' => [
                'request_count' => '120',
                'request_count_window' => '60',
            ]
        ];

        // fetch all config for merchant
        $this->assertArraySelectiveEquals(
            $expected,
            $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'Fetch']['request'])
        );

        // create config for payment_create
        $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'2']['request']);

        $expected['payment_create'] = [
            'request_count' => '100',
            'request_count_window' => '60',
        ];

        // fetch all config for merchant
        $this->assertArraySelectiveEquals(
            $expected,
            $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'Fetch']['request'])
        );

        // update order create request window
        $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'3']['request']);

        // fetch only order create config
        $request = $this->testData[__FUNCTION__.'Fetch']['request'];
        $request['url'] = '/throttle/config?merchant_id=10000000000000&&route=order_create';

         $this->assertArraySelectiveEquals(
            ['request_count' => '180',
            'request_count_window' => '60'],
            $this->makeRequestAndGetContent($request)
        );

         // delete order create config
        $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'Delete1']['request']);

        $expected = [
            'payment_create' => [
                'request_count' => '100',
                'request_count_window' => '60',
            ]
        ];

        // fetch all config
        $this->assertArraySelectiveEquals(
            $expected,
            $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'Fetch']['request'])
        );

        // delete all config for the merchant
        $request = $this->testData[__FUNCTION__.'Delete1']['request'];

        $request['url'] = '/throttle/config?merchant_id=10000000000000';

        $this->makeRequestAndGetContent($request);

        // fetch all config for the merchant
        $this->assertArraySelectiveEquals(
            [],
            $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'Fetch']['request'])
        );
    }

     public function testThrottleConfigCreateRoute()
    {
        $this->ba->adminAuth();

        // create config order create type merchant
        $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'1']['request']);
         $expected = [
            'order_create' => [
                'type'          => 'merchant',
                'request_count' => '120',
                'request_count_window' => '60',
            ]
        ];

        // fetch route config for order create
        $this->assertArraySelectiveEquals(
            $expected,
            $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'Fetch']['request'])
        );

        // create config order create type org
        $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'2']['request']);

        $expected['order_create']['type'] = 'org';

        // fetch route config for order create
        $this->assertArraySelectiveEquals(
            $expected,
            $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'Fetch']['request'])
        );

        $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'3']['request']);


        $expected['order_create']['type'] = 'ip';

        // fetch route config for order create
        $this->assertArraySelectiveEquals(
            $expected,
            $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'Fetch']['request'])
        );

        $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'4']['request']);

        $expected['order_create']['request_count'] = '180';

        // fetch route config for order create
        $this->assertArraySelectiveEquals(
            $expected,
            $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'Fetch']['request'])
        );

        // delete order create config
        $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'Delete1']['request']);

        // fetch route config for order create
        $this->assertArraySelectiveEquals(
            [],
            $this->makeRequestAndGetContent($this->testData[__FUNCTION__.'Fetch']['request'])
        );
    }

    public function testThrottleFetchAll()
    {
        $this->ba->adminAuth();

        // create config order create type merchant
        $this->makeRequestAndGetContent($this->testData['testThrottleConfigCreateRoute1']['request']);

        // create config for order create
        $this->makeRequestAndGetContent($this->testData['testThrottleConfigCreateMerchant1']['request']);

        // create config for payment create
        $this->makeRequestAndGetContent($this->testData['testThrottleConfigCreateMerchant2']['request']);

        $expected = [
            'merchants' => [
                '10000000000000' => [
                    'order_create' => [
                        'request_count' => '120',
                        'request_count_window' => '60',
                    ],
                    'payment_create' => [
                        'request_count' => '100',
                        'request_count_window' => '60',
                    ],
                ],
            ],
            'routes' => [
                'order_create' => [
                    'type'  => 'merchant',
                    'request_count' => '120',
                    'request_count_window' => '60',
                ]
            ],
        ];

        // fetch all config
        $this->assertArraySelectiveEquals(
            $expected,
            $this->makeRequestAndGetContent($this->testData[__FUNCTION__]['request'])
        );
    }

    /**
     * Even though the testcase is not related to throttling, adding it here because the corresponding code is in Middleware/Throttle.php
     */
    public function testPushHttpMetricsTeamDimension()
    {
        $this->ba->adminAuth();

        Trace::shouldReceive('histogram')->zeroOrMoreTimes();

        Trace::shouldReceive('info', 'debug', 'addRecord', 'error')->zeroOrMoreTimes();

        $actualData = [];

        Trace::shouldReceive('count')->andReturnUsing(function ($metric, $data) use (&$actualData)
        {
            $actualData[$metric] = $data;
        });

        $this->makeRequestAndGetContent([
            'method'  => 'GET',
            'url'     => '/roles',
        ]);

        $this->assertArrayHasKey('http_requests_total', $actualData);

        $this->assertEquals('payments_care', $actualData['http_requests_total']['rzp_team']);

        $this->assertEquals(self::API_PRODUCTION_HOST, $actualData['http_requests_total']['host']);
    }
}
