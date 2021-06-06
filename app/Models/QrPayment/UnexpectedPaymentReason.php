<?php

namespace RZP\Models\QrPayment;

class UnexpectedPaymentReason
{
    const QR_PAYMENT_AMOUNT_MISMATCH   = 'QR_PAYMENT_AMOUNT_MISMATCH';

    const QR_PAYMENT_ON_CLOSED_QR_CODE = 'QR_PAYMENT_ON_CLOSED_QR_CODE';

    const QR_PAYMENT_QR_NOT_FOUND      = 'QR_PAYMENT_QR_NOT_FOUND';
}
