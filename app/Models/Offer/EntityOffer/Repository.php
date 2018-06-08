<?php

namespace RZP\Models\Offer\EntityOffer;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::ENTITY_OFFER;

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }
}
