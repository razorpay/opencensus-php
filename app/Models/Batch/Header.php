<?php

namespace RZP\Models\Batch;

class Header
{
    // Refund Input Headers
    const PAYMENT_ID        = 'payment_id';
    const AMOUNT            = 'amount';
    const REFUND_ID         = 'refund_id';
    const REFUNDED_AMOUNT   = 'refunded_amount';
    const STATUS            = 'status';
    const ERROR_CODE        = 'error_code';
    const ERROR_DESCRIPTION = 'error_description';

    const REFUND_HEADERS = [
        self::PAYMENT_ID,
        self::AMOUNT,
        self::REFUND_ID,
        self::REFUNDED_AMOUNT,
        self::STATUS,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
    ];
}
