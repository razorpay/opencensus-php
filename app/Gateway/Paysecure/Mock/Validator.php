<?php

namespace RZP\Gateway\Paysecure\Mock;

use RZP\Base;
use RZP\Gateway\Paysecure\Fields;

class Validator extends Base\Validator
{
    protected static $initiate2Rules = [
        Fields::PARTNER_ID                        => 'required|string',
        Fields::MERCHANT_PASSWORD                 => 'required|string',
        Fields::CARD_NO                           => 'required|numeric|digits_between:13,19|luhn',
        Fields::CARD_EXP_DATE                     => 'required|numeric|digits:6',
        Fields::LANGUAGE_CODE                     => 'required|in:en',
        Fields::AUTH_AMOUNT                       => 'required|numeric',
        Fields::CURRENCY_CODE                     => 'required|in:356',
        Fields::CVD2                              => 'required|numeric',
        Fields::TRANSACTION_TYPE_INDICATOR        => 'required|in:SMS,DMS',
        Fields::TID                               => 'required|string',
        Fields::STAN                              => 'required|numeric',
        Fields::TRAN_TIME                         => 'required|numeric',
        Fields::TRAN_DATE                         => 'required|numeric',
        Fields::MCC                               => 'required|numeric',
        Fields::ACQUIRER_INSTITUTION_COUNTRY_CODE => 'required|in:356',
        Fields::RETRIEVAL_REF_NUMBER              => 'required|numeric',
        Fields::CARD_ACCEPTOR_ID                  => 'required|string',
        Fields::TERMINAL_OWNER_NAME               => 'required|string|max:23',
        Fields::TERMINAL_CITY                     => 'required|string|max:13',
        Fields::TERMINAL_STATE_CODE               => 'required|string|max:2',
        Fields::TERMINAL_COUNTRY_CODE             => 'required|string|max:2',
        Fields::MERCHANT_POSTAL_CODE              => 'required|string|max:9',
        Fields::MERCHANT_TELEPHONE                => 'required|string|max:20',
        Fields::ORDER_ID                          => 'required|string|size:14',
        Fields::BROWSER_USERAGENT                 => 'required|string|max:512',
        Fields::IP_ADDRESS                        => 'required|string|max:50',
        Fields::HTTP_ACCEPT                       => 'required|string|max:256',
    ];

    protected static $initiateRules = [
        Fields::PARTNER_ID                        => 'required|string',
        Fields::MERCHANT_PASSWORD                 => 'required|string',
        Fields::CARD_NO                           => 'required|numeric|digits_between:13,19|luhn',
        Fields::CARD_EXP_DATE                     => 'required|numeric|digits:6',
        Fields::LANGUAGE_CODE                     => 'required|in:en',
        Fields::AUTH_AMOUNT                       => 'required|numeric',
        Fields::CURRENCY_CODE                     => 'required|in:356',
        Fields::CVD2                              => 'required|numeric',
        Fields::TRANSACTION_TYPE_INDICATOR        => 'required|in:SMS,DMS',
        Fields::TID                               => 'required|string',
        Fields::STAN                              => 'required|numeric',
        Fields::TRAN_TIME                         => 'required|numeric',
        Fields::TRAN_DATE                         => 'required|numeric',
        Fields::MCC                               => 'required|numeric',
        Fields::ACQUIRER_INSTITUTION_COUNTRY_CODE => 'required|in:356',
        Fields::RETRIEVAL_REF_NUMBER              => 'required|numeric',
        Fields::CARD_ACCEPTOR_ID                  => 'required|string',
        Fields::TERMINAL_OWNER_NAME               => 'required|string|max:23',
        Fields::TERMINAL_CITY                     => 'required|string|max:13',
        Fields::TERMINAL_STATE_CODE               => 'required|string|max:2',
        Fields::TERMINAL_COUNTRY_CODE             => 'required|string|max:2',
        Fields::MERCHANT_POSTAL_CODE              => 'required|string|max:9',
        Fields::MERCHANT_TELEPHONE                => 'required|string|max:20',
        Fields::ORDER_ID                          => 'required|string|size:14',
    ];
}
