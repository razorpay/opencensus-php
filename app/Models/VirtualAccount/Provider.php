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
        self::YESBANK   => [
            // Todo
        ],
        self::KOTAK     => [
            'RAZO',
            // 'RZRP',
            // 'RAZR',
            //
            // This is to be used for our own nodal account,
            // DO NOT REFUND PAYMENTS MADE HERE
            // 'RZRN',
        ],
        self::VVS       => [
            'RAZO',
        ],
    ];

    const DEFAULT_HANDLE_MAPPING = [
        'RAZO' => 'RPAY',
    ];

    // The default details are fixed by each provider, most specifically
    // the IFSC code where the virtual accounts are said to be located.
    // Further details can be derived from this IFSC, but are not required
    // for the virtual accounts use case
    //
    const DEFAULT_DETAILS = [
        self::YESBANK => [
            BankAccount::IFSC_CODE => 'YESB0CMSNOC',
        ],
        self::KOTAK => [
            BankAccount::IFSC_CODE => 'KKBK0000958',
        ],
        self::VVS => [
            BankAccount::IFSC_CODE => 'RAZR0000001',
        ],
    ];

    const IP = [
        self::YESBANK => [
            // Todo
        ],
        self::KOTAK => [
            '14.141.97.12',
        ],
        self::VVS => [
            '*',
        ],
    ];

    public static function getBankCode(string $provider)
    {
        $ifsc = self::DEFAULT_DETAILS[$provider][BankAccount::IFSC_CODE];

        return substr($ifsc, 0, 4);
    }

    public static function validateIp(string $provider, string $ip)
    {
        $providerIps = self::IP[$provider];

        if (in_array('*', $providerIps, true) === true)
        {
            return true;
        }

        if (in_array($ip, $providerIps, true) === true)
        {
            return true;
        }

        return false;
    }
}
