<?php

namespace RZP\Services\Mock;

use RZP\Services\Mutex as BaseLock;

class Mutex extends BaseLock
{
    protected $cache;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->cache = $app['cache'];
    }

    /**
     * Acquires lock on a resource
     *
     * @param string $resource      Key on which to acquire lock
     * @param int    $ttl           Expiry time of lock in seconds
     * @param int    $retryCount    Number of times to retry for acquiring lock
     * @param int    $minRetryDelay Minimum time to wait before retry in millisec
     * @param int    $maxRetryDelay Maximum time to wait before retry in millisec
     * @param bool   $strict        Block/continue on redis exception
     *
     * @return bool Whether finally lock was acquired or not
     */
    public function acquire(
        $resource,
        $ttl = 60,
        $retryCount = 0,
        $minRetryDelay = 100,
        $maxRetryDelay = 200,
        $strict = false): bool
    {
        do {

            if ($this->cache->store('file')->get($resource))
            {
                $acquired = false;
            }
            else
            {
                $this->cache->store('file')->put($resource, $this->requestId, $ttl);

                $acquired = true;

                break;
            }

            $delay = mt_rand($minRetryDelay, $maxRetryDelay);

            usleep($delay * 10000);

            $retryCount--;
        }
        while ($retryCount >= 0);

        return $acquired;
    }

    /**
     * Release the lock for the resource provided
     *
     * @param string $resource Name of the resource
     *
     * @return integer
     */
    public function release($resource)
    {
        if ($this->cache->store('file')->get($resource) === $this->requestId)
        {
            return $this->cache->store('file')->forget($resource);
        }

        return false;
    }
}
