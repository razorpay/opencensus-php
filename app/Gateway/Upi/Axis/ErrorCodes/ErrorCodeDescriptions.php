<?php

namespace RZP\Gateway\Upi\Axis\ErrorCodes;

use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Axis\Constants;

class ErrorCodeDescriptions extends Base\ErrorCodes\Upi\ErrorCodeDescriptions
{
    public static $codeDescriptionMap = [
        '500'  => 'Internal Server Error - checksum not generated properly',
        '444'  => 'Checksum does not match',
        '125'  => 'Validation error - invalid special characters in txn id',
        '303'  => 'Duplicate transaction ID',
        '111'  => [
            Constants::EMPTI      => 'Missing or empty parameter',
            Constants::DUPLICATE  => 'Duplicate collect request',
            Constants::TOKEN      => 'Token not found',
            Constants::ABSENT     => 'Refund not found',
        ],
        'F'    => 'Failed transaction',
        'D'    => 'Deemed transaction',
        'P'    => 'Transaction is pending',
        'E'    => 'Transaction expired',
        'R'    => 'Transaction rejected',
        'A79'  => 'REFUND AMOUNT CANNOT BE ZERO OR NEGATIVE',
        'A78'  => 'DUPLICATE REFUND ID',
        '077'  => 'RECORD NOT AVAILABLE',
        '076'  => 'REFUND LIMIT CROSSED',
        '222'  => 'ERROR WHILE PROCESSING REFUND REQUEST',
        'FL'   => 'Failed',
        'FP'   => 'Failed',
        'ML01' => 'Multiple Order Ids found',
    ];

    public static function getErrorCodeDescription($code, $content)
    {
        if (isset(self::$codeDescriptionMap[$code]) === true)
        {
            if (is_array(self::$codeDescriptionMap[$code]) === true)
            {
                $field = null;

                if (isset($content['result']) === true)
                {
                    $field = 'result';
                }
                else if (isset($content['gatewayResponseMessage']) === true)
                {
                    $field = 'gatewayResponseMessage';
                }

                if ($field != null)
                {
                    if (str_contains(strtoupper($content[$field]), Constants::EMPTI) === true)
                    {
                        return self::$codeDescriptionMap[$code][Constants::EMPTI];
                    }

                    if (str_contains(strtoupper($content[$field]), Constants::DUPLICATE) === true)
                    {
                        return self::$codeDescriptionMap[$code][Constants::DUPLICATE];
                    }

                    if (str_contains(strtoupper($content[$field]), Constants::TOKEN) === true)
                    {
                        return self::$codeDescriptionMap[$code][Constants::TOKEN];
                    }
                }

                return 'Unknown Error';
            }

            return self::$codeDescriptionMap[$code];
        }

        if (isset(self::$errorDescriptionMap[$code]) === true)
        {
            return self::$errorDescriptionMap[$code];
        }

        return 'Unknown Error';
    }
}
