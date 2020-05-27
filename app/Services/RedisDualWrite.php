<?php

namespace RZP\Services;

use Illuminate\Support\Facades\Redis;
use Predis\PredisException;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class RedisDualWrite
{
    protected $trace;

    protected $shouldReadRedisLabs;

    const PREFIX = 'mutex:ec';

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $config = $app['config']->get('applications.redisdualwrite');

        $this->shouldReadRedisLabs = $config['redislab_cache_read'];
    }

    public function set($key, $value, $expireResolution, $expireTTL, $flag)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $keyWithPrefix = $this->appendPrefix($key);

            $redisEcResponse = $redisEc->set($keyWithPrefix, $value, $expireResolution, $expireTTL, $flag);

            $redisLabsResponse = $redis->set($key, $value, $expireResolution, $expireTTL, $flag);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redisLabsResponse;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_STORE_ERROR,
                ['key' => $key]);

            $redis->del($key);

            $redisEc->del($keyWithPrefix);

            throw $e;
        }

        return $response;
    }

    public function get($key)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $keyWithPrefix = $this->appendPrefix($key);

            $redisEcResponse = $redisEc->get($keyWithPrefix);

            $redisLabsResponse = $redis->get($key);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redisLabsResponse;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }

        return $response;
    }

    public function ttl($key)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $keyWithPrefix = $this->appendPrefix($key);

            $redisEcResponse = $redisEc->ttl($keyWithPrefix);

            $redisLabsResponse = $redis->ttl($key);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redisLabsResponse;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_TTL_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }

        return $response;

    }


    public function del($key)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        list($data, $ttl) = $this->backup($key);

        try
        {
            $redisEcKey = $this->appendPrefix($key);

            $redisEcResponse = $redisEc->del($redisEcKey);

            $redisLabsResponse = $redis->del($key);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redisLabsResponse;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_DELETE_ERROR,
                ['key' => $key]);

            $this->restore($key, $data, $ttl);

            throw $e;
        }
    }

    public function hGetAll($key)
    {
        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $keyWithPrefix = $this->appendPrefix($key);

            $redisEcResponse = $redisEc->hGetAll($keyWithPrefix);

            $redisLabsResponse = $redis->hGetAll($key);

            if ($this->shouldReadRedisLabs === true)
            {
                return $redisLabsResponse;
            }

            return $redisEcResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_READ_ERROR,
                ['key' => $key]);

            throw $e;
        }

        return $response;
    }

    protected function backup($key)
    {
        $data = null;
        $ttl = null;

        $redisLabskey = $this->appendPrefix($key);

        $redis = Redis::Connection();

        $redisEc = Redis::Connection('mutex_redis');

        try
        {
            $data = $redis->get($key);
            $ttl  =  $redis->ttl($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_BACKUP_ERROR,
                ['key' => $key]);

        }

        if ($data === null)
        {
            $data = $redisEc->get($key);
            $ttl =  $redisEc->ttl($key);
        }

        return [$data, $ttl];
    }

    protected function restore($key, $data, $ttl)
    {
        $this->set($key, $data, 'ex', $ttl, 'nx');
    }

    protected function appendPrefix($key)
    {
        return self::PREFIX . $key;
    }
}
