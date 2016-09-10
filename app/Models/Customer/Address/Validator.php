<?php

namespace RZP\Models\Customer\Address;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ADDRESS_TYPE    => 'required|custom',
        Entity::LINE_ONE        => 'required|string|between:10,1024',
        Entity::LINE_TWO        => 'sometimes|string|between:5,1024',
        Entity::CITY            => 'sometimes|string|between:2,128',
        Entity::PINCODE         => 'sometimes|string|between:2,32',
        Entity::STATE           => 'required|string|between:2,128',
        // TODO: Should we add a validator for the country, now? Or later?
        Entity::COUNTRY         => 'required|string|between:2,128',
        Entity::PRIMARY         => 'sometimes|in:0,1',
    ];

    protected static function validateAddressType($attribute, $value)
    {
        Type::validateAddressType($value, Type::CUSTOMER);
    }
}