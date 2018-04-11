<?php

namespace RZP\Tests\Functional\Request;

use RZP\Trace\TraceCode;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsThrottle;
use RZP\Http\Throttle\Constant as K;
use RZP\Tests\Functional\RequestResponseFlowTrait;

/**
 * A few end to end functional test to assert rate limiting is working fine.
 */
class ThrottleTest extends TestCase
{
    use TestsThrottle { setUp as baseSetUp; }
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/ThrottleTestData.php';

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

    public function testGetOrderWhenBlockedForTestMerchant()
    {
        // Case 1: Blocks GET /invoice route for test mid, so GET /orders should pass
        $this->setRedisIdLevelSettings('10000000000000', ['test:private:0:invoice_fetch_multiple:block' => 1]);
        $this->startTest($this->testData[__FUNCTION__.'1']);

        // Case 2: Blocks GET /orders route too for test mid, so GET /orders should error
        $this->setRedisIdLevelSettings('10000000000000', ['test:private:0:order_fetch:block' => 1]);
        $this->startTest($this->testData[__FUNCTION__.'2']);
    }
}
