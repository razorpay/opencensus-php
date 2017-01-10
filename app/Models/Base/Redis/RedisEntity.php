<?php

namespace RZP\Models\Base\Redis;

abstract class RedisEntity
{
    abstract public function getKey();

    abstract public function getRedisKey();

    abstract public function getData();

    abstract public function getDataToSave();

    abstract public function setData(array $redisData);

    abstract public function toArray();
}
