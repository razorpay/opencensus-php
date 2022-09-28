<?php

namespace RZP\Models\Partner;

use RZP\Models\Base;
use RZP\Exception\BadRequestException;

/**
 * Class PartnershipsRateLimiter
 * The current rate limiting would use fixed window of 24hrs. This can be extended to any sources by
 * adding the respective ratelimiting configurations in the RateLimitConstants.php
 *
 * @package RZP\Models\Partner
 */
class PartnershipsRateLimiter extends Base\Core
{
    protected $redis;

    protected $rateLimitConfig;

    protected $source;

    public function __construct(string $source)
    {
        parent::__construct();

        $this->source = $source;

        $this->rateLimitConfig = RateLimitConstants::RATELIMIT_CONFIG[$source];

        $this->redis = $this->app['redis']->Connection('mutex_redis');
    }

    /**
     * @param string $rateLimitingKey
     * This function ratelimits the provided key. The consumer needs to generate this key and provide it to the
     * ratelimiter
     * returns true - If ratelimiter updated
     * throws exception in case of ratelimit exceeeded
     *
     * @return bool
     * @throws BadRequestException
     */
    public function rateLimit(string $rateLimitingKey): bool
    {
        $exceeded = $this->isRateLimitExceeded($rateLimitingKey);

        if ($exceeded === true)
        {
            //Adding merchant Id to dimension as this is a extreme case
            $this->trace->count($this->rateLimitConfig[RateLimitConstants::METRIC], ['key' => $rateLimitingKey, 'source' => $this->source]);

            throw new BadRequestException($this->rateLimitConfig[RateLimitConstants::ERROR_CODE], null,
                                          [
                                              'key'    => $rateLimitingKey,
                                              'source' => $this->source
                                          ]);
        }

        $this->incrementRateLimitCount($rateLimitingKey);

        return true;
    }

    public function getRateLimitRedisKey(string $id): string
    {
        return RateLimitConstants::RATE_LIMIT_PREFIX . $id . "_" . $this->source;
    }

    public function decrementRateLimitCount(string $rateLimitRedisKey): void
    {
        $this->redis->decr($rateLimitRedisKey);
    }

    private function isRateLimitExceeded(string $rateLimitKey): bool
    {
        $value = $this->getRateLimitCount($rateLimitKey);

        $this->trace->info($this->rateLimitConfig[RateLimitConstants::TRACE_CODE],
                           [
                               'value'     => $value,
                               'threshold' => $this->rateLimitConfig[RateLimitConstants::THRESHOLD]
                           ]);

        if ($value >= $this->rateLimitConfig[RateLimitConstants::THRESHOLD])
        {
            $data = [
                'count'  => $value,
                'key'    => $rateLimitKey,
                'source' => $this->source,
            ];

            $this->trace->info($this->rateLimitConfig[RateLimitConstants::ERROR_TRACE_CODE], $data);

            return true;
        }

        return false;
    }


    private function incrementRateLimitCount(string $rateLimitRedisKey): void
    {
        $index = $this->redis->incr($rateLimitRedisKey);

        // add expiry for first increment of the key
        if ($index == 1)
        {
            $this->redis->expire($rateLimitRedisKey, $this->getTTL());
        }
    }

    /**
     * This function would provide the difference of current time and the last second of the day.
     * That would be TTL left for the key in that day
     *
     * @return int
     */
    private function getTTL(): int
    {
        $value = (strtotime("tomorrow") - 1) - time();
        $this->trace->info($this->rateLimitConfig[RateLimitConstants::TRACE_CODE],
                           [
                               'TTLValueLeft' => $value,
                           ]);

        return $value;
    }

    private function getRateLimitCount(string $rateLimitRedisKey): int
    {
        return $this->redis->get($rateLimitRedisKey) ?? 0;
    }
}
