<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Constants\Mode;

class Provider
{
    const YESBANK   = 'yesbank';
    const KOTAK     = 'kotak';

    // Dashboard acts as a mock provider bank,
    // and is used to run tests.
    // Also used when merchant makes a test
    // payment to a virtual account.
    const DASHBOARD = 'dashboard';

    const TEST_PROVIDERS = [
        self::DASHBOARD,
    ];

    // Kotak's whitelisted IP
    const KOTAK_IP = '14.141.97.12';

    // Each provider gives us a range of bank accounts
    // by alloting an account number prefix/master/root
    //
    // We use the default root along with out own handle,
    // in cases where handle is unset.
    //
    // Standard root is used when handle is set.
    const ROOT = [
        self::YESBANK   => [
            // Todo
            'default'  => '',
            'standard' => '',
            'reserved' => [],
        ],
        self::KOTAK     => [
            'default'  => 'RAZO',
            'standard' => 'RZRP',
            'reserved' => [
                // This is to be used for our own nodal account,
                // DO NOT REFUND PAYMENTS MADE HERE
                'RAZR',
                'RZRN',
            ],
        ],
        self::DASHBOARD       => [
            'default'  => 'RAZO',
            'standard' => 'RZRP',
            'reserved' => [
                'RZRN',
            ],
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
        self::DASHBOARD => [
            BankAccount::IFSC_CODE => 'RAZR0000001',
        ],
    ];

    const IP = [
        self::YESBANK => [
            // Todo
            '*',
        ],
        self::KOTAK => [
            self::KOTAK_IP,
        ],
        self::DASHBOARD => [
            '*',
        ],
    ];

    public static function getBankCode(string $provider)
    {
        $ifsc = self::DEFAULT_DETAILS[$provider][BankAccount::IFSC_CODE];

        return substr($ifsc, 0, 4);
    }

    // Checks if request is originating from known IP for the given provider
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

    // Blocks test providers for making live requests
    //
    // Unused right now because Kotak is making changes in their
    // format, and IMPS testing is ongoing, so we need to use
    // Dashboard to make corrective requests occasionally.
    //
    // TODO: Use in validateProvider when changes are stable
    public static function validateMode(string $provider, string $mode)
    {
        $isLiveProvider = (in_array($provider, self::TEST_PROVIDERS, true) === false);

        return (($mode === Mode::TEST) or $isLiveProvider);
    }

    public static function isReservedAccount(string $accountNumber, string $provider)
    {
        $reservedRoots = self::ROOT[$provider]['reserved'];

        foreach ($reservedRoots as $root)
        {
            if (substr($accountNumber, 0, strlen($root)) === $root)
            {
                return true;
            }
        }

        return false;
    }
}
