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
    const REFUND_RRN_UPDATED         = 'refund_rrn_updated';
    const CUSTOMER_FAILED            = 'customer_failed';
    const B2B_UPLOAD_INVOICE         = 'b2b_upload_invoice';

    const CUSTOMER_EVENTS = [
        self::AUTHORIZED,
        self::CAPTURED,
        self::REFUNDED,
        self::FAILED_TO_AUTHORIZED,
        self::CARD_SAVED,
        self::INVOICE_PAYMENT_AUTHORIZED,
        self::REFUND_RRN_UPDATED,
        self::CUSTOMER_FAILED,
    ];

    protected static $customerEventBitPosition = [
        self::AUTHORIZED                 => 1,
        self::CAPTURED                   => 2,
        self::REFUNDED                   => 3,
        self::FAILED_TO_AUTHORIZED       => 4,
        self::CARD_SAVED                 => 5,
        self::INVOICE_PAYMENT_AUTHORIZED => 6,
        self::REFUND_RRN_UPDATED         => 7,
        self::CUSTOMER_FAILED            => 8,
    ];

    const MERCHANT_EVENTS = [
        self::CAPTURED,
        self::REFUNDED,
        self::FAILED,
        self::FAILED_TO_AUTHORIZED,
        self::INVOICE_PAYMENT_CAPTURED,
        self::REFUND_RRN_UPDATED,
        self::B2B_UPLOAD_INVOICE,
    ];

    const INVOICE_EVENTS = [
        self::INVOICE_PAYMENT_AUTHORIZED,
        self::INVOICE_PAYMENT_CAPTURED,
    ];

    const MAIL_TAG_MAP = [
        self::AUTHORIZED                 => MailTags::PAYMENT_SUCCESSFUL,
        self::REFUNDED                   => MailTags::REFUND_SUCCESSFUL,
        self::FAILED                     => MailTags::PAYMENT_FAILED,
        self::CUSTOMER_FAILED            => MailTags::PAYMENT_FAILED,
        self::INVOICE_PAYMENT_AUTHORIZED => MailTags::INVOICE,
        self::INVOICE_PAYMENT_CAPTURED   => MailTags::INVOICE,
        self::FAILED_TO_AUTHORIZED       => MailTags::FAILED_TO_AUTHORIZED,
        self::CARD_SAVED                 => MailTags::CARD_SAVING,
        self::REFUND_RRN_UPDATED         => MailTags::REFUND_RRN_UPDATE,
        self::B2B_UPLOAD_INVOICE         => MailTags::B2B_UPLOAD_INVOICE,
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
            case self::REFUND_RRN_UPDATED:
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


    /**
     * Set corresponding bit for the events passed in array, if $events has ['authorised'=> '1' and 'refunded' => '1'],
     * and initial $hex 0, final hex returned will be 5, which in binary is 101, as for authorised and refunded
     * bitposition is 1 and 3 respectively.
     * @param $events
     * @param $hex
     * @return int
     */
    public static function getCustomerEventHexValue($events, $hex)
    {
        foreach ($events as $event => $value)
        {
            $pos = self::getCustomerEventBitPosition($event);

            $value = ($value === '1') ? 1 : 0;

            // Sets the bit value for the current type.
            $hex ^= ((-1 * $value) ^ $hex) & (1 << ($pos - 1));
        }

        return $hex;
    }

    public static function getCustomerEventBitPosition($event)
    {
        return self::$customerEventBitPosition[$event];
    }

    public static function getCustomerEventsFromHex($hex)
    {
        $events = [];

        foreach (self::CUSTOMER_EVENTS as $event)
        {
            $pos = self::$customerEventBitPosition[$event];

            $value = ($hex >> ($pos - 1)) & 1;

            if ($value)
            {
                array_push($events, $event);
            }
        }
        return $events;
    }
}
