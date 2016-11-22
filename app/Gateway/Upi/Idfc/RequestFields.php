<?php

namespace RZP\Gateway\Upi\Idfc;

class RequestFields
{
    const DEFAULT_ATTRIBUTES = [
        'UPI'   =>  [
            'TimeStamp',
            'MsgId',
        ]
    ];

    const 'GenerateMerchantDEK' = [
        'UPI' => [
            'DeviceID',
            'Channel',
            'PayerType',
            'OrgId',
            'BankId',
            'MobileNo',
            'MerchantID',
            'TerminalID',
            'MerchantCredentials'
        ]
    ];

    const 'MerchantListPublicKeys'  = [
        'UPI'   =>  [
            'Channel',
            'MobileNo',
            'OrgId',
            'BankId',
            'Remarks',
            'DeviceID',
            'PayerType',
            'SubMerchantID',
            'MerchantID',
            'TerminalID',
            'MerchantCredentials',
        ],
        'CredData',
        'CredSubType',
        'CredType',
        'KeyIndex',
        'KeyCode',
        'TxnType',
    ];

    const 'MerchantGenerateBankOTP' = [
        'BankName','AddrType', 'PayerCode',
        'UPI'   =>  [
            'Channel', 'MobileNo', 'OrgId', 'BankId', 'Remarks', 'UserID', 'UserPwd', 'DeviceID', 'PayerType', 'MerchantID', 'TerminalID', 'MerchantCredentials',
        ],
        'GeoCode',
        'DevLocation',
        'DevIp',
        'DevType',
        'DevOs',
        'DevApp',
        'DevCapability',
        'PayerAccNo'
    ];

    const 'MerchantAddBank' = [
        'UPI'   =>  [
            'Channel',
            'MobileNo',
            'OrgId',
            'BankId',
            'Remark',
            'DeviceID',
            'PayerType',
            'SubMerchantID',
            'MerchantID',
            'TerminalID',
            'MerchantCredentials',
        ]
    ];

    const 'MerchantProfileCreation' = [
        'AdhaarNo'
        'QuestionId'
        'Answer'
        'DOB'
        'Email'
        'FirstName'
        'Gender'
        'devName'
        'devModel'
        'os'
        'osVersion'
        'appName'
        'appVersion'
        'LastName'
        'UserName'
        'AppPwd'
        'GcmID'
        'VirAdddr'
        'UPI'   =>  [
            'Channel','MobileNo','MsgId','OrgId','BankId','Remarks','TimeStamp','DeviceID',
            'PayerType','SubMerchantID','MerchantID','TerminalID','MerchantCredentials',
        ]
    ];

    const 'MerchantViewRegAccnts'   =   [
        'UPI'   =>  [
            'Channel','MobileNo','MsgId','OrgId','BankId','Remarks','TimeStamp','DeviceID','PayerType','SubMerchantID','MerchantID','TerminalID','MerchantCredentials',
        ],
        'Category',
        'VirAddr',
    ];

    /**
     * Returns an array containing all the required
     * fields for that request.
     * @return array
     */
    public static function getRequestTemplate(string $method)
    {
        $attribs =  constant(__NAMESPACE__ , "::$method");

        // TODO: Make sure this is recursive as well
        return $attribs + self::DEFAULT_ATTRIBUTES;
    }

    /**
     * Returns a boolean whether the particular request
     * requires an HMAC to be sent or not
     * @param  string $method
     * @return boolean
     */
    public static function requiresHMAC(string $method)
    {
        // Always returns false for now
        // FSS has disabled HMAC at their end
        return false;
    }
}
