<?php

namespace RZP\Models\P2p\Complaint;

use RZP\Models\P2p\Base;

/**
 * Class Entity
 * Complaint Entity which will be used in case of UDIR
 * @package RZP\Models\P2p\Complaint
 */
class Entity extends Base\Entity
{
    const MERCHANT_ID    = 'merchant_id';
    const GATEWAY_DATA   = 'gateway_data';
    const META           = 'meta';
    const ENTITY_ID      = 'entity_id';
    const ENTITY_TYPE    = 'entity_type';
    const CLOSED_AT      = 'closed_at';
    const CRN            = 'crn';

    /************ Constants ************/
    const ENTITY         = 'entity';
    const TRANSACTION    = 'transaction';
    const COMPLAINT      = 'complaint';
    const ACTION         = 'action';
    const ADJAMOUNT      = 'adjAmount';
    const ADJFLAG        = 'adjFlag';
    const ADJCODE        = 'adjCode';
    const REQ_ADJ_AMOUNT =  'reqAdjAmount';
    const REQ_ADJ_FLAG   =  'reqAdjFlag';
    const REQ_ADJ_CODE   =  'reqAdjCode';

    protected $entity    = 'p2p_complaint';

    protected static $generators  = [];
    protected $dates = [
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
        Entity::CLOSED_AT
    ];

    protected $fillable = [
        Entity::CRN,
        Entity::GATEWAY_DATA,
        Entity::META,
        Entity::ENTITY_ID,
        Entity::ENTITY_TYPE,
        Entity::CREATED_AT,
        Entity::CLOSED_AT,
        Entity::UPDATED_AT,
    ];

    protected $public = [
        Entity::CRN,
        Entity::GATEWAY_DATA,
        Entity::META,
        Entity::ENTITY_ID,
        Entity::ENTITY_TYPE,
        Entity::CREATED_AT,
        Entity::CLOSED_AT,
        Entity::UPDATED_AT,
    ];

    protected $casts = [
        Entity::ID           => 'string',
        Entity::MERCHANT_ID  => 'string',
        Entity::CRN          => 'string',
        Entity::GATEWAY_DATA => 'array',
        Entity::META         => 'array',
        Entity::ENTITY_ID    => 'string',
        Entity::ENTITY_TYPE  => 'string',
        Entity::CREATED_AT   => 'int',
        Entity::UPDATED_AT   => 'int',
        Entity::CLOSED_AT    => 'int',
    ];


    /***************** GETTERS *****************/

    /**
     * @return false|string
     */
    public function getP2pEntityName()
    {
        $entity = $this->entity;

        if (starts_with($this->entity, 'p2p_'))
        {
            $entity = substr($this->entity, 4);
        }

        return $entity;
    }

    /**
     * @return $this|string
     */
    public function getEntity()
    {
        return $this;
    }

    /**
     * @return string self::ENTITY_ID
     */
    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    /**
     * @return string self::ENTITY_TYPE
     */
    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function toArrayPublic(): array
    {
        $array = parent::toArrayPublic();

        return $array;
    }

    public function setEntityType($entityType)
    {
        $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }

    public function setEntityId($entityId)
    {
        $this->setAttribute(self::ENTITY_ID, $entityId);
    }
}
