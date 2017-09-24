<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Constants\MailTags;

class Event
{
    //
    // ======== Events ========
    //
    const AUTHENTICATED   = 'authenticated';
    const CHARGED         = 'charged';
    const PENDING         = 'pending';
    const HALTED          = 'halted';
    const CANCELLED       = 'cancelled';
    const COMPLETED       = 'completed';
    const CARD_CHANGED    = 'card_changed';

    //
    // ======== Event options ========
    //

    const CHARGE_SUCCESS  = 'charge_success';
    const IMMEDIATE       = 'immediate';
    const AUTO_REFUND     = 'auto_refund';
    const PAYMENT         = 'payment';
    const FUTURE_CANCEL   = 'future_cancel';
    const PAST_INVOICE    = 'past_invoice';
    const INVOICE_CHARGED = 'invoice_charged';
    const REACTIVATED     = 'reactivated';

    const DEFAULT_OPTIONS = [
        self::AUTHENTICATED   => [
            self::IMMEDIATE      => false,
            self::AUTO_REFUND    => true,
        ],
        self::CHARGED         => [
            self::PAST_INVOICE   => false,
            self::REACTIVATED    => false,
        ],
        self::COMPLETED       => [
            self::CHARGE_SUCCESS => true,
        ],
        self::CANCELLED       => [
            self::FUTURE_CANCEL  => false,
        ],
        self::CARD_CHANGED    => [
            self::INVOICE_CHARGED => false,
            self::REACTIVATED     => false,
        ],
        self::PENDING         => [],
        self::HALTED          => [],
    ];

    const CUSTOMER_EVENTS = [
        self::AUTHENTICATED,
        self::CHARGED,
        self::PENDING,
        self::HALTED,
        self::CANCELLED,
        self::COMPLETED,
        self::CARD_CHANGED,
    ];

    const MERCHANT_EVENTS = [
        self::AUTHENTICATED,
        self::CHARGED,
        self::PENDING,
        self::HALTED,
        self::CANCELLED,
        self::COMPLETED,
        self::CARD_CHANGED,
    ];

    const MAIL_TAG_MAP = [
        self::AUTHENTICATED   => MailTags::SUBSCRIPTION_AUTHENTICATED,
        self::CHARGED         => MailTags::SUBSCRIPTION_CHARGED,
        self::PENDING         => MailTags::SUBSCRIPTION_PENDING,
        self::HALTED          => MailTags::SUBSCRIPTION_HALTED,
        self::CANCELLED       => MailTags::SUBSCRIPTION_CANCELLED,
        self::COMPLETED       => MailTags::SUBSCRIPTION_COMPLETED,
        self::CARD_CHANGED    => MailTags::SUBSCRIPTION_CARD_CHANGED,
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
