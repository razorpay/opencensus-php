<?php

namespace RZP\Gateway\Netbanking\Vijaya;

use RZP\Exception;

class VerifyResponse
{
    const SUCCESS = 'Your Payment is Successful';
    const FAILURE = 'Payment Record Not Found Check the Parameters sent';

    const STATUS_LIST = [
        self::SUCCESS,
        self::FAILURE
    ];

    public static function isSuccess($status): bool
    {
        if (in_array($status, self::STATUS_LIST) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                $status,
                'Gateway response status is invalid'
            );
        }

        return ($status === self::SUCCESS);
    }
}
