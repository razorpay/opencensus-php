<?php

namespace RZP\Models\QrPayment;

class UnexpectedPaymentReason
{
    const QR_PAYMENT_AMOUNT_MISMATCH   = 'QR_PAYMENT_AMOUNT_MISMATCH';

    const QR_PAYMENT_ON_CLOSED_QR_CODE = 'QR_PAYMENT_ON_CLOSED_QR_CODE';

    const QR_PAYMENT_QR_NOT_FOUND      = 'QR_PAYMENT_QR_NOT_FOUND';

    const QR_CODE_PAYMENT_FAILED_FEE_OR_TAX_TAMPERED = 'Payment failed because fees or tax was tampered';

    protected static $toCreateUnexpected = [
        self::QR_CODE_PAYMENT_FAILED_FEE_OR_TAX_TAMPERED
    ];

    public static function shouldCreateUnexpectedPayment(string $message) : bool
    {
        return in_array($message, self::$toCreateUnexpected);
    }
}
