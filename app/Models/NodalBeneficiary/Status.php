<?php

namespace RZP\Models\NodalBeneficiary;

class Status
{
    const FAILED     = 'failed';
    const CREATED    = 'created';
    const PENDING    = 'pending';
    const REGISTERED = 'registered';

    protected static $registrationStatuses = [
        self::CREATED,
        self::PENDING,
        self::FAILED,
        self::REGISTERED
    ];

    protected static $allowedStateTransition = [
        self::FAILED     => [ self::FAILED, self::PENDING, self::REGISTERED ],
        self::PENDING    => [ self::PENDING, self::FAILED, self::REGISTERED ],
        self::CREATED    => [ self::CREATED, self::PENDING ],
        self::REGISTERED => [ self::REGISTERED, self::FAILED ]
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