<?php

namespace RZP\Models\Customer\Address;

use RZP\Models\Base;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    const ENTITY_ID             = 'entity_id';
    const ENTITY_TYPE           = 'entity_type';
    const ADDRESS_TYPE          = 'address_type';
    const PRIMARY               = 'primary';
    const LINE_ONE              = 'address_line_one';
    const LINE_TWO              = 'address_line_two';
    const PINCODE               = 'pincode';
    const CITY                  = 'city';
    const STATE                 = 'state';
    const COUNTRY               = 'country';
    const DELETED_AT            = 'deleted_at';

    protected static $sign      = 'addr';

    protected $entity           = 'address';

    protected $table            = Table::ADDRESS;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::LINE_ONE,
        self::LINE_TWO,
        self::PINCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
    ];

    protected $visible = [
        self::ID,
        self::LINE_ONE,
        self::LINE_TWO,
        self::PINCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::LINE_ONE,
        self::LINE_TWO,
        self::PINCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
    ];

    protected $defaults = [
        self::LINE_TWO  => null,
        self::PINCODE           => null,
    ];

    // public function customer()
    // {
    //     return $this->belongsTo('RZP\Models\Customer\Entity');
    // }

    // public function source()
    // {
    //     $entityType = $this->getAttribute(self::ENTITY_TYPE);
    //
    //     Type::validateType($entityType);
    //
    //     $class = 'RZP\\Models\\';
    //
    //     if ($type === Transaction\Type::REFUND)
    //     {
    //         $class .= 'Payment\\';
    //     }
    //
    //     $class .= ucfirst($type).'\\'.'Entity';
    //
    //     return $this->belongsTo($class, self::ENTITY_ID);
    // }
}