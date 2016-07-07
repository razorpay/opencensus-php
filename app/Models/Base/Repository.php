<?php

namespace RZP\Models\Base;

use RZP\Base\Repository as BaseRepository;
use Illuminate\Support\Facades\App;
use Trace\TraceCode;

class Repository extends BaseRepository
{
    public function fetchEntitiesForReport($merchantId, $from, $to)
    {
        return $this->fetchBetweenTimestampWithRelations($merchantId, $from, $to);
    }
}