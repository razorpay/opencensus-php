<?php

namespace RZP\Gateway\Enach\Citi;

use RZP\Error;
use RZP\Exception\GatewayErrorException;

class Status
{
    const DEBIT_SUCCESS = '1';
    const DEBIT_REJECT  = '0';

    const REGISTRATION_SUCCESS          = 'accept';
    const REGISTRATION_FAILURE          = 'reject';
    const REGISTRATION_ACKNOWLEDGED     = 'initial';

    const REGISTRATION_FILE_STATUSES = [
        self::REGISTRATION_SUCCESS,
        self::REGISTRATION_FAILURE,
        self::REGISTRATION_ACKNOWLEDGED,
    ];

    const DEBIT_FILE_STATUSES = [
        self::DEBIT_SUCCESS,
        self::DEBIT_REJECT,
    ];

    /**
     * @param $status
     * @return bool
     * @throws GatewayErrorException
     */
    public static function isRegistrationSuccess($status, $content)
    {
        $status = strtolower($status);

        if (in_array($status, self::REGISTRATION_FILE_STATUSES) === false)
        {
            throw new GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                '',
                ['content' => $content]);
        }

        return ($status === self::REGISTRATION_SUCCESS);
    }

    public static function isRegistrationAcknowledged($status, $content)
    {
        $status = strtolower($status);

        if (in_array($status, self::REGISTRATION_FILE_STATUSES) === false)
        {
            throw new GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                '',
                ['content' => $content]);
        }

        return ($status === self::REGISTRATION_ACKNOWLEDGED);
    }

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
}
