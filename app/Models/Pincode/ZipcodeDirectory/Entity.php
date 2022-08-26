<?php

namespace RZP\Models\Pincode\ZipcodeDirectory;

use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const DELETED_AT            = 'deleted_at';
    const ZIPCODE               = 'zipcode';
    const COUNTRY               = 'country';
    const STATE                 = 'state';
    const STATE_CODE            = 'state_code';
    const CITY                  = 'city';

    protected $entity           = 'zipcode_directory';

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID,
    ];

    protected $fillable = [
        self::ZIPCODE,
        self::CITY,
        self::STATE,
        self::STATE_CODE,
        self::COUNTRY,
    ];


    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::ZIPCODE,
        self::CITY,
        self::STATE,
        self::STATE_CODE,
        self::COUNTRY,
    ];

    protected $defaults = [
        self::STATE_CODE  => "",
        self::ZIPCODE => "",
        self::CITY => "",
        self::STATE => "",
        self::COUNTRY => "",
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
    ];

}
