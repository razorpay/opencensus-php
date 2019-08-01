<?php

namespace RZP\Gateway\Paysecure\ErrorCodes;

use RZP\Gateway\Paysecure\Fields;

class ErrorFields
{
    // dummy field for mapping paysecure specific errors received in field:errorcode
    //
    // for every field present in response, there can be only 1 corresponding error code map which
    // contains field -> error code mapping. but in case of paysecure, we receive both
    // ISO 8583 error codes and paysecure error codes in same field: errorcode. So we add a dummy field instead
    // with mapping to paysecure specific error code. Now, base error framework calls ErrorCodes::getErrorFieldName()
    // in which this dummy field gets resolved to actual field:errorcode. error codes are mapped using paysecure specific
    // codes and the life goes on...
    const DUMMY_ERROR_CODE_FIELD = 'error_code';

    public static $errorCodeMap = [
        self::DUMMY_ERROR_CODE_FIELD    => 'errorCodeMappings',
        Fields::ERROR_CODE              => 'errorCodeMap',
        Fields::ACCU_RESPONSE_CODE      => 'errorCodeMappings',
        Fields::STATUS                  => 'fallBackMapping',
    ];

    public static $errorDescriptionMap = [
        self::DUMMY_ERROR_CODE_FIELD    => 'errorCodeDescriptions',
        Fields::ERROR_CODE              => 'errorDescriptionMap',
        Fields::ACCU_RESPONSE_CODE      => 'errorCodeDescriptions',
        Fields::STATUS                  => 'errorCodeDescriptions',
    ];

    public static function getErrorCodeFields()
    {
        return [
            self::DUMMY_ERROR_CODE_FIELD,
            Fields::ERROR_CODE,
            Fields::ACCU_RESPONSE_CODE,
            Fields::STATUS,
        ];
    }
}
