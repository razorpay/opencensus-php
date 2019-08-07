<?php

namespace RZP\Gateway\Hitachi\ErrorCodes;

use RZP\Gateway\Base\ErrorCodes\Cards;

class ErrorCodeDescriptions extends Cards\ErrorCodeDescriptions
{
    public static $authRespDescriptionMap = [
        'IC' => 'Invalid currency code'
    ];

    public static function getErrorFieldName($fieldName)
    {
        return 'pRespCode';
    }
}
