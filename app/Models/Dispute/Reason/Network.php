<?php

namespace RZP\Models\Dispute\Reason;

class Network
{
    const VISA       = 'Visa';
    const MASTERCARD = 'Mastercard';
    const JCB        = 'JCB';
    const DISCOVER   = 'Discover';
    const AMEX       = 'Amex';
    const RZP        = 'RZP';

    protected static $networksList = [
        self::VISA,
        self::MASTERCARD,
        self::JCB,
        self::DISCOVER,
        self::AMEX,
        self::RZP,
    ];

    public static function exists(string $network): bool
    {
        return defined(get_class() . '::' . strtoupper($network));
    }

    public static function isValid(string $network): bool
    {
        return (in_array($network, self::$networksList, true) === true);
    }
}
