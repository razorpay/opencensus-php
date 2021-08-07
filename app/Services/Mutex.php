<?php

namespace RZP\Services;

use Illuminate\Support\Facades\Redis;
use Predis\PredisException;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

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

    const PREFIX = 'mutex:';

    protected $acquiredResources;

    public function __construct($app)
    {
        $this->requestId = $app['request']->getId();

        $this->trace = $app['trace'];

        $this->acquiredResources = [];
    }

    /**
     * Set the lock for the all resource provided
     *
     * @param array  $resources Array of the resource
     * @param int    $ttl       Expiry time of lock in seconds
     * @param bool   $strict    defines lock should happen or not, even if one resource is not locked
     * @param string $suffix
     *
     * @return array containing values of locked and not_locked keys
     * @throws Exception\InvalidArgumentException
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
            'locked'   => $lockedResources,
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
     * @param bool   $strict   Block/continue on redis exception
     *
     * @return boolean
     */
    protected function acquireNoWait($resource, $ttl = 60, $strict = false) : bool
    {
        $this->appendPrefix($resource);

        try
        {
            $resourceRedisValue = $this->redis->get($resource);

            $requestId = $this->getRequestIdWithoutCount($resourceRedisValue);

            if ($requestId === $this->requestId)
            {
                $requestId = $this->getRequestIdWithCount($resourceRedisValue);

                $response = $this->redis->set($resource, $requestId, 'ex', $ttl, 'xx');
            }
            else
            {
                $response = $this->redis->set($resource, $this->requestId, 'ex', $ttl, 'nx');
            }
        }
        catch (PredisException $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MUTEX_UNABLE_TO_ACQUIRE,
                [
                    'resource'  => $resource,
                    'ttl'       => $ttl
                ]);

            // Do not block in case of any exception if strict is false
            return ($strict === false);
        }

        /**
         * Do not block the payment if redis returns unexpected response
         * Currently, if a lock is already acquired then the expected
         * response is null
         */
        if ($response !== null)
        {
            $this->trace->info(TraceCode::MUTEX_REDIS_RESPONSE_NOT_NULL,
                [
                    'response' => $response
            ]);

            return true;
        }

        return false;
    }

    /**
     * Acquires lock on a resource
     * Delays between retry is at least 100 milliseconds since anything below
     * that can attributed to i/o and other random delays.
     *
     * @param string $resource      Key on which to acquire lock
     * @param int    $ttl           Time delay before lock is automatically released
     * @param int    $retryCount    Number of times to retry for acquiring lock
     * @param int    $minRetryDelay Minimum time to wait before retry in millisec
     * @param int    $maxRetryDelay Maximum time to wait before retry in millisec
     * @param bool   $strict        Block/continue on redis exception
     *
     * @return bool Whether finally lock was acquired or not
     *
     * @throws Exception\InvalidArgumentException
     */
    public function acquire(
        $resource,
        $ttl = 60,
        $retryCount = 0,
        $minRetryDelay = 100,
        $maxRetryDelay = 200,
        $strict = false) : bool
    {
        // max and min retry delay is in millisec
        if (($retryCount > 0) and
            ($maxRetryDelay - $minRetryDelay < 100))
        {
            throw new Exception\InvalidArgumentException(
                'Retry delay difference between min and max not enough.');
        }

        do
        {
            // Try to acquire lock
            $acquired = $this->acquireNoWait($resource, $ttl, $strict);

            // If acquired then get out of loop
            if ($acquired === true)
            {
                $this->acquiredResources += [$resource => 1];

                break;
            }

            // Insert a random delay
            $delay = mt_rand($minRetryDelay, $maxRetryDelay);

            // usleep works on microsec. delay is in millisec so multiply by 1000
            usleep($delay * 1000);

            // Reduce retry count
            $retryCount--;
        }
        while ($retryCount >= 0);

        return $acquired;
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
        $this->appendPrefix($resource);

        try
        {
            unset($this->acquiredResources[$resource]);

            $resourceValue = $this->redis->get($resource);

            $requestId = $this->getRequestIdWithoutCount($resourceValue);

            if ($requestId === $this->requestId)
            {
                return $this->resetRequestResourceCount($resource, $resourceValue);
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

    public function releaseAllAcquired()
    {
        foreach ($this->acquiredResources as $acquiredResource)
        {
            $this->release($acquiredResource);
        }
    }

    /**
     * @param string   $resource      Key on which to acquire lock
     * @param callable $callback      Callback function
     * @param int      $ttl           Time delay before lock is automatically released
     * @param string   $errorCode     Time delay before lock is automatically released
     * @param int      $retryCount    Number of times to retry for acquiring lock
     * @param int      $minRetryDelay Minimum time to wait before retry in millisec
     * @param int      $maxRetryDelay Maximum time to wait before retry in millisec
     * @param bool     $strict        Block/continue on redis exception
     *
     * @return mixed|null
     *
     * @throws Exception\BadRequestException
     * @throws Exception\InvalidArgumentException
     */
    public function acquireAndRelease(
        $resource,
        callable $callback,
        $ttl = 60,
        $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
        $retryCount = 0,
        $minRetryDelay = 100,
        $maxRetryDelay = 200,
        $strict = false)
    {
        $ret = null;

        $startTime = microtime(true);

        try
        {
            $acquired = $this->acquire(
                $resource, $ttl, $retryCount, $minRetryDelay, $maxRetryDelay, $strict);

            $this->trace->info(TraceCode::MUTEX_REDIS_TIME_TAKEN_TO_ACQUIRE,
                [
                    'resource' => $resource,
                    'acquired' => $acquired,
                    'step_time_taken'  => ( microtime(true) - $startTime) * 1000
                ]
            );

            if ($acquired === false)
            {
                $data = ['resource' => $resource];

                throw new Exception\BadRequestException(
                    $errorCode, null, $data);
            }

            $callbackStartTime = microtime(true);

            $ret = call_user_func($callback);

            $this->trace->info(TraceCode::MUTEX_REDIS_TIME_TAKEN_TO_RETURN,
                [
                    'resource' => $resource,
                    'time_taken'  => ( microtime(true) - $startTime) * 1000,
                    'step_time_taken'   => ( microtime(true) - $callbackStartTime) * 1000,
                ]
            );

            return $ret;
        }
        finally
        {
            $releaseStartTime = microtime(true);

            $released = $this->release($resource);

            if ($released === false)
            {
                $this->trace->error(TraceCode::MUTEX_LOCK_ALREADY_RELEASED);
            }

            $this->trace->info(TraceCode::MUTEX_REDIS_TIME_TAKEN_TO_RELEASE,
                [
                    'resource' => $resource,
                    'time_taken'  => ( microtime(true) - $startTime) * 1000,
                    'step_time_taken'   => ( microtime(true) - $releaseStartTime) * 1000
                ]
            );
        }
    }

    public function acquireAndReleaseStrict($resource,
                                            callable $callback,
                                            $ttl = 60,
                                            $errorCode = ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                                            $retryCount = 0,
                                            $minRetryDelay = 100,
                                            $maxRetryDelay = 200)
    {
        return $this->acquireAndRelease($resource, $callback, $ttl, $errorCode, $retryCount, $minRetryDelay,
            $maxRetryDelay, true);
    }

    public function setRedisClient($client)
    {
        $this->redis = $client;
    }

    protected function appendPrefix(& $key)
    {
        $key = self::PREFIX . $key;
    }

    /**
     * Returns new request id appended with an integer value indicating number of times resource is being locked
     * in current request id
     *
     * @param $requestId
     * @return string
     */
    protected function getRequestIdWithCount($requestId)
    {
        $requestIdArray = explode('_', $requestId);

        $requestCount = $requestIdArray[1] ?? 0;

        $requestId = $requestIdArray[0] . '_' . (++$requestCount);

        return $requestId;
    }

    /**
     * Returns original request id from the given request id.
     * In case resource is locked multiple times, request id will contain integer value also.
     *
     * @param $requestId
     * @return mixed
     */
    protected function getRequestIdWithoutCount($requestId)
    {
        $requestIdArray = explode('_', $requestId);

        return $requestIdArray[0];
    }

    /**
     * Resets the resource count in current request id after release is called.
     * If release is called on resource which is not locked further, delete the resource from redis.
     *
     * @param $resource
     * @param $ttl
     * @param $requestId
     * @return int
     */
    protected function resetRequestResourceCount($resource, $requestId)
    {
        $requestIdArray = explode('_', $requestId);

        $requestCount = $requestIdArray[1] ?? 0;

        try
        {
            if ($requestCount === 0)
            {
                return ($this->redis->del($resource) === 1);
            }
            else
            {
                $requestId = $requestIdArray[0] . '_' . (--$requestCount);

                $ttl = $this->redis->ttl($resource);

                $this->redis->set($resource, $requestId, 'ex', $ttl, 'xx');
            }
        }
        catch (PredisException $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MUTEX_UNABLE_TO_ACQUIRE,
                [
                    'resource'  => $resource,
                ]);
        }

        return true;
    }
}
