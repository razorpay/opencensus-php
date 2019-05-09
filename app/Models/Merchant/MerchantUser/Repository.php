<?php

namespace RZP\Models\Merchant\MerchantUser;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::MERCHANT_USER;

    //
    // Default order defined in RepositoryFetch is created_at, id
    // Overriding here because pivot table does not have an id col.
    //
    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }
}
