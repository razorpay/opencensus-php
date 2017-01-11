<?php

namespace RZP\Models\Base\Redis;

/**
 * This abstract class defines methods which any entity stored in redis
 * should implement
 */
abstract class Entity
{
    abstract public function getKey();

    abstract public function getRedisKey();

    abstract public function getData();

    abstract public function getDataToSave();

    abstract public function setData(array $data);

    abstract public function toArray();
}
