<?php

namespace RZP\Gateway\Netbanking\Airtel\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Airtel\RequestFields;
use RZP\Gateway\Netbanking\Airtel\VerifyFields;

class Validator extends Base\Validator
{
    // Make sure this works
    protected static $authRules = [
        RequestFields::MERCHANT_ID               => 'required|numeric',
        RequestFields::TRANSACTION_REFERENCE_NO  => 'required|alpha_num',
        RequestFields::SUCCESS_URL               => 'required|string',
        RequestFields::FAILURE_URL               => 'required|string',
        RequestFields::AMOUNT                    => 'required',
        RequestFields::DATE                      => 'required|string',
        RequestFields::MERCHANT_SERVICE_CODE     => 'sometimes',
        RequestFields::CURRENCY                  => 'required|in:INR',
        RequestFields::END_MERCHANT_NAME         => 'sometimes',
        RequestFields::CUSTOMER_MOBILE           => 'required',
        RequestFields::CUSTOMER_EMAIL            => 'required',
        RequestFields::SERVICE                   => 'required',
        RequestFields::HASH                      => 'required',
    ];

    protected static $verifyRules = [
        VerifyFields::SESSION_ID                => 'required|alpha_num',
        VerifyFields::TRANSACTION_REFERENCE_NO  => 'required|alpha_num',
        VerifyFields::TRANSACTION_DATE          => 'required|string',
        VerifyFields::MERCHANT_ID               => 'required|numeric',
        VerifyFields::HASH                      => 'required',
        VerifyFields::AMOUNT                    => 'required',
    ];
}
