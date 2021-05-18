<?php

namespace RZP\Models\Merchant\Product\Request;

use RZP\Models\Base;
use RZP\Models\Merchant\Product;

class Entity Extends Base\PublicEntity
{

    const ID                    = 'id';
    const MERCHANT_PRODUCT_ID   = 'merchant_product_id';
    const REQUESTED_ENTITY_TYPE = 'requested_entity_type';
    const REQUESTED_ENTITY_ID   = 'requested_entity_id';
    const REQUESTED_CONFIG      = 'requested_config';
    const CONFIG_TYPE           = 'config_type';
    const STATUS                = 'status';

    protected $entity = 'merchant_product_request';

    protected $generateIdOnCreate = true;

    protected static $generators = [self::ID];

    protected $fillable = [
        self::MERCHANT_PRODUCT_ID,
        self::REQUESTED_ENTITY_TYPE,
        self::REQUESTED_ENTITY_ID,
        self::REQUESTED_CONFIG,
        self::CONFIG_TYPE,
        self::STATUS,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_PRODUCT_ID,
        self::REQUESTED_ENTITY_TYPE,
        self::REQUESTED_ENTITY_ID,
        self::REQUESTED_CONFIG,
        self::CONFIG_TYPE,
        self::STATUS,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::REQUESTED_CONFIG => 'array'
    ];

    public function merchantProduct()
    {
        return $this->belongsTo(Product\Entity::class);
    }

}

