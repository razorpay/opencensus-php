<?php

namespace RZP\Gateway\Upi\Mindgate\ErrorCodes;

use RZP\Gateway\Upi\Mindgate\ResponseFields;

class ErrorFields
{
    public static $errorCodeMap = [
        ResponseFields::STATUS   => 'statusCodeMap',
        ResponseFields::RESPCODE => 'errorCodeMap',
    ];

    public static $errorDescriptionMap = [
        ResponseFields::STATUS   => 'statusDescriptionMap',
        ResponseFields::RESPCODE => 'errorDescriptionMap',
    ];

    public static function getErrorCodeFields()
    {
        return [
            ResponseFields::RESPCODE,
            ResponseFields::STATUS,
        ];
    }
}
