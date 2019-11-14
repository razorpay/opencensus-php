<?php

namespace RZP\Models\Vpa;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    const MIN_LENGTH = '3';

    const MAX_LENGTH = '100';

    const VPA_REGEX = '/^[a-zA-Z0-9][a-zA-Z0-9\.-]*@[a-zA-Z]+$/';

    protected static $createRules = [
        Entity::ADDRESS => 'required|string|custom',
    ];

    public function validateAddress(string $attribute, string $address)
    {
        if ((preg_match(self::VPA_REGEX, $address) === 0) or
            (strlen($address) > self::MAX_LENGTH) or
            (strlen($address) < self::MIN_LENGTH))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
                $attribute,
                [
                    'vpa' => $address
                ]);
        }
    }
}
