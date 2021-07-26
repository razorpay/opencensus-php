<?php

namespace RZP\Gateway\Enach\Npci\Physical\Icici\Registration;

use RZP\Error;
use RZP\Exception\GatewayErrorException;

class Status
{
    const SUCCESS = 'true';
    const FAILURE = 'false';

    const REGISTRATION_FILE_STATUSES = [
        self::SUCCESS,
        self::FAILURE
    ];

    public static function isRegistrationSuccess($status): bool
    {
        $status = strtolower($status);

        if (in_array($status, self::REGISTRATION_FILE_STATUSES) === false)
        {
            throw new GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                '',
                ['status' => $status]);
        }

        return ($status === self::SUCCESS);
    }
}
