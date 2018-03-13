<?php

namespace RZP\Tests\Unit\Request;

use RZP\Tests\TestCase;
use RZP\Http\Throttle\Throttler;
use RZP\Tests\Traits\TestsThrottle;
use RZP\Http\Throttle\Constant as K;
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

        $this->setRedisGlobalSettings(['test:private:0:mbs' => 0]);

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

        $requestMock = $this->invokeRequestCase('privateRouteWithInvalidKey');

        (new Throttler)->throttle($requestMock);
    }

    /**
     * When skip flag is enabled in global settings:
     * - Must not trigger attemptThrottle
     */
    public function testAttemptThrottleWhenSkipped()
    {
        $this->setRedisGlobalSettings([K::SKIP => 1]);

        $requestMock = $this->invokeRequestCase('privateRoute');

        $throttlerMock = $this->createThrottlerMock(['attemptThrottle']);
        $throttlerMock->expects($this->never())
                      ->method('attemptThrottle');

        $throttlerMock->throttle($requestMock);
    }

    public function testAttemptThrottleWhenSkippedForSpecificMerchant()
    {
        $this->setRedisIdLevelSettings('10000000000000', [K::SKIP => 1]);

        $requestMock = $this->invokeRequestCase('privateRoute');

        $throttlerMock = $this->createThrottlerMock(['attemptThrottle']);
        $throttlerMock->expects($this->never())
                      ->method('attemptThrottle');

        $throttlerMock->throttle($requestMock);
    }

    public function testAttemptThrottleWhenMocked()
    {
        // Sets global mock as true. Also, sets mbs as 0 so first request gets throttled itself.
        $this->setRedisGlobalSettings([K::MOCK => 1, 'test:private:0:mbs' => 0]);

        $requestMock = $this->invokeRequestCase('privateRoute');

        // Just shouldn't throw any exception.
        (new Throttler)->throttle($requestMock);
        $this->assertTrue(true);
    }

    public function testAttemptThrottleWhenMockedForSpecificMerchant()
    {
        // Sets mbs as 0 so first request gets throttled itself.
        $this->setRedisGlobalSettings(['test:private:0:mbs' => 0]);
        $this->setRedisIdLevelSettings('10000000000000', [K::MOCK => 1]);

        $requestMock = $this->invokeRequestCase('privateRoute');

        // Just shouldn't throw any exception.
        (new Throttler)->throttle($requestMock);
        $this->assertTrue(true);
    }

    /**
     * When skip flag is enabled in .env file (locally - test/local environment)
     * - Must not trigger attemptThrottle
     */
    public function testAttemptThrottleWhenSkippedLocally()
    {
        $this->app['config']->set('throttle.skip', true);

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

            $throttlerMock = $this->createThrottlerMock(['loadSettingsFromRedis']);
            $throttlerMock->expects($this->exactly(count($settings)))
                          ->method('loadSettingsFromRedis')
                          ->will($this->onConsecutiveCalls(...array_values($settings)));

            $throttlerMock->initRequestContextVars($requestMock);
            $throttlerMock->initRedisConnection();
            $throttlerMock->setMidIfApplicable();

            $this->assertEquals($expected['id_settings_key'], $throttlerMock->getIdSettingsKey());
            $this->assertEquals($expected['throttle_key'], $throttlerMock->getThrottleKey());

            foreach (array_keys($settings) as $key)
            {
                // We reset throttle settings every iteration and assert that if
                // the new returned(mocked ^) value were settings what throttle
                // values would be picked for given requests.
                $throttlerMock->initThrottleSettings();

                $expectedSettings         = $expected['settings'][$key] ?? [];
                $expectedBlock            = $expectedSettings[K::BLOCK] ?? K::DEFAULT_BLOCK;
                $expectedSkip             = $expectedSettings[K::SKIP] ?? K::DEFAULT_SKIP;
                $expectedMock             = $expectedSettings[K::MOCK] ?? K::DEFAULT_MOCK;
                $expectedMaxBucketSize    = $expectedSettings[K::MAX_BUCKET_SIZE] ?? K::DEFAULT_MAX_BUCKET_SIZE;
                $expectedLeakRateValue    = $expectedSettings[K::LEAK_RATE_VALUE] ?? K::DEFAULT_LEAK_RATE_VALUE;
                $expectedLeakRateDuration = $expectedSettings[K::LEAK_RATE_DURATION] ?? K::DEFAULT_LEAK_RATE_DURATION;

                $this->assertEquals($expectedBlock, $throttlerMock->isBlocked());
                $this->assertEquals($expectedSkip, $throttlerMock->isThrottleSkipped());
                $this->assertEquals($expectedMock, $throttlerMock->isThrottleMocked());
                $this->assertEquals($expectedMaxBucketSize, $throttlerMock->getThrottleMaxBucketSize());
                $this->assertEquals($expectedLeakRateValue, $throttlerMock->getThrottleLeakRateValue());
                $this->assertEquals($expectedLeakRateDuration, $throttlerMock->getThrottleLeakRateDuration());
            }
        }
    }

    /**
     * Creates mock of throttler class.
     * @param  array     $methods
     * @return Throttler
     */
    protected function createThrottlerMock(array $methods = []): Throttler
    {
        return $this->getMockBuilder(Helpers\Throttler::class)
                    ->setMethods($methods)
                    ->getMock();
    }
}
