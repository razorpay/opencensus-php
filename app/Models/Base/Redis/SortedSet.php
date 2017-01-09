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

    public function __construct(string $key)
    {
        $app = App::getFacadeRoot();

        $this->key = $key;
        $this->trace = $app['trace'];
    }

    public function toArray()
    {
        return [
            $this->key => $this->data
        ];
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

    public function getRedisKey()
    {
        return static::$keyPrefix . static::$delimiter . $this->key;
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
}
