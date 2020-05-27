<?php

namespace RZP\Services\Mock;

use Redis;

use RZP\Services\RedisDualWrite as BaseRedisDualWrite;

class RedisDualWrite extends BaseRedisDualWrite
{
    public function set($key, $value, $expireResolution, $expireTTL, $flag)
    {
        $redis = Redis::getFacadeRoot();

        return $redis->set($key, $value, $expireResolution, $expireTTL, $flag);
    }

    public function get($key)
    {
        $redis = Redis::getFacadeRoot();

        return $redis->get($key);
    }

    public function ttl($key)
    {
        $redis = Redis::getFacadeRoot();

        return $redis->ttl($key);
    }

    public function del($key)
    {
        $redis = Redis::getFacadeRoot();

        return $redis->del($key);
    }

    public function hGetAll($key)
    {
        $redis = Redis::getFacadeRoot();

        return $redis->hGetAll($key);
    }
}
