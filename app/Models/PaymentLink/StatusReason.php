<?php

namespace RZP\Models\PaymentLink;

/**
 * Status of a payment link is active or inactive.
 * For active link, status_reason is null;
 * For inactive link, valid values are : expired,
 * deactivated and completed
 *
 * Class StatusReason
 * @package RZP\Models\PaymentLink
 */
class StatusReason
{
    const EXPIRED     = 'expired';
    const DEACTIVATED = 'deactivated';
    const COMPLETED   = 'completed';

    public static function isValid(string $statusReason): bool
    {
        if ($statusReason === null)
        {
            return true;
        }

        $key = __CLASS__ . '::' . strtoupper($statusReason);

        return ((defined($key) === true) and (constant($key) === $statusReason));
    }
}
