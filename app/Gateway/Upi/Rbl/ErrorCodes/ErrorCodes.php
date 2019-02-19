<?php

namespace RZP\Gateway\Upi\Rbl\ErrorCodes;

use RZP\Gateway\Base;
use RZP\Error\ErrorCode;

class ErrorCodes extends Base\ErrorCodes\Upi\ErrorCodes
{
    public static function getErrorCode($code)
    {
        return self::getInternalErrorCode($code);
    }
}
