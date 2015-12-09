<?php

namespace Gateway\Wallet\Payzapp\Mock;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'merchantInfo'      => 'required|array',
        'transactionInfo'   => 'required|array',
        'customerInfo'      => 'required|array',
        'msgHash'           => 'required|string',
    );

    protected static $authValidators = array(
        'auth_data'
    );

    protected static $merchantInfoRules = array(
        'merId'                     => 'required|in:0122',
        'merAppId'                  => 'required|alpha_num',
        'merCountryCode'            => 'required|alpha_num|size:14',
        'merName'                   => 'required|alpha_num',
    );

    protected static $transactionInfoRules = array(
        'txnAmount'                 => 'required|in:0400',
        'txnCurrency'               => 'required|alpha_num',
        'txnDesc'                   => 'required|',
        'merTxnId'                  => 'required|',
        'merAppData'                => 'required|alpha_num|size:14',
        'supportedPaymentType'      => 'required|numeric',
    );

    protected static $customerInfoRules = array(
        'custEmail'                 => 'required',
        'custMobile'                => 'required',
    );

    protected function validateAuthData($input)
    {
        foreach ($input as $key => $value)
        {
            if (is_array($input[$key]))
            {sd('d');
                $this->validateInput($key, $input[$key]);
            }
        }
    }
}
