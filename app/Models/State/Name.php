<?php

namespace RZP\Models\State;

use RZP\Exception;
use RZP\Error\PublicErrorDescription;


class Name
{
    // States of the action
    const APPROVED             = 'approved';
    const REJECTED             = 'rejected';
    const EXECUTED             = 'executed';
    const OPEN                 = 'open';
    const CLOSED               = 'closed';

    // Action States post which we do not accept any state changes
    const CLOSED_ACTION_STATES = [
        self::REJECTED,
        self::EXECUTED,
        self::CLOSED,
    ];

    const OPEN_ACTION_STATES   = [
        self::OPEN,
        self::APPROVED,
    ];

    const VALID_ACTION_STATES = [
        self::APPROVED,
        self::REJECTED,
        self::EXECUTED,
        self::OPEN,
        self::CLOSED,
    ];

    public static function validate(string $state)
    {
        if (self::isValid($state) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_INVALID_STATE,
                Entity::NAME,
                [Entity::NAME => $state]);
        }
    }

    public static function isValid(string $state): bool
    {
        return (in_array($state, self::VALID_ACTION_STATES, true) === true);
    }
}
