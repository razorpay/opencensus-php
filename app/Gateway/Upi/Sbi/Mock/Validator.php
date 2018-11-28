<?php

namespace RZP\Gateway\Upi\Sbi\Mock;

use Razorpay\Api\Request;
use RZP\Base;
use RZP\Gateway\Upi\Sbi\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::ADDITIONAL_INFO                                          => 'required|array',
        RequestFields::ADDITIONAL_INFO . '.' . RequestFields::ADDITIONAL_INFO9  => 'required|string|in:NA',
        RequestFields::ADDITIONAL_INFO . '.' . RequestFields::ADDITIONAL_INFO10 => 'required|string|in:NA',
        RequestFields::AMOUNT                                                   => 'required|string',
        RequestFields::EXPIRY_TIME                                              => 'required|string',
        RequestFields::PAYER_TYPE                                               => 'required|array|size:1',
        RequestFields::PAYER_TYPE . '.' . RequestFields::VIRTUAL_ADDRESS        => 'required|string',
        RequestFields::REQUEST_INFO                                             => 'required|array|size:2',
        RequestFields::REQUEST_INFO . '.' . RequestFields::PG_MERCHANT_ID       => 'required|string',
        RequestFields::REQUEST_INFO . '.' . RequestFields::PSP_REFERENCE_NO     => 'required|string|size:14',
        RequestFields::TRANSACTION_NOTE                                         => 'required|string',
    ];

    protected static $verifyRules = [
        RequestFields::REQUEST_INFO                                         => 'required|array|size:2',
        RequestFields::CUSTOMER_REFERENCE_NO                                => 'sometimes|string|nullable',
        RequestFields::REQUEST_INFO . '.' . RequestFields::PG_MERCHANT_ID   => 'required|string',
        RequestFields::REQUEST_INFO . '.' . RequestFields::PSP_REFERENCE_NO => 'required|string|size:14'
    ];

    protected static $validateVpaRules = [
        RequestFields::REQUEST_INFO                                         => 'required|array|size:2',
        RequestFields::REQUEST_INFO . '.' . RequestFields::PG_MERCHANT_ID   => 'required|string',
        RequestFields::REQUEST_INFO . '.' . RequestFields::PSP_REFERENCE_NO => 'required|string',
        RequestFields::PAYEE_TYPE                                           => 'required|array|size:1',
        RequestFields::PAYEE_TYPE . '.' . RequestFields::VIRTUAL_ADDRESS    => 'required|string',
        RequestFields::VA_REQUEST_TYPE                                      => 'required|string|in:T',
    ];
}
