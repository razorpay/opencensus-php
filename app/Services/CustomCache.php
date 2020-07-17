<?php

namespace RZP\Services;

use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;

class CustomCache implements \Illuminate\Contracts\Cache\Store
{
    /** @var \Illuminate\Contracts\Cache\Repository */
    protected $redislabs, $ecCluster;

    protected $shouldReadElasticCache;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->redislabs = $app['cache']->driver('redislabs');

        $this->ecCluster = $app['cache']->driver('ec_cluster');

        $config = $app['config']->get('applications.cache_dual_write');

        $this->shouldReadElasticCache = $config['cluster_cache_read'];
    }

    public function many(array $keys)
    {
        try
        {
            if ($this->shouldReadElasticCache === true)
            {
                return $this->ecCluster->many($keys);
            }

            return $this->redislabs->many($keys);
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

    public function pull($key, $default = null)
    {
        try
        {
            $this->ecCluster->pull($key, $default);

            return $this->redislabs->pull($key, $default);

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

    public function setConnection($connection)
    {
        // not required. we are managing connections.
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush()
    {
        // TODO: Implement flush() method.
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        // TODO: Implement getPrefix() method.
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string|array $key
     * @return mixed
     * @throws \Throwable
     */
    public function get($key)
    {
        try
        {
            if ($this->shouldReadElasticCache === true)
            {
                return $this->ecCluster->get($key);
            }

            return $this->redislabs->get($key);
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

    public function set($key, $value, $ttl = null)
    {
        return $this->put($key, $value, $ttl);
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param string $key
     * @param mixed $value
     * @param float|int $minutes
     * @return void
     * @throws \Throwable
     */
    public function put($key, $value, $minutes)
    {
        try
        {
            $this->redislabs->put($key, $value, $minutes);

            $this->ecCluster->put($key, $value, $minutes);

            return;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_STORE_ERROR,
                ['keys' => $key]);

            $this->redislabs->forget($key);

            $this->ecCluster->forget($key);

            throw $e;
        }
    }

    /**
     * Store multiple items in the cache for a given number of minutes.
     *
     * @param array $values
     * @param float|int $minutes
     * @return void
     */
    public function putMany(array $values, $minutes)
    {
        // TODO: Implement putMany() method.
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @return int|bool
     * @throws \Throwable
     */
    public function increment($key, $value = 1)
    {
        $incrementedValue = $this->redislabs->increment($key, $value);

        try
        {
            if ($this->ecCluster->get($key) !== null)
            {
                return $this->ecCluster->increment($key, $value);
            }

            // set the key by getting data from old redis
            return $this->ecCluster->increment($key, $incrementedValue);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_STORE_ERROR,
                ['keys' => $key]);

            $this->redislabs->decrement($key, $value);

            $this->ecCluster->forget($key);

            throw $e;
        }
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @return int|bool
     * @throws \Throwable
     */
    public function decrement($key, $value = 1)
    {
        $decrementedValue = $this->redislabs->decrement($key, $value);

        try
        {
            if ($this->ecCluster->get($key) !== null)
            {
               return $this->ecCluster->decrement($key, $value);
            }

            // set the key by getting data from old redis
            // $decrementedValue will be negative value.
            return $this->ecCluster->increment($key, $decrementedValue);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_STORE_ERROR,
                ['keys' => $key]);

            $this->redislabs->increment($key, $value);

            $this->ecCluster->forget($key);

            throw $e;
        }
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws \Throwable
     */
    public function forever($key, $value)
    {
        try
        {
            $this->redislabs->forever($key, $value);

            $this->ecCluster->forever($key, $value);

            return;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_STORE_ERROR,
                ['keys' => $key]);

            $this->redislabs->forget($key);

            $this->ecCluster->forget($key);

            throw $e;
        }
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return void
     * @throws \Throwable
     */
    public function forget($key)
    {
        try
        {
            $this->redislabs->forget($key);

            $this->ecCluster->forget($key);

            return;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_DUAL_WRITE_DELETE_ERROR,
                ['keys' => $key]);

            throw $e;
        }
    }
}
