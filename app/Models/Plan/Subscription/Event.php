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
    const INVOICE_CHARGED = 'invoice_charged';

    //
    // ======== Event options ========
    //

    const CHARGE_SUCCESS = 'charge_success';
    const CARD_CHANGE    = 'card_change';
    const IMMEDIATE      = 'immediate';
    const AUTO_REFUND    = 'auto_refund';
    const PAYMENT        = 'payment';
    const OLD_STATUS     = 'old_status';
    const FUTURE_CANCEL  = 'future_cancel';

    const DEFAULT_OPTIONS = [
        self::AUTHENTICATED   => [
            self::IMMEDIATE      => false,
            self::AUTO_REFUND    => true,
        ],
        self::CHARGED         => [
            self::CARD_CHANGE    => false,
        ],
        self::COMPLETED       => [
            self::CHARGE_SUCCESS => true,
        ],
        self::CANCELLED       => [
            self::FUTURE_CANCEL  => false,
        ],
        self::CARD_CHANGED    => [
            self::OLD_STATUS     => null,
        ],
        self::PENDING         => [],
        self::HALTED          => [],
        self::INVOICE_CHARGED => [],
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
