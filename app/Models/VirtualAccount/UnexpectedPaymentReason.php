<?php


namespace RZP\Models\VirtualAccount;

use RZP\Error\PublicErrorDescription;

class UnexpectedPaymentReason
{
    const VIRTUAL_ACCOUNT_PAYMENT_FAILED_FEE_OR_TAX_TAMPERED                = 'Payment failed because fees or tax was tampered';
    const VIRTUAL_ACCOUNT_PAYMENT_FAILED_INVOICE_ALREADY_PAID               = 'Invoice is not payable in paid status.';
    const VIRTUAL_ACCOUNT_PAYMENT_FAILED_INVOICE_ALREADY_CANCELLED          = 'Invoice is not payable in cancelled status.';
    const VIRTUAL_ACCOUNT_PAYMENT_FAILED_INVOICE_ALREADY_EXPIRED            = 'Invoice is not payable in expired status.';
    const VIRTUAL_ACCOUNT_PAYMENT_FAILED_INVOICE_EXPIRY_TIME_PASSED         = 'Invoice is not payable post its expiry time';
    const VIRTUAL_ACCOUNT_PAYMENT_FAILED_PAYMENT_LINK_ALREADY_PAID          = 'Payment Link is not payable in paid status.';
    const VIRTUAL_ACCOUNT_PAYMENT_FAILED_PAYMENT_LINK_ALREADY_CANCELLED     = 'Payment Link is not payable in cancelled status.';
    const VIRTUAL_ACCOUNT_PAYMENT_FAILED_PAYMENT_LINK_ALREADY_EXPIRED       = 'Payment Link is not payable in expired status.';
    const VIRTUAL_ACCOUNT_PAYMENT_FAILED_PAYMENT_LINK_EXPIRY_TIME_PASSED    = 'Payment Link is not payable post its expiry time';
    const VIRTUAL_ACCOUNT_PAYMENT_FAILED_GATEWAY_DISABLED                   = 'Gateway linked to the virtual bank account has been disabled';

    protected static $toCreateUnexpected = [
        self::VIRTUAL_ACCOUNT_PAYMENT_FAILED_FEE_OR_TAX_TAMPERED,
        self::VIRTUAL_ACCOUNT_PAYMENT_FAILED_INVOICE_ALREADY_PAID,
        self::VIRTUAL_ACCOUNT_PAYMENT_FAILED_INVOICE_ALREADY_CANCELLED,
        self::VIRTUAL_ACCOUNT_PAYMENT_FAILED_INVOICE_ALREADY_EXPIRED,
        self::VIRTUAL_ACCOUNT_PAYMENT_FAILED_INVOICE_EXPIRY_TIME_PASSED,
        self::VIRTUAL_ACCOUNT_PAYMENT_FAILED_PAYMENT_LINK_ALREADY_PAID,
        self::VIRTUAL_ACCOUNT_PAYMENT_FAILED_PAYMENT_LINK_ALREADY_CANCELLED,
        self::VIRTUAL_ACCOUNT_PAYMENT_FAILED_PAYMENT_LINK_ALREADY_EXPIRED,
        self::VIRTUAL_ACCOUNT_PAYMENT_FAILED_PAYMENT_LINK_EXPIRY_TIME_PASSED,
        self::VIRTUAL_ACCOUNT_PAYMENT_FAILED_GATEWAY_DISABLED,
        PublicErrorDescription::BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT,
    ];

    public static function shouldCreateUnexpectedPayment(string $message) : bool
    {
        return in_array($message, self::$toCreateUnexpected);
    }
}
