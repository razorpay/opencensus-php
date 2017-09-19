<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Constants\MailTags;

class Event
{
    const AUTHENTICATED   = 'authenticated';
    const CHARGED         = 'charged';
    const PENDING         = 'pending';
    const HALTED          = 'halted';
    const CANCELLED       = 'cancelled';
    const COMPLETED       = 'completed';
    const CARD_CHANGED    = 'card_changed';
    const INVOICE_CHARGED = 'invoice_charged';

    // Event options
    const CHARGE_SUCCESS = 'charge_success';
    const CARD_CHANGE    = 'card_change';
    const IMMEDIATE      = 'immediate';
    const UPFRONT        = 'upfront';
    const PAYMENT        = 'payment';
    const OLD_STATUS     = 'old_status';

    const DEFAULT_OPTIONS = [
        self::CHARGE_SUCCESS => true,
        self::CARD_CHANGE    => false,
        self::IMMEDIATE      => false,
        self::UPFRONT        => false,
    ];

    const CUSTOMER_EVENTS = [
        self::AUTHENTICATED,
        self::CHARGED,
        self::PENDING,
        self::HALTED,
        self::CANCELLED,
        self::COMPLETED,
        self::CARD_CHANGED,
        self::INVOICE_CHARGED,
    ];

    const MERCHANT_EVENTS = [
        self::AUTHENTICATED,
        self::CHARGED,
        self::PENDING,
        self::HALTED,
        self::CANCELLED,
        self::COMPLETED,
        self::CARD_CHANGED,
        self::INVOICE_CHARGED,
    ];

    const MAIL_TAG_MAP = [
        self::AUTHENTICATED   => MailTags::SUBSCRIPTION_AUTHENTICATED,
        self::CHARGED         => MailTags::SUBSCRIPTION_CHARGED,
        self::PENDING         => MailTags::SUBSCRIPTION_PENDING,
        self::HALTED          => MailTags::SUBSCRIPTION_HALTED,
        self::CANCELLED       => MailTags::SUBSCRIPTION_CANCELLED,
        self::COMPLETED       => MailTags::SUBSCRIPTION_COMPLETED,
        self::CARD_CHANGED    => MailTags::SUBSCRIPTION_CARD_CHANGED,
        self::INVOICE_CHARGED => MailTags::SUBSCRIPTION_INVOICE_CHARGED,
    ];

    public static function isCustomerEvent(string $event)
    {
        return (in_array($event, self::CUSTOMER_EVENTS, true) === true);
    }

    public static function isMerchantEvent(string $event)
    {
        return (in_array($event, self::MERCHANT_EVENTS, true) === true);
    }

    public static function getMailTag(string $event)
    {
        return self::MAIL_TAG_MAP[$event] ?? MailTags::PAYMENT_SUCCESSFUL;
    }
}
