<?php

namespace RZP\Gateway\FirstData\Mock;

use RZP\Models\Base;
use RZP\Gateway\FirstData;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        FirstData\Gateway::TXNTYPE                   => 'required|',
        FirstData\Gateway::TIMEZONE                  => 'required|string',
        FirstData\Gateway::TXNDATETIME               => 'required|string',
        FirstData\Gateway::HASH_ALGORITHM            => 'required|',
        FirstData\Gateway::HASH                      => 'required|size:40|string',
        FirstData\Gateway::STORENAME                 => 'required|size:10|string',
        FirstData\Gateway::MODE                      => 'sometimes|',
        FirstData\Gateway::CHARGETOTAL               => 'required|numeric',
        FirstData\Gateway::CURRENCY                  => 'required|',
        FirstData\Gateway::OID                       => 'sometimes|',
        FirstData\Gateway::TDATE                     => 'sometimes|',
        FirstData\Gateway::PAYMENT_METHOD            => 'required|',
        FirstData\Gateway::CUSTOMERID                => 'sometimes|',
        FirstData\Gateway::INVOICENUMBER             => 'sometimes|',
        FirstData\Gateway::CARD_FUNCTION             => 'sometimes|in:credit,debit|string',
        FirstData\Gateway::COMMENTS                  => 'sometimes|',
        FirstData\Gateway::RESPONSE_SUCCESS_URL      => 'required|url',
        FirstData\Gateway::RESPONSE_FAIL_URL         => 'required|url',
        FirstData\Gateway::DYNAMIC_MERCHANT_NAME     => 'sometimes|string',
        FirstData\Gateway::LANGUAGE                  => 'sometimes|',
        FirstData\Gateway::HASH_EXTENDED             => 'sometimes|size:40|string',
        FirstData\Gateway::NUMBER_OF_INSTALLMENTS    => 'sometimes|',
        FirstData\Gateway::CARDNUMBER                => 'required|numeric|digits_between:12,19',
        FirstData\Gateway::NAME                      => 'sometimes|',
        FirstData\Gateway::EXPMONTH                  => 'required|size:2',
        FirstData\Gateway::EXPYEAR                   => 'required|size:4',
        FirstData\Gateway::CVM                       => 'required|numeric|digits_between:2,4',
    );


    protected static $authValidators = array(
        FirstData\Gateway::TXNTYPE,
        FirstData\Gateway::MODE,
        FirstData\Gateway::PAYMENT_METHOD,
        FirstData\Gateway::HASH_ALGORITHM,
        FirstData\Gateway::CURRENCY,
        FirstData\Gateway::LANGUAGE,
    );


    protected function validateTxntype($input)
    {
        if ((isset($input['txntype']) === false) or
            (in_array($input['txntype'], FirstData\Codes::$txnTypes) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid txntype');
        }
    }

    protected function validateMode($input)
    {
        if ((isset($input['mode']) === true) and
            (in_array($input['mode'], FirstData\Codes::$paymentModes) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid mode');
        }
    }

    protected function validatePaymentMethod($input)
    {
        if ((isset($input['paymentMethod']) === false) or
            (in_array($input['paymentMethod'], array_values(FirstData\Mapping::$paymentMethodCodes)) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid paymentMethod');
        }
    }

    protected function validateLanguage($input)
    {
        if ((isset($input['language']) === true) and
            ($input['language'] !== FirstData\Codes::ENGLISH_UK_LANG_CODE_CONNECT))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Unsupported language');
        }
    }

    protected function validateCurrency($input)
    {
        if ((isset($input['currency']) === false) or
            ($input['currency'] !== FirstData\Mapping::$isoNumericCodes['INR']))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Unsupported currency');
        }
    }

    protected function validateHashAlgorithm($input)
    {
        if ((isset($input['hash_algorithm']) === false) or
            ($input['hash_algorithm'] !== FirstData\Codes::FIRST_DATA_HASH_ALGORITHM))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Unsupported hash_algorithm');
        }
    }
}
