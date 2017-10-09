<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Constants\Entity;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;

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
    const INVOICE_PARTIALLY_PAID    = 'invoice.partially_paid';
    const INVOICE_EXPIRED           = 'invoice.expired';
    const VPA_EDITED                = 'vpa.edited';
    const P2P_CREATED               = 'p2p.created';
    const P2P_REJECTED              = 'p2p.rejected';
    const P2P_TRANSFERRED           = 'p2p.transferred';
    const SUBSCRIPTION_ACTIVATED    = 'subscription.activated';
    const SUBSCRIPTION_CHARGED      = 'subscription.charged';
    const SUBSCRIPTION_PENDING      = 'subscription.pending';
    const SUBSCRIPTION_HALTED       = 'subscription.halted';
    const SUBSCRIPTION_CANCELLED    = 'subscription.cancelled';
    const SUBSCRIPTION_COMPLETED    = 'subscription.completed';
    // const SUBSCRIPTION_EXPIRED      = 'subscription.expired';
    const ACCOUNT_ACTIVATED         = 'account.activated';

    protected static $events = [
        self::PAYMENT_AUTHORIZED,
        self::PAYMENT_FAILED,
        self::PAYMENT_CAPTURED,
        self::ORDER_PAID,
        self::INVOICE_PARTIALLY_PAID,
        self::INVOICE_PAID,
        self::INVOICE_EXPIRED,
        self::VPA_EDITED,
        self::P2P_CREATED,
        self::P2P_REJECTED,
        self::P2P_TRANSFERRED,
        self::SUBSCRIPTION_ACTIVATED,
        self::SUBSCRIPTION_CHARGED,
        self::SUBSCRIPTION_PENDING,
        self::SUBSCRIPTION_HALTED,
        self::SUBSCRIPTION_CANCELLED,
        self::SUBSCRIPTION_COMPLETED,
        // self::SUBSCRIPTION_EXPIRED,
        self::ACCOUNT_ACTIVATED,
    ];

    protected static $bitMap = [
        self::PAYMENT_AUTHORIZED        => 0x1,
        self::PAYMENT_FAILED            => 0x2,
        self::PAYMENT_CAPTURED          => 0x3,
        self::ORDER_PAID                => 0x4,
        self::INVOICE_PAID              => 0x5,
        self::VPA_EDITED                => 0x6,
        self::P2P_CREATED               => 0x7,
        self::P2P_REJECTED              => 0x8,
        self::SUBSCRIPTION_ACTIVATED    => 0x9,
        self::SUBSCRIPTION_PENDING      => 0x10,
        self::SUBSCRIPTION_HALTED       => 0x11,
        self::SUBSCRIPTION_CHARGED      => 0x12,
        self::SUBSCRIPTION_CANCELLED    => 0x13,
        self::SUBSCRIPTION_COMPLETED    => 0x14,
        // self::SUBSCRIPTION_EXPIRED      => 0x15,
        self::INVOICE_EXPIRED           => 0x16,
        self::INVOICE_PARTIALLY_PAID    => 0x17,
        self::ACCOUNT_ACTIVATED         => 0x18,
    ];

    /**
     * Events which are present in the system and
     * can be enabled/disabled.
     * @var array
     */
    protected static $names = [
        self::PAYMENT_AUTHORIZED,
        self::PAYMENT_FAILED,
        self::PAYMENT_CAPTURED,
        self::ORDER_PAID,
        self::INVOICE_PARTIALLY_PAID,
        self::INVOICE_PAID,
        self::INVOICE_EXPIRED,
        self::VPA_EDITED,
        self::P2P_CREATED,
        self::P2P_REJECTED,
        self::P2P_TRANSFERRED,
        self::SUBSCRIPTION_ACTIVATED,
        self::SUBSCRIPTION_PENDING,
        self::SUBSCRIPTION_HALTED,
        self::SUBSCRIPTION_CHARGED,
        self::SUBSCRIPTION_CANCELLED,
        self::SUBSCRIPTION_COMPLETED,
        // self::SUBSCRIPTION_EXPIRED,
        self::ACCOUNT_ACTIVATED,
    ];

    protected static $bitPosition = [
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
        self::SUBSCRIPTION_PENDING      => 11,
        self::SUBSCRIPTION_HALTED       => 12,
        self::SUBSCRIPTION_CHARGED      => 13,
        self::SUBSCRIPTION_CANCELLED    => 14,
        self::SUBSCRIPTION_COMPLETED    => 15,
        // self::SUBSCRIPTION_EXPIRED      => 16,
        self::INVOICE_EXPIRED           => 17,
        self::INVOICE_PARTIALLY_PAID    => 18,
        self::ACCOUNT_ACTIVATED         => 19,
    ];

    /**
     * These are events which will displayed to merchants
     * for enabling/disabling.
     * @var array
     */
    protected static $launchedEvents = [
        self::PAYMENT_AUTHORIZED,
        self::PAYMENT_FAILED,
        self::PAYMENT_CAPTURED,
        self::ORDER_PAID,
        self::INVOICE_PAID,
        self::INVOICE_PARTIALLY_PAID,
        self::INVOICE_EXPIRED,
        self::VPA_EDITED,
        self::P2P_CREATED,
        self::P2P_REJECTED,
        self::P2P_TRANSFERRED,
        self::SUBSCRIPTION_ACTIVATED,
        self::SUBSCRIPTION_PENDING,
        self::SUBSCRIPTION_HALTED,
        self::SUBSCRIPTION_CHARGED,
        self::SUBSCRIPTION_CANCELLED,
        self::SUBSCRIPTION_COMPLETED,
        // self::SUBSCRIPTION_EXPIRED,
        self::ACCOUNT_ACTIVATED,
    ];

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
        self::INVOICE_PARTIALLY_PAID    => Entity::INVOICE,
        self::INVOICE_EXPIRED           => Entity::INVOICE,
        self::ORDER_PAID                => Entity::ORDER,
        self::SUBSCRIPTION_ACTIVATED    => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_PENDING      => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_HALTED       => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_CHARGED      => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_CANCELLED    => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_COMPLETED    => Entity::SUBSCRIPTION,
        // self::SUBSCRIPTION_EXPIRED      => Entity::SUBSCRIPTION,
    ];

    public static $eventsToFeatureMap = [
        self::SUBSCRIPTION_ACTIVATED    => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_PENDING      => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_HALTED       => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_CHARGED      => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_CANCELLED    => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_COMPLETED    => Feature\Constants::SUBSCRIPTIONS,
        // self::SUBSCRIPTION_EXPIRED      => Feature\Constants::SUBSCRIPTIONS,
        self::INVOICE_PARTIALLY_PAID    => Feature\Constants::INVOICE_PARTIAL_PAYMENTS,
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

    public static function validateEventName(string $event): bool
    {
        return (in_array($event, self::$names) === true);
    }

    public static function getBitPosition(string $event): int
    {
        return self::$bitPosition[$event];
    }
}
