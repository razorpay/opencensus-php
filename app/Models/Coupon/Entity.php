<?php

namespace RZP\Models\Coupon;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Constants\Entity as PublicEntity;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ENTITY_ID   = 'entity_id';
    const ENTITY_TYPE = 'entity_type';
    const CODE        = 'code';
    const START_DATE  = 'start_date';
    const END_DATE    = 'end_date';
    const USAGE       = 'usage';
    const USED_COUNT  = 'used_count';
    const DELETED_AT  = 'deleted_at';

    protected $entity = 'coupon';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::CODE,
        self::START_DATE,
        self::END_DATE,
        self::USAGE,
    ];

    protected $visible = [
        self::ID,
        self::CODE,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::MERCHANT_ID,
        self::USAGE,
        self::START_DATE,
        self::END_DATE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::USED_COUNT => 0,
    ];

    protected $casts = [
        self::START_DATE => 'int',
        self::END_DATE   => 'int',
        self::USAGE      => 'int',
        self::USED_COUNT => 'int',
    ];

    protected static $modifiers = [
        self::ENTITY_ID,
    ];

    /**
     * Creates a polymorphic relation with entities
     * implementing a morphMany association on the
     * 'entity' key
     */
    public function source()
    {
        return $this->morphTo('source', self::ENTITY_TYPE, self::ENTITY_ID);
    }

    protected function modifyEntityId(array & $input)
    {
        $entityClass = PublicEntity::getEntityClass($input[Entity::ENTITY_TYPE]);

        $entityClass::verifyIdAndSilentlyStripSign($input[Entity::ENTITY_ID]);
    }

    public function getUsage()
    {
        return $this->getAttribute(self::USAGE);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getUsedCount()
    {
        return $this->getAttribute(self::USED_COUNT);
    }

    public function getStartDate()
    {
        return $this->getAttribute(self::START_DATE);
    }

    public function getEndDate()
    {
        return $this->getAttribute(self::END_DATE);
    }

    public function setUsedCount(int $count)
    {
        return $this->setAttribute(self::USED_COUNT, $count);
    }

    public function incrementUsedCount()
    {
        $this->increment(self::USED_COUNT);
    }
}
