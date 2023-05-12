<?php

namespace RZP\Constants;

class Timezone
{
    const IST = 'Asia/Kolkata';
    const MYT = 'Asia/Kuala_Lumpur';

    protected static $timeZoneAbbrevationMap = [
        self::IST => 'IST',
        self::MYT => 'MYT',
    ];

    public static function getTimeZoneAbbrevation(string $timeZone='IST')
    {
        if (array_key_exists($timeZone, self::$timeZoneAbbrevationMap)){
            return self::$timeZoneAbbrevationMap[$timeZone];
        }
    }
}
