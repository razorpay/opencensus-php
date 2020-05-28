<?php

namespace RZP\Services;

use Illuminate\Cache\CacheManager;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class CacheDualWrite
{
    protected $trace;

    protected $shouldReadElasticCache;

    const CLUSTER_CONNECTION = 'query_cache_redis';

    const DEFAULT_CONNECTION = 'default';

    /** @var $cache CacheManager */
    protected $cache;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $config = $app['config']->get('applications.cache_dual_write');

        $this->cache = $app['cache'];

        $this->shouldReadElasticCache = $config['cluster_cache_read'];
    }

    public function setConnection($connection)
    {
        $this->cache->setConnection($connection);
    }

    public function many(array $keys)
    {
        try
        {
            if ($this->shouldReadElasticCache === true)
            {
                $this->cache->setConnection(self::CLUSTER_CONNECTION);

                $newRedisResponse = $this->cache->many($keys);

                $this->cache->setConnection(self::DEFAULT_CONNECTION);

                return $newRedisResponse;
            }

            return $this->cache->many($keys);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_MGET_ERROR,
                ['keys' => $keys]);

            throw $e;
        }
    }

    public function get($key)
    {
        try
        {
            if ($this->shouldReadElasticCache === true)
            {
                $this->cache->setConnection(self::CLUSTER_CONNECTION);

                $newRedisResponse = $this->cache->get($key);

                $this->cache->setConnection(self::DEFAULT_CONNECTION);

                return $newRedisResponse;
            }

            return $this->cache->get($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_READ_ERROR,
                ['keys' => $key]);

            throw $e;
        }
    }

    public function put($key, $value, $minutes)
    {
        try
        {
            $this->cache->put($key, $value, $minutes);

            $this->cache->setConnection(self::CLUSTER_CONNECTION);

            $this->cache->put($key, $value, $minutes);

            $this->cache->forget($key);

            $this->cache->setConnection(self::DEFAULT_CONNECTION);

            return;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_STORE_ERROR,
                ['keys' => $key]);

            $this->cache->setConnection(self::DEFAULT_CONNECTION);

            $this->cache->forget($key);

            $this->cache->setConnection(self::CLUSTER_CONNECTION);

            $this->cache->forget($key);

            $this->cache->setConnection(self::DEFAULT_CONNECTION);

            throw $e;
        }
    }
}
