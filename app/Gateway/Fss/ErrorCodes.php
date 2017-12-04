<?php

namespace RZP\Gateway\Fss;

use RZP\Error\ErrorCode;

class ErrorCodes
{
    protected static $reasonCodes = [
        'IPAY0100254'   => 'Merchant not enabled for performing transaction.',
    ];

    protected static $errorCodeMap = [
        'IPAY0100254'   => ErrorCode::BAD_REQUEST_MERCHANT_NO_TERMINAL_ASSIGNED,
    ];
}