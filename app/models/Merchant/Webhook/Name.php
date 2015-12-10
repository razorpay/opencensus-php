<?php

namespace Models\Merchant\Webhook;

use EE\Exception;
use Models\Base;

/**
 * The events whether they are enabled or disabled are store in bit format.
 * See this link for a guide on bitwise operations:
 * http://stackoverflow.com/questions/47981/how-do-you-set-clear-and-toggle-a-single-bit-in-c-c
 */
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

    protected static $bitPosition = array(
        self::PAYMENT_AUTHORIZED => 0,
    );

    public static function getHexValue($events, $hex)
    {
        foreach ($events as $event => $value)
        {
            $pos = Name::getBitPosition($event);

            $value = ($value === '1') ? 1 : 0;

            $hex ^= ((-1 * $value) ^ $hex) & (1 << $pos);
        }

        return $hex;
    }

    public static function getAllEventNames()
    {
        return self::$names;
    }

    public static function getEnabledEvents($hex)
    {
        $events = array();

        foreach (self::$events as $event)
        {
            $pos = self::$bitPosition[$event];
            $value = ($hex >> $pos) & 1;

            if ($value)
            {
                array_push($events, $event);
            }
        }

        return $events;
    }

    public static function isEventEnabled($hexEvent, $event)
    {
        $pos = self::getBitPosition($event);

        return ($hexEvent >> $pos) & 1;
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

    public static function getBitPosition($event)
    {
        $event = str_replace('_', '.', $event);
        $event = strtolower($event);

        return self::$bitPosition[$event];
    }
}