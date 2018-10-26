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

    protected $shouldReadElasticCache;

    const PREFIX = 'mutex:';

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $config = $app['config']->get('applications.redisdualwrite');

        $this->shouldReadElasticCache = $config['elastic_cache_read'];
    }

    public function set($key, $value, $expireResolution, $expireTTL, $flag)
    {
        $redis = Redis::Connection();

        $redisLabs = Redis::Connection('redis_labs');

        try
        {
            $keyWithPrefix = $this->appendPrefix($key);

            $redisLabsResponse = $redisLabs->set($keyWithPrefix, $value, $expireResolution, $expireTTL, $flag);

            $elasticCacheResponse = $redis->set($key, $value, $expireResolution, $expireTTL, $flag);

            if ($this->shouldReadElasticCache === true)
            {
                return $elasticCacheResponse;
            }

            return $redisLabsResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_STORE_ERROR,
                ['key' => $key]);

            $redis->del($key);

            $redisLabs->del($keyWithPrefix);

            throw $e;
        }

        return $response;
    }

    public function get($key)
    {
        $redis = Redis::Connection();

        $redisLabs = Redis::Connection('redis_labs');

        try
        {
            $keyWithPrefix = $this->appendPrefix($key);

            $redisLabsResponse = $redisLabs->get($keyWithPrefix);

            $elasticCacheResponse = $redis->get($key);

            if ($this->shouldReadElasticCache === true)
            {
                return $elasticCacheResponse;
            }

            return $redisLabsResponse;
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


    public function del($key)
    {
        $redis = Redis::Connection();

        $redisLabs = Redis::Connection('redis_labs');

        list($data, $ttl) = $this->backup($key);

        try
        {
            $redisLabskey = $this->appendPrefix($key);

            $redisLabsResponse = $redisLabs->del($redisLabskey);

            $elasticCacheResponse = $redis->del($key);

            if ($this->shouldReadElasticCache === true)
            {
                return $elasticCacheResponse;
            }

            return $redisLabsResponse;
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

    protected function backup($key)
    {
        $data = null;
        $ttl = null;

        $redisLabskey = $this->appendPrefix($key);

        $redis = Redis::Connection();

        $redisLabs = Redis::Connection('redis_labs');

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
            $data = $redisLabs->get($key);
            $ttl =  $redisLabs->ttl($key);
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
