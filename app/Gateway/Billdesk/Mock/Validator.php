<?php

namespace RZP\Gateway\Billdesk\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'MerchantID'        => 'required|alpha_num',
        'CustomerID'        => 'required|alpha_num',
        'AccountNumber'     => 'required|alpha_num',
        'TxnAmount'         => 'required|numeric',
        'BankID'            => 'required|',
        'Unknown2'          => 'required|in:NA',
        'Unknown3'          => 'required|in:NA',
        'CurrencyType'      => 'required|in:INR',
        'ItemCode'          => 'required|in:DIRECT',
        'TypeField1'        => 'required|in:R',
        'SecurityID'        => 'required|',
        'Unknown4'          => 'required|in:NA',
        'Unknown5'          => 'required|in:NA',
        'TypeField2'        => 'required|in:F',
        'AdditionalInfo1'   => 'required|string',
        'AdditionalInfo2'   => 'required|string',
        'AdditionalInfo3'   => 'required|string',
        'AdditionalInfo4'   => 'required|string',
        'AdditionalInfo5'   => 'required|in:NA',
        'AdditionalInfo6'   => 'required|in:NA',
        'AdditionalInfo7'   => 'required|in:NA',
        'RU'                => 'required|url',
        'Checksum'          => 'required|alpha_num',
    );

    protected static $verifyRules = array(
        'RequestType'               => 'required|in:0122',
        'Merchant ID'               => 'required|alpha_num',
        'Customer ID'               => 'required|string',
        // 'Current Date/ Timestamp'   => 'required|alpha_num',
        'Checksum'                  => 'required|alpha_num',
    );

    protected static $refundRules = array(
        'RequestType'               => 'required|in:0400',
        'MerchantID'                => 'required|alpha_num',
        'TxnReferenceNo'            => 'required|',
        'TxnDate'                   => 'required|',
        'CustomerID'                => 'required|string',
        'TxnAmount'                 => 'required|numeric',
        'RefAmount'                 => 'required|numeric',
        'RefDateTime'               => 'required|',
        'MerchantRefNo'             => 'required|alpha_num|size:14',
        'Filler1'                   => 'required|in:NA',
        'Filler2'                   => 'required|in:NA',
        'Filler3'                   => 'required|in:NA',
        'Checksum'                  => 'required|alpha_num',
    );
}
