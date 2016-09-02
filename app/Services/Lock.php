<?php

namespace RZP\Services;

use Redis;
use Request;

/**
 * The below lock implementation is based on single-instance redis redlock algorithm
 * as detailed here - http://redis.io/topics/distlock
 *
 * SETNX - This command is crucial to lock implementation.
 *         Man page - http://redis.io/commands/setnx
 */
class Lock
{
    protected $requestId;

    protected $redis;

    public function __construct($app)
    {
        $this->requestId = $app['request']->getId();

        $this->redis = $app['redis'];
    }

    /**
     * Set the lock for the resource provided
     *
     * @param string $resource Name of the resource
     * @param int    $ttl      Expiry time of lock in seconds
     *
     * @return boolean
     */
    public function acquire($resource, $ttl = 60)
    {
        $response = $this->redis->set($resource, $this->requestId, 'ex', $ttl, 'nx');

        return ($response->getPayload() === 'OK');
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
        if ($this->redis->get($resource) === $this->requestId)
        {
            return $this->redis->del($resource);
        }

        return 0;
    }
}