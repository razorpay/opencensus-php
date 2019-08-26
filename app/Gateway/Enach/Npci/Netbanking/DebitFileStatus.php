<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;

class DebitFileStatus
{
    const ACCEPTED = 'accepted';
    const REJECTED = 'rejected';

    const DEBIT_STATUS = [
        self::ACCEPTED,
        self::REJECTED
    ];

    public static function isDebitSuccess($status, $content)
    {
        $status = strtolower($status);

        self::throwInvalidResponseErrorIfCodeNotMapped($status, self::DEBIT_STATUS, $content);

        return ($status === self::ACCEPTED);
    }

    protected static function throwInvalidResponseErrorIfCodeNotMapped($status, array $mapping, array $content)
    {
        if (in_array($status, $mapping, true) === false)
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Gateway response code mapping not found.',
                $content);
        }
    }
}
