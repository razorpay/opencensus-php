<?php

namespace RZP\Models\BankingAccountTpv;

class Status
{
    const PENDING = 'pending';

    const APPROVED = 'approved';

    const REJECTED = 'rejected';

    const ALL_STATUSES = [
        self::PENDING,
        self::APPROVED,
        self::REJECTED
    ];

    public static function getAll()
    {
        return self::ALL_STATUSES;
    }
}
