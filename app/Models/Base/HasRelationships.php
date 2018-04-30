<?php

namespace RZP\Models\Base;

use RZP\Models\Base\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use RZP\Models\Base\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasRelationships as BaseHasRelationships;

trait HasRelationships
{
    use BaseHasRelationships;

    /**
     * Instantiate a new BelongsTo relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $child
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    protected function newBelongsTo(Builder $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        return new BelongsTo($query, $child, $foreignKey, $ownerKey, $relation);
    }
}
