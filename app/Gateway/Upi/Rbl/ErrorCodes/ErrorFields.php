<?php

namespace RZP\Gateway\Upi\Rbl\ErrorCodes;

use RZP\Gateway\Upi\Rbl\Fields;

class ErrorFields
{
    public static $errorCodeMap = [
        Fields::NPCI_ERROR_CODE => 'errorCodeMap',
    ];

    public static $errorDescriptionMap = [
        Fields::NPCI_ERROR_CODE => 'errorDescriptionMap',
    ];

    public static function getErrorCodeFields()
    {
        return [
            Fields::NPCI_ERROR_CODE,
        ];
    }
}
