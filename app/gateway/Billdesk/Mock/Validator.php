<?php

namespace Gateway\Billdesk\Mock;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'MerchantID'        => 'required|alpha_num',
        'CustomerID'        => 'required|alpha_num',
        'Unknown1'          => 'required|in:NA',
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
        'AdditionalInfo1'   => 'required|alpha_num',
        'Unknown6'          => 'required|in:NA',
        'Unknown7'          => 'required|in:NA',
        'Unknown8'          => 'required|in:NA',
        'Unknown9'          => 'required|in:NA',
        'Unknown10'         => 'required|in:NA',
        'Unknown11'         => 'required|in:NA',
        'RU'                => 'required|url',
        'Checksum'          => 'required|alpha_num',
    );
}
