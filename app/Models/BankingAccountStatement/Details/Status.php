<?php

namespace RZP\Models\BankingAccountStatement\Details;

use RZP\Exception;

class Status
{
    const ACTIVE = 'active';
    const INACTIVE = 'inactive';

    public static function getStatuses()
    {
        return [
            self::ACTIVE,
            self::INACTIVE,
        ];
    }

    public static function validate(string $status = null)
    {
        if (in_array($status, self::getStatuses(), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid status: ' . $status);
        }
    }
}
