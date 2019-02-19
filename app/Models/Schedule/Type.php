<?php

namespace RZP\Models\Schedule;

class Type
{
    const SETTLEMENT       = 'settlement';

    const SUBSCRIPTION     = 'subscription';

    const PROMOTION        = 'promotion';

    const REPORTING        = 'reporting';

    const TYPE_LIST = [
        self::PROMOTION,
        self::REPORTING,
        self::SETTLEMENT,
        self::SUBSCRIPTION,
    ];

    public static function isTypeValid(string $type):bool
    {
        return (in_array($type, self::TYPE_LIST, true) === true);
    }
}
