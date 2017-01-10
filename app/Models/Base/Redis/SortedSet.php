<?php

namespace RZP\Models\Base\Redis;

use App;
use Redis;

use Predis\PredisException;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;

/**
 * This class is used to store a sorted set data type in redis
 * More info about sorted set here https://redis.io/topics/data-types-intro
 */
class SortedSet extends RedisEntity
{
    // Variable to store the redis namespace for a sorted set.
    // Should be overriden in child class
    protected static $keyPrefix = '';

    // Used to form the redis key. Should be overriden in child class.
    protected static $delimiter = '';

    protected $key;

    protected $data;

    public function __construct(string $key)
    {
        $this->key = $key;

        $this->data = [];
    }

    public function getKey()
    {
        return $this->key;
    }

    /**
     * Generates the key which is actually stored in redis
     *
     * @return string Key stored in redis
     */
    public function getRedisKey()
    {
        return static::$keyPrefix . static::$delimiter . $this->key;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Predis methods accept redis data in a numerically indexed array.
     * For sorted set the format is [<score>, <member_name>, ...]
     *
     * @return array data to be passed to predis zadd method
     */
    public function getDataToSave()
    {
        $formattedData = [];

        foreach ($this->data as $item => $score)
        {
            $formattedData[] = $score;
            $formattedData[] = $item;
        }

        return $formattedData;
    }

    /**
     * Predis returns the data as associative array which is what we want (Great Stuff)
     * So we store it as is is
     */
    public function setData(array $redisData)
    {
        $this->data = $redisData;
    }

    public function toArray()
    {
        return [
            $this->key => $this->data
        ];
    }

    /**
     * Returns the members of the sorted set in sorted order.
     * @return array Sorted set members or null if data is empty array
     */
    public function getSetMembers()
    {
        return (empty($this->data) === true) ? null : array_keys($this->data);
    }
}
