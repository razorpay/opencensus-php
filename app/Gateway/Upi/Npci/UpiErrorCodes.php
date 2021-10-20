<?php

namespace RZP\Gateway\Upi\Npci;

class UpiErrorCodes
{
    /**
     * Error codes populated by UPI in various validation levels
     */

    const DEFAULT_ERROR = 'DF';

    // These response codes will be populated by UPI in the final response pay
    protected static $map = [
        'Z9' => 'INSUFFICIENT FUNDS IN CUSTOMER (REMITTER) ACCOUNT',
        'K1' => 'SUSPECTED FRAUD, DECLINE / TRANSACTIONS DECLINED BASED ON RISK SCORE BY REMITTER',
        'ZI' => 'SUSPECTED FRAUD, DECLINE / TRANSACTIONS DECLINED BASED ON RISK SCORE BY BENEFICIARY',
        'Z8' => 'PER TRANSACTION LIMIT EXCEEDED AS SET BY REMITTING MEMBER',
        'Z7' => 'TRANSACTION FREQUENCY LIMIT EXCEEDED AS SET BY REMITTING MEMBER',
        'Z6' => 'NUMBER OF PIN TRIES EXCEEDED',
        'ZM' => 'INVALID MPIN',
        'ZD' => 'VALIDATION ERROR',
        'ZR' => 'INVALID / INCORRECT OTP',
        'ZS' => 'OTP EXPIRED',
        'ZT' => 'OTP TRANSACTION LIMIT EXCEEDED',
        'ZX' => 'INACTIVE OR DORMANT ACCOUNT (REMITTER)',
        'XD' => 'INVALID AMOUNT (REMITTER)',
        'XF' => 'FORMAT ERROR (INVALID FORMAT) (REMITTER)',
        'XH' => 'ACCOUNT DOES NOT EXIST (REMITTER)',
        'XJ' => 'REQUESTED FUNCTION NOT SUPPORTED',
        'XL' => 'EXPIRED CARD, DECLINE (REMITTER)',
        'XN' => 'NO CARD RECORD (REMITTER)',
        'XP' => 'TRANSACTION NOT PERMITTED TO CARDHOLDER (REMITTER)',
        'XR' => 'RESTRICTED CARD, DECLINE (REMITTER)',
        'XT' => 'CUT-OFF IS IN PROCESS (REMITTER)',
        'XV' => 'TRANSACTION CANNOT BE COMPLETED. COMPLIANCE VIOLATION (REMITTER)',
        'XY' => 'REMITTER CBS OFFLINE',
        'YA' => 'LOST OR STOLEN CARD (REMITTER)',
        'YC' => 'DO NOT HONOUR (REMITTER)',
        'YE' => 'REMITTING ACCOUNT BLOCKED/FROZEN',
        'Z5' => 'INVALID BENEFICIARY CREDENTIALS',
        'ZP' => 'BANKS AS BENEFICIARY NOT LIVE ON PARTICULAR TXN TYPE',
        'ZY' => 'INACTIVE OR DORMANT ACCOUNT (BENEFICIARY)',
        'XE' => 'INVALID AMOUNT (BENEFICIARY)',
        'XG' => 'FORMAT ERROR (INVALID FORMAT) (BENEFICIARY)',
        'XI' => 'ACCOUNT DOES NOT EXIST (BENEFICIARY)',
        'XK' => 'REQUESTED FUNCTION NOT SUPPORTED',
        'XM' => 'EXPIRED CARD, DECLINE (BENEFICIARY)',
        'XO' => 'NO CARD RECORD (BENEFICIARY)',
        'XQ' => 'TRANSACTION NOT PERMITTED TO CARDHOLDER (BENEFICIARY)',
        'XS' => 'RESTRICTED CARD, DECLINE (BENEFICIARY)',
        'XU' => 'CUT-OFF IS IN PROCESS (BENEFICIARY)',
        'XW' => 'TRANSACTION CANNOT BE COMPLETED. COMPLIANCE VIOLATION (BENEFICIARY)',
        'Y1' => 'BENEFICIARY CBS OFFLINE',
        'YB' => 'LOST OR STOLEN CARD (BENEFICIARY)',
        'YD' => 'DO NOT HONOUR (BENEFICIARY)',
        'YF' => 'BENEFICIARY ACCOUNT BLOCKED/FROZEN',
        'X6' => 'INVALID MERCHANT (ACQURIER)',
        'X7' => 'MERCHANT not reachable (ACQURIER)',
        'XB' => 'INVALID TRANSACTION OR IF MEMBER IS NOT ABLE TO FIND ANY APPROPRIATE RESPONSE CODE (REMITTER)',
        'XC' => 'INVALID TRANSACTION OR IF MEMBER IS NOT ABLE TO FIND ANY APPROPRIATE RESPONSE CODE (BENEFICIARY)',
        'AM' => 'MPIN not set by customer',
        'B1' => 'Registered Mobile number linked to the account has been changed/removed',
        'B3' => 'Transaction not permitted to the account',
        '00' => 'APPROVED OR COMPLETED SUCCESSFULLY',
        'DF' => 'Random unknown error',
        'ZA' => 'TRANSACTION DECLINED BY CUSTOMER',
    ];

    public static function getErrorMessage($code)
    {
        if (isset(self::$map[$code]) === true)
        {
            return self::$map[$code];
        }

        return self::$map[self::DEFAULT_ERROR];
    }
}
