<?php

namespace Models\Settlement;

use Models\Base;
use Models\Settlement;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Settlement';

    public function getSettlementForToday()
    {
        $timestamp = Settlement\Daily::getTodayTimestamp();

        return Settlement\Daily::findOrFail($timestamp);
    }
}