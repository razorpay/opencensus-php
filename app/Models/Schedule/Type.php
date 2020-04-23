<?php

namespace RZP\Models\Schedule;

class Type
{
    const SETTLEMENT       = 'settlement';

    const SUBSCRIPTION     = 'subscription';

    const PROMOTION        = 'promotion';

    const REPORTING        = 'reporting';

    const FEE_RECOVERY     = 'fee_recovery';

    const TYPE_LIST = [
        self::PROMOTION,
        self::REPORTING,
        self::SETTLEMENT,
        self::SUBSCRIPTION,
        self::FEE_RECOVERY,
    ];

    public static function isTypeValid(string $type):bool
    {
        return (in_array($type, self::TYPE_LIST, true) === true);
    }
}
