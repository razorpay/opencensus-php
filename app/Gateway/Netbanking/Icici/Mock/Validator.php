<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Icici\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::MODE                  => 'required|alpha|in:P,SI',
        RequestFields::PAYEE_ID              => 'required|string',
        RequestFields::SPID                  => 'sometimes_if:MD,P|string',
        RequestFields::ENCRYPTED_STRING      => 'sometimes_if:MD,P|string',
        RequestFields::SI_DEBIT_PAYMENT_DATE => 'sometimes_if:MD,SI',
        RequestFields::PAYMENT_ID            => 'sometimes_if:MD,SI',
        RequestFields::ITEM_CODE             => 'sometimes_if:MD,SI',
        RequestFields::AMOUNT                => 'sometimes_if:MD,SI',
        RequestFields::CURRENCY_CODE         => 'sometimes_if:MD,SI',
        RequestFields::SI_REFERENCE_NUMBER   => 'sometimes_if:MD,SI',
    ];

    protected static $authDecryptedRules = [
        RequestFields::AMOUNT              => 'required',
        RequestFields::CONFIRMATION        => 'required|in:Y,N',
        RequestFields::CURRENCY_CODE       => 'required',
        RequestFields::PAYMENT_ID          => 'required',
        RequestFields::ITEM_CODE           => 'required',
        RequestFields::RETURN_URL          => 'required',
        RequestFields::ACCOUNT_NO          => 'sometimes',
        RequestFields::SI                  => 'sometimes',
        RequestFields::SI_PAYMENT_DATE     => 'sometimes',
        RequestFields::SI_PAYMENT_TYPE     => 'sometimes',
        RequestFields::SI_PAYMENT_FREQ     => 'sometimes',
        RequestFields::SI_NUM_INSTALLMENTS => 'sometimes',
        RequestFields::SI_AUTO_PAY_AMOUNT  => 'sometimes',
        RequestFields::SI_END_DATE         => 'sometimes',
        RequestFields::SI_REFERENCE_NUMBER => 'sometimes',
    ];

    protected static $verifyRules = [
        RequestFields::MODE                => 'required|alpha|size:1|in:V',
        RequestFields::PAYEE_ID            => 'required|string',
        RequestFields::AMOUNT              => 'required|numeric',
        RequestFields::PAYMENT_ID          => 'required|alpha_num|size:14',
        RequestFields::ITEM_CODE           => 'required|alpha_num|size:14',
        RequestFields::CURRENCY_CODE       => 'required|in:INR',
        RequestFields::ACCOUNT_NO          => 'sometimes|string',
        RequestFields::PAYMENT_DATE        => 'required|date_format:Y-m-d',
        RequestFields::RETURN_URL          => 'sometimes|string',
        RequestFields::SHOW_ON_SAME_PAGE   => 'sometimes|string',
        RequestFields::SI_REFERENCE_NUMBER => 'sometimes|string',
        RequestFields::SI                  => 'sometimes|string',
        RequestFields::SI_AUTO_PAY_AMOUNT  => 'sometimes|numeric',
    ];
}
