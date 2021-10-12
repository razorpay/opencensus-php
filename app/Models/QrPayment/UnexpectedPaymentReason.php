<?php

namespace RZP\Models\QrPayment;

class UnexpectedPaymentReason
{
    const QR_PAYMENT_AMOUNT_MISMATCH   = 'Actual payment amount does not match expected payment amount';

    const QR_PAYMENT_ON_CLOSED_QR_CODE = 'Payment made on closed QR code';


    const QR_PAYMENT_QR_NOT_FOUND      = 'Payment made on invalid QR code';

    const QR_CODE_PAYMENT_FAILED_FEE_OR_TAX_TAMPERED = 'Payment failed because fees or tax was tampered';

    protected static $toCreateUnexpected = [
        self::QR_CODE_PAYMENT_FAILED_FEE_OR_TAX_TAMPERED,
    ];

    public static function shouldCreateUnexpectedPayment(string $message) : bool
    {
        return in_array($message, self::$toCreateUnexpected);
    }
}
