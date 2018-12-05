<?php

namespace RZP\Services;;

use Illuminate\Contracts\Cache\Repository as CacheContract;

use RZP\Constants\Metric;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;


class CustomSessionHandler implements \SessionHandlerInterface
{
    /**
     * The cache repository instance.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;
    protected $redisLabs;

    /**
     * The number of minutes to store the data in the cache.
     *
     * @var int
     */
    protected $minutes;

    /**
     * Create a new cache driven handler instance.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @param  int  $minutes
     * @return void
     */
    public function __construct($app)
    {
        $this->cache = $app['cache'];
        $this->trace = $app['trace'];
        $this->redisLabs = $app['cache']->driver('session');
        $this->minutes = $app['config']->get('session.lifetime');
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        try
        {
            $response = $this->redisLabs->get($sessionId, '');

            if (empty($response) === false)
            {
                return $response;
            }

            $response = $this->cache->get($sessionId, '');

            if (empty($response) === false)
            {
                $this->trace->count(Metric::SESSIONS_REDIS_LABS_MISS, []);

                $this->write($sessionId, $response);
            }

            return $response;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_READ_ERROR,
                ['key' => $sessionId]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        try
        {
            $this->redisLabs->put($sessionId, $data, $this->minutes);

            return $this->cache->put($sessionId, $data, $this->minutes);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_STORE_ERROR,
                ['key' => $sessionId]);

            $this->destroy($sessionId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        try
        {
            $this->redisLabs->forget($sessionId);

            return $this->cache->forget($sessionId);
        }
        catch (\Throwable $e)
        {
           $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REDIS_SESSION_DELETE_ERROR,
                ['key' => $sessionId]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
        return true;
    }

    /**
     * Get the underlying cache repository.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function getCache()
    {
        return $this->cache;
    }
}
