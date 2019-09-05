<?php

namespace RZP\Models\NodalBeneficiary;

class Status
{
    const FAILED     = 'failed';
    const CREATED    = 'created';
    const PENDING    = 'pending';
    const REGISTERED = 'registered';
    const VERIFIED   = 'verified';

    protected static $registrationStatuses = [
        self::CREATED,
        self::PENDING,
        self::FAILED,
        self::REGISTERED,
        self::VERIFIED,
    ];

    protected static $allowedStateTransition = [
        self::FAILED     => [ self::FAILED, self::PENDING, self::REGISTERED ],
        self::PENDING    => [ self::PENDING, self::FAILED, self::REGISTERED ],
        self::CREATED    => [ self::CREATED, self::PENDING, self::REGISTERED, self::FAILED ],
        self::REGISTERED => [ self::REGISTERED, self::VERIFIED, self::FAILED ],
        self::VERIFIED   => [ self::VERIFIED]
    ];

    /**
     * @return array
     */
    public static function getAllowedBeneficiaryStatus(): array
    {
        return self::$registrationStatuses;
    }

    /**
     * @return array
     */
    public static function getAllowedStatusChange(): array
    {
        return self::$allowedStateTransition;
    }
}
