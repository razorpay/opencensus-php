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
        'merId'                     => 'required|string|max:21',
        'merAppId'                  => 'required|integer|digits:4',
        'merCountryCode'            => 'required|in:IN',
        'merName'                   => 'required|in:RazorPay',
    );

    protected static $transactionInfoRules = array(
        'txnAmount'                 => 'required|integer',
        'txnCurrency'               => 'required|integer|in:356',
        'txnDesc'                   => 'required|string|max:255',
        'merTxnId'                  => 'required|alpha_num|size:14',
        'merAppData'                => 'sometimes|',
        'supportedPaymentType'      => 'required|array',
    );

    protected static $customerInfoRules = array(
        'custEmail'                 => 'required|email',
        'custMobile'                => 'required|integer|digits_between:9,12',
    );

    protected function validateAuthData($input)
    {
        foreach ($input as $key => $value)
        {
            if (is_array($input[$key]))
            {
                $this->validateInput($key, $input[$key]);
            }
        }
    }
}
