<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\BankAccount\Entity as BankAccount;

class Provider
{
    const YES_BANK = 'yesbank';
    const KOTAK    = 'kotak';

    // Mock provider
    // Named so because it only works in tests
    const GAVASKAR = 'gavaskar';

    const MASTER = [
        Provider::YES_BANK  => 'RAZORP',
        Provider::KOTAK     => 'RAZR',
        Provider::GAVASKAR  => 'RAZOR',
    ];

    const DEFAULT_DETAILS = [
        Provider::YES_BANK => [
            BankAccount::IFSC_CODE => 'YESB0CMSNOC',
        ],
        Provider::KOTAK => [
            BankAccount::IFSC_CODE => 'KKBK0000958',
        ],
        Provider::GAVASKAR => [
            BankAccount::IFSC_CODE => 'RAZR0000001',
        ],
    ];
}
