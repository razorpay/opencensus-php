<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Models\Settlement;
use RZP\Exception\BadRequestValidationFailureException;

class Channel
{
    const RBL = Settlement\Channel::RBL;

    public static function isValid(string $channel): bool
    {
        $key = __CLASS__ . '::' . strtoupper($channel);

        return ((defined($key) === true) and (constant($key) === $channel));
    }

    public static function validate(string $channel)
    {
        if (self::isValid($channel) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid channel: ' . $channel);
        }
    }
}
