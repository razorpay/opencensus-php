<?php

namespace RZP\Models\Pincode\ZipcodeDirectory;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $zipcodeDirectoryRules = [
        'zipcodes'                    => 'required',
    ];

    protected static $createRules = [
        Entity::STATE_CODE            => 'required|string|between:0,16',
        Entity::CITY                  => 'required|string|between:0,64',
        Entity::ZIPCODE               => 'required|string|between:0,12',
        Entity::STATE                 => 'required|string|between:0,64',
        Entity::COUNTRY               => 'required|string|between:0,16',
    ];

    protected static $editRules = [
        Entity::STATE_CODE            => 'required|string|between:0,16',
        Entity::CITY                  => 'required|string|between:0,64',
        Entity::ZIPCODE               => 'required|string|between:0,12',
        Entity::STATE                 => 'required|string|between:0,64',
        Entity::COUNTRY               => 'required|string|between:0,16',
    ];

    public function __construct($entity = null)
    {
        parent::__construct($entity);
    }

    public static function validateZipcodeDirectory($input)
    {
        (new static)->validateInput('zipcodeDirectory', $input);
    }
}
