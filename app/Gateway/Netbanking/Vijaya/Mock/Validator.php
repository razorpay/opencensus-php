<?php

namespace RZP\Gateway\Netbanking\Vijaya\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Vijaya\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        RequestFields::MERCHANT_CONSTANT => 'required|string|',
        RequestFields::AMOUNT            => 'required|numeric',
        RequestFields::MERCHANT_NAME     => 'required|string',
        RequestFields::MERCHANT_ID       => 'required|string',
        RequestFields::ITEM_CODE         => 'required|string|in:Razorpay',
        RequestFields::RETURN_URL        => 'sometimes|url',
        RequestFields::PAYMENT_ID        => 'required|alpha_num|size:14',
        RequestFields::CURRENCY          => 'required|string|in:INR',

    );

    protected static $verifyRules = [
        RequestFields::BANK_REFERENCE_NUMBER => 'sometimes|string',
        RequestFields::MERCHANT_CONSTANT     => 'required|string',
        RequestFields::AMOUNT                => 'required|numeric',
        RequestFields::ITEM_CODE             => 'required|string|in:Razorpay',
        RequestFields::RETURN_URL            => 'sometimes|string', //TODO fix this
        RequestFields::PAYMENT_ID            => 'required|alpha_num|size:14',
        RequestFields::CURRENCY              => 'required|string|in:INR',

        'Action_ShoppingMall_Login_Init'     => 'required',
        'BankId'                             => 'required',
        'MD'                                 => 'required',
        'CG'                                 => 'required',
        'USER_LANG_ID'                       => 'required',
        'UserType'                           => 'required',
        'AppType'                            => 'required'
    ];
}
