<?php

namespace RZP\Gateway\Card\Fss\Mock;

use RZP\Base;
use RZP\Gateway\Card\Fss\Acquirer;
use RZP\Gateway\Card\Fss\CardType;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Card\Fss\Fields;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $authRules = [
        Fields::ACTIONVPAS          => 'sometimes|string',
        Fields::TRAN_DATA           => 'required|string',
        Fields::ERROR_URL           => 'required|string',
        Fields::RESPONSE_URL        => 'required|string',
        Fields::TRANPORTAL_ID       => 'required|string',
        Fields::PARAM               => 'sometimes|string',
        Fields::ACQUIRER            => 'sometimes|string',
    ];

    protected static $refundRules = [
        Fields::CURRENCY_CODE       => 'required|string',
        Fields::TYPE                => 'required|string|custom',
        Fields::UDF5                => 'required|string|in:TrackID,trackid',
        Fields::UDF3                => 'sometimes|string',
        Fields::LANGUAGE_ID         => 'required|string|in:USA',
        Fields::ID                  => 'required|string',
        Fields::PASSWORD            => 'sometimes|string',
        Fields::TRANSACTION_ID      => 'required|string',
        Fields::ACTION              => 'required|string',
        Fields::TRACK_ID            => 'required|string',
        Fields::AMOUNT              => 'required',
        Fields::BANK_CODE           => 'sometimes|string',
    ];

    protected static $authTransactionDataRules = [
        Fields::CARD                => 'required|string',
        Fields::CVV                 => 'required|string|size:3',
        Fields::CURRENCY_CODE       => 'required|string|custom',
        Fields::EXPIRY_YEAR         => 'required|string',
        Fields::EXPIRY_MONTH        => 'required|string',
        Fields::TYPE                => 'required|string|custom',
        Fields::MEMBER              => 'required|string',
        Fields::AMOUNT              => 'required',
        Fields::ACTION              => 'required|in:1',
        Fields::TRACK_ID            => 'required|size:14',
        Fields::ERROR_URL           => 'required|string|url',
        Fields::RESPONSE_URL        => 'required|string|url',
        Fields::ID                  => 'required|string',
        Fields::PASSWORD            => 'sometimes|string',
        Fields::UDF5                => 'sometimes|string',
        Fields::UDF3                => 'sometimes|string',
        Fields::LANGUAGE_ID         => 'sometimes|string',
        Fields::BANK_CODE           => 'sometimes|string',
    ];

    protected function validateCurrencyCode($attribute, $value)
    {
        if ($value !== Currency::getIsoCode(Currency::INR))
        {
            throw new Exception\BadRequestValidationFailureException('Invalid CurrencyCode');
        }
    }

    protected function validateType($attribute, $value)
    {
        $bobCardTypes = array_values(CardType::getCardTypesByAcquirer(Acquirer::BOB));
        $fssCardTypes = array_values(CardType::getCardTypesByAcquirer(Acquirer::FSS));

        if ((in_array($value, $bobCardTypes) === false) and (in_array($value, $fssCardTypes) === false))
        {
            throw new Exception\BadRequestValidationFailureException( 'Invalid Card Type');
        }
    }
}
