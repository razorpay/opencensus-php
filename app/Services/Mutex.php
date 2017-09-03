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
    protected function acquireNoWait($resource, $ttl = 60) : bool
    {
        $redis = Redis::getFacadeRoot();

        try
        {
            $response = $redis->set($resource, $this->requestId, 'ex', $ttl, 'nx');
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
     * Acquires lock on a resource
     * Delays between retry is at least 100 milliseconds since anything below
     * that can attributed to i/o and other random delays.
     *
     * @param string $resource      Key on which to acquire lock
     * @param int    $ttl           Time delay before lock is automatically released
     * @param int    $retryCount    Number of times to retry for acquiring lock
     * @param int    $minRetryDelay Minimum time to wait before retry in millisec
     * @param int    $maxRetryDelay Maximum time to wait before retry in millisec
     *
     * @return bool Whether finally lock was acquired or not
     * @throws Exception\InvalidArgumentException
     */
    public function acquire(
        $resource,
        $ttl = 60,
        $retryCount = 0,
        $minRetryDelay = 100,
        $maxRetryDelay = 200) : bool
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
            $acquired = $this->acquireNoWait($resource, $ttl);

            // If acquired then get out of loop
            if ($acquired === true)
            {
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

    public function acquireAndRelease(
        $resource,
        callable $callback,
        $ttl = 60,
        $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
        $retryCount = 0,
        $minRetryDelay = 100,
        $maxRetryDelay = 200)
    {
        $ret = null;

        try
        {
            $acquired = $this->acquire(
                $resource, $ttl, $retryCount, $minRetryDelay, $maxRetryDelay);

            if ($acquired === false)
            {
                $data = ['resource' => $resource];

                throw new Exception\BadRequestException(
                    $errorCode, null, $data);
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
