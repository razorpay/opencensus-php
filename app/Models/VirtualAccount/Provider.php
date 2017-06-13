<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\BankAccount\Entity as BankAccount;

class Provider
{
    const YESBANK = 'yesbank';
    const KOTAK    = 'kotak';

    // Mock provider bank
    // Named so because it only works in tests
    const VVS      = 'vvs';

    // Each provider gives us a range of bank accounts
    // by alloting an account number prefix/master/root
    //
    // We may have more than one root per provider, but
    // this will be leveraged later.
    const ROOT = [
        Provider::YESBANK   => 'RAZORP',
        Provider::KOTAK     => 'RZRP',
        Provider::VVS       => 'RAZOR',
    ];

    // The default details are fixed by each provider, most specifically
    // the IFSC code where the virtual accounts are said to be located.
    // Further details can be derived from this IFSC, but are not required
    // for the virtual accounts use case
    //
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
