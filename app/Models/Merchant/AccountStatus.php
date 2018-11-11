<?php

namespace RZP\Models\Merchant;

use RZP\Exception\BadRequestValidationFailureException;

/**
 * Holds different values for query parameter ACCOUNT_STATUS.
 */
final class AccountStatus
{
    const ALL                         = 'all';
    const SUSPENDED                   = 'suspended';
    const ARCHIVED                    = 'archived';
    const ACTIVATED                   = 'activated';
    const PENDING_OLD                 = 'pending_old';  // To be removed; for bc
    const ARCHIVED_OLD                = 'archived_old'; // To be removed; for bc
    const PENDING                     = 'pending';
    const PENDING_UNDER_REVIEW        = 'pending_under_review';
    const PENDING_NEEDS_CLARIFICATION = 'pending_needs_clarification';
    const DEAD                        = 'dead';
    const INSTANTLY_ACTIVATED         = 'instantly_activated';
    const REJECTED                    = 'rejected';

    public static function isValid($value)
    {
        $key = __CLASS__ . '::' . strtoupper($value);

        return ((defined($key) === true) and (constant($key) === $value));
    }

    public static function validate($value)
    {
        if (self::isValid($value) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid account status: ' . $value);
        }
    }
}
