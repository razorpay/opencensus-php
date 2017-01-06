<?php

namespace RZP\Models\Base\Redis;

use Redis;

class SortedSet
{
    protected static $keyPrefix = '';

    protected static $delimioter = '';

    protected $key;

    protected $data;

    protected $redis;

    public function __construct()
    {
        $this->redis = Redis::getFacadeRoot();

        $this->key = '';

        $this->data = [];
    }

    public function save()
    {
        $redisKey = $this->generateRedisKey();

        $dataToSave = $this->getFormattedData();

        $result = $this->redis->zadd($redisKey, ...$dataToSave);

        return $result >= 0 ? true : false;
    }

    public function toArray()
    {
        return [
            $this->key => $this->data
        ];
    }

    protected function fetchMembers($withScores = true, $descending = true)
    {
        $redisKey = $this->generateRedisKey();

        $fetchOptions = $this->generateFetchOptions($withScores);

        if ($descending === true)
        {
            $this->data = $this->redis->zrevrange($redisKey, ...$fetchOptions);
        }
        else
        {
            $this->data = $this->zrange($redisKey, ...$fetchOptions);
        }
    }

    protected function removeMembers(array $members)
    {
        $redisKey = $this->generateRedisKey();

        $this->redis->zrem($redisKey, ...$members);
    }

    protected function generateFetchOptions($withScores)
    {
        $options = [
            'startIndex' => 0,
            'endIndex' => -1,
            'withScores' => 'WITHSCORES'
        ];

        if ($withScores === false)
        {
            unset($options['withScores']);
        }

        return array_values($options);
    }

    protected function generateRedisKey()
    {
        return static::$keyPrefix . static::$delimiter . $this->key;
    }

    protected function getFormattedData()
    {
        $formattedData = [];

        foreach ($this->data as $item => $score)
        {
            $formattedData[] = $score;
            $formattedData[] = $item;
        }

        return $formattedData;
    }
}
