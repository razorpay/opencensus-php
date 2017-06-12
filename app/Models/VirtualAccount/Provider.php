<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\BankAccount\Entity as BankAccount;

class Provider
{
    const YES_BANK = 'yesbank';
    const KOTAK    = 'kotak';

    // Provider for tests
    const BLADE    = 'blade';

    const MASTER = [
        Provider::YES_BANK => 'RAZORP',
        Provider::KOTAK    => 'RAZR',
        Provider::BLADE    => 'RAZOR',
    ];

    const DEFAULT_DETAILS = [
        Provider::YES_BANK => [
            BankAccount::IFSC_CODE => 'YESB0CMSNOC',
        ],
        Provider::KOTAK => [
            BankAccount::IFSC_CODE => 'KKBK0000958',
        ],
        Provider::BLADE => [
            BankAccount::IFSC_CODE => 'RAZR0000001',
        ],
    ];
}
