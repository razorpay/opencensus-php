<?php

namespace RZP\Models\BankingAccountService;

use RZP\Models\Settlement\Channel as FTAChannel;
use RZP\Exception\BadRequestValidationFailureException;

class Channel
{
    const ICICI   = FTAChannel::ICICI;

    protected static $channels = [
        self::ICICI,
    ];

    protected static $directTypeChannels = [
        self::ICICI,
    ];

    public static function isValid(string $channel = null): bool
    {
        $key = __CLASS__ . '::' . strtoupper($channel);

        return ((defined($key) === true) and (constant($key) === $channel));
    }

    public static function isValidDirectTypeChannel(string $channel = null): bool
    {
        self::validateChannel($channel);

        return (in_array($channel, self::$directTypeChannels, true) === true);
    }

    public static function validateChannel(string $channel = null)
    {
        if (self::isValid($channel) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid channel: ' . $channel,
                Constants::CHANNEL,
                [Constants::CHANNEL => $channel]);
        }
    }

    public static function getAll(): array
    {
        return self::$channels;
    }

}
