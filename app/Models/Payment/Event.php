<?php

namespace RZP\Models\Payment;

use RZP\Constants\MailTags;

class Event
{
    const AUTHORIZED                 = 'authorized';
    const CARD_SAVED                 = 'card_saved';
    const CAPTURED                   = 'captured';
    const REFUNDED                   = 'refunded';
    const FAILED                     = 'failed';
    const FAILED_TO_AUTHORIZED       = 'failed_to_authorized';
    const INVOICE_PAYMENT_AUTHORIZED = 'invoice_payment_authorized';
    const INVOICE_PAYMENT_CAPTURED   = 'invoice_payment_captured';

    const CUSTOMER_EVENTS = [
        self::AUTHORIZED,
        self::REFUNDED,
        self::FAILED_TO_AUTHORIZED,
        self::CARD_SAVED,
        self::INVOICE_PAYMENT_AUTHORIZED,
    ];

    const MERCHANT_EVENTS = [
        self::CAPTURED,
        self::REFUNDED,
        self::FAILED,
        self::FAILED_TO_AUTHORIZED,
        self::INVOICE_PAYMENT_CAPTURED,
    ];

    const INVOICE_EVENTS = [
        self::INVOICE_PAYMENT_AUTHORIZED,
        self::INVOICE_PAYMENT_CAPTURED,
    ];

    const MAIL_TAG_MAP = [
        self::AUTHORIZED                 => MailTags::PAYMENT_SUCCESSFUL,
        self::REFUNDED                   => MailTags::REFUND_SUCCESSFUL,
        self::FAILED                     => MailTags::PAYMENT_FAILED,
        self::INVOICE_PAYMENT_AUTHORIZED => MailTags::INVOICE,
        self::INVOICE_PAYMENT_CAPTURED   => MailTags::INVOICE,
        self::FAILED_TO_AUTHORIZED       => MailTags::FAILED_TO_AUTHORIZED,
        self::CARD_SAVED                 => MailTags::CARD_SAVING,
    ];

    const RECEIPT_EMAIL_EVENTS = [
        self::AUTHORIZED,
        self::REFUNDED,
        self::FAILED_TO_AUTHORIZED
    ];

    public static function isCustomerEvent(string $event)
    {
        return (in_array($event, self::CUSTOMER_EVENTS, true) === true);
    }

    public static function isMerchantEvent(string $event)
    {
        return (in_array($event, self::MERCHANT_EVENTS, true) === true);
    }

    public static function isInvoiceEvent(string $event)
    {
        return (in_array($event, self::INVOICE_EVENTS, true) === true);
    }

    /**
     * Generates the actual mailable class name from the invoice event name
     * For eg. event 'invoice_payment_authorized' gives 'Authorized'
     *
     * @param  string $event Invoice event
     *
     * @return string Invoice class name
     */
    public static function getInvoiceEventName(string $event)
    {
        $arr = explode('_', $event);

        $event = array_pop($arr);

        $event = studly_case($event);

        return $event;
    }

    public static function getAction(string $event, array $data)
    {
        switch ($event)
        {
            case self::REFUNDED:
                $action = 'Refund';
                break;
            case self::INVOICE_PAYMENT_AUTHORIZED:
            case self::INVOICE_PAYMENT_CAPTURED:
                $action = ucwords($data['invoice']['type_label']) . '\'s Payment';
                break;
            default:
                $action = 'Payment';
                break;
        }
        return $action;
    }

    public static function getMailTag(string $event)
    {
        return self::MAIL_TAG_MAP[$event] ?? MailTags::PAYMENT_SUCCESSFUL;
    }
}
