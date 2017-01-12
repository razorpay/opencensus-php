<?php

namespace RZP\Models\Store;

use Predis\PredisException;

use RZP\Error\ErrorCode;
use RZP\Exception;

/**
 * This class is used to store a sorted set data type in redis
 * More info about sorted set here https://redis.io/topics/data-types-intro
 */
class PriorityStore extends Base
{
    protected $keyPrefix;

    protected $key;

    protected $data;

    protected static $delimiter = ':';

    public function __construct(string $keyPrefix, string $key)
    {
        parent::__construct();

        $this->key = $key;

        $this->keyPrefix = $keyPrefix;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getKeyPrefix()
    {
        return $this->keyPrefix;
    }

    public function generateStoreKey()
    {
        return  $this->keyPrefix . self::$delimiter . $this->key;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function toArray()
    {
        return [
            $this->key => $this->data
        ];
    }

    /**
     * Returns the members of the sorted set ordered by socre.
     *
     * @return array Sorted set members or null if data is null
     */
    public function getSetMembers()
    {
        return (is_array($this->data) === true) ? array_keys($this->data) : $this->data;
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
                        "Error saving to redis sorted set with key: $storeKey",
                        ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                        $this->data);

        }

        return $this;
    }

    public function fetch()
    {
        $storeKey = $this->generateStoreKey();

        $fetchOptions = array_values([
            'startIndex' => 0,
            'endIndex'   => -1,                 // end index is -1 to denote we want to fetch all members
            'withScores' => 'WITHSCORES'        // option to tell redis to return sorted set data with scores
        ]);

        try
        {
            $this->data = $this->redis->zrevrange($storeKey, ...$fetchOptions);
        }
        catch (PredisException $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                        "Error fetching from redis sorted set with key: $storeKey",
                        ErrorCode::SERVER_ERROR_REDIS_EXCEPTION);
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
                        "Error removing data from sorted set with key: $storeKey",
                        ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                        $this->data);
        }

        return $this;
    }
}
