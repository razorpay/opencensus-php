<?php

namespace RZP\Base;

use Illuminate\Database\Eloquent\Relations;

use RZP\Models\Base\PublicCollection;

class Pivot extends Relations\Pivot
{
    const CREATED_AT  = 'created_at';
    const UPDATED_AT  = 'updated_at';

    //
    // Below functions are implemented for most
    // entities in EloquentEx or Base\PublicEntity
    // Pivot is not a child of these, but these funcs are
    // needed when we want to treat it like an entity anyway
    // Eg. in fixtures for tests, or for admin fetch routes
    //

    public function getTable(): string
    {
        return $this->table;
    }

    public function newCollection(array $models = []): PublicCollection
    {
        return new PublicCollection($models);
    }

    public function toArrayAdmin(): array
    {
        return $this->toArray();
    }
}
