<?php

namespace RZP\Models\VirtualAccountProducts;

use RZP\Constants;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const VIRTUAL_ACCOUNT_ID            = 'virtual_account_id';
    const ENTITY_TYPE                   = 'entity_type';
    const ENTITY_ID                     = 'entity_id';

    protected static $sign = 'vap';

    protected $entity = Constants\Entity::VIRTUAL_ACCOUNT_PRODUCTS;

    protected $primaryKey = self::ID;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::VIRTUAL_ACCOUNT_ID,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
    ];

    protected $visible = [
        self::ID,
        self::VIRTUAL_ACCOUNT_ID,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];
    // -------------------- Associations --------------------

    public function virtualAccount()
    {
        return $this->belongsTo('RZP\Models\VirtualAccount\Entity');
    }

    public function entity()
    {
        return $this->morphTo();
    }

    // -------------------- End Associations --------------------

}
