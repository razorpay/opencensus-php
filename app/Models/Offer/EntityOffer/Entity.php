<?php

namespace RZP\Models\Offer\EntityOffer;

use RZP\Base\Pivot;
use RZP\Constants\Table;

//
// Added for future use in retrieving the
// status, soft deletes, etc, for pivot table
//
class Entity extends Pivot
{
    const ENTITY_ID   = 'entity_id';
    const ENTITY_TYPE = 'entity_type';
    const OFFER_ID    = 'offer_id';
    const CREATED_AT  = 'created_at';
    const UPDATED_AT  = 'updated_at';

    protected $table = Table::ENTITY_OFFER;
}
