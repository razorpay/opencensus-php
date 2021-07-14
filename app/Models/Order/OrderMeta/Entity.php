<?php

namespace RZP\Models\Order\OrderMeta;

use RZP\Models\Base;

/**
 * Class Entity
 *
 * @package RZP\Models\Order\OrderMeta
 */
class Entity extends Base\PublicEntity
{
    const ID            = 'id';
    const ORDER_ID      = 'order_id';
    const TYPE          = 'type';
    const VALUE         = 'value';

    protected $generateIdOnCreate = true;

    protected $entity   = 'order_meta';

    protected $fillable = [
        self::ORDER_ID,
        self::TYPE,
        self::VALUE,
    ];

    protected $visible  = [
        self::ID,
        self::ORDER_ID,
        self::TYPE,
        self::VALUE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public   = [
        self::ID,
        self::ORDER_ID,
        self::TYPE,
        self::VALUE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $dates     = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts     = [
        self::VALUE => 'array',
    ];

    public function toArrayTrace(): array
    {
        return array_only($this->toArray(), [
            self::ID,
            self::ORDER_ID,
            self::TYPE,
            self::VALUE,
        ]);
    }

    /*************** Getters *******************/

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getValue()
    {
        return $this->getAttribute(self::VALUE);
    }

    public function getOrderId()
    {
        return $this->getAttribute(self::ORDER_ID);
    }
}

