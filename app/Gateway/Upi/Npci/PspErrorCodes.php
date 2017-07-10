<?php

namespace RZP\Gateway\Upi\Npci;

class PspErrorCodes
{
    /**
     * This class contains error codes that must be populated by
     * the PSP in case of any error.
     */

    const DEFAULT_ERROR = 'Random error';

    protected static $RespPayMap = [
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
    ];

    // RespPay reversal failures
    protected static $RespPayReversalMap = [
        '00' => 'Reversal Success',
        '96' => 'Reversal Failure'
    ];

    // RespAuthDetail Api failure codes
    protected static $RespAuthDetailMap = [
        'ZA' => 'TRANSACTION DECLINED BY CUSTOMER',
        'ZH' => 'INVALID VIRTUAL ADDRESS',
        'UX' => 'EXPIRED VIRTUAL ADDRESS',
        'ZG' => 'VPA RESTRICTED BY CUSTOMER',
        'ZE' => 'TRANSACTION NOT PERMITTED TO VPA by the PSP',
        'ZB' => 'INVALID MERCHANT (PAYEE PSP)',
        'YG' => 'MERCHANT ERROR (PAYEE PSP)',
        'X1' => 'RESPONSE NOT RECEIVED WITHIN TAT AS SET BY PAYEE',
        'TM' => 'Invalid/Incorrect ATM PIN',
    ];

    // RespListAccount, RespRegMob, RespBalEnq, RespSetCred, RespSetCre
    protected static $metaApiMap = [
        'XH' => 'Account does not exist',
        'XL' => 'Expired Card Details',
        'XN' => 'No Card Record found',
        'XR' => 'Restricted Card',
        'ZM' => 'Invalid / Incorrect MPIN',
        'ZR' => 'Invalid / Incorrect OTP',
        'ZS' => 'OTP Time expired',
        'ZT' => 'Number of OTP’s has been exceeded',
        'Z6' => 'No of PIN tries exceeded',
        'RM' => 'Invalid MPIN ( Violation of policies while setting/changing MPIN )',
        'RN' => 'Registration is temporary blocked due to maximum no of attempts exceeded',
        'RZ' => 'Account is already registered wit MBEBA flag as Y',
        'AM' => 'MPIN not set by customer ',
        'BR' => 'Mobile number registered with multiple customer IDs',
        'B2' => 'Account linked with multiple names',
        'SP' => 'Invalid/Incorrect ATM PIN Invalid/Incorrect ATM PIN',
        'AJ' => 'Card is not active',
    ];

    const API_ERRORCODE_MAP = [
        'respPayMap'         => ['RespPay', 'RespChkTxn'],
        'respPayReversalMap' => ['RespPay'], // Check for reversal
        'respAuthDetailMap'  => ['RespAuthDetail'],
        'metaApiMap'         => ['RespListAccount', 'RespRegMob', 'RespBalEnq', 'RespSetCre'],
    ];

    public static function getErrorMessage($code, $api)
    {
        $map = [];

        foreach (self::API_ERRORCODE_MAP as $map => $apis)
        {
            // If the $api being used is in the array's values above
            if (in_array($api, $apis, true))
            {
                break;
            }
        }

        //
        // A variable variable can be accessed using $$variable
        //
        // For Eg. $map = 'respPayMap'
        // self::$$map will access the corresponding array defined in this class
        //
        if (isset(self::$$map[$code]) === true)
        {
            return self::$$map[$code];
        }

        return self::DEFAULT_ERROR;
    }
}
