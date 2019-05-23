<?php

namespace RZP\Models\P2p\Beneficiary;

use RZP\Models\P2p\Base;

class Entity extends Base\Entity
{
    use Base\Traits\HasDevice;

    const DEVICE_ID    = 'device_id';
    const ENTITY_TYPE  = 'entity_type';
    const ENTITY_ID    = 'entity_id';
    const NAME         = 'name';

    /***************** Input Keys ****************/
    const BENEFICIARY  = 'beneficiary';
    const TYPE         = 'type';
    const VALIDATED    = 'validated';
    const BLOCKED      = 'blocked';
    const SPAMMED      = 'spammed';
    const BLOCKED_AT   = 'blocked_at';

    /************** Entity Properties ************/

    protected $entity             = 'p2p_beneficiary';
    protected $generateIdOnCreate = true;
    protected static $generators  = [];

    protected $dates = [
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::ENTITY_TYPE,
        Entity::ENTITY_ID,
        Entity::NAME,
    ];

    protected $visible = [
        Entity::ID,
        Entity::DEVICE_ID,
        Entity::ENTITY_TYPE,
        Entity::ENTITY_ID,
        Entity::NAME,
        Entity::CREATED_AT,
    ];

    protected $public = [
        Entity::ID,
        Entity::ENTITY_TYPE,
        Entity::ENTITY_ID,
        Entity::NAME,
        Entity::CREATED_AT,
    ];

    protected $defaults = [
        Entity::ENTITY_TYPE  => null,
        Entity::ENTITY_ID    => null,
        Entity::NAME         => null,
    ];

    protected $casts = [
        Entity::ID           => 'string',
        Entity::DEVICE_ID    => 'string',
        Entity::ENTITY_TYPE  => 'string',
        Entity::ENTITY_ID    => 'string',
        Entity::NAME         => 'string',
        Entity::CREATED_AT   => 'int',
        Entity::UPDATED_AT   => 'int',
    ];

    /***************** SETTERS *****************/

    /**
     * @return $this
     */
    public function setDeviceId(string $deviceId)
    {
        return $this->setAttribute(self::DEVICE_ID, $deviceId);
    }

    /**
     * @return $this
     */
    public function setEntityType(string $entityType)
    {
        return $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }

    /**
     * @return $this
     */
    public function setEntityId(string $entityId)
    {
        return $this->setAttribute(self::ENTITY_ID, $entityId);
    }

    /**
     * @return $this
     */
    public function setName(string $name)
    {
        return $this->setAttribute(self::NAME, $name);
    }

    /***************** GETTERS *****************/

    /**
     * @return string self::DEVICE_ID
     */
    public function getDeviceId()
    {
        return $this->getAttribute(self::DEVICE_ID);
    }

    /**
     * @return string self::ENTITY_TYPE
     */
    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    /**
     * @return string self::ENTITY_ID
     */
    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    /**
     * @return string self::NAME
     */
    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function beneficiary()
    {
        return $this->morphTo(self::ENTITY);
    }

    public function toArrayPublic()
    {
        return $this->beneficiary ? $this->beneficiary->toArrayBeneficiary() : null;
    }
}
