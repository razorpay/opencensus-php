<?php

namespace RZP\Gateway\Fss\Mock;

use RZP\Base;
use RZP\Gateway\Fss\Fields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        Fields::ACTIONVPAS          => 'required:string',
        Fields::TRAN_DATA           => 'required:string',
        Fields::ERROR_URL           => 'required:string',
        Fields::RESPONSE_URL        => 'required:string',
        Fields::TRANPORTAL_ID       => 'required:string',
    ];

    protected static $transactionDataRules = [
        Fields::CARD                => 'required:string',
        Fields::CVV                 => 'required:string:size:3',
        Fields::CURRENCY_CODE       => 'required:string',
        Fields::EXPIRY_YEAR         => 'required:string',
        Fields::EXPIRY_MONTH        => 'required:string',
        Fields::TYPE                => 'required:string:custom',
        Fields::MEMBER              => 'required:string',
        Fields::AMOUNT              => 'required',
        Fields::ACTION              => 'required:in:1',
        Fields::TRACK_ID            => 'required:size:14',
        Fields::ERROR_URL           => 'required:string:url',
        Fields::RESPONSE_URL        => 'required:string:url',
        Fields::ID                  => 'required:string',
        Fields::PASSWORD            => 'required:string',
    ];
}