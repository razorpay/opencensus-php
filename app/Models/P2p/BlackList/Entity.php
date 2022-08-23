<?php

namespace RZP\Models\P2p\BlackList;

use RZP\Models\P2p\Base;

class Entity extends Base\Entity
{
    use Base\Traits\HasHandle;
    use Base\Traits\HasBankAccount;

    const ENTITY_ID     = 'entity_id';
    const CLIENT_ID     = 'client_id';

    /***************** Input Keys ****************/
    const TYPE = 'type';

    /************** Entity Properties ************/

    protected $entity             = 'p2p_blacklist';
    protected $generateIdOnCreate = true;
    protected static $generators  = [];

    protected $dates = [
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
        Entity::DELETED_AT,
    ];

    protected $fillable = [
        Entity::TYPE,
        Entity::ENTITY_ID,
        Entity::CLIENT_ID,
        Entity::CREATED_AT,
    ];

    protected $visible = [
        Entity::ID,
        Entity::CLIENT_ID,
        Entity::ENTITY_ID,
        Entity::TYPE,
        Entity::CREATED_AT,
    ];

    protected $public = [
        Entity::ID,
        Entity::CLIENT_ID,
        Entity::ENTITY_ID,
        Entity::TYPE,
        Entity::CREATED_AT,
    ];

    protected $casts = [
        Entity::ID          => 'string',
        Entity::CLIENT_ID   => 'string',
        Entity::ENTITY_ID   => 'string',
        Entity::TYPE        => 'string',
        Entity::CREATED_AT  => 'int',
        Entity::UPDATED_AT  => 'int',
        Entity::DELETED_AT  => 'int',
    ];

    protected $defaults = [
        Entity::CLIENT_ID       => '',
        Entity::MERCHANT_ID     => '',
        Entity::DELETED_AT      => 0,
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

}
