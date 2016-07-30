<?php

namespace RZP\Models\Base;

use RZP\Base\Repository as BaseRepository;

class Repository extends BaseRepository
{
    public function fetchEntitiesForReport($merchantId, $from, $to)
    {
        return $this->fetchBetweenTimestampWithRelations($merchantId, $from, $to);
    }
}