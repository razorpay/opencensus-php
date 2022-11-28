<?php

namespace RZP\Models\QrPayment;

use RZP\Error\PublicErrorDescription;

class UnexpectedPaymentReason
{
    const QR_PAYMENT_AMOUNT_MISMATCH = 'Actual payment amount does not match expected payment amount';

    const QR_PAYMENT_ON_CLOSED_QR_CODE = 'Payment made on closed QR code';

    const QR_PAYMENT_QR_NOT_FOUND = 'Payment made on invalid QR code';

    const QR_CODE_PAYMENT_FAILED_FEE_OR_TAX_TAMPERED = 'Payment failed because fees or tax was tampered';

    const QR_CODE_PAYMENT_FAILED_UPI_NOT_ENABLED = 'UPI transactions are not enabled for the merchant';

    const QR_CODE_CUTOFF_TIME_EXCEEDED = 'The payment transaction time exceeds the cutoff limit';

    const QR_CODE_MISSING_ORDER_ID = 'Payment processing failed due to missing order id';

    const BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED = 'There is a temporary block placed on the account currently because of which new payment operations are put on hold. If you are seeing this message unexpectedly, please contact the site admin regarding the issue.';

    const CHECKOUT_ORDER_NOT_PRESENT = 'The checkout QR code does not have checkout order associated to it';

    const CHECKOUT_ORDER_CLOSED = 'The checkout order associated to the QR is closed';

    protected static $toCreateUnexpected = [
        self::QR_CODE_PAYMENT_FAILED_FEE_OR_TAX_TAMPERED,
        self::QR_CODE_PAYMENT_FAILED_UPI_NOT_ENABLED,
        self::QR_CODE_MISSING_ORDER_ID,
        self::BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED,
        PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID,
        PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH,
        PublicErrorDescription::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
        PublicErrorDescription::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_WEBSITE_MISMATCH,
        PublicErrorDescription::BAD_REQUEST_PAYMENT_LINK_NOT_PAYABLE,
        PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_COLLECT_NOT_ENABLED_FOR_MERCHANT,
        PublicErrorDescription::BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT,
    ];

    public static function shouldCreateUnexpectedPayment(string $message): bool
    {
        return in_array($message, self::$toCreateUnexpected);
    }
}
