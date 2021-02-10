<?php

namespace RZP\Models\BankingAccountStatement\Details;

use RZP\Exception;

class Channel
{
    const RBL = 'rbl';

    public static function getChannels()
    {
        return [
            self::RBL,
        ];
    }

    public static function validate(string $channel = null)
    {
        if (in_array($channel, self::getChannels(), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid channel name: ' . $channel);
        }
    }
}
