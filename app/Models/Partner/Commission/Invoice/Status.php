<?php

namespace RZP\Models\Partner\Commission\Invoice;

use RZP\Exception;

class Status
{
    const ISSUED    = 'issued';
    const APPROVED  = 'approved';
    const PROCESSED = 'processed';

    const UNDER_REVIEW = 'under_review';

    const VALID_STATUSES = [
        self::ISSUED,
        self::APPROVED,
        self::PROCESSED,
        self::UNDER_REVIEW,
    ];

    const ALLOWED_NEXT_STATUSES_MAPPING = [
        self::ISSUED       => [self::UNDER_REVIEW, self::APPROVED],
        self::UNDER_REVIEW => [self::APPROVED, self::PROCESSED],
        self::APPROVED     => [self::PROCESSED],
        self::PROCESSED    => [],
    ];

    const ALLOWED_STATUSES_FOR_MERCHANT = [
        self::UNDER_REVIEW
    ];

    public static function isValidStateTransition(string $current, string $next)
    {
        return (in_array($next, self::ALLOWED_NEXT_STATUSES_MAPPING[$current], true) === true);
    }

    public static function validateStatus(string $status)
    {
        if (in_array($status, self::VALID_STATUSES, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid status: ' . $status);
        }
    }
}
