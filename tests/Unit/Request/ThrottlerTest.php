<?php

namespace RZP\Tests\Unit\Request;

use RZP\Tests\TestCase;
use RZP\Http\Throttle\Throttler;
use RZP\Tests\Traits\TestsThrottle;
use RZP\Exception\ThrottleException;
use RZP\Exception\BadRequestException;

class ThrottlerTest extends TestCase
{
    use TestsThrottle { setUp as baseSetUp; }
    use Traits\HasRequestCases;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/ThrottlerTestData.php';

        $this->baseSetUp();
    }

    /**
     * When global setting is set with max bucket size 0:
     * - Must throw ThrottleException
     */
    public function testAttemptThrottle()
    {
        $this->expectException(ThrottleException::class);

        $this->setRedisGlobalSettings(['skip' => 0, 'mock' => 0, 'test:private:0:mbs' => 0]);

        $requestMock = $this->invokeRequestCase('privateRoute');

        (new Throttler)->throttle($requestMock);
    }

    /**
     * When requested with incorrect key id, throttler:
     * - Must fail with BadRequestException
     */
    public function testAttemptThrottleWhenInvalidKeyId()
    {
        $this->expectException(BadRequestException::class);

        $requestMock = $this->invokeRequestCase('privateRouteWhenInvalidKey');

        (new Throttler)->throttle($requestMock);
    }

    /**
     * When skip flag is enabled in global settings:
     * - Must not trigger attemptThrottle
     */
    public function testAttemptThrottleWhenSkipped()
    {
        $this->setRedisGlobalSettings(['skip' => 1]);

        $requestMock = $this->invokeRequestCase('privateRoute');

        $throttlerMock = $this->createThrottlerMock(['attemptThrottle']);
        $throttlerMock->expects($this->never())
                      ->method('attemptThrottle');

        $throttlerMock->throttle($requestMock);
    }

    /**
     * When there is redis connection error:
     * - Must not trigger attemptThrottle
     * - Must not throw any exception
     */
    public function testAttemptThrottleWhenRedisConnectionError()
    {
        $requestMock = $this->invokeRequestCase('privateRoute');

        $throttlerMock = $this->createThrottlerMock(['attemptThrottle', 'initRedisConnection']);
        $throttlerMock->expects($this->once())
                      ->method('initRedisConnection')
                      ->will($this->throwException(new \Exception));
        $throttlerMock->expects($this->never())
                      ->method('attemptThrottle');

        $throttlerMock->throttle($requestMock);
    }

    public function testAttemptMultipleThrottleAndAssertMidIsCached()
    {
        $requestMock = $this->invokeRequestCase('privateRoute');

        $throttlerMock = $this->createThrottlerMock(['getMidForKeyIdFromDb']);
        $throttlerMock->expects($this->once())
                      ->method('getMidForKeyIdFromDb')
                      ->willReturn('10000000000000');

        $throttlerMock->throttle($requestMock);
        $throttlerMock->throttle($requestMock);
        $throttlerMock->throttle($requestMock);
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
        // The settings config in redis(mocked)
        $settings = $this->testData['settings'];

        $requestCases = array_keys(array_except($this->testData, 'settings'));

        foreach ($requestCases as $case)
        {
            $expected = $this->testData[$case];

            $requestMock = $this->invokeRequestCase($case);

            $consecutiveReturns = array_values(array_only($settings, array_keys($expected['settings'])));
            $throttlerMock = $this->createThrottlerMock(['loadSettingsFromRedis']);
            $throttlerMock->method('loadSettingsFromRedis')
                          ->will($this->onConsecutiveCalls($consecutiveReturns));

            $throttlerMock->initRequestContextVars($requestMock);
            $throttlerMock->initRedisConnection();
            $throttlerMock->setMidIfApplicable();

            $this->assertEquals($expected['id'], $throttlerMock->getIdSettingsKey());
            $this->assertEquals($expected['key'], $throttlerMock->getThrottleKey());

            foreach ($expected['settings'] as $idx => $expectedSettings)
            {
                $this->assertEquals($expectedSettings[0], $throttlerMock->getThrottleRateValue());
                $this->assertEquals($expectedSettings[1], $throttlerMock->getThrottleRateDuration());
                $this->assertEquals($expectedSettings[2], $throttlerMock->getThrottleMaxBucketSize());
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
