<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Settlement\Channel as FTAChannel;
use RZP\Exception\BadRequestValidationFailureException;

class Channel
{
    const YESBANK = FTAChannel::YESBANK;
    const RBL     = FTAChannel::RBL;

    public static function isValid(string $channel): bool
    {
        $key = __CLASS__ . '::' . strtoupper($channel);

        return ((defined($key) === true) and (constant($key) === $channel));
    }

    public static function validateChannel(string $channel)
    {
        if (self::isValid($channel) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid channel: ' . $channel);
        }
    }

    public static function getAll(): array
    {
        return [
            self::YESBANK,
            self::RBL,
        ];
    }
}
