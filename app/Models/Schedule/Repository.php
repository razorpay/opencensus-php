<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'schedule';

    public function fetchSchedulesWithDueRun($timestamp)
    {
        return $this->newQuery()
                    ->where(Entity::NEXT_RUN, '<', $timestamp)
                    ->get();
    }
}
