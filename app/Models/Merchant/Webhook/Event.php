<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Constants\Entity;
use RZP\Exception;
use RZP\Models\Base;

/**
 * The events whether they are enabled or disabled are store in bit format.
 * See this link for a guide on bitwise operations:
 * http://stackoverflow.com/questions/47981/how-do-you-set-clear-and-toggle-a-single-bit-in-c-c
 */
class Event
{
    const PAYMENT_AUTHORIZED        = 'payment.authorized';
    const PAYMENT_FAILED            = 'payment.failed';
    const PAYMENT_CAPTURED          = 'payment.captured';
    const ORDER_PAID                = 'order.paid';
    const INVOICE_PAID              = 'invoice.paid';
    const VPA_EDITED                = 'vpa.edited';
    const P2P_CREATED               = 'p2p.created';
    const P2P_REJECTED              = 'p2p.rejected';
    const P2P_TRANSFERRED           = 'p2p.transferred';
    const SUBSCRIPTION_ACTIVATED    = 'subscription.activated';
    const SUBSCRIPTION_OVERDUE      = 'subscription.overdue';
    const SUBSCRIPTION_ON_HOLD      = 'subscription.on_hold';
    const SUBSCRIPTION_EXPIRED      = 'subscription.expired';

    protected static $events = array(
        self::PAYMENT_AUTHORIZED,
        self::PAYMENT_FAILED,
        self::PAYMENT_CAPTURED,
        self::ORDER_PAID,
        self::INVOICE_PAID,
        self::VPA_EDITED,
        self::P2P_CREATED,
        self::P2P_REJECTED,
        self::P2P_TRANSFERRED,
        self::SUBSCRIPTION_ACTIVATED,
        self::SUBSCRIPTION_OVERDUE,
        self::SUBSCRIPTION_ON_HOLD,
        self::SUBSCRIPTION_EXPIRED,
    );

    protected static $bitMap = array(
        self::PAYMENT_AUTHORIZED        => 0x1,
        self::PAYMENT_FAILED            => 0x2,
        self::PAYMENT_CAPTURED          => 0x3,
        self::ORDER_PAID                => 0x4,
        self::INVOICE_PAID              => 0x5,
        self::VPA_EDITED                => 0x6,
        self::P2P_CREATED               => 0x7,
        self::P2P_REJECTED              => 0x8,
        self::SUBSCRIPTION_ACTIVATED    => 0x9,
        self::SUBSCRIPTION_OVERDUE      => 0x10,
        self::SUBSCRIPTION_ON_HOLD      => 0x11,
        self::SUBSCRIPTION_EXPIRED      => 0x12,
    );

    /**
     * Events which are present in the system and
     * can be enabled/disabled.
     * @var array
     */
    protected static $names = array(
        self::PAYMENT_AUTHORIZED,
        self::PAYMENT_FAILED,
        self::ORDER_PAID,
        self::INVOICE_PAID,
        self::VPA_EDITED,
        self::P2P_CREATED,
        self::P2P_REJECTED,
        self::P2P_TRANSFERRED,
        self::SUBSCRIPTION_ACTIVATED,
        self::SUBSCRIPTION_OVERDUE,
        self::SUBSCRIPTION_ON_HOLD,
        self::SUBSCRIPTION_EXPIRED,
    );

    protected static $bitPosition = array(
        self::PAYMENT_AUTHORIZED        => 1,
        self::PAYMENT_FAILED            => 2,
        self::PAYMENT_CAPTURED          => 3,
        self::ORDER_PAID                => 4,
        self::INVOICE_PAID              => 5,
        self::VPA_EDITED                => 6,
        self::P2P_CREATED               => 7,
        self::P2P_REJECTED              => 8,
        self::P2P_TRANSFERRED           => 9,
        self::SUBSCRIPTION_ACTIVATED    => 10,
        self::SUBSCRIPTION_OVERDUE      => 11,
        self::SUBSCRIPTION_ON_HOLD      => 12,
        self::SUBSCRIPTION_EXPIRED      => 13,
    );

    /**
     * These are events which will displayed to merchants
     * for enabling/disabling.
     * @var array
     */
    protected static $launchedEvents = array(
        self::PAYMENT_AUTHORIZED,
        self::PAYMENT_FAILED,
        self::ORDER_PAID,
        self::INVOICE_PAID,
        self::VPA_EDITED,
        self::P2P_CREATED,
        self::P2P_REJECTED,
        self::P2P_TRANSFERRED,
        self::SUBSCRIPTION_ACTIVATED,
        self::SUBSCRIPTION_OVERDUE,
        self::SUBSCRIPTION_ON_HOLD,
        self::SUBSCRIPTION_EXPIRED,
    );

    /**
     * Defines the mapping to entity for respective event and also
     * the field description to be set in mail content for webhook related mails
     *
     * @var array
     */
    public static $eventsToEntityMap = [
        self::PAYMENT_AUTHORIZED        => Entity::PAYMENT,
        self::PAYMENT_CAPTURED          => Entity::PAYMENT,
        self::PAYMENT_FAILED            => Entity::PAYMENT,
        self::INVOICE_PAID              => Entity::INVOICE,
        self::ORDER_PAID                => Entity::ORDER,
        self::SUBSCRIPTION_ACTIVATED    => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_OVERDUE      => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_ON_HOLD      => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_EXPIRED      => Entity::SUBSCRIPTION,
    ];

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
