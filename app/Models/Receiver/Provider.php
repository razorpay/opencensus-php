<?php

namespace RZP\Models\Receiver;

use RZP\Models\BankAccount\Entity as BankAccount;

class Provider
{
    const YES_BANK = 'yesbank';

    const KOTAK    = 'kotak';

    const MASTER = [
        Provider::YES_BANK => 'RAZORP',
        Provider::KOTAK    => 'RZP',
    ];

    const ACCOUNT_DETAILS = [
        Provider::YES_BANK => [
            BankAccount::IFSC_CODE            => 'YESB0CMSNOC',
            BankAccount::BENEFICIARY_ADDRESS1 => 'YES BANK TOWER',
            BankAccount::BENEFICIARY_ADDRESS2 => 'IFC2 8TH FLOOR',
            BankAccount::BENEFICIARY_ADDRESS3 => 'SB MARG',
            BankAccount::BENEFICIARY_ADDRESS4 => 'ELPHINSTONE',
            BankAccount::BENEFICIARY_PIN      => '400013',
            BankAccount::BENEFICIARY_CITY     => 'MUMBAI',
            BankAccount::BENEFICIARY_STATE    => 'MH',
        ],
    ];
}
