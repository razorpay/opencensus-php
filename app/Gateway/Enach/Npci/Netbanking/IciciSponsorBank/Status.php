<?php

namespace RZP\Gateway\Enach\Npci\Netbanking\IciciSponsorBank;

use RZP\Error;
use RZP\Exception\GatewayErrorException;

class Status
{
    const DEBIT_SUCCESS         = '1';
    const DEBIT_REJECT          = '0';
    const DEBIT_INITIAL_REJECT  = '2';
    const DEBIT_PENDING         = '3';

    const DEBIT_FILE_STATUSES = [
        self::DEBIT_SUCCESS,
        self::DEBIT_REJECT,
        self::DEBIT_PENDING,
        self::DEBIT_INITIAL_REJECT,
    ];

    /**
     * @param $status
     * @return bool
     * @throws GatewayErrorException
     */
    public static function isDebitSuccess($status)
    {
        if (in_array($status, self::DEBIT_FILE_STATUSES) === false)
        {
            throw new GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                '',
                ['status' => $status]);
        }

        return ($status === self::DEBIT_SUCCESS);
    }

    /**
     * @param $status
     * @return bool
     * @throws GatewayErrorException
     */
    public static function isDebitRejected($status)
    {
        if (in_array($status, self::DEBIT_FILE_STATUSES) === false)
        {
            throw new GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                '',
                ['status' => $status]);
        }

        return (($status === self::DEBIT_REJECT) or ($status === self::DEBIT_INITIAL_REJECT));
    }
}
