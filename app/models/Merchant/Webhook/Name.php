<?php

namespace Models\Merchant\Webhook;

use EE\Exception;
use Models\Base;

class Name
{
    const PAYMENT_AUTHORIZED = 0x1;

    protected static $events = array(
        self::PAYMENT_AUTHORIZED,
    );

    protected static $names = array(
        self::PAYMENT_AUTHORIZED => 'payment.authorized',
    );

    public static function getEvents($int)
    {
        $events = array();

        foreach (self::$events as $event)
        {
            if ($event xor $int === true)
            {
                array_push($events, self::$names[$event]);
            }
        }

        return $events;
    }
}