<?php

namespace RZP\Models\Merchant\Balance\LowBalanceConfig;

use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const ENABLED  = 'enabled';
    const DISABLED = 'disabled';

    public static function isValid(string $status): bool
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }

    public static function validate($status)
    {
        if (self::isValid($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid Status', // TODO:to decide on error message
                Entity::STATUS,
                [
                    Entity::STATUS => $status
                ]);
        }
    }
}
