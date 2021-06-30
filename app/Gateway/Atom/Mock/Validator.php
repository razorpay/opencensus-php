<?php

namespace RZP\Gateway\Atom\Mock;

use RZP\Base;
use RZP\Gateway\Atom\DateFormat;
use RZP\Gateway\Atom\AuthRequestFields;
use RZP\Gateway\Atom\RefundRequestFields;
use RZP\Gateway\Atom\VerifyRequestFields;
use RZP\Gateway\Atom\VerifyRefundFields;

class Validator extends Base\Validator
{
    protected static $authorizeRules = array(
        AuthRequestFields::LOGIN                      => 'required|string',
        AuthRequestFields::PASSWORD                   => 'required|string',
        AuthRequestFields::TRANSACTION_TYPE           => 'required|string',
        AuthRequestFields::PRODUCT_ID                 => 'required|string',
        AuthRequestFields::AMOUNT                     => 'required|numeric',
        AuthRequestFields::TRANSACTION_CURRENCY       => 'required|in:INR',
        AuthRequestFields::TRANSACTION_SERVICE_CHARGE => 'required',
        AuthRequestFields::CLIENT_CODE                => 'required|string',
        AuthRequestFields::TRANSACTION_ID             => 'required|alpha_num',
        AuthRequestFields::DATE                       => 'required|date_format:'.DateFormat::AUTHORIZE,
        AuthRequestFields::CUSTOMER_ACCOUNT           => 'required',
        AuthRequestFields::SIGNATURE                  => 'required',
        AuthRequestFields::RETURN_URL                 => 'required|url',
        AuthRequestFields::BANK_ID                    => 'required|integer',
        AuthRequestFields::UDF9                       => 'required|string',
    );

    protected static $refundRules = array(
        RefundRequestFields::MERCHANT_ID            => 'required|string',
        RefundRequestFields::PASSWORD               => 'required',
        RefundRequestFields::GATEWAY_TRANSACTION_ID => 'required',
        RefundRequestFields::REFUND_AMOUNT          => 'required|numeric',
        RefundRequestFields::TRANSACTION_DATE       => 'required|date_format:'.DateFormat::REFUND,
        RefundRequestFields::REFUND_ID              => 'required|alpha_num',
    );

    protected static $verifyRules = array(
        VerifyRequestFields::MERCHANT_ID      => 'required',
        VerifyRequestFields::TRANSACTION_DATE => 'required|date_format:'.DateFormat::VERIFY,
        VerifyRequestFields::TRANSACTION_ID   => 'required|alpha_num',
        VerifyRequestFields::AMOUNT           => 'required|numeric',
    );

    protected static $verifyRefundRules = array(
        VerifyRefundFields::LOGIN     => 'required',
        VerifyRefundFields::ENC_DATA  => 'required',
        VerifyRefundFields::REFUND_ID => 'required',
    );
}
