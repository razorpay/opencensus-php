<?php

namespace RZP\Gateway\Netbanking\Vijaya;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;

class VerifyResponse
{
    const SUCCESS = 'Your Payment is Successful';
    const FAILURE = 'Payment Record Not Found Check the Parameters sent';

    const STATUS_LIST = [
        self::SUCCESS,
        self::FAILURE
    ];

    public static function isSuccess($content): bool
    {
        if (in_array(self::SUCCESS, $content))
        {
            return true;
        }
        else if (in_array(self::FAILURE, $content))
        {
            return false;
        }
        else
        {
            throw new LogicException(
                'Invalid Verify Response',
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                [
                    'gateway' => 'netbanking_vijaya',
                    'content' => $content
                ]);
        }
    }
}
