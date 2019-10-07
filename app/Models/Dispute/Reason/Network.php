<?php

namespace RZP\Models\Dispute\Reason;

class Network
{
    const RZP        = 'RZP';
    const JCB        = 'JCB';
    const AMEX       = 'Amex';
    const VISA       = 'Visa';
    const RUPAY      = 'RuPay';
    const MAESTRO    = 'Maestro';
    const DISCOVER   = 'Discover';
    const UNIONPAY   = 'Unionpay';
    const MASTERCARD = 'Mastercard';

    protected static $networksList = [
        self::RZP,
        self::JCB,
        self::AMEX,
        self::VISA,
        self::RUPAY,
        self::MAESTRO,
        self::DISCOVER,
        self::UNIONPAY,
        self::MASTERCARD,
    ];

    public static function exists(string $network): bool
    {
        return defined(get_class() . '::' . strtoupper($network));
    }

    /**
     * Returns Network in the format stored in DB
     *
     * @param string $network
     * @return string
     */
    public static function getNetwork(string $network): string
    {
        return constant(get_class() . '::' . strtoupper($network));
    }

    public static function isValid(string $network): bool
    {
        return (in_array($network, self::$networksList, true) === true);
    }

    public static function list(): array
    {
        return self::$networksList;
    }
}
