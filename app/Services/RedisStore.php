<?php

namespace RZP\Services;

use Redis;
use Predis\PredisException;

use RZP\Error\ErrorCode;
use RZP\Models\Base\Redis as RedisEntity;
use RZP\Exception;

class RedisStore
{
    const SORTED_SET = 'sorted_set';

    protected $requestId;

    protected $trace;

    protected $redis;

    protected static $entityClassToDataTypeMap = [
        'RZP\Models\Base\Redis\SortedSet' => self::SORTED_SET
    ];

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->redis = Redis::getFacadeRoot();
    }

    public function save($entity)
    {
        $redisKey = $entity->getRedisKey();

        $dataToSave = $entity->getDataToSave();

        switch (true)
        {
            case $entity instanceof RedisEntity\SortedSet:
                return $this->saveSortedSet($redisKey, $dataToSave);

            default:
                throw new Exception\LogicException('Should not come here');
                break;
        }
    }

    public function fetchData($entity)
    {
        $redisKey = $entity->getRedisKey();

        switch (true)
        {
            case $entity instanceof RedisEntity\SortedSet:
                $entity->setData($this->fetchSortedSetData($redisKey));
                break;

            default:
                # code...
                break;
        }

        return $entity;
    }

    public function removeData($entity, $data)
    {
        $redisKey = $entity->getRedisKey();

        switch (true)
        {
            case $entity instanceof RedisEntity\SortedSet:
                $this->removeSortedSetMembers($redisKey, $data);
                break;

            default:
                # code...
                break;
        }

        return $entity;
    }

    protected function saveSortedSet($redisKey, $dataToSave)
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
                        $this->data);

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
                        "Error removing data from set with key: $redisKey",
                        ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                        $this->data);
        }
    }
}
