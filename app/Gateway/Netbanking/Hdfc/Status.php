<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use RZP\Error;
use RZP\Exception\GatewayErrorException;

class Status
{
    const DEBIT_SUCCESS    = 'success';
    const DEBIT_REJECT     = 'rejected';
    const DEBIT_CANCELLED  = 'cancelled';
    const DEBIT_FAILED     = 'failure';

    const REGISTRATION_SUCCESS = 'success';
    const REGISTRATION_FAILURE = 'failure';

    const PAYMENT_SUCCESS   = 'success';
    const PAYMENT_FAILED    = 'failed';

    const REGISTRATION_FILE_STATUSES = [
        self::REGISTRATION_SUCCESS,
        self::REGISTRATION_FAILURE,
    ];

    const DEBIT_FILE_STATUSES = [
        self::DEBIT_SUCCESS,
        self::DEBIT_REJECT,
        self::DEBIT_CANCELLED,
        self::DEBIT_FAILED,
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

    /**
     * @param $status
     * @return bool
     * @throws GatewayErrorException
     */
    public static function isDebitSuccess($status)
    {
        $status = strtolower($status);

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

    public static function bankMappedStatus($status)
    {
        $status = strtolower($status);

        if (in_array($status, self::DEBIT_FILE_STATUSES) === false)
        {
            return "";
        }

        if ($status === self::DEBIT_SUCCESS)
        {
            return self::PAYMENT_SUCCESS;
        }
        else if (($status === self::DEBIT_REJECT) or ($status === self::DEBIT_CANCELLED) or ($status === self::DEBIT_FAILED))
        {
            return self::PAYMENT_FAILED;
        }

        return "";
    }
}
