<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'schedule';

    const WITH_TRASHED = 'deleted';

    protected $appFetchParamRules = [
        self::WITH_TRASHED => 'sometimes|in:0,1',
    ];

    public function fetchSchedulesWithDueRun($timestamp)
    {
        return $this->newQuery()
                    ->where(Entity::NEXT_RUN, '<', $timestamp)
                    ->get();
    }

    protected function addQueryParamDeleted($query, $params)
    {
        if ($params[self::WITH_TRASHED] === '1')
        {
            $query->withTrashed();
        }
    }
}
