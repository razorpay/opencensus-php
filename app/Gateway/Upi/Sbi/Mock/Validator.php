<?php

namespace RZP\Gateway\Upi\Sbi\Mock;

use Razorpay\Api\Request;
use RZP\Base;
use RZP\Gateway\Upi\Sbi\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::ADDITIONAL_INFO  => 'required|array',
        RequestFields::AMOUNT           => 'required',
        RequestFields::EXPIRY_TIME      => 'required|string|in:1110',
        RequestFields::PAYER_TYPE       => 'required|array',
        RequestFields::REQUEST_INFO     => 'required|array',
        RequestFields::TRANSACTION_NOTE => 'required|string',
    ];

    /**
     * @var array Validates additional info key in auth request
     */
    protected static $authAdditionalInfoRules = [
        RequestFields::ADDITIONAL_INFO9  => 'required|string|in:NA',
        RequestFields::ADDITIONAL_INFO10 => 'required|string|in:NA',
    ];

    /**
     * @var array Validates payer type key in auth request
     */
    protected static $authPayerTypeRules = [
        RequestFields::VIRTUAL_ADDRESS => 'required|string',
    ];

    /**
     * @var array Validates request info key in auth request
     */
    protected static $authRequestInfoRules = [
        RequestFields::PG_MERCHANT_ID   => 'required|string',
        RequestFields::PSP_REFERENCE_NO => 'required|string|size:14'
    ];

    protected static $verifyRules = [
        RequestFields::REQUEST_INFO          => 'required|array',
        RequestFields::CUSTOMER_REFERENCE_NO => 'required|string',
    ];

    /**
     * @var array Validates request info key in verify request
     * TODO: Re-use code above?
     */
    protected static $verifyRequestInfoRules = [
        RequestFields::PG_MERCHANT_ID   => 'required|string',
        RequestFields::PSP_REFERENCE_NO => 'required|string|size:14'
    ];
}