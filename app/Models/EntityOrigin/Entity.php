<?php

namespace RZP\Models\EntityOrigin;

use RZP\Constants;
use RZP\Models\Base;
use Illuminate\Database\Eloquent\Relations;

class Entity extends Base\PublicEntity
{
    const ID          = 'id';
    const ENTITY_ID   = 'entity_id';
    const ORIGIN_ID   = 'origin_id';
    const ENTITY_TYPE = 'entity_type';
    const ORIGIN_TYPE = 'origin_type';

    protected $entity = Constants\Entity::ENTITY_ORIGIN;

    protected $generateIdOnCreate = true;

    protected $visible = [
        self::ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::ORIGIN_ID,
        self::ORIGIN_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $fillable = [
        self::ENTITY_ID,
        self::ENTITY_TYPE,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::ENTITY_ID,
        self::ORIGIN_ID,
        self::ENTITY_TYPE,
        self::ORIGIN_TYPE,
        self::CREATED_AT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    // --------------- Relation to other entities ------------------------------

    /**
     * @return Relations\MorphTo
     */
    public function entity()
    {
        return $this->morphTo();
    }

    /**
     * Defines a polymorphic relation with entities implementing a morphOne association on the 'origin' key.
     *
     * @return Relations\MorphTo
     */
    public function origin()
    {
        return $this->morphTo();
    }
}
