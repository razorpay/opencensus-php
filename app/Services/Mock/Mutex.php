<?php

namespace RZP\Services\Mock;

use RZP\Services\Mutex as BaseLock;

class Mutex extends BaseLock
{
    protected $cache;

    public function __construct($app)
    {
        $this->requestId = $app['request']->getId();

        $this->cache = $app['cache'];
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
        if ($this->cache->store('file')->get($resource))
        {
            return false;
        }

        return $this->cache->store('file')->put($resource, $this->requestId, $ttl);
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
        if ($this->cache->get($resource) === $this->requestId)
        {
            return $this->cache->forget($resource);
        }

        return false;
    }
}