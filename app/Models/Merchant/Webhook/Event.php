<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Models\Base;
use RZP\Exception;

/**
 * The events whether they are enabled or disabled are store in bit format.
 * See this link for a guide on bitwise operations:
 * http://stackoverflow.com/questions/47981/how-do-you-set-clear-and-toggle-a-single-bit-in-c-c
 */
class Event
{
    const PAYMENT_AUTHORIZED        = 'payment.authorized';
    const PAYMENT_FAILED            = 'payment.failed';

    protected static $events = array(
        self::PAYMENT_AUTHORIZED,
        self::PAYMENT_FAILED,
    );

    protected static $bitMap = array(
        self::PAYMENT_AUTHORIZED    => 0x1,
        self::PAYMENT_FAILED        => 0x2,
    );

    /**
     * Events which are present in the system and
     * can be enabled/disabled.
     * @var array
     */
    protected static $names = array(
        self::PAYMENT_AUTHORIZED,
        self::PAYMENT_FAILED,
    );

    protected static $bitPosition = array(
        self::PAYMENT_AUTHORIZED    => 1,
        self::PAYMENT_FAILED        => 2,
    );

    /**
     * These are events which will displayed to merchants
     * for enabling/disabling.
     * @var array
     */
    protected static $launchedEvents = array(
        self::PAYMENT_AUTHORIZED,
    );

    /**
     * Takes the hex value and merges it
     * with the hex value of the events passed.
     *
     * @param  array    $events
     * @param  integer  $hex
     * @return integer
     */
    public static function getHexValue($events, $hex)
    {
        foreach ($events as $event => $value)
        {
            $pos = Event::getBitPosition($event);

            $value = ($value === '1') ? 1 : 0;

            // Sets the bit value for the current event.
            $hex ^= ((-1 * $value) ^ $hex) & (1 << ($pos - 1));
        }

        return $hex;
    }

    public static function getAllEventNames()
    {
        return self::$names;
    }

    public static function getLaunchedEventNames()
    {
        return self::$launchedEvents;
    }

    public static function getEnabledEvents($hex)
    {
        $events = array();

        foreach (self::$events as $event)
        {
            $pos = self::$bitPosition[$event];
            $value = ($hex >> ($pos - 1)) & 1;

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

        return ($hexEvent >> ($pos - 1)) & 1;
    }

    public static function validateEventName($event)
    {
        $event = strtoupper(str_replace('.', '_', $event));

        return (defined(__CLASS__ . '::' . $event));
    }

    public static function getBitPosition($event)
    {
        $event = str_replace('_', '.', $event);
        $event = strtolower($event);

        return self::$bitPosition[$event];
    }
}
