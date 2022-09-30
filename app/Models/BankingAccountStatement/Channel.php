<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Models\Settlement;
use RZP\Exception\BadRequestValidationFailureException;

class Channel
{
    const RBL     = Settlement\Channel::RBL;
    const ICICI   = Settlement\Channel::ICICI;
    const AXIS    = Settlement\Channel::AXIS;
    const YESBANK = Settlement\Channel::YESBANK;

    protected static $channels = [
        self::RBL,
        self::ICICI,
        self::AXIS,
        self::YESBANK,
    ];

    /**
     * This array has a list of channels for which transactions
     * should not be created through reversals
     * @var array
     */
    protected static $skipTxnCreation = [
        self::RBL,
        self::ICICI,
        self::AXIS,
        self::YESBANK,
    ];

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

    public static function shouldSkipTransaction(string $channel): bool
    {
        self::validate($channel);

        return in_array($channel, self::$skipTxnCreation, true);
    }

    public static function getAll(): array
    {
        return self::$channels;
    }
}
