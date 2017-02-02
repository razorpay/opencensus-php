<?php

namespace RZP\Gateway\Billdesk;

class AccountType
{
    const UNKNOWN   = 'unknown';
    const PRIMARY   = 'primary';
    const SECONDARY = 'secondary';

    const ACCOUNT_MAP = [
        'R5' => self::PRIMARY,
        'R8' => self::SECONDARY,
    ];
}
