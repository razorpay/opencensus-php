<?php

namespace RZP\Base;

use RZP\Error\ErrorCode;
use RZP\Models\Vpa\Entity;
use RZP\Exception\BadRequestException;

class VpaValidator
{
    const MIN_LENGTH = '3';

    const MAX_LENGTH = '100';

    const VPA_REGEX = '/^[a-zA-Z0-9][a-zA-Z0-9\.-]*@[a-zA-Z]+$/';

    public function validateVpa($address)
    {
        if ((preg_match(self::VPA_REGEX, $address) === 0) or
            (strlen($address) > self::MAX_LENGTH) or
            (strlen($address) < self::MIN_LENGTH))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
                Entity::ADDRESS,
                [
                    'vpa' => $address
                ]);
        }
    }
}
