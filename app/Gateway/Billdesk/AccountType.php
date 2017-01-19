<?php

namespace RZP\Gateway\Billdesk;

class AccountType
{
    const PRIMARY   = 'primary';
    const SECONDARY = 'secondary';
    const UNKNOWN   = 'unknown';


    const ACCOUNT_MAP = [
        "R5"      => self::PRIMARY,
        "R8"      => self::SECONDARY,
    ];
}
