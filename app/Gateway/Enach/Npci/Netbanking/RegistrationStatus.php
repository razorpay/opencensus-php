<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

use RZP\Models\Customer\Token;

class RegistrationStatus
{
    const SUCCESS = 'true';
    const FAILURE = 'false';

    const STATUS_TO_RECURRING_STATUS_MAP = [
        self::SUCCESS => Token\RecurringStatus::INITIATED,
        self::FAILURE => Token\RecurringStatus::REJECTED
    ];
}
