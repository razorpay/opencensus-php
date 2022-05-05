<?php

namespace RZP\Models\PartnerBankHealth;

class Status
{
    const UP     = 'up';
    const DOWN   = 'down';

    public static function getAllowedStatuses()
    {
        return [
            self::UP,
            self::DOWN,
        ];
    }
}
