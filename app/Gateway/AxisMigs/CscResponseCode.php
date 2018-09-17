<?php

namespace RZP\Gateway\AxisMigs;

use RZP\Error\ErrorCode;
use RZP\Gateway\Base\ErrorCodes;

class CscResponseCode extends ErrorCodes
{
    public static $errorDescriptionMap = [
        'M' => 'Valid or matched CSC', // This is a success case
        'S' => 'Merchant indicates CSC not present on card',
        'P' => 'CSC Not Processed',
        'U' => 'Card issuer is not registered and/or certified',
        'N' => 'Code invalid or not matched',
    ];

    public static $map = [
        'S' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_PROVIDED,
        'P' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        'U' => ErrorCode::BAD_REQUEST_CARD_ISSUER_INVALID,
        'N' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
    ];
}
