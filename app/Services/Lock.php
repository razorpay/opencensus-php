<?php

namespace RZP\Services;

use Redis;
use Request;
use Predis\PredisException;

/**
 * The below lock implementation is based on single-instance redis redlock algorithm
 * as detailed here - http://redis.io/topics/distlock
 *
 * SETNX - This command is crucial to lock implementation.
 *         Man page - http://redis.io/commands/setnx
 */
class Lock
{
    protected $requestId;

    protected $redis;

    public function __construct($app)
    {
        $this->requestId = $app['request']->getId();

        $this->redis = $app['redis'];

        $this->trace = $app['trace'];
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
        try
        {
            $response = $this->redis->set($resource, $this->requestId, 'ex', $ttl, 'nx');
        }
        catch (PredisException $e)
        {
            $this->trace->traceException($e);

            // Do not block the payment in case of any exception
            return true;
        }

        /**
         * Do not block the payment if redis returns unexpected response
         * Currently, if a lock is already acquired then the expected
         * response is null
         */
        if ($response !== null)
        {
            return true;
        }

        return false;
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
        try
        {
            if (($this->redis->get($resource) === $this->requestId) and
                ($this->redis->del($resource) === 1))
            {
                return true;
            }
        }
        catch (PredisException $e)
        {
            $this->trace->traceException($e);

            // Do not block the payment in case of any exception
            return true;
        }

        return false;
    }
}