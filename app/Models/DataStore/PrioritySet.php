<?php

namespace RZP\Models\DataStore;

use Predis\PredisException;

use RZP\Error\ErrorCode;
use RZP\Exception;

/**
 * This class is used to store a sorted set data type in redis
 * More info about sorted set here https://redis.io/topics/data-types-intro
 */
class PrioritySet extends Base
{
    public function __construct(string $prefix, string $key)
    {
        parent::__construct();

        $this->key = $key;

        $this->prefix = $prefix;
    }

    protected static $delimiter = ':';

    /**
     * Returns the members of the sorted set ordered by socre.
     *
     * @return array Sorted set members or null if data is null
     */
    public function getSetMembers()
    {
        if (empty($this->data) === true)
        {
            return null;
        }

        return array_keys($this->data);
    }

    /**
     * Returns members with positive scores. If no such members are present
     * then returns null
     */
    public function getSetMembersWithPositiveScore()
    {
        if (empty($this->data) === true)
        {
            return null;
        }

        $result = array_keys(array_filter($this->data));

        return ((empty($result) === true) ? null : $result);
    }

    public function saveOrFail()
    {
        $storeKey = $this->generateStoreKey();

        try
        {
            $this->redis->zadd($storeKey, $this->data);
        }
        catch (PredisException $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                    'Error saving to redis sorted set',
                    ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                    $this->toArray());
        }

        return $this;
    }

    public function fetch()
    {
        $storeKey = $this->generateStoreKey();

        $fetchOptions = [
            0,              // starting index from where to fetch
            -1,             // ending index till which to fetch. -1 to denote we want to fetch all members
            'WITHSCORES'    // option to tell redis to return sorted set data with scores
        ];

        try
        {
            $this->data = $this->redis->zrevrange($storeKey, $fetchOptions);
        }
        catch (PredisException $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                    'Error fetching from redis sorted set',
                    ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                    $this->key);
        }

        return $this;
    }

    public function delete()
    {
        $storeKey = $this->generateStoreKey();

        try
        {
            $this->redis->zrem($storeKey, $this->data);
        }
        catch (PredisException $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                    'Error removing data from sorted set',
                    ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                    $this->toArray());
        }

        return $this;
    }
}
