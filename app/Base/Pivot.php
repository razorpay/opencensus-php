<?php

namespace RZP\Base;

use Illuminate\Database\Eloquent\Relations;

use Carbon\Carbon;
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

    /**
     * Used in HasTimestamps for updateTimestamps
     */
    public function freshTimestamp()
    {
        return Carbon::now()->getTimestamp();
    }

    /**
     * Used in freshTimestampString, in turn used in BelongsToMany for touch
     */
    public function fromDateTime($value)
    {
        return $value;
    }


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

    public function getCreatedAtAttribute()
    {
        return (int) $this->attributes[self::CREATED_AT];
    }

    public function getUpdatedAtAttribute()
    {
        return (int) $this->attributes[self::UPDATED_AT];
    }
}
