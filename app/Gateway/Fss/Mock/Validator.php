<?php

namespace RZP\Gateway\Fss\Mock;

use RZP\Base;
use RZP\Gateway\Fss\Constants;
use RZP\Gateway\Fss\Fields;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $authRules = [
        Fields::ACTIONVPAS          => 'required:string',
        Fields::TRAN_DATA           => 'required:string',
        Fields::ERROR_URL           => 'required:string',
        Fields::RESPONSE_URL        => 'required:string',
        Fields::TRANPORTAL_ID       => 'required:string',
    ];

    protected static $refundRules = [
        Fields::CURRENCY_CODE       => 'required:string',
        Fields::TYPE                => 'required:string:custom',
        Fields::UDF5                => 'required:string:in:TrackID',
        Fields::LANGUAGE_ID         => 'required:string:in:USA',
        Fields::ID                  => 'required:string',
        Fields::PASSWORD            => 'required:string',
        Fields::TRANSACTION_ID      => 'required:string',
        Fields::ACTION              => 'required:string',
        Fields::TRACK_ID            => 'required:string',
        Fields::AMOUNT              => 'required',
    ];

    protected static $authTransactionDataRules = [
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

    protected static $authTransactionDataValidators = [
        Fields::CURRENCY_CODE,
        Fields::TYPE,
    ];

    protected function validateCurrencyCode($input)
    {
        if ($input[Fields::CURRENCY_CODE] !== Constants::CURRENCY_CODE)
        {
            throw new Exception\BadRequestValidationFailureException("Invalid CurrencyCode");
        }
    }

    protected function validateType($input)
    {
        if ($input[Fields::TYPE] !== Constants::CREDIT_CARD_TYPE and
            $input[Fields::TYPE] !== Constants::DEBIT_CARD_TYPE)
        {
            throw new Exception\BadRequestValidationFailureException( "Invalid Card Type");
        }
    }
}
