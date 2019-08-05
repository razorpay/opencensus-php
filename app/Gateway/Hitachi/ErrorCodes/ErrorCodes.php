<?php

namespace RZP\Gateway\Hitachi\ErrorCodes;

use RZP\Error\ErrorCode;
use RZP\Gateway\Base\ErrorCodes\Cards;

class ErrorCodes extends Cards\ErrorCodes
{
    public static $authRespCodeMap = [
        'IC' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY,
    ];

    public static function getErrorFieldName($fieldName)
    {
        return 'pRespCode';
    }
}
