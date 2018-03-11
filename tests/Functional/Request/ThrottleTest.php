<?php

namespace RZP\Tests\Functional\Request;

use RZP\Trace\TraceCode;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsThrottle;
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

    public function testGetOrderWhenThrottledSecondTime()
    {
        $this->mockTraceAndExpectNoError();

        // Sets max bucket size for specific test mid and for private auth
        // to 1 and expects the first request to pass and second requests to be throttled.
        $this->setRedisIdLevelSettings('10000000000000', ['test:private:0:order_fetch:mbs' => 1]);

        $this->startTest($this->testData[__FUNCTION__.'1']);
        $this->startTest($this->testData[__FUNCTION__.'2']);
    }

    public function testGetOrderWhenThrottledForSpecificMerchant()
    {
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
}
