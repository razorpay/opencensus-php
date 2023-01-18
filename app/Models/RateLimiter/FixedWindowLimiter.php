<?php

namespace RZP\Models\RateLimiter;

use App;
use Carbon\Carbon;

class FixedWindowLimiter
{
    protected $redis;

    protected $limit;

    protected $windowLengthInSeconds;

    protected $keyPrefix;

    private $redisKey;

    private $currentRequestNumber;

    private $remainingTimeForWindowResetInMs = 0;

    const DEFAULT_RATE_LIMIT = 15;

    const DEFAULT_FIXED_WINDOW_LENGTH = 1;

    const COMMON_RATE_LIMITER_PREFIX = "rate_limit_";

    /**
     * FixedWindowLimiter constructor.
     * @param int $limit
     * @param int $windowLengthInSeconds
     * @param string $keyPrefix
     */
    public function __construct(
        int $limit,
        int $windowLengthInSeconds,
        string $keyPrefix = ''
    )
    {
        $app = App::getFacadeRoot();
        $this->redis = $app['redis']->connection();

        $this->keyPrefix = $keyPrefix;
        $this->setLimitAndWindow($limit, $windowLengthInSeconds);
    }

    public function checkLimit()
    {
        $currentTime = Carbon::now()->getTimestamp();

        $currentWindow = (int)($currentTime / $this->windowLengthInSeconds);

        $this->redisKey = self::COMMON_RATE_LIMITER_PREFIX . $this->keyPrefix . "_" . stringify($currentWindow);

        // if the redis key is unset it will return null.
        $currentRequests = $this->redis->get($this->redisKey);

        if ($currentRequests !== null and $currentRequests >= $this->limit)
        {
            // returning $currentRequests + 1 as $currentRequests number of requests were already sent in the current window
            // and the request received now is ($currentRequests + 1)th request.
            $this->currentRequestNumber = $currentRequests + 1;

            $this->computedRemainingTime($currentWindow);

            return false;
        }

        if ($currentRequests === null)
        {
            $this->redis->set($this->redisKey, 0, 'NX','EX', $this->windowLengthInSeconds);
        }

        $this->currentRequestNumber = $this->redis->incr($this->redisKey);

        if ($this->currentRequestNumber > $this->limit)
        {
            $this->redis->decr($this->redisKey);

            $this->computedRemainingTime($currentWindow);

            return false;
        }

        return true;
    }

    public function getKey(): string
    {
        return $this->redisKey;
    }

    public function getCurrentRequestNumber(): int
    {
        return $this->currentRequestNumber;
    }

    public function getRemainingTimeForWindowResetInMs(): int
    {
        return $this->remainingTimeForWindowResetInMs;
    }

    /**
     * @param int $limit
     * @param int $windowLength
     */
    private function setLimitAndWindow(int $limit, int $windowLength): void
    {
        // rate_limit and window_length is an interdependent combination, hence even if one of them is missing we take
        // default values for both.
        if (empty($limit) === true or empty($windowLength) === true)
        {
            $this->limit = self::DEFAULT_RATE_LIMIT;
            $this->windowLengthInSeconds = self::DEFAULT_FIXED_WINDOW_LENGTH;
            return;
        }

        $this->limit = $limit;
        $this->windowLengthInSeconds = $windowLength;
    }

    /**
     * @param int $currentWindow
     */
    private function computedRemainingTime(int $currentWindow): void
    {
        $currentTimeInMs = Carbon::now()->valueOf();

        $timestampForCurrentWindow = $currentWindow * $this->windowLengthInSeconds;

        $remainingTimeInMs = (($timestampForCurrentWindow + $this->windowLengthInSeconds) * 1000) - $currentTimeInMs;

        $this->remainingTimeForWindowResetInMs = ($remainingTimeInMs > 0) ? $remainingTimeInMs : 0;
    }
}
