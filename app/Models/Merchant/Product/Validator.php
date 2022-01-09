<?php

namespace RZP\Models\Merchant\Product;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        'product_name' => 'required|string|custom',
        'tnc_accepted' => 'sometimes|boolean|in:1',
    ];

    public function validateProductName($attribute, $value)
    {
        $validProductName = (in_array($value, Name::ENABLED, true) === true);

        if ($validProductName === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PRODUCT_NAME, null, ['valid_product_names' => Name::ENABLED]);
        }
    }
}
