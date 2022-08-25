<?php

namespace RZP\Gateway\Enach\Citi;

use RZP\Error;
use RZP\Exception\GatewayErrorException;

class Status
{
    const DEBIT_SUCCESS         = '1';
    const DEBIT_REJECT          = '0';
    const DEBIT_INITIAL_REJECT  = '2';
    const DEBIT_PENDING         = '3';

    const REGISTRATION_SUCCESS          = 'accepted';
    const REGISTRATION_FAILURE          = 'rejected';
    const REGISTRATION_INITIAL_FAILURE  = 'initial reject';
    const REGISTRATION_ACKNOWLEDGED     = 'initial';
    const REGISTRATION_PENDING          = 'pending';
    const REGISTRATION_PENDING_BANK     = 'pending for confirmation from destination bank';

    const PAYMENT_SUCCESS   = 'success';
    const PAYMENT_FAILED    = 'failed';
    const PAYMENT_PENDING   = 'pending';

    const REGISTRATION_FILE_STATUSES = [
        self::REGISTRATION_SUCCESS,
        self::REGISTRATION_FAILURE,
        self::REGISTRATION_ACKNOWLEDGED,
        self::REGISTRATION_INITIAL_FAILURE,
        self::REGISTRATION_PENDING,
        self::REGISTRATION_PENDING_BANK,
    ];

    const REGISTRATION_ACKNOWLEDGED_STATUSES = [
        self::REGISTRATION_PENDING,
        self::REGISTRATION_ACKNOWLEDGED,
        self::REGISTRATION_PENDING_BANK,
    ];

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

        return (in_array($status, self::REGISTRATION_ACKNOWLEDGED_STATUSES) === true);
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

    public static function bankMappedStatus($status): string
    {
        if (in_array($status, self::DEBIT_FILE_STATUSES) === false)
        {
            return "";
        }

        if ($status === self::DEBIT_SUCCESS)
        {
            return self::PAYMENT_SUCCESS;
        }
        else if (($status === self::DEBIT_REJECT) or ($status === self::DEBIT_INITIAL_REJECT))
        {
            return self::PAYMENT_FAILED;
        }
        else if ($status === self::DEBIT_PENDING)
        {
            return self::PAYMENT_PENDING;
        }

        return "";
    }
}
