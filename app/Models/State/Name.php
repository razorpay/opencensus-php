<?php

namespace RZP\Models\State;

use RZP\Exception;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Detail\Status as Status;

class Name
{
    // States of the action
    const APPROVED             = 'approved';
    const REJECTED             = 'rejected';
    const EXECUTED             = 'executed';
    const OPEN                 = 'open';
    const CLOSED               = 'closed';
    const FAILED               = 'failed';

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
        self::FAILED,

        // Activation Action States
        Status::ACTIVATED_MCC_PENDING,
        Status::INSTANTLY_ACTIVATED,
        Status::UNDER_REVIEW,
        Status::NEEDS_CLARIFICATION,
        Status::ACTIVATED,
        Status::REJECTED,
        Status::ACTIVATED_KYC_PENDING,
        Status::KYC_QUALIFIED_UNACTIVATED
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
