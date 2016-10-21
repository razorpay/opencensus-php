<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'schedule';

    public function getScheduleByIdAndOwnerId($id, $ownerId)
    {
        return $this->newQuery()
                    ->where(Entity::ID, '=', $id)
                    ->where(Entity::OWNER_ID, '=', $ownerId)
                    ->first();
    }
}
