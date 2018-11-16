<?php

namespace RZP\Gateway\Upi\Yesbank;

use RZP\Error\ErrorCode;

class ResponseCodes
{
    const CODES = [
        'MT01' => 'Debit Transaction Failed',
        'MT02' => 'Credit Transaction Failed',
        'MT03' => 'Insufficient Balance in Account',
        'MT04' => 'Transaction Limit Exceeded',
        'MT05' => 'Transaction Amount Exceeded',
        'MT06' => 'Closed Account',
        'MT07' => 'Inactive/Dormant account (Remitter)',
        'MT08' => 'Invalid UPI PIN entered',
        'MT09' => 'Inactive/Dormant account (Beneficiary)',
        'MT10' => 'No Credit Account',
        'MT11' => 'Credit decline reversal',
        'MT12' => 'Partial Decline',
        'MT13' => 'Invalid amount (Remitter)',
        'MT14' => 'Incorrect/Invalid Payer Virtual Address',
        'MT15' => 'Incorrect/Invalid Payee Virtual Address',
        'MT16' => 'Collect rejected successfully',
        'MT17' => 'Number of PIN tries exceeded.',
        'MT18' => 'Account does not exist (Remitter)',
        'MT19' => 'Account does not exist (Beneficiary)',
        'MT20' => 'Account not whitelisted',
        'MT21' => 'Cutoff is in process(Remitter)',
        'MT22' => 'Cutoff is in process(Beneficiary)',
        'MT23' => 'Remitter CBS offline',
        'MT24' => 'Beneficiary CBS offline',
        'MT25' => 'Invalid transaction (Remitter)',
        'MT26' => 'Invalid transaction (Beneficiary)',
        'MT27' => 'Transaction not permitted to account',
        'MT28' => 'Requested function not supported (Remitter)',
        'MT29' => 'Requested function not supported (Beneficiary)',
        'MT30' => 'Beneficiary account blocked / Frozen',
        'MT31' => 'Remitter account blocked / Frozen',

        // below error codes are Applicable for Transaction Status Enquiry and Callback API
        'Z9'   => 'INSUFFICIENT FUNDS IN CUSTOMER (REMITTER) ACCOUNT',
        'RM'   => 'Invalid UPI PIN (Violation of policies while setting/changing UPI PIN )',
        'RN'   => 'Registration is temporary blocked due to maximum no of attempts exceeded',
        'RZ'   => 'Account is already registered with MBEBA flag as \'Y\'',
        'BR'   => 'Mobile number registered with multiple customer IDs',
    ];

    public static function getResponseMessage($code)
    {
        return self::CODES[$code] ?? 'Unknown Gateway Response Code';
    }
}