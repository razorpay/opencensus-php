<?php

namespace RZP\Models\QrPayment;

use RZP\Error\PublicErrorDescription;

class UnexpectedPaymentReason
{
    const QR_PAYMENT_AMOUNT_MISMATCH    = 'Actual payment amount does not match expected payment amount';

    const QR_PAYMENT_ON_CLOSED_QR_CODE  = 'Payment made on closed QR code';

    const QR_PAYMENT_QR_NOT_FOUND       = 'Payment made on invalid QR code';

    const QR_CODE_PAYMENT_FAILED_FEE_OR_TAX_TAMPERED = 'Payment failed because fees or tax was tampered';

    const QR_CODE_PAYMENT_FAILED_UPI_NOT_ENABLED     = 'UPI transactions are not enabled for the merchant';

    const QR_CODE_CUTOFF_TIME_EXCEEDED               = 'The payment transaction time exceeds the cutoff limit';

    const QR_CODE_MISSING_ORDER_ID                   = 'Payment processing failed due to missing order id';

    protected static $toCreateUnexpected = [
        self::QR_CODE_PAYMENT_FAILED_FEE_OR_TAX_TAMPERED,
        self::QR_CODE_PAYMENT_FAILED_UPI_NOT_ENABLED,
        self::QR_CODE_MISSING_ORDER_ID,
        PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID,
    ];

    public static function shouldCreateUnexpectedPayment(string $message) : bool
    {
        return in_array($message, self::$toCreateUnexpected);
    }
}
