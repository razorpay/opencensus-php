<?php

namespace RZP\Gateway\FirstData\Mock;

use RZP\Models\Base;
use RZP\Gateway\FirstData\Constants;
use RZP\Gateway\FirstData\Mapping;
use RZP\Gateway\FirstData\ConnectRequestFields;
use RZP\Gateway\FirstData\TxnType;
use RZP\Gateway\FirstData\Codes;
use RZP\Constants\HashAlgo;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        ConnectRequestFields::CARD_FUNCTION             => 'sometimes|in:credit,debit|string',
        ConnectRequestFields::CARD_NUMBER               => 'required|numeric|digits_between:12,19',
        ConnectRequestFields::CHARGE_TOTAL              => 'required|numeric',
        ConnectRequestFields::COMMENTS                  => 'sometimes|',
        ConnectRequestFields::CURRENCY                  => 'required|',
        ConnectRequestFields::CVV                       => 'required|numeric|digits_between:2,4',
        ConnectRequestFields::DYNAMIC_MERCHANT_NAME     => 'sometimes|string',
        ConnectRequestFields::EXP_MONTH                 => 'required|size:2',
        ConnectRequestFields::EXP_YEAR                  => 'required|size:4',
        ConnectRequestFields::HASH                      => 'required|size:40|string',
        ConnectRequestFields::HASH_ALGORITHM            => 'required|',
        ConnectRequestFields::INVOICE_NUMBER            => 'sometimes|',
        ConnectRequestFields::LANGUAGE                  => 'sometimes|',
        ConnectRequestFields::MODE                      => 'sometimes|',
        ConnectRequestFields::NAME                      => 'sometimes|',
        ConnectRequestFields::NUMBER_OF_INSTALLMENTS    => 'sometimes|',
        ConnectRequestFields::ORDER_ID                  => 'sometimes|',
        ConnectRequestFields::PAYMENT_METHOD            => 'required|',
        ConnectRequestFields::RESPONSE_FAIL_URL         => 'required|url',
        ConnectRequestFields::RESPONSE_SUCCESS_URL      => 'required|url',
        ConnectRequestFields::STORE_NAME                => 'required|size:10|string',
        ConnectRequestFields::TIME_ZONE                 => 'required|string',
        ConnectRequestFields::TXN_DATE_TIME             => 'required|string',
        ConnectRequestFields::TXN_TYPE                  => 'required|in:preauth',
    );

    protected static $authValidators = array(
        ConnectRequestFields::TXN_TYPE,
        ConnectRequestFields::MODE,
        ConnectRequestFields::PAYMENT_METHOD,
        ConnectRequestFields::HASH_ALGORITHM,
        ConnectRequestFields::CURRENCY,
        ConnectRequestFields::LANGUAGE,
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
            (in_array($input['txntype'], TxnType::$typeList) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid txntype');
        }
    }

    protected function validateMode($input)
    {
        if ((isset($input['mode']) === true) and
            (in_array($input['mode'], Codes::PAYMENT_MODES) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid mode');
        }
    }

    protected function validatePaymentMethod($input)
    {
        if ((isset($input['paymentMethod']) === false) or
            (in_array($input['paymentMethod'], array_values(Codes::PAYMENT_METHODS)) === false))
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
            ($input['currency'] !== Codes::ISO_NUMERIC_CURRENCY['INR']))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Unsupported currency');
        }
    }

    protected function validateHashAlgorithm($input)
    {
        if ((isset($input['hash_algorithm']) === false) or
            ($input['hash_algorithm'] !== strtoupper(HashAlgo::SHA1)))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Unsupported hash_algorithm');
        }
    }
}
