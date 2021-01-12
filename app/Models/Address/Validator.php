<?php

namespace RZP\Models\Address;

use RZP\Base;
use RZP\Constants\Country;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::TYPE    => 'required|string',
        Entity::LINE1   => 'required|string|between:10,255',
        Entity::LINE2   => 'sometimes|string|between:5,255',
        Entity::CITY    => 'sometimes|string|between:2,32',
        Entity::ZIPCODE => 'sometimes|string|between:2,10',
        Entity::STATE   => 'required|string|between:2,32',
        Entity::COUNTRY => 'required|string|between:2,64|custom',
        Entity::PRIMARY => 'sometimes|in:0,1',
    ];

    protected static $createForPaymentRules = [
        Entity::TYPE    => 'required|string',
        Entity::LINE1   => 'required|string|between:1,255',
        Entity::LINE2   => 'sometimes|string|between:5,255',
        Entity::CITY    => 'required|string|between:2,32',
        Entity::ZIPCODE => 'sometimes|between:2,10',
        Entity::STATE   => 'sometimes|string|between:2,32',
        Entity::COUNTRY => 'required|string|between:2,64|custom',
        Entity::PRIMARY => 'sometimes|in:0,1',
    ];

    protected static $editRules = [
        Entity::LINE1   => 'sometimes|string|between:10,255',
        Entity::LINE2   => 'sometimes|string|between:5,255',
        Entity::CITY    => 'sometimes|string|between:2,32',
        Entity::ZIPCODE => 'sometimes|string|between:2,10',
        Entity::STATE   => 'sometimes|string|between:2,32',
        Entity::COUNTRY => 'sometimes|string|between:2,64|custom',
        Entity::PRIMARY => 'sometimes|in:0,1',
    ];

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
