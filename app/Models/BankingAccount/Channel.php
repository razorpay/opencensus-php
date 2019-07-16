<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Settlement\Channel as FTAChannel;
use RZP\Exception\BadRequestValidationFailureException;

class Channel
{
    const YESBANK = FTAChannel::YESBANK;
    const RBL     = FTAChannel::RBL;

    protected static $channels = [
        self::YESBANK,
        self::RBL,
    ];

    public static function isValid(string $channel = null): bool
    {
        $key = __CLASS__ . '::' . strtoupper($channel);

        return ((defined($key) === true) and (constant($key) === $channel));
    }

    public static function validateChannel(string $channel = null)
    {
        if (self::isValid($channel) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid channel: ' . $channel,
                Entity::CHANNEL,
                [Entity::CHANNEL => $channel]);
        }
    }

    public static function getAll(): array
    {
        return self::$channels;
    }
}
