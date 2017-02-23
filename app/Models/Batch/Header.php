<?php

namespace RZP\Models\Batch;

class Header
{
    // Refund Input Headers
    const PAYMENT_ID        = 'Payment Id';
    const AMOUNT            = 'Amount';
    const REFUND_ID         = 'Refund Id';
    const REFUNDED_AMOUNT   = 'Refunded Amount';
    const STATUS            = 'Status';
    const ERROR_CODE        = 'Error Code';
    const ERROR_DESCRIPTION = 'Error Description';

    const REFUND_OUTPUT_HEADERS = [
        self::PAYMENT_ID,
        self::AMOUNT,
        self::REFUND_ID,
        self::REFUNDED_AMOUNT,
        self::STATUS,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
    ];

    const REFUND_INPUT_HEADERS = [
        self::PAYMENT_ID,
        self::AMOUNT
    ];
}
