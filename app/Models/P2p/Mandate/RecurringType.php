<?php

namespace RZP\Models\P2p\Mandate;

/**
 * Class RecurringType
 *
 * @package RZP\Models\P2p\Mandate
 */
class RecurringType
{
    const ONETIME       = 'ONETIME';
    const DAILY         = 'DAILY';
    const WEEKLY        = 'WEEKLY';
    const FORTNIGHTLY   = 'FORTNIGHTLY';
    const MONTHLY       = 'MONTHLY';
    const BIMONTHLY     = 'BIMONTHLY';
    const QUARTERLY     = 'QUARTERLY';
    const HALFYEARLY    = 'HALFYEARLY';
    const YEARLY        = 'YEARLY';
    const ASPRESENTED   = 'ASPRESENTED';

    /**
     * Set of allowed Recurring Types
     *
     * @var string[]
     */
    public static $allowed = [
        self::ONETIME,
        self::DAILY,
        self::WEEKLY,
        self::FORTNIGHTLY,
        self::MONTHLY,
        self::BIMONTHLY,
        self::QUARTERLY,
        self::HALFYEARLY,
        self::YEARLY,
        self::ASPRESENTED,
    ];

    /**
     * Checks if the given key is a valid RecurringType
     *
     * @param string $key
     *
     * @return bool
     */
    public static function isValid(string $key): bool
    {
        return (defined(static::class.'::'.strtoupper($key)) === true);
    }
}
