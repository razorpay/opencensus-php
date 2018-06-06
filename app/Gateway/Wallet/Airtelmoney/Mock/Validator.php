<?php

namespace RZP\Gateway\Wallet\Airtelmoney\Mock;

use RZP\Base;
use RZP\Gateway\Wallet\Airtelmoney\AuthFields;
use RZP\Gateway\Wallet\Airtelmoney\VerifyFields;
use RZP\Gateway\Wallet\Airtelmoney\RefundFields;

class Validator extends Base\Validator
{
    protected static $authorizeRules = [
        AuthFields::MERCHANT_ID               => 'required|string',
        AuthFields::TRANSACTION_REFERENCE_NO  => 'required|alpha_num|size:14',
        AuthFields::SUCCESS_URL               => 'required|url',
        AuthFields::FAILURE_URL               => 'required|url',
        AuthFields::AMOUNT                    => 'required|numeric',
        AuthFields::DATE                      => 'required|date_format:'.Constants::TIME_FORMAT,
        AuthFields::MERCHANT_SERVICE_CODE     => 'sometimes',
        AuthFields::CURRENCY                  => 'required|in:INR',
        AuthFields::END_MERCHANT_ID           => 'sometimes',
        AuthFields::SERVICE                   => 'required|size:2',
        AuthFields::HASH                      => 'required',
        AuthFields::END_MERCHANT_ID           => 'required',
        AuthFields::CUSTOMER_MOBILE           => 'sometimes'
    ];

    protected static $verifyRules = [
        VerifyFields::SESSION_ID                => 'required|alpha_num',
        VerifyFields::TRANSACTION_REFERENCE_NO  => 'required|alpha_num|size:14',
        VerifyFields::TRANSACTION_DATE          => 'required|date_format:'.Constants::TIME_FORMAT,
        VerifyFields::REQUEST                   => 'required|in:ECOMM_INQ',
        VerifyFields::MERCHANT_ID               => 'required|string',
        VerifyFields::HASH                      => 'required',
        VerifyFields::AMOUNT                    => 'required|string',
    ];

    protected static $refundRules = [
        RefundFields::SESSION_ID                => 'required|alpha_num',
        RefundFields::TRANSACTION_ID            => 'required|alpha_num',
        RefundFields::TRANSACTION_DATE          => 'required|date_format:'.Constants::TIME_FORMAT,
        RefundFields::REQUEST                   => 'required',
        RefundFields::MERCHANT_ID               => 'required|string',
        RefundFields::HASH                      => 'required',
        RefundFields::AMOUNT                    => 'required',
    ];
}
