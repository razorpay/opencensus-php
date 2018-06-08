<?php

namespace RZP\Models\Offer\EntityOffer;

use Illuminate\Database\Eloquent\Relations\Pivot;

use RZP\Constants\Table;
use RZP\Models\Base\PublicCollection;

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

    //
    // Below functions are implemented for most
    // entities in EloquentEx or Base\PublicEntity
    // Pivot is not a child of these, but these funcs are
    // needed when we want to treat it like an entity anyway
    // Eg. in fixtures for tests, or for admin fetch routes
    //

    public function getTable()
    {
        return Table::ENTITY_OFFER;
    }

    public function newCollection(array $models = [])
    {
        return new PublicCollection($models);
    }

    public function toArrayAdmin()
    {
        return $this->toArray();
    }
}
