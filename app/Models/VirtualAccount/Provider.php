<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\BankAccount\Entity as BankAccount;

class Provider
{
    const YES_BANK = 'yesbank';
    const KOTAK    = 'kotak';

    const MASTER = [
        Provider::YES_BANK => 'RAZORP',
        Provider::KOTAK    => 'RAZR',
    ];

    const DEFAULT_DETAILS = [
        Provider::YES_BANK => [
            BankAccount::IFSC_CODE => 'YESB0CMSNOC',
        ],
        Provider::KOTAK => [
            BankAccount::IFSC_CODE => 'KKBK0000958',
        ],
    ];
}
