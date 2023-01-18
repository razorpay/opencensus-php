<?php

use Carbon\Carbon;
use \RZP\Tests\TestCase;
use RZP\Models\RateLimiter\FixedWindowLimiter;

class FixedWindowLimiterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testRateLimitWhenNumberOfRequestsAreWithinTheLimit()
    {
        Carbon::setTestNow(Carbon::create(2022, 5, 1, 12, 00, 00));

        $rateLimiter = new FixedWindowLimiter(2, 2, 'test');

        $rateLimiter->checkLimit();

        $passed = $rateLimiter->checkLimit();

        $this->assertTrue($passed);
    }

    public function testRateLimitWhenNumberOfRequestsAreNotWithinTheLimit()
    {
        Carbon::setTestNow(Carbon::create(2022, 5, 2, 12, 00, 00));

        $rateLimiter = new FixedWindowLimiter(2, 2, 'test');

        $rateLimiter->checkLimit();
        $rateLimiter->checkLimit();

        $passed = $rateLimiter->checkLimit();

        $this->assertFalse($passed);
    }

    public function testRateLimitResetsForDifferentTimeWindow()
    {
        Carbon::setTestNow(Carbon::create(2022, 5, 3, 12, 00, 00));

        $rateLimiter = new FixedWindowLimiter(2, 2, 'test');

        $rateLimiter->checkLimit();
        $rateLimiter->checkLimit();

        Carbon::setTestNow(Carbon::create(2022, 5, 3, 12, 00, 02));

        $rateLimiter->checkLimit();
        $passed = $rateLimiter->checkLimit();

        $this->assertTrue($passed);
    }

    public function testRateLimitRedisKey()
    {
        Carbon::setTestNow(Carbon::create(2022, 5, 4, 12, 00, 00));

        $rateLimiter = new FixedWindowLimiter(2, 2, 'test');

        $currentTime = Carbon::now()->getTimestamp();

        $currentWindow = (int)($currentTime / 2);

        $rateLimiter->checkLimit();

        $this->assertEquals('rate_limit_' . 'test_' . $currentWindow, $rateLimiter->getKey());
    }

    public function testRateLimitCurrentRequestNumber()
    {
        Carbon::setTestNow(Carbon::create(2022, 5, 5, 12, 00, 00));

        $rateLimiter = new FixedWindowLimiter(1, 1, 'test');

        $rateLimiter->checkLimit();
        $rateLimiter->checkLimit();

        $this->assertEquals(2, $rateLimiter->getCurrentRequestNumber());
    }

    public function testRateLimitCurrentRequestNumberAfterWindowReset()
    {
        Carbon::setTestNow(Carbon::create(2022, 5, 6, 12, 00, 00));

        $rateLimiter = new FixedWindowLimiter(3, 2, 'test');

        $rateLimiter->checkLimit();
        $rateLimiter->checkLimit();

        Carbon::setTestNow(Carbon::create(2022, 5, 6, 12, 00, 02));

        $rateLimiter->checkLimit();

        $this->assertEquals(1, $rateLimiter->getCurrentRequestNumber());
    }

    public function testRateLimitIsTakenAsDefaultWhenLimitIsNotDefined()
    {
        Carbon::setTestNow(Carbon::create(2022, 5, 7, 12, 00, 00));

        $rateLimiter = new FixedWindowLimiter(0, 2, 'test');

        $defaultRateLimit = 15;

        $passed = false;

        for ($i = 0; $i < $defaultRateLimit; $i++)
        {
            $passed = $rateLimiter->checkLimit();
        }

        $this->assertEquals($defaultRateLimit, $rateLimiter->getCurrentRequestNumber());

        // assert that the last request was not rate limited
        $this->assertEquals(true, $passed);
    }

    public function testRateLimitIsTakenAsDefaultWhenWindowLengthIsNotDefined()
    {
        Carbon::setTestNow(Carbon::create(2022, 5, 8, 12, 00, 00));

        $rateLimiter = new FixedWindowLimiter(1, 0, 'test');

        $defaultRateLimit = 15;

        $passed = true;

        for ($i = 0; $i < $defaultRateLimit + 1; $i++)
        {
            $passed = $rateLimiter->checkLimit();
        }

        // ensure that the first window is rate limited
        $this->assertFalse($passed);

        $this->assertEquals(16, $rateLimiter->getCurrentRequestNumber());

        // sleep for default window duration
        Carbon::setTestNow(Carbon::create(2022, 5, 8, 12, 00, 01));

        $passed = $rateLimiter->checkLimit();

        // assert that window gets reset after default time
        $this->assertEquals(1, $rateLimiter->getCurrentRequestNumber());

        $this->assertEquals(true, $passed);
    }
}
