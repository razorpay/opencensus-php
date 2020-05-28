<?php

namespace RZP\Services;;

use Throwable;
use SessionHandlerInterface;
use Illuminate\Contracts\Cache\Repository;

use RZP\Constants\Metric;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class CustomSessionHandler implements SessionHandlerInterface
{
    /**
     * The cache repository instance.
     *
     * @var Repository
     */
    protected $cache;
    protected $ecCluster;

    /**
     * The number of minutes to store the data in the cache.
     *
     * @var int
     */
    protected $minutes;

    public function __construct($app)
    {
        $this->cache = $app['cache'];
        $this->trace = $app['trace'];
        $this->ecCluster = $app['cache']->driver('session_with_ec_cluster');
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
            $response = $this->ecCluster->get($sessionId, '');

            if (empty($response) === false)
            {
                return $response;
            }

            $response = $this->cache->get($sessionId, '');

            if (empty($response) === false)
            {
                $this->trace->count(Metric::SESSIONS_REDIS_CLUSTER_MISS, []);

                $this->write($sessionId, $response);
            }

            if (empty($response) === true)
            {
                $this->trace->count(Metric::SESSIONS_REDIS_READ_MISS, []);
            }

            return $response;
        }
        catch (Throwable $e)
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
            $this->ecCluster->put($sessionId, $data, $this->minutes);

            return $this->cache->put($sessionId, $data, $this->minutes);
        }
        catch (Throwable $e)
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
            $this->ecCluster->forget($sessionId);

            return $this->cache->forget($sessionId);
        }
        catch (Throwable $e)
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
     * @return Repository
     */
    public function getCache()
    {
        return $this->cache;
    }
}
