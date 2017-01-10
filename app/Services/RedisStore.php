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
    protected $trace;

    protected $redis;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->redis = Redis::getFacadeRoot();
    }

    /**
     * Saves given RedisEntity data to redis
     *
     * @param  RedisEntity $entity Entity to save
     *
     * @return bool status to denote if save was successful
     */
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

    /**
     * Fetches data for entity from redis and stores it in entity
     *
     * @param  RedisEntity $entity Entity for which to fetch redis data
     */
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

    /**
     * Removes entity data from redis
     *
     * @param  RedisEntity $entity Entity for which to remove data
     * @param  array       $data   Data to be removed
     */
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
                        "Error saving to redis sorted set with key: $redisKey",
                        ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                        $dataToSave);

        }
        return $result >= 0 ? true : false;
    }

    protected function fetchSortedSetData(string $key)
    {
        $fetchOptions = array_values([
            'startIndex' => 0,
            'endIndex'   => -1,                 // end index is -1 to denote we want to fetch all members
            'withScores' => 'WITHSCORES'        // option to tell redis to return sorted set data with scores
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
                        "Error removing data from sorted set with key: $key",
                        ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                        $members);
        }
    }
}
