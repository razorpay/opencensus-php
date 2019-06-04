<?php

namespace RZP\Models\BankingAccount;

use RZP\Exception\BadRequestValidationFailureException;

class RblStatus
{
    const OPEN           = 'open';
    const DRAFT          = 'draft';
    const REWORK         = 'rework';
    const VERIFIED       = 'verified';
    const DISCREPANCY    = 'discrepancy';
    const CLOSED         = 'closed';
    const CANCELLED      = 'cancelled';

    protected static $bankToInternalStatusMap = [
        Status::PROCESSING     => [self::OPEN, self::DRAFT, self::REWORK, self::VERIFIED, self::DISCREPANCY],
        Status::PROCESSED      => [self::CLOSED],
        Status::CANCELLED      => [self::CANCELLED],
        Status::INITIATED      => [],
        Status::UNSERVICEABLE  => [],
        Status::CREATED        => [],
    ];

    public static function isValidStatus(string $status)
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        if (((defined($key) === true) and (constant($key) === $status)) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid RBL status',
                Entity::BANK_INTERNAL_STATUS,
                [
                 Entity::BANK_INTERNAL_STATUS => $status
                ]);
        }
    }

    public static function isValidRblToInternalStatusMapping(string $bankStatus, string $status)
    {
        $statusList = self::$bankToInternalStatusMap[$status];

        if (in_array($bankStatus, $statusList, true) === false)
        {
            throw new BadRequestValidationFailureException(
                 'bank internal status ' . $bankStatus . ' cannot be passed with status ' . $status,
                Entity::BANK_INTERNAL_STATUS,
              [
                  Entity::BANK_INTERNAL_STATUS => $bankStatus,
                  Entity::STATUS               => $status
              ]);
        }
    }
}
