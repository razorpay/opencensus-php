<?php

namespace RZP\Models\Offer\EntityOffer;

use Illuminate\Database\Eloquent\Relations\Pivot;

//
// Added for future use in retrieving the
// status, soft deletes, etc, for pivot table
//
class Entity extends Pivot
{
    const ENTITY_ID   = 'entity_id';
    const ENTITY_TYPE = 'entity_type';
    const OFFER_ID    = 'offer_id';
    const CREATED_AT  = 'created_At';
    const UPDATED_AT  = 'updated_at';
}
