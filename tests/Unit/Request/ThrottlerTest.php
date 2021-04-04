<?php

namespace RZP\Tests\Unit\Request;

use RZP\Tests\TestCase;
use RZP\Tests\Traits\TestsThrottle;
use RZP\Http\Throttle\Constant as K;
use RZP\Exception\ThrottleException;
use RZP\Tests\Unit\Request\Helpers\Throttler;

class ThrottlerTest extends TestCase
{
    use TestsThrottle { setUp as baseSetUp; }
    use Traits\HasRequestCases;

    protected function setUp(): void
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

        $this->invokeRequestCaseAndBindNewContext('privateRoute');

        (new Throttler)->throttle();
    }

    /**
     * When requested with incorrect key prefix (e.g. rzp_PQRS_{14 char}):
     * - Must not attempt to get mid corresponding to this key, because db
     *   connection is incorrect.
     * - Must not throw any exception and let basic auth take over and error out
     *   with invalid key too.
     */
    public function testAttemptThrottleWhenInvalidModeInKey()
    {
        $requestMock = $this->mockRouteRequest('invoice_get_status', 'invoices/inv_1000000invoice/status', [], ['rzp_ORPBLICKEY']);

        (new Throttler)->throttle($requestMock);

        $this->assertTrue(true);
    }

    /**
     * When skip flag is enabled in global settings:
     * - Must not trigger attemptThrottle
     */
    public function testAttemptThrottleWhenSkipped()
    {
        $this->setRedisGlobalSettings([K::SKIP => 1]);

        $this->invokeRequestCaseAndBindNewContext('privateRoute');

        $throttlerMock = $this->createThrottlerMock(['attemptThrottle']);
        $throttlerMock->expects($this->never())
                      ->method('attemptThrottle');

        $throttlerMock->throttle();
    }

    public function testAttemptThrottleWhenSkippedForSpecificMerchant()
    {
        $this->setRedisIdLevelSettings('10000000000000', [K::SKIP => 1]);

        $this->invokeRequestCaseAndBindNewContext('privateRoute');

        $throttlerMock = $this->createThrottlerMock(['attemptThrottle']);
        $throttlerMock->expects($this->never())
                      ->method('attemptThrottle');

        $throttlerMock->throttle();
    }

    public function testAttemptThrottleWhenMocked()
    {
        // Sets global mock as true. Also, sets mbs as 0 so first request gets throttled itself.
        $this->setRedisGlobalSettings([K::MOCK => 1, 'test:private:0:mbs' => 0]);

        $this->invokeRequestCaseAndBindNewContext('privateRoute');

        // Just shouldn't throw any exception.
        (new Throttler)->throttle();
        $this->assertTrue(true);
    }

    public function testAttemptThrottleWhenMockedForSpecificMerchant()
    {
        // Sets mbs as 0 so first request gets throttled itself.
        $this->setRedisGlobalSettings(['test:private:0:mbs' => 0]);
        $this->setRedisIdLevelSettings('10000000000000', [K::MOCK => 1]);

        $this->invokeRequestCaseAndBindNewContext('privateRoute');

        // Just shouldn't throw any exception.
        (new Throttler)->throttle();
        $this->assertTrue(true);
    }

    /**
     * When skip flag is enabled in .env file (locally - test/local environment)
     * - Must not trigger attemptThrottle
     */
    public function testAttemptThrottleWhenSkippedLocally()
    {
        $this->app['config']->set('throttle.skip', true);

        $this->invokeRequestCaseAndBindNewContext('privateRoute');

        $throttlerMock = $this->createThrottlerMock(['attemptThrottle']);
        $throttlerMock->expects($this->never())
                      ->method('attemptThrottle');

        $throttlerMock->throttle();
    }

    /**
     * When there is redis connection error:
     * - Must not trigger attemptThrottle
     * - Must not throw any exception
     */
    public function testAttemptThrottleWhenRedisConnectionError()
    {
        $this->invokeRequestCaseAndBindNewContext('privateRoute');

        $throttlerMock = $this->createThrottlerMock(['attemptThrottle', 'initRedisConnection']);
        $throttlerMock->expects($this->once())
                      ->method('initRedisConnection')
                      ->will($this->throwException(new \Exception));
        $throttlerMock->expects($this->never())
                      ->method('attemptThrottle');

        $throttlerMock->throttle();
    }

    /**
     * When there is redis connection error:
     * - Must not trigger attemptBlock
     * - Must not throw any exception
     */
    public function testAttemptBlockWhenRedisConnectionError()
    {
        $this->invokeRequestCaseAndBindNewContext('privateRoute');

        $throttlerMock = $this->createThrottlerMock(['blockIfApplicable', 'initRedisConnection']);
        $throttlerMock->expects($this->once())
                      ->method('initRedisConnection')
                      ->will($this->throwException(new \Exception));
        $throttlerMock->expects($this->never())
                      ->method('blockIfApplicable');

        $throttlerMock->throttle();
    }

    public function testAssertThrottleKeysPickForInvoiceSendNotification()
    {
        $this->invokeRequestCaseAndBindNewContext(
            'publicRouteWithKeyInQuery',
            'invoice_send_notification',
            'invoices/inv_1000000invoice/notify/email');

        $throttler = new Throttler;

        $this->assertEquals(
            '10000000000000',
            $throttler->getIdSettingsKey());

        $this->assertEquals(
            'invoice_send_notification:test:public:0::10000000000000::1.1.1.1:inv_1000000invoice',
            $throttler->getThrottleKey());
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

            $this->invokeRequestCaseAndBindNewContext($case);

            $throttlerMock = $this->createThrottlerMock(['loadSettingsFromRedis']);
            $throttlerMock->expects($this->exactly(count($settings)))
                          ->method('loadSettingsFromRedis')
                          ->will($this->onConsecutiveCalls(...array_values($settings)));

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
        return $this->getMockBuilder(Throttler::class)
                    ->onlyMethods($methods)
                    ->getMock();
    }
}
