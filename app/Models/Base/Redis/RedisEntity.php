<?php

namespace RZP\Models\Base\Redis;

class RedisEntity
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
        return $this->data;
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
}
