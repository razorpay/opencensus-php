<?php

namespace RZP\Models\BankingAccountStatement\Details;

use RZP\Exception;

class Status
{
    const ACTIVE = 'active';
    const ARCHIVED = 'archived';
    const INACTIVE = 'inactive';
    const UNDER_MAINTENANCE = 'under_maintenance';

    public static function getStatuses()
    {
        return [
            self::ACTIVE,
            self::ARCHIVED,
            self::INACTIVE,
            self::UNDER_MAINTENANCE,
        ];
    }

    public static function getStatusesForProcessing()
    {
        return [
            self::ACTIVE,
            self::ARCHIVED,
            self::INACTIVE,
        ];
    }

    public static function validate(string $status = null)
    {
        if (in_array($status, self::getStatuses(), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid status: ' . $status);
        }
    }

    public static function getStatusesForWhichSubAccountPayoutIsAllowed()
    {
        return [
            self::ACTIVE,
            self::UNDER_MAINTENANCE,
        ];
    }
}
