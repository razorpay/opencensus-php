<?php

namespace RZP\Models\Address;

use RZP\Base;
use RZP\Constants\Country;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base\UniqueIdEntity;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME     => 'sometimes|string|between:2,64',
        Entity::CONTACT  => 'sometimes|contact_syntax',
        Entity::TYPE     => 'required|string',
        Entity::LINE1    => 'required|string|between:10,255',
        Entity::LINE2    => 'sometimes|string|between:5,255|custom',
        Entity::CITY     => 'sometimes|string|between:2,32',
        Entity::ZIPCODE  => 'sometimes|string|between:2,10',
        Entity::STATE    => 'required|string|between:2,64',
        Entity::COUNTRY  => 'sometimes|string|between:2,64|custom',
        Entity::TAG      => 'sometimes|string|between:2,32',
        Entity::LANDMARK => 'sometimes|string|between:2,255',
        Entity::PRIMARY  => 'sometimes|in:0,1',
    ];

    protected static $createForPaymentRules = [
        Entity::TYPE    => 'required|string',
        Entity::LINE1   => 'required|string|between:1,255',
        Entity::LINE2   => 'sometimes|string|between:1,255|custom',
        Entity::CITY    => 'required|string|between:2,32',
        Entity::ZIPCODE => 'sometimes|between:2,10|regex:/^(?=.*[0-9])[A-Za-z0-9\s]*$/',
        Entity::STATE   => 'sometimes|string|between:2,64',
        Entity::COUNTRY => 'required|string|between:2,64|custom',
        Entity::PRIMARY => 'sometimes|in:0,1',
        Entity::NAME    => 'sometimes|string|between:2,64',
    ];

    protected static $editRules = [
        UniqueIdEntity::ID  => 'sometimes|string',
        Entity::CONTACT     => 'sometimes|contact_syntax',
        Entity::NAME        => 'sometimes|string|between:2,64',
        Entity::LINE1       => 'sometimes|string|between:10,255',
        Entity::LINE2       => 'sometimes|string|between:5,255|custom',
        Entity::CITY        => 'sometimes|string|between:2,32',
        Entity::ZIPCODE     => 'sometimes|string|between:2,10',
        Entity::STATE       => 'sometimes|string|between:2,32',
        Entity::COUNTRY     => 'sometimes|string|between:2,64|custom',
        Entity::TAG         => 'sometimes|string|between:2,32',
        Entity::LANDMARK    => 'sometimes|string|between:2,255',
        Entity::PRIMARY     => 'sometimes|in:0,1',
    ];

    protected static $createForCustomerRules = [
        Entity::NAME     => 'sometimes|string|between:2,64',
        Entity::CONTACT  => 'sometimes|contact_syntax',
        Entity::TYPE     => 'sometimes|string',
        Entity::LINE1    => 'required|string|between:1,255',
        Entity::LINE2    => 'sometimes|string|between:1,255',
        Entity::CITY     => 'sometimes|string|between:2,32',
        Entity::ZIPCODE  => 'sometimes|string|between:0,16',
        Entity::STATE    => 'required|string|between:2,64',
        Entity::COUNTRY  => 'required|string|between:2,64|custom',
        Entity::TAG      => 'sometimes|string|between:2,32',
        Entity::LANDMARK => 'sometimes|string|between:2,255',
        Entity::PRIMARY  => 'sometimes|in:0,1',
        Entity::SOURCE_ID => 'sometimes',
        Entity::SOURCE_TYPE => 'sometimes|in:bulk_upload,payment_pages,thirdwatch,shopify,woocommerce',
    ];

    protected static $editForCustomerRules = [
        UniqueIdEntity::ID  => 'required|string',
        Entity::NAME        => 'sometimes|string|between:2,64',
        Entity::CONTACT     => 'sometimes|contact_syntax',
        Entity::TYPE        => 'sometimes|string',
        Entity::LINE1       => 'required|string|between:1,255',
        Entity::LINE2       => 'sometimes|string|between:1,255',
        Entity::CITY        => 'sometimes|string|between:2,32',
        Entity::ZIPCODE     => 'sometimes|string|between:0,16',
        Entity::STATE       => 'required|string|between:2,64',
        Entity::COUNTRY     => 'required|string|between:2,64|custom',
        Entity::TAG         => 'sometimes|string|between:2,32',
        Entity::LANDMARK    => 'sometimes|string|between:2,255',
        Entity::PRIMARY     => 'sometimes|in:0,1',
    ];

    protected static $codServiceabilityCheckRules = [
      Entity::NAME             => 'sometimes|string|between:2,64',
      Entity::CONTACT          => 'sometimes|contact_syntax',
      Entity::TYPE             => 'required|string',
      Entity::LINE1            => 'required|string|between:1,255',
      Entity::LINE2            => 'sometimes|string|between:1,255',
      Entity::CITY             => 'sometimes|string|between:2,32',
      Entity::ZIPCODE          => 'sometimes|string|between:0,16',
      Entity::STATE            => 'required|string|between:2,64',
      Entity::COUNTRY          => 'required|string|between:2,64|custom',
      Entity::TAG              => 'sometimes|string|between:2,32',
      Entity::LANDMARK         => 'sometimes|string|between:2,255',
      Entity::PRIMARY          => 'sometimes|in:0,1',
    ];

    protected static $createFor1ccOrderRules = [
        Entity::NAME             => 'sometimes|string|between:2,64',
        Entity::CONTACT          => 'sometimes|contact_syntax',
        Entity::TYPE             => 'required|string',
        Entity::ZIPCODE          => 'sometimes|string|between:0,16',
        Entity::STATE            => 'required_with:country|string|between:2,64',
        Entity::COUNTRY          => 'required_with:state|string|between:2,64|custom',
        Entity::CITY             => 'sometimes|string|between:2,32',
        Entity::TAG              => 'sometimes|string|between:2,32',
        Entity::LANDMARK         => 'sometimes|string|between:2,255',
        Entity::LINE1            => 'sometimes|string|between:1,255',
        Entity::LINE2            => 'sometimes|string|between:1,255',
        Entity::PRIMARY          => 'sometimes|in:0,1',
    ];

    protected static $addressCodScoreResponseRules = [
        "score"        => 'required|numeric',
        "label"        => 'required|string',
        "id"           => 'required|string',
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

    /*
     * Added a custom error message for Line2 entity
     */
    protected function validateLine2($attribute, $value)
    {
        if((strlen($value) > 255 )|| (strlen($value) < 1))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                Entity::LINE2,
                [$value],
            "Address Line 2 must be between 1 and 255 characters.");
        }
    }
}
