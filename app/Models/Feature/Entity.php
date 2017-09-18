<?php

namespace RZP\Models\Feature;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const NAME        = 'name';
    const ENTITY_ID   = 'entity_id';
    const ENTITY_TYPE = 'entity_type';

    // Input attributes
    const NAMES       = 'names';
    const SHOULD_SYNC = 'should_sync';

    // Keys used for tracing requests
    const MERCHANT_ID  = 'merchant_id';
    const OLD_FEATURES = 'old_features';
    const NEW_FEATURE  = 'new_feature';
    const FEATURE      = 'feature';

    protected $table = \RZP\Constants\Table::FEATURE;

    protected $entity = 'feature';

    // We are explicitly generating Id so that same Id gets stored in live and test db
    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::NAME,
        self::ENTITY_ID,
        self::ENTITY_TYPE
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::ENTITY_ID,
        self::ENTITY_TYPE
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::ENTITY_ID,
        self::ENTITY_TYPE
    ];

    protected static $modifiers = [
        self::NAME,
    ];

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    /**
     * Creates a polymorphic relation with entities
     * implementing a morphMany association on the
     * 'entity' key
     */
    public function entity()
    {
        return $this->morphTo();
    }

    protected function modifyName(& $input)
    {
        if (isset($input[self::NAME]) === true)
        {
            $input[self::NAME] = strtolower($input[self::NAME]);
        }
    }
}
