<?php

namespace RZP\Models\Address;

use RZP\Constants\Country;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::TYPE    => 'required|custom',
        Entity::LINE1   => 'required|string|between:10,1024',
        Entity::LINE2   => 'sometimes|string|between:5,1024',
        Entity::CITY    => 'sometimes|string|between:2,128',
        Entity::ZIPCODE => 'sometimes|string|between:2,32',
        Entity::STATE   => 'sometimes|string|between:2,128',
        Entity::COUNTRY => 'sometimes|string|between:2,128|custom',
        Entity::PRIMARY => 'sometimes|in:0,1',
    ];

    protected function validateType($attribute, $value)
    {
        Type::validateType($value, Type::CUSTOMER);
    }

    protected function validateCountry($attribute, $value)
    {
        $isValid = Country::checkIfValidCountry($value);

        if ($isValid === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_COUNTRY, null, [$value]);
        }
    }
}