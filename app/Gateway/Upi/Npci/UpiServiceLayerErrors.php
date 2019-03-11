<?php

namespace RZP\Gateway\Upi\Npci;

class UpiServiceLayerErrors
{
    /**
     * This class contains upi service layer errors
     */

    const DEFAULT_ERROR = 'Random error';

    protected static $map = [
        'U01' => 'The request is duplicate',
        'U02' => 'Amount CAP is exceeded',
        'U03' => 'Net debit CAP is exceeded',
        'U04' => 'Request is not found',
        'U05' => 'Formation is not proper',
        'U06' => 'Transaction ID is mismatched',
        'U07' => 'Validation error',
        'U08' => 'System exception',
        'U09' => 'ReqAuth Time out for PAY',
        'U10' => 'Illegal operation',
        'U11' => 'Credentials is not present',
        'U12' => 'Amount or currency mismatch',
        'U13' => 'External error',
        'U14' => 'Encryption error',
        'U15' => 'Checksum failed',
        'U16' => 'Risk threshold exceeded',
        'U17' => 'PSP is not registered',
        'U18' => 'Request authorisation acknowledgement is not received',
        'U19' => 'Request authorisation is declined',
        'U20' => 'Request authorisation timeout',
        'U21' => 'Request authorisation is not found',
        'U22' => 'CM request is declined',
        'U23' => 'CM request timeout',
        'U24' => 'CM request acknowledgement is not received',
        'U25' => 'CM URL is not found',
        'U26' => 'PSP request credit pay acknowledgement is not received',
        'U27' => 'No response from PSP',
        'U28' => 'PSP not available',
        'U29' => 'Address resolution is failed',
        'U30' => 'Debit has been failed',
        'U31' => 'Credit has been failed',
        'U32' => 'Credit revert has been failed',
        'U33' => 'Debit revert has been failed',
        'U34' => 'Reverted',
        'U35' => 'Response is already been received',
        'U36' => 'Request is already been sent',
        'U37' => 'Reversal has been sent',
        'U38' => 'Response is already been sent',
        'U39' => 'Transaction is already been failed',
        'U40' => 'IMPS processing failed in UPI',
        'U41' => 'IMPS is signed off',
        'U42' => 'IMPS transaction is already been processed',
        'U43' => 'IMPS is declined',
        'U44' => 'Form has been signed off',
        'U45' => 'Form processing has been failed in UPI',
        'U46' => 'Request credit is not found',
        'U47' => 'Request debit is not found',
        'U48' => 'Transaction id not present',
        'U49' => 'Request message id is not present',
        'U50' => 'IFSC is not present',
        'U51' => 'Request refund is not found',
        'U52' => 'PSP orgId not found',
        'U53' => 'PSP Request Pay Debit Acknowledgement not received',
        'U54' => 'Transaction Id or Amount in credential block does not match with that in ReqPay',
        'U55' => 'Message integrity failed due to orgid mismatch',
        'U56' => 'Number of Payees differs from original request',
        'U57' => 'Payee Amount differs from original request',
        'U58' => 'Payer Amount differs from original request',
        'U59' => 'Payee Address differs from original request',
        'U60' => 'Payer Address differs from original request',
        'U61' => 'Payee Info differs from original request',
        'U62' => 'Payer Info differs from original request',
        'U63' => 'Device registration failed in UPI',
        'U64' => 'Data tag should contain 4 parts during device registration',
        'U65' => 'Creds block should contain correct elements during device registration',
        'U66' => 'Device Fingerprint mismatch',
        'U67' => 'Debit TimeOut',
        'U68' => 'Credit TimeOut',
        'U69' => 'Collect Expired',
        'U70' => 'Received Late Response',
    ];

    public static function getErrorMessage($code)
    {
        if (isset(self::$map[$code]) === true)
        {
            return self::$map[$code];
        }

        return self::DEFAULT_ERROR;
    }
}
