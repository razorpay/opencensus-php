<?php

namespace RZP\Models\Base;

use Redis;
use Request;

class Lock
{
    public function __construct(Request $request, Redis $redis)
    {
        $this->requestId = Request::getFacadeRoot()->getId();

        $this->redis = Redis::getFacadeRoot();
    }

    /**
     * Set the lock for the resource provided
     *
     * @param string $resource Name of the resource
     * @param int    $ttl      Expiry time of lock in minutes
     *
     * @return boolean
     */
    public function set($resource, $ttl = 60)
    {
        $status = $this->redis->set($resource, $this->requestId, 'ex', $ttl, 'nx');

        return ($status === 'OK');
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