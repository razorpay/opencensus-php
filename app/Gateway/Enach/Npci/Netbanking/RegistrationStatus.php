<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

use RZP\Error\ErrorCode;
use RZP\Models\Customer\Token;
use RZP\Exception\GatewayErrorException;

class RegistrationStatus
{
    // online flow. This does not indicate final registration status. That is provided by the registration
    // response file
    const SUCCESS = 'true';
    const FAILURE = 'false';

    const VERIFY_SUCCESS = '000';

    const STATUS_TO_RECURRING_STATUS_MAP = [
        self::SUCCESS => Token\RecurringStatus::CONFIRMED,
        self::FAILURE => Token\RecurringStatus::REJECTED
    ];

    const REGISTRATION_SUCCESS = 'active';
    const REGISTRATION_FAILURE = 'cancel';

    const REGISTRATION_STATUS = [
        self::REGISTRATION_SUCCESS,
        self::REGISTRATION_FAILURE
    ];

    public static function isFileRegistrationSuccess($status, $content)
    {
        $status = strtolower($status);

        self::throwInvalidResponseErrorIfCodeNotMapped($status, self::REGISTRATION_STATUS, $content);

        return ($status === self::REGISTRATION_SUCCESS);
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
