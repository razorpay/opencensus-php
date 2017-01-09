<?php

namespace RZP\Services;

use Redis;
use Predis\PredisException;

use RZP\Error\ErrorCode;
use RZP\Models\Base\Redis as RedisModel;
use RZP\Models\Base\Redis\RedisEntity;
use RZP\Exception;

class RedisStore
{
    protected $requestId;

    protected $trace;

    protected $redis;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->redis = Redis::getFacadeRoot();
    }

    public function save(RedisEntity $entity)
    {
        $redisKey = $entity->getRedisKey();

        $dataToSave = $entity->getDataToSave();

        switch (true)
        {
            case $entity instanceof RedisModel\SortedSet:
                return $this->saveSortedSet($redisKey, $dataToSave);

            default:
                throw new Exception\LogicException('Should not come here');
                break;
        }
    }

    public function fetchEntityData(RedisEntity $entity)
    {
        $redisKey = $entity->getRedisKey();

        switch (true)
        {
            case $entity instanceof RedisModel\SortedSet:
                $entity->setData($this->fetchSortedSetData($redisKey));
                break;

            default:
                # code...
                break;
        }

        return $entity;
    }

    public function removeEntityData(RedisEntity $entity, array $data)
    {
        $redisKey = $entity->getRedisKey();

        switch (true)
        {
            case $entity instanceof RedisModel\SortedSet:
                $this->removeSortedSetMembers($redisKey, $data);
                break;

            default:
                # code...
                break;
        }

        return $entity;
    }

    protected function saveSortedSet(string $redisKey, array $dataToSave)
    {
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
                        $dataToSave);

        }
        return $result >= 0 ? true : false;
    }

    protected function fetchSortedSetData(string $key)
    {
        $fetchOptions = array_values([
            'startIndex' => 0,
            'endIndex'   => -1,
            'withScores' => 'WITHSCORES'
        ]);

        try
        {
            return $this->redis->zrevrange($key, ...$fetchOptions);
        }
        catch (PredisException $e)
        {
            $this->trace->traceException($e);

            return [];
        }
    }

    protected function removeSortedSetMembers(string $key, array $members)
    {
        try
        {
            $this->redis->zrem($key, ...$members);
        }
        catch (PredisException $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                        "Error removing data from set with key: $key",
                        ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                        $members);
        }
    }
}
