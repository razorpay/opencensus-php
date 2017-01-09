<?php

namespace RZP\Models\Base\Redis;

use App;
use Redis;

use Predis\PredisException;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;

class SortedSet
{
    protected static $keyPrefix = '';

    protected static $delimioter = '';

    protected $key;

    protected $data;

    protected $redis;

    protected $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->redis = Redis::getFacadeRoot();

        $this->trace = $app['trace'];
    }

    public function save()
    {
        $redisKey = $this->generateRedisKey();

        $dataToSave = $this->getFormattedData();

        try
        {
            $result = $this->redis->zadd($redisKey, ...$dataToSave);
        }
        catch (PredisException $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                        "Error saving to redis with key: $redisKey",
                        ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                        $this->data);

        }
        return $result >= 0 ? true : false;
    }

    public function toArray()
    {
        return [
            $this->key => $this->data
        ];
    }

    protected function fetchMembers($descending = true)
    {
        $redisKey = $this->generateRedisKey();

        $fetchOptions = [
            'startIndex' => 0,
            'endIndex'   => -1,
            'withScores' => 'WITHSCORES'
        ];

        try
        {
            if ($descending === true)
            {
                $this->data = $this->redis->zrevrange($redisKey, ...$fetchOptions);
            }
            else
            {
                $this->data = $this->zrange($redisKey, ...$fetchOptions);
            }
        }
        catch (PredisException $e)
        {
            $this->trace->traceException($e);
        }
    }

    protected function removeMembers(array $members)
    {
        $redisKey = $this->generateRedisKey();

        try
        {
            $this->redis->zrem($redisKey, ...$members);
        }
        catch (PredisException $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                        "Error removing data from set with key: $redisKey",
                        ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                        $this->data);
        }
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
