<?php

namespace RZP\Gateway\Netbanking\Sbi\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Sbi\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::REF_NO                  => 'sometimes|string',
        RequestFields::AMOUNT                  => 'required_with:' . RequestFields::REF_NO . '|numeric',
        RequestFields::PAYMENT_ID              => 'required_with:' . RequestFields::REF_NO . '|string',
        RequestFields::REDIRECT_URL            => 'required_with:' . RequestFields::REF_NO . '|url',
        RequestFields::CANCEL_URL              => 'required_with:' . RequestFields::REF_NO . '|url',
        RequestFields::CHECKSUM                => 'required_with:' . RequestFields::REF_NO . '|string',
        RequestFields::ACCOUNT_NUMBER          => 'sometimes|string',

        //emandate
        RequestFields::MANDATE_HOLDER_NAME     => 'sometimes|string',
        RequestFields::MANDATE_PAYMENT_ID      => 'required_with:' . RequestFields::MANDATE_HOLDER_NAME. '|alpha_num|size:14',
        RequestFields::DEBIT_ACCOUNT_NUMBER    => 'required_with:' . RequestFields::MANDATE_HOLDER_NAME. '|numeric',
        RequestFields::MANDATE_AMOUNT          => 'required_with:' . RequestFields::MANDATE_HOLDER_NAME. '|numeric',
        RequestFields::MANDATE_START_DATE      => 'required_with:' . RequestFields::MANDATE_HOLDER_NAME. '|date_format:d/m/Y',
        RequestFields::MANDATE_END_DATE        => 'required_with:' . RequestFields::MANDATE_HOLDER_NAME. '|date_format:d/m/Y',
        RequestFields::FREQUENCY               => 'required_with:' . RequestFields::MANDATE_HOLDER_NAME. '|string',
        RequestFields::MANDATE_RETURN_URL      => 'required_with:' . RequestFields::MANDATE_HOLDER_NAME. '|string',
        RequestFields::MANDATE_ERROR_URL       => 'required_with:' . RequestFields::MANDATE_HOLDER_NAME. '|string',
        RequestFields::MANDATE_AMOUNT_TYPE     => 'required_with:' . RequestFields::MANDATE_HOLDER_NAME. '|string',
        RequestFields::TOKEN_ID                => 'required_with:' . RequestFields::MANDATE_HOLDER_NAME. '|string',
        RequestFields::MANDATE_TXN_AMOUNT      => 'required_with:' . RequestFields::MANDATE_HOLDER_NAME. '|numeric',
        RequestFields::MANDATE_MODE            => 'required_with:' . RequestFields::MANDATE_HOLDER_NAME. '|string',
    ];

    protected static $verifyRules = [
        RequestFields::REF_NO                      => 'sometimes|alpha_num|size:14',
        RequestFields::BANK_REF_NO                 => 'sometimes|string',
        RequestFields::AMOUNT                      => 'required_with:' . RequestFields::REF_NO . '|numeric',
        RequestFields::CHECKSUM                    => 'required_with:' . RequestFields::REF_NO . '|string',

        // emandate
        RequestFields::MANDATE_PAYMENT_ID          => 'sometimes|alpha_num|size:14',
        RequestFields::MANDATE_VERIFY_TXN_AMOUNT   => 'required_with:' . RequestFields::MANDATE_PAYMENT_ID
    ];
}
