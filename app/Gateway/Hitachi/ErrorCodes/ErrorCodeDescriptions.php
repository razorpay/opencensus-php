<?php

namespace RZP\Gateway\Hitachi\ErrorCodes;

use RZP\Gateway\Base\ErrorCodes\Cards;

class ErrorCodeDescriptions extends Cards\ErrorCodeDescriptions
{
    public static $authRespDescriptionMap = [
        '17' => 'Customer cancellation',
        '51' => 'Insufficient funds',
        '52' => 'No checking account',
        '53' => 'No savings account',
        '54' => 'Expired card',
        '55' => 'Incorrect PIN',
        '57' => 'Transaction not permitted to cardholder',
        '58' => 'Transaction not allowed at terminal',
        '59' => 'Suspected fraud',
        '61' => 'Activity amount limit exceeded',
        '62' => 'Restricted card (for example, in Country Exclusion table)',
        '63' => 'Security violation',
        '65' => 'Activity count limit exceeded',
        '68' => 'Response received too late',
        '69' => 'Cardholder/Issuer Not Enrolled with 3D Secure',
        '70' => '3D Secure Authentication Failure',
        '75' => 'Allowable number of PIN-entry tries exceeded',
        '76' => 'Unable to locate previous message (no match on Retrieval Reference number)',
        '77' => 'Previous message located for a repeat or reversal, but repeat or reversal data are
        inconsistent with original message',
        '78' => '’Blocked, first used’—The transaction is from a new cardholder, and the card has not been
        properly unblocked.',
        '80' => 'Visa transactions: credit issuer unavailable. Private label and check acceptance: Invalid
        date',
        '81' => 'PIN cryptographic error found (error found by VIC security module during PIN decryption)',
        '82' => 'Negative CAM, dCVV, iCVV, or CVV results',
        '83' => 'Unable to verify PIN',
        '85' => 'No reason to decline a request for account number verification, address verification, CVV2
        verification, or a credit voucher or merchandise return',
        'B1' => 'Surcharge amount not permitted on Visa cards (U.S. acquirers only)',
        'N0' => 'Force STIP',
        'N3' => 'Cash service not available',
        'N4' => 'Cashback request exceeds issuer limit',
        'N7' => 'Decline for CVV2 failure',
        'P2' => 'Invalid biller information',
        'P5' => 'PIN Change/Unblock request declined',
        'P6' => 'Unsafe PIN',
        'Q1' => 'Card Authentication failed',
        'R0' => 'Stop Payment Order',
        'R1' => 'Revocation of Authorization Order',
        'R3' => 'Revocation of All Authorizations Order',
        'XA' => 'Forward to issuer',
        'XD' => 'Forward to issuer',
        'Z3' => 'Unable to go online',
        'IC' => 'Invalid currency code'
    ];

    public static function getErrorFieldName($fieldName)
    {
        return 'pRespCode';
    }
}
