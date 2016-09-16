<?php

namespace RZP\Gateway\FirstData\Mock;

use RZP\Models\Base;
use RZP\Gateway\FirstData\Constants;
use RZP\Gateway\FirstData\Mapping;
use RZP\Gateway\FirstData\Codes;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        Constants::TXN_TYPE                  => 'required|in:preauth',
        Constants::TIME_ZONE                 => 'required|string',
        Constants::TXN_DATE_TIME             => 'required|string',
        Constants::HASH_ALGORITHM            => 'required|',
        Constants::HASH                      => 'required|size:40|string',
        Constants::STORE_NAME                => 'required|size:10|string',
        Constants::MODE                      => 'sometimes|',
        Constants::CHARGE_TOTAL              => 'required|numeric',
        Constants::CURRENCY                  => 'required|',
        Constants::ORDER_ID                  => 'sometimes|',
        Constants::TDATE                     => 'sometimes|',
        Constants::PAYMENT_METHOD            => 'required|',
        Constants::CUSTOMER_ID               => 'sometimes|',
        Constants::INVOICE_NUMBER            => 'sometimes|',
        Constants::CARD_FUNCTION             => 'sometimes|in:credit,debit|string',
        Constants::COMMENTS                  => 'sometimes|',
        Constants::RESPONSE_SUCCESS_URL      => 'required|url',
        Constants::RESPONSE_FAIL_URL         => 'required|url',
        Constants::DYNAMIC_MERCHANT_NAME     => 'sometimes|string',
        Constants::LANGUAGE                  => 'sometimes|',
        Constants::HASH_EXTENDED             => 'sometimes|size:40|string',
        Constants::NUMBER_OF_INSTALLMENTS    => 'sometimes|',
        Constants::CARD_NUMBER               => 'required|numeric|digits_between:12,19',
        Constants::NAME                      => 'sometimes|',
        Constants::EXP_MONTH                 => 'required|size:2',
        Constants::EXP_YEAR                  => 'required|size:4',
        Constants::CVV                       => 'required|numeric|digits_between:2,4',
    );

    protected static $authValidators = array(
        Constants::TXN_TYPE,
        Constants::MODE,
        Constants::PAYMENT_METHOD,
        Constants::HASH_ALGORITHM,
        Constants::CURRENCY,
        Constants::LANGUAGE,
    );

    protected static $captureRules = array(
        'Transaction'                                => 'required|',
        );

    protected static $captureValidators = array(
        'Transaction',
        );

    protected function validateTransaction($input)
    {
        if ((in_array($input['Transaction'], 'CreditCardTxType') === false) or
            (in_array($input['Transaction'], 'Payment') === false) or
            (in_array($input['Transaction'], 'TransactionDetails') === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Transaction Body');
        }
    }

    protected function validateTxntype($input)
    {
        if ((isset($input['txntype']) === false) or
            (in_array($input['txntype'], Codes::$txnTypes) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid txntype');
        }
    }

    protected function validateMode($input)
    {
        if ((isset($input['mode']) === true) and
            (in_array($input['mode'], Codes::$paymentModes) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid mode');
        }
    }

    protected function validatePaymentMethod($input)
    {
        if ((isset($input['paymentMethod']) === false) or
            (in_array($input['paymentMethod'], array_values(Mapping::PAYMENT_METHOD_CODES)) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid paymentMethod');
        }
    }

    protected function validateLanguage($input)
    {
        if ((isset($input['language']) === true) and
            ($input['language'] !== Codes::ENGLISH_UK_LANG_CODE_CONNECT))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Unsupported language');
        }
    }

    protected function validateCurrency($input)
    {
        if ((isset($input['currency']) === false) or
            ($input['currency'] !== Mapping::ISO_NUMERIC_CODES['INR']))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Unsupported currency');
        }
    }

    protected function validateHashAlgorithm($input)
    {
        if ((isset($input['hash_algorithm']) === false) or
            ($input['hash_algorithm'] !== Codes::FIRST_DATA_HASH_ALGORITHM))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Unsupported hash_algorithm');
        }
    }
}
