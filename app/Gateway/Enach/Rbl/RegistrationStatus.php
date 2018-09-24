<?php

namespace RZP\Gateway\Enach\Rbl;

use RZP\Models\Customer\Token;

class RegistrationStatus
{
    const SUCCESS = 'true';
    const FAILURE = 'false';

    const STATUS_TO_RECURRING_STATUS_MAP = [
        self::SUCCESS => Token\RecurringStatus::CONFIRMED,
        self::FAILURE => Token\RecurringStatus::REJECTED
    ];
}
