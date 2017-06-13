<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\BankAccount\Entity as BankAccount;

class Provider
{
    const YESBANK = 'yesbank';
    const KOTAK    = 'kotak';

    // Mock provider
    // Named so because it only works in tests
    const VVS      = 'vvs';

    const MASTER = [
        Provider::YESBANK   => 'RAZORP',
        Provider::KOTAK     => 'RZRP',
        Provider::VVS       => 'RAZOR',
    ];

    const DEFAULT_DETAILS = [
        Provider::YESBANK => [
            BankAccount::IFSC_CODE => 'YESB0CMSNOC',
        ],
        Provider::KOTAK => [
            BankAccount::IFSC_CODE => 'KKBK0000958',
        ],
        Provider::VVS => [
            BankAccount::IFSC_CODE => 'RAZR0000001',
        ],
    ];
}
