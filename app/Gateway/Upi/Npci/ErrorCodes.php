<?php

namespace RZP\Gateway\Upi\Npci;

class ErrorCodes
{
    const DEFAULT_ERROR = 'Random error';

    protected static $upiResponseCodes = [
        'UT' => 'REMITTER/ISSUER UNAVAILABLE (TIMEOUT)',
        'BT' => 'ACQUIRER/BENEFICIARY UNAVAILABLE(TIMEOUT)',
        'RB' => 'CREDIT REVERSAL TIMEOUT(REVERSAL)',
        'RR' => 'DEBIT REVERSAL TIMEOUT(REVERSAL)',
        'RP' => 'PARTIAL DEBIT REVERSAL TIMEOUT',
        '32' => 'PARTIAL REVERSAL',
        '21' => 'NO ACTION TAKEN (FULL REVERSAL)',
        'UM0'=> 'REQAUTHMANDATE NOT RECEIVED',
        'UM1'=> 'RESPAUTHMANDATE DECLINED BY PSP',
        'UM3'=> 'MANDATE CREATION EXPIRED',
        'UM8'=> 'MANDATE CREATION DECLINED BY REMITTER BANK',
        'UM9'=> 'MANDATE CREATION TIMED OUT AT REMITTER END',
        'U86'=> 'REMITTER BANK THROTTLING DECLINE',
        'U90'=> 'REMITTER BANK HIGH RESPONSE',
        'U91'=> 'BENEFICIARY BANK HIGH RESPONSE',
        'U92'=> 'PAYER PSP NOT AVAILABLE',
    ];

    protected static $impsResponseCodes = [
        '00' => 'TRANSACTION APPROVED',
        '08' => 'HOST (CBS) OFFLINE',
        '08' => 'ISSUER NODE OFFLINE',
        '91' => 'RESPONSE TIME OUT (DEEMED APPROVED)',
        '12' => 'INVALID TRANSACTION',
        '20' => 'INVALID RESPONSE CODE',
        '96' => 'UNABLE TO PROCESS',
        '30' => 'INVALID MESSAGE FORMAT',
        '21' => 'NO ACTION TAKEN',
        '68' => 'RESPONSE RECEIVED TOO LATE',
        '10' => 'PIN BLOCK ERROR',
        '63' => 'SECURITY VIOLATION',
        '22' => 'SUSPECTED MALFUNCTION',
        '13' => 'INVALID AMOUNT FIELD',
        '96' => 'MODULE PROCESSING ERROR',
        'M0' => 'VER SUCCESSFUL ORG CRD TRXN DECLINED',
        'M1' => 'INVALID BENEFICIARY DETAILS',
        'M2' => 'AMOUNT LIMIT EXCEEDED FOR CUSTOMER',
        'M3' => 'ACCOUNT BLOCKED/FROZEN',
        'M4' => 'NRE ACCOUNT',
        'M5' => 'ACCOUNT CLOSED',
        'M6' => 'LIMIT EXCEEDED FOR MEMBER BANK',
        '92' => 'INVALID NBIN',
        'MA' => 'MERCHANT ERROR',
        'MC' => 'FUNCTIONALITY NOT YET AVAILABLE FOR MERCHANT THROUGH THE PAYEE BANK',
        'MK' => 'PAYEE IS AN INDIVIDUAL AND NOT A MERCHANT. PLEASE USE PERSON-TO-PERSON FORM FOR MAKING PAYMENT',
        'ML' => 'PAYEE IS A MERCHANT AND NOT AN INDIVIDUAL. PLEASE USE PERSON-TO-MERCHANT FORM FOR MAKING PAYMENT',
        'MF' => 'MERCHANT SYSTEM NOT AVAILABLE, PLEASE TRY AGAIN',
        'M9' => 'INVALID / INCORRECT OTP',
        'MZ' => 'OTP EXPIRED',
        'MH' => 'OTP TRANSACTION LIMIT EXCEEDED',
        'MG' => 'FUNCTIONALITY NOT YET AVAILABLE FOR CUSTOMER THROUGH THE ISSUING BANK',
        '51' => 'NOT SUFFICIENT FUNDS',
        '57' => 'TRANSACTION NOT PERMITTED TO ACCOUNT HOLDER',
        '61' => 'EXCEEDS TRANSACTION AMOUNT LIMIT',
        '43' => 'LOST OR STOLEN ACCOUNT',
        '05' => 'DO NOT HONOUR',
        '94' => 'DUPLICATE TRANSACTION',
        '34' => 'SUSPECTED FRAUD',
        '17' => 'CUSTOMER CANCELLATION',
        '65' => 'EXCEEDS TRANSACTION FREQUENCY LIMIT',
        '75' => 'EXCESSIVE PIN TRIES',
        '36' => 'RESTRICTED CARD',
        '54' => 'EXPIRED CARD',
        '14' => 'INVALID CARD NUMBER',
        '96' => 'S & F NOT PROCESSED',
        'MP' => 'BANKS AS BENEFICIARY NOT LIVE ON PARTICULAR TXN',
        '40' => 'INVALID DEBIT ACCOUNT',
        '01' => 'UNABLE TO PROCESS REVERSAL',
        'M8' => 'INVALID OTP',
        '62' => 'WARM CARD: RESTRICTED USE',
        'MV' => 'BANK IS NOT ENABLED FOR P2U',
        '04' => 'DO NOT HONOUR',
        '52' => 'INVALID ACCOUNT',
        'MU' => 'ADDHAR NO NOT FOUND IN MAPPER FILE',
    ];

    public static function getErrorMessage($code)
    {
        if (isset($upiResponseCodes[$code]) === true)
        {
            return $upiResponseCodes[$code];
        }
        else if (isset($impsResponseCodes[$code]) === true)
        {
            return $impsResponseCodes[$code];
        }

        return self::DEFAULT_ERROR;
    }
}
