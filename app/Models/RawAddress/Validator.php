<?php

namespace RZP\Models\RawAddress;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Models\Admin\File;
use RZP\Models\Address;
use RZP\Constants\Country;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const ADDRESSES   = 'addresses';
    const STATUS_CODE = 'statusCode';
    const MESSAGE     = 'message';
    const SOURCE = 'source';

    protected static $createRules = [
        Entity::CONTACT          => 'sometimes|contact_syntax',
        Entity::STATUS           => 'sometimes|string|in:pending,invalid',
        Entity::NAME             => 'sometimes|string|between:2,64',
        Entity::LINE1            => 'sometimes|string|between:0,255',
        Entity::LINE2            => 'sometimes|string|between:0,255',
        Entity::CITY             => 'sometimes|string|between:0,100',
        Entity::ZIPCODE          => 'sometimes|string|between:0,12',
        Entity::STATE            => 'sometimes|string|between:0,100',
        Entity::COUNTRY          => 'sometimes|string|between:0,100|custom',
        Entity::TAG              => 'sometimes|string|between:0,50',
        Entity::LANDMARK         => 'sometimes|string|between:0,255',
        Entity::MERCHANT_ID      => 'required|alpha_num|size:14',
        Entity::BATCH_ID         => 'sometimes|alpha_num|size:14',
    ];

    protected static $createForAddressRules = [
        Entity::NAME             => 'required|string|between:2,64',
        Entity::CONTACT          => 'required|contact_syntax',
        Address\Entity::TYPE     => 'sometimes|string',
        Entity::LINE1            => 'required|string|between:1,255',
        Entity::LINE2            => 'sometimes|string|between:1,255',
        Entity::CITY             => 'required|string|between:2,32',
        Entity::ZIPCODE          => 'required|string|between:2,10',
        Entity::STATE            => 'required|string|between:2,32',
        Entity::COUNTRY          => 'required|string|between:2,64|custom',
        Entity::TAG              => 'sometimes|string|between:2,32',
        Entity::LANDMARK         => 'sometimes|string|between:2,32',
        Address\Entity::PRIMARY  => 'sometimes|in:0,1',
    ];

    protected static $bulkCreateForAddressRules = [
        self::ADDRESSES => 'required|array|min:1',
        self::SOURCE => 'sometimes|string|in:woocommerce',
    ];

    protected static $processKafkaMessageRules = [
        Entity::CONTACT     => 'required|contact_syntax',
        self::ADDRESSES     => 'required|array',
        self::STATUS_CODE   => 'sometimes|in:200,400,500',
        self::MESSAGE       => 'sometimes|string',
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
