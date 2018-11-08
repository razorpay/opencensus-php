<?php

namespace RZP\Gateway\Amex\Mock;

use RZP\Gateway\AxisMigs;

class Validator extends AxisMigs\Mock\Validator
{
    protected static $authRules = array(
        'vpc_SubMerchant_ID'                    => 'sometimes|string|max:10',
        'vpc_SubMerchant_RegisteredName'        => 'sometimes|string|max:100',
        'vpc_SubMerchant_TradingName'           => 'sometimes|string|max:100',
        'vpc_SubMerchant_Street'                => 'sometimes|string|max:128',
        'vpc_SubMerchant_PostCode'              => 'sometimes|string|max:9',
        'vpc_SubMerchant_City'                  => 'sometimes|string|max:128',
        'vpc_SubMerchant_StateProvince'         => 'sometimes|string|max:128',
        'vpc_SubMerchant_Country'               => 'sometimes|string|size:3',
        'vpc_SubMerchant_Phone'                 => 'sometimes|string|max:10',
        'vpc_SubMerchant_Email'                 => 'sometimes|email|max:127',
        'vpc_SubMerchant_MerchantCategoryCode'  => 'sometimes|string|max:4',
        'vpc_Command'                           => 'required|in:pay',
        'vpc_Amount'                            => 'required|integer',
        'vpc_Currency'                          => 'required|in:INR|max:3',
        'vpc_MerchTxnRef'                       => 'required|alpha_num|size:14',
        'vpc_Version'                           => 'required|in:1',
        'vpc_ReturnURL'                         => 'required|url',
        'vpc_Locale'                            => 'required|in:en',
        'vpc_gateway'                           => 'required|in:ssl,threeDSecure',
        'vpc_Card'                              => 'required|in:MasterCard,Visa,Amex',
        'vpc_CardNum'                           => 'required|numeric|luhn|digits_between:12,19',
        'vpc_CardExp'                           => 'required|size:4',
        'vpc_CardSecurityCode'                  => 'required|numeric|digits_between:2,4',
        'vpc_Merchant'                          => 'required|alpha_num|max:16',
        'vpc_AccessCode'                        => 'required|alpha_num|size:8',
        'vpc_SecureHash'                        => 'required|alpha_num|size:64',
        'vpc_SecureHashType'                    => 'required|in:SHA256',
        'vpc_OrderInfo'                         => 'sometimes|alpha_num|max:34',

        // To uncomment after sub-merchant details go live for every merchant
        // 'vpc_SubMerchant_ID'                    => 'required|string|max:10',
        // 'vpc_SubMerchant_RegisteredName'        => 'required|string|max:100',
        // 'vpc_SubMerchant_TradingName'           => 'required|string|max:100',
        // 'vpc_SubMerchant_Street'                => 'required|string|max:128',
        // 'vpc_SubMerchant_PostCode'              => 'required|string|max:9',
        // 'vpc_SubMerchant_City'                  => 'required|string|max:128',
        // 'vpc_SubMerchant_StateProvince'         => 'required|string|max:128',
        // 'vpc_SubMerchant_Country'               => 'required|string|size:3',
        // 'vpc_SubMerchant_Phone'                 => 'required|string|max:10',
        // 'vpc_SubMerchant_Email'                 => 'required|email|max:127',
        // 'vpc_SubMerchant_MerchantCategoryCode'  => 'required|string|max:4',
    );
}
