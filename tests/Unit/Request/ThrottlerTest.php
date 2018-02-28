<?php

namespace RZP\Tests\Unit\Request;

use RZP\Http\Throttle\Throttler;

class ThrottlerTest extends \RZP\Tests\AbstractThrottleTest
{
    use Traits\HasRequestCases;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/ThrottlerTestData.php';
        parent::setUp();
    }

    /**
     * Must not trigger attempt when redis has skip flag enabled.
     */
    public function testAttemptThrottleWhenSkipped()
    {
        $this->setRedisGlobalSettings(1);

        $requestMock = $this->mockRouteRequest('invoice_fetch_multiple');

        $throttlerMock = $this->createThrottlerMock(['attemptThrottle']);
        $throttlerMock->expects($this->never())->method('attemptThrottle');

        $throttlerMock->throttle($requestMock);
    }

    public function testAttemptMultipleThrottleAndAssertMidIsCached()
    {
    }

    /**
     * Runs all available request cases against a set of settings.
     * For each request and settings combination asserts following:
     * - Correct id is being picked for querying redis config
     * - Correct throttle key is being used
     * - Correct settings value is being picked from available redis settings
     */
    public function testAssertKeysAndSettingsPickForAllRequestCases()
    {
        $settings = $this->testData['settings'];
        $requestCases = array_keys(array_except($this->testData, 'settings'));

        foreach ($requestCases as $case)
        {
            $requestMock = $this->invokeRequestCase($case);

            $throttlerMock = $this->createThrottlerMock(['loadSettingsFromRedis']);
            $throttlerMock->method('loadSettingsFromRedis')
                          ->will($this->onConsecutiveCalls($settings));

            $throttlerMock->initRequestContextVars($requestMock);
            $throttlerMock->initRedisConnection();
            $throttlerMock->setMidIfApplicable();

            $expected = $this->testData[$case];
            $this->assertEquals($expected['id'], $throttlerMock->getIdSettingsKey());
            $this->assertEquals($expected['key'], $throttlerMock->getThrottleKey());

            foreach ($settings as $idx => $setting)
            {
                $expectedSettings = $expected['settings'];
                $this->assertEquals($expectedSettings[$idx][0], $throttlerMock->getThrottleRateValue());
                $this->assertEquals($expectedSettings[$idx][1], $throttlerMock->getThrottleRateDuration());
                $this->assertEquals($expectedSettings[$idx][2], $throttlerMock->getThrottleMaxBucketSize());
            }
        }
    }

    /**
     * Creates mock of throttler class.
     * @param  array     $withMethods
     * @return Throttler
     */
    protected function createThrottlerMock(array $withMethods = []): Throttler
    {
        return $this->getMockBuilder(Helpers\Throttler::class)
                    ->setMethods($withMethods)
                    ->getMock();
    }
}
