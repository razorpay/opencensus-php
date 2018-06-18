<?php

namespace RZP\Models\PaymentLink;

use RZP\Exception\BadRequestValidationFailureException;

/**
 * Status of a payment link is active or inactive. Additionally we have status reason accompanying the status.
 * - For active link, status_reason is null.
 * - For inactive link, valid values are : expired, deactivated and completed.
 *
 * @package RZP\Models\PaymentLink
 */
class StatusReason
{
    const EXPIRED     = 'expired';
    const DEACTIVATED = 'deactivated';
    const COMPLETED   = 'completed';

    public static function isValid(string $statusReason): bool
    {
        $key = __CLASS__ . '::' . strtoupper($statusReason);

        return ((defined($key) === true) and (constant($key) === $statusReason));
    }

    public static function checkStatusReason(string $statusReason)
    {
        if (self::isValid($statusReason) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid status reason: ' . $statusReason);
        }
    }
}
