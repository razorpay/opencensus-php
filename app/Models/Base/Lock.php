<?php

namespace RZP\Models\Base;

use Redis;
use Request;

class Lock
{
    public static function set($resource, $ttl = 60)
    {
        $requestId = Request::getFacadeRoot()->getId();
        $redis = Redis::getFacadeRoot();

        $status = (string) $redis->set($resource, $requestId, 'ex', $ttl, 'nx');

        return ($status === 'OK');
    }

    public static function release($resource)
    {
        $requestId = Request::getFacadeRoot()->getId();
        $redis = Redis::getFacadeRoot();

        if ($redis->get($resource) === $requestId)
        {
            return $redis->del($resource);
        }

        return 0;
    }
}