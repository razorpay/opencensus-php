<?php

namespace RZP\Models\PartnerBankHealth;

class Events
{
    const FAIL_FAST_HEALTH = 'fail_fast_health';
    const DOWNTIME         = 'downtime';

    public static function getPartnerBankHealthEvents()
    {
        return [
            self::FAIL_FAST_HEALTH,
            self::DOWNTIME,
        ];
    }

    public static function isEventValid($event)
    {
        if (in_array($event, self::getPartnerBankHealthEvents()) === true)
        {
            return true;
        }

        return false;
    }
}
