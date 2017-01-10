<?php

namespace RZP\Models\Base\Redis;

use App;
use Redis;

use Predis\PredisException;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;

class SortedSet extends RedisEntity
{
    protected static $keyPrefix = '';

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

    public function getRedisKey()
    {
        return static::$keyPrefix . static::$delimiter . $this->key;
    }

    public function getData()
    {
        return $this->data;
    }

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

    public function getSetMembers()
    {
        return (empty($this->data) === true) ? null : array_keys($this->data);
    }
}
