<?php

namespace RZP\Models\DataStore\PrioritySet\Implementation;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\DataStore\PrioritySet;
use RZP\Trace\TraceCode;

/**
 * This class is used to store priority set data type in redis
 * More info about sorted set here https://redis.io/topics/data-types-intro
 */
class Redis extends PrioritySet\Base
{
    protected $redis;

    protected $delimiter = ':';

    protected function init()
    {
        $this->redis = $this->app['redis'];
    }

    protected function generateStoreKey()
    {
        return $this->prefix . $this->delimiter . $this->key;
    }

    public function saveOrFail()
    {
        $storeKey = $this->generateStoreKey();

        try
        {
            $this->redis->zadd($storeKey, $this->data);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                    'Error saving to redis',
                    ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                    $this->toArray());
        }

        return $this;
    }

    public function fetchOrFail()
    {
        $storeKey = $this->generateStoreKey();

        $fetchOptions = [
            0,              // starting index from where to fetch
            -1,             // ending index till which to fetch. -1 to denote we want to fetch all members
            'WITHSCORES'    // option to tell redis to return sorted set data with scores
        ];

        try
        {
            $this->data = $this->redis->zrevrange($storeKey, ...$fetchOptions);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                    'Error fetching from redis',
                    ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                    $this->toArray());
        }

        return $this;
    }

    public function deleteOrFail()
    {
        $storeKey = $this->generateStoreKey();

        try
        {
            $this->redis->zrem($storeKey, $this->data);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                    'Error removing data from redis',
                    ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                    $this->toArray());
        }

        return $this;
    }
}
