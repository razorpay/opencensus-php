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

    public function getSetMembers()
    {
        return (empty($this->data) === true) ? null : array_keys($this->data);
    }
}
