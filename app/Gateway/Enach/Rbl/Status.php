<?php

namespace RZP\Gateway\Enach\Rbl;

use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;

class Status
{
    const DEBIT_SUCCESS = 'paid';
    const DEBIT_REJECT  = 'bounce';

    const ACKNOWLEDGE_SUCCESS = 'true';
    const ACKNOWLEDGE_FAILURE = 'false';

    const REGISTRATION_SUCCESS = 'active';
    const REGISTRATION_FAILURE = 'rejected';

    const PAYMENT_SUCCESS   = 'success';
    const PAYMENT_FAILED    = 'failed';

    protected static $registrationStatuses = [
        self::REGISTRATION_SUCCESS,
        self::REGISTRATION_FAILURE,
    ];

    protected static $debitStatuses = [
        self::DEBIT_SUCCESS,
        self::DEBIT_REJECT,
    ];

    public static function isAcknowledgeSuccess($status)
    {
        $status = strtolower($status);

        return ($status === self::ACKNOWLEDGE_SUCCESS);
    }

    public static function isRegistrationSuccess($status, $content)
    {
        $status = strtolower($status);

        self::throwInvalidResponseErrorIfCodeNotMapped($status, self::$registrationStatuses, $content);

        return ($status === self::REGISTRATION_SUCCESS);
    }

    public static function isDebitSuccess($status, $content)
    {
        $status = strtolower($status);

        self::throwInvalidResponseErrorIfCodeNotMapped($status, self::$debitStatuses, $content);

        return ($status === self::DEBIT_SUCCESS);
    }

    public static function isDebitRejected($status, $content)
    {
        $status = strtolower($status);

        self::throwInvalidResponseErrorIfCodeNotMapped($status, self::$debitStatuses, $content);

        return ($status === self::DEBIT_REJECT);
    }

    public static function bankMappedStatus($status)
    {
        $status = strtolower($status);

        if (in_array($status, self::$debitStatuses) === false)
        {
            return "";
        }

        if ($status === self::DEBIT_SUCCESS)
        {
            return self::PAYMENT_SUCCESS;
        }
        else if ($status === self::DEBIT_REJECT)
        {
            return self::PAYMENT_FAILED;
        }

        return "";
    }

    protected static function throwInvalidResponseErrorIfCodeNotMapped($status, array $mapping, array $content)
    {
        if (in_array($status, $mapping, true) === false)
        {
            // Log the whole row, that way it'd be easier to debug based on token id or
            // payment id in case it fails
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Gateway response code mapping not found.',
                $content);
        }
    }

}
