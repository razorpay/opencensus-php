<?php

namespace RZP\Models\P2p\BankAccount;

class Type
{
    const SAVINGS                = 'savings';
    const CURRENT                = 'current';
    const SOD                    = 'SOD';
    const UOD                    = 'UOD';

    const BANK_ACCOUNT_TYPES = [
        self::SAVINGS,
        self::CURRENT,
        self::SOD,
        self::UOD,
    ];
}
