<?php

namespace RZP\Models\PaymentLink;

use RZP\Exception\BadRequestValidationFailureException;

class DonationGoalTrackerType
{
    const DONATION_AMOUNT_BASED     = 'donation_amount_based';
    const DONATION_SUPPORTER_BASED  = 'donation_supporter_based';

    public static function isValid(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    /**
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public static function checkType(string $type)
    {
        if (self::isValid($type) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid Tracker Type: ' . $type);
        }
    }
}
