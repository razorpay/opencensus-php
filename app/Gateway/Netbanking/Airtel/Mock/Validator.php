<?php

namespace RZP\Gateway\Netbanking\Airtel\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Airtel\AuthFields;
use RZP\Gateway\Netbanking\Airtel\VerifyFields;
use RZP\Gateway\Netbanking\Airtel\RefundFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        AuthFields::MERCHANT_ID               => 'required|numeric',
        AuthFields::TRANSACTION_REFERENCE_NO  => 'required|alpha_num',
        AuthFields::SUCCESS_URL               => 'required|string',
        AuthFields::FAILURE_URL               => 'required|string',
        AuthFields::AMOUNT                    => 'required',
        AuthFields::DATE                      => 'required|string',
        AuthFields::MERCHANT_SERVICE_CODE     => 'sometimes',
        AuthFields::CURRENCY                  => 'required|in:INR',
        AuthFields::END_MERCHANT_ID           => 'required',
        AuthFields::CUSTOMER_MOBILE           => 'required',
        AuthFields::CUSTOMER_EMAIL            => 'required',
        AuthFields::SERVICE                   => 'required|size:2',
        AuthFields::HASH                      => 'required',
        AuthFields::MERCHANT_NAME             => 'required'
    ];

    protected static $verifyRules = [
        VerifyFields::SESSION_ID                => 'required|alpha_num',
        VerifyFields::TRANSACTION_REFERENCE_NO  => 'required|alpha_num',
        VerifyFields::TRANSACTION_DATE          => 'required|string',
        VerifyFields::REQUEST                   => 'required|in:ECOMM_INQ',
        VerifyFields::MERCHANT_ID               => 'required|numeric',
        VerifyFields::HASH                      => 'required',
        VerifyFields::AMOUNT                    => 'required|string',
    ];

    protected static $refundRules = [
        RefundFields::SESSION_ID                => 'required|alpha_num',
        RefundFields::TRANSACTION_ID            => 'required|alpha_num',
        RefundFields::TRANSACTION_DATE          => 'required|string',
        RefundFields::REQUEST                   => 'required|in:ECOMM_REVERSAL',
        RefundFields::MERCHANT_ID               => 'required|numeric',
        RefundFields::HASH                      => 'required',
        RefundFields::AMOUNT                    => 'required',
    ];
}
