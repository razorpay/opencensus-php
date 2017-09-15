<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Constants\MailTags;

class Event
{
    const ACTIVATED     = 'activated';
    const CHARGED       = 'charged';
    const PENDING       = 'pending';
    const HALTED        = 'halted';
    const CANCELLED     = 'cancelled';

    const CUSTOMER_EVENTS = [
        self::ACTIVATED,
        self::CHARGED,
        self::PENDING,
        self::HALTED,
        self::CANCELLED,
    ];

    const MERCHANT_EVENTS = [
        self::ACTIVATED,
        self::CHARGED,
        self::PENDING,
        self::HALTED,
        self::CANCELLED,
    ];

    const MAIL_TAG_MAP = [
        self::ACTIVATED => MailTags::SUBSCRIPTION_ACTIVATED,
        self::CHARGED   => MailTags::SUBSCRIPTION_CHARGED,
        self::PENDING   => MailTags::SUBSCRIPTION_PENDING,
        self::HALTED    => MailTags::SUBSCRIPTION_HALTED,
        self::CANCELLED => MailTags::SUBSCRIPTION_CANCELLED,
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
