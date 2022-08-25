<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;

class DebitFileStatus
{
    const ACCEPTED = 'accepted';
    const REJECTED = 'rejected';
    const PENDING  = 'pending';


    const DEBIT_STATUS = [
        self::ACCEPTED,
        self::REJECTED,
        self::PENDING,
    ];

    const PAYMENT_SUCCESS   = 'success';
    const PAYMENT_FAILED    = 'failed';
    const PAYMENT_PENDING   = 'pending';

    public static function isDebitSuccess($status, $content)
    {
        $status = strtolower($status);

        self::throwInvalidResponseErrorIfCodeNotMapped($status, self::DEBIT_STATUS, $content);

        return ($status === self::ACCEPTED);
    }

    public static function isDebitRejected($status, $content)
    {
        $status = strtolower($status);

        self::throwInvalidResponseErrorIfCodeNotMapped($status, self::DEBIT_STATUS, $content);

        return ($status === self::REJECTED);
    }

    public static function bankMappedStatus($status)
    {
        $status = strtolower($status);

        if (in_array($status, self::DEBIT_STATUS) === false)
        {
            return "";
        }

        if ($status === self::ACCEPTED)
        {
            return self::PAYMENT_SUCCESS;
        }
        else if ($status === self::REJECTED)
        {
            return self::PAYMENT_FAILED;
        }
        else if ($status === self::PENDING)
        {
            return self::PAYMENT_PENDING;
        }

        return "";
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
