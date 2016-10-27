<?php

namespace RZP\Services;

use Redis;
use Predis\PredisException;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

/**
 * The below lock implementation is based on single-instance redis redlock algorithm
 * as detailed here - http://redis.io/topics/distlock
 *
 * SETNX - This command is crucial to lock implementation.
 *         Man page - http://redis.io/commands/setnx
 */
class Mutex
{
    protected $requestId;

    protected $redis;

    public function __construct($app)
    {
        $this->requestId = $app['request']->getId();

        $this->trace = $app['trace'];

    }

    /**
     * Set the lock for the all resource provided
     *
     * @param array $resources Array of the resource
     * @param int $ttl Expiry time of lock in seconds
     * @param bool $strict defines lock should happen or not, even if one resource is not locked
     * @param string $suffix
     *
     * @return array containing values of locked and not_locked keys
     */
    public function acquireMultiple($resources, $ttl = 60, $suffix = '', $strict = false)
    {
        $lockedResources = [];

        $alreadyLockedResources = [];

        foreach ($resources as $resource)
        {
            $resourceWithSuffix = $resource . $suffix;
            $isLockAcquired = $this->acquire($resourceWithSuffix, $ttl);

            if ($isLockAcquired === true)
            {
                $lockedResources[] = $resource;
            }
            else
            {
                if ($strict === true)
                {
                    $this->releaseMultiple($lockedResources, $suffix);

                    return [
                        'locked' => [],
                        'unlocked' => $resources
                    ];
                }

                $alreadyLockedResources[] = $resource;
            }
        }

        return [
            'locked' => $lockedResources,
            'unlocked' => $alreadyLockedResources
        ];
    }

    /**
     * Release the lock for the resource array provided
     *
     * @param array $resources Array of the resource
     *
     * @return void
     */
    public function releaseMultiple($resources, $suffix = '')
    {
        foreach ($resources as $resource)
        {
            $resourceWithSuffix = $resource . $suffix;

            $this->release($resourceWithSuffix);
        }
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
        $redis = Redis::getFacadeRoot();

        try
        {
            $response = $redis->set($resource, $this->requestId, 'ex', $ttl, 'nx');
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
        $redis = Redis::getFacadeRoot();

        try
        {
            if (($redis->get($resource) === $this->requestId) and
                ($redis->del($resource) === 1))
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

    public function acquireAndRelease($resource, callable $callback, $ttl = 60)
    {
        $ret = null;

        try
        {
            $acquired = $this->acquire($resource, $ttl);

            if ($acquired === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);
            }

            $ret = call_user_func($callback);

            return $ret;
        }
        finally
        {
            $released = $this->release($resource);

            if ($released === false)
            {
                $this->trace->error(TraceCode::MUTEX_LOCK_ALREADY_RELEASED);
            }
        }
    }
}
