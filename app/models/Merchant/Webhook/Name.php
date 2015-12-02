<?php

namespace Models\Merchant\Webhook;

use EE\Exception;
use Models\Base;

class Name
{
    const PAYMENT_AUTHORIZED = 'payment.authorized';

    protected static $events = array(
        self::PAYMENT_AUTHORIZED,
    );

    protected static $bitMap = array(
        self::PAYMENT_AUTHORIZED => 0x1,
    );

    protected static $names = array(
        self::PAYMENT_AUTHORIZED,
    );

    public static function getAllEventNames()
    {
        return self::$names;
    }

    public static function getEnabledEvents($int)
    {
        $events = array();

        foreach (self::$events as $event)
        {
            $bit = self::$bitMap[$event];

            if ($bit ^ $int === false)
            {
                array_push($events, $event);
            }
        }

        return $events;
    }

    public static function validateEventNames($events)
    {
        foreach ($events as $event)
        {


        }
    }

    public static function validateEventName($event)
    {
        $event = strtoupper(str_replace('.', '_', $event));

        return (defined(__CLASS__.'::'.$event));
    }

    public static function getBitValue($event)
    {
        $event = str_replace('_', '.', $event);
        $event = strtolower($event);

        return self::$bitMap[$event];
    }
}