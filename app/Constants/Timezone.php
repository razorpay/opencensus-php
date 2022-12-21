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

    public static function getTimeZoneAbbrevation(string $country='IN')
    {
        if (array_key_exists($country, self::$timeZoneAbbrevationMap)){
            return self::$timeZoneAbbrevationMap[$country];
        }
    }
}
