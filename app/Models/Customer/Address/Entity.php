<?php

namespace RZP\Models\Customer\Address;

use RZP\Models\Base;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    //const CUSTOMER_ID           = 'customer_id';
    const ADDRESS_LINE_ONE      = 'address_line_one';
    const ADDRESS_LINE_TWO      = 'address_line_two';
    const PINCODE               = 'pincode';
    const CITY                  = 'city';
    const STATE                 = 'state';
    const COUNTRY               = 'country';

    protected static $sign      = 'addr';

    protected $entity           = 'address';

    protected $table            = Table::ADDRESS;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        //self::CUSTOMER_ID,
        self::ADDRESS_LINE_ONE,
        self::ADDRESS_LINE_TWO,
        self::PINCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
    ];

    protected $visible = [
        self::ID,
        //self::CUSTOMER_ID,
        self::ADDRESS_LINE_TWO,
        self::ADDRESS_LINE_ONE,
        self::PINCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ADDRESS_LINE_ONE,
        self::ADDRESS_LINE_TWO,
        self::PINCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
    ];

    protected $defaults = [
        self::ADDRESS_LINE_TWO  => null,
        self::PINCODE           => null,
    ];

    // public function customer()
    // {
    //     return $this->belongsTo('RZP\Models\Customer\Entity');
    // }
}