<?php

namespace RZP\Models\UpiMandate;

use RZP\Exception\InvalidArgumentException;

class Status
{
    const CREATED   = 'created';

    const CONFIRMED = 'confirmed';

    const REJECTED  = 'rejected';

    const REVOKED   = 'revoked';

    const PAUSED    = 'paused';

    public static function isUpiMandateStatusValid($status): bool
    {
        return (defined(__CLASS__ . '::' . strtoupper($status)));
    }

    public static function validateUpiMandateStatus($status)
    {
        if (self::isUpiMandateStatusValid($status) === false)
        {
            throw new InvalidArgumentException(
                'Invalid recurring type',
                [
                    'field'            => Entity::STATUS,
                    'recurring_status' => $status
                ]);
        }
    }
}
