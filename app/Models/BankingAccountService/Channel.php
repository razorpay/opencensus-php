<?php

namespace RZP\Models\BankingAccountService;

use RZP\Models\Settlement\Channel as FTAChannel;
use RZP\Exception\BadRequestValidationFailureException;

class Channel
{
    const ICICI   = FTAChannel::ICICI;
    const YESBANK = FTAChannel::YESBANK;
    const AXIS    = FTAChannel::AXIS;
    const RBL     = FTAChannel::RBL; // RBL on BAS

    protected static $channels = [
        self::ICICI,
        self::YESBANK,
        self::AXIS,
    ];

    protected static $directTypeChannels = [
        self::ICICI,
        self::YESBANK,
        self::AXIS,
    ];

    public static function getDirectTypeChannels(): array
    {
        return self::$directTypeChannels;
    }

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
