<?php

namespace RZP\Tests\Functional\Request;

use Illuminate\Support\Facades\Redis;

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

    public function testFetchOrdersWhenNotThrottled()
    {
        $this->startTest();
    }

    public function testGetOrderWhenThrottled()
    {
        // Sets max bucket size for specific test mid and for private auth
        // to O and expects the first requests itself to be throttled.
        $this->setRedisIdLevelSettings('10000000000000', ['test:private:0:order_fetch:mbs' => 0]);

        $this->startTest();
    }

    /**
     * If redis setting is missing, no throttle happens and an alert is raised.
     */
    public function testGetOrderWhenRedisSettingsMissing()
    {
        $this->setRedisGlobalSettings([]);

        $this->startTest();
    }
}
