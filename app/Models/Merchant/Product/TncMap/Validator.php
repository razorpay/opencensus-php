<?php

namespace RZP\Models\Merchant\Product\TncMap;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Product\Name;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PRODUCT_NAME => 'required|string|custom',
        Entity::CONTENT      => 'required|array',
    ];

    protected static $editRules   = [
        Entity::STATUS  => 'sometimes|string|custom',
        Entity::CONTENT => 'sometimes|array',
    ];

    protected static $fetchRules = [
        Entity::PRODUCT_NAME => 'required|string|custom',
        Entity::STATUS       => 'sometimes|string|custom',
    ];

    public function validateProductName($attribute, $value)
    {
        $validProductName = (in_array($value, Name::ADMIN_ENABLED, true) === true);

        if ($validProductName === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PRODUCT_NAME, null, ['valid_product_names' => Name::ADMIN_ENABLED]);
        }
    }

    public function validateStatus($attribute, $value)
    {
        $validStatus = (in_array($value, Constants::STATUS, true) === true);

        if ($validStatus === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TNC_STATUS_INVALID, null, ['valid_status' => Constants::STATUS]);
        }
    }
}
