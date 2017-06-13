<?php

namespace RZP\Gateway\Wallet\Payzapp\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'merchantInfo'      => 'required|array',
        'transactionInfo'   => 'required|array',
        'customerInfo'      => 'required|array',
        'msgHash'           => 'required|string',
        'wIapDefaults'      => 'required|array',
    );

    protected static $authValidators = array(
        'auth_data'
    );

    protected static $refundRules = array(
        'pg_instance_id'                 => 'required|string',
        'merchant_id'                    => 'required|string',
        'perform'                        => 'required|in:processMerchantAPI#DirectVoidORRefund',
        'orginal_transaction_id'         => 'required|string',
        'original_merchant_reference_no' => 'required|string',
        'new_merchant_reference_no'      => 'required|string',
        'login_id'                       => 'required|in:random',
        'pgName'                         => 'required|in:hdfcpg',
        'message_hash'                   => 'required|string',
        'amount'                         => 'required|numeric'
    );

    protected static $verifyRules = array(
        'pg_instance_id'                 => 'required|string',
        'merchant_id'                    => 'required|string',
        'perform'                        => 'required|in:getPaymentResult',
        'currency_code'                  => 'required|in:356',
        'transaction_type'               => 'required|in:9003,9011,9021,9030',
        'amount'                         => 'required|integer',
        'merchant_reference_no'          => 'required|string',
        'message_hash'                   => 'required|string',
    );


    protected static $refundValidators = array(

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

    /**
     * custMobile should match an indian mobile number
     * of format +91 and then 10 digits
     * @var array
     */
    protected static $customerInfoRules = array(
        'custEmail'                 => 'required|email',
        'custMobile'                => 'required|regex:/^\+91[0-9]{10}$/',
    );

    protected static $wIapDefaultsRules = array(
        'wIapManualTrigger'         => 'required|boolean',
        'wIapButtonId'              => 'required|in:wIapBtn',
        'wIapWibmoDomain'           => 'required|in:wallet.pc.enstage-sas.com',
        'wIapInlineResponse'        => 'required|boolean',
        'wIapInlineResponseHandler' => 'required|in:handleWibmoIapResponse',
        'wIapReturnUrl'             => 'required|url',
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
