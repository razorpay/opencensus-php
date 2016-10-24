<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'schedule';

    public function getByIdAndMerchantId($id, $ownerId)
    {
        return $this->newQuery()
                    ->where(Entity::ID, '=', $id)
                    ->where(Entity::MERCHANT_ID, '=', $ownerId)
                    ->firstOrFail();
    }
}
