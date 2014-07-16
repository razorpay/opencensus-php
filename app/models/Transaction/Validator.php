<?php

namespace Models\Transaction;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Transaction;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'merchant_id'   =>  'required|numeric',
        'amount'        =>  'required|numeric|max:500000|min:100',
        'currency'      =>  'required|max:3',
        'description'   =>  'sometimes',
        'email'         =>  'required|email',
        'contact'       =>  'required',
        'udf'           =>  'array');

    protected static $captureRules = array(
        'amount'        => 'required|numeric|max:500000|min:100');

    protected static $createValidators = array('currency', 'contact', 'description', 'udf');

    protected function validateContact($input)
    {
        $contact = $input['contact'];
        if (is_string($contact) === false)
        {
            throw new Exception\FieldErrorException(
                'Contact number can only contain numbers and + symbol',
                ErrorCode::FIELD_ERROR_INVALID_CONTACT,
                'contact');
        }

        $origContact = $contact;

        if ($contact[0] === '+')
            $contact = substr($contact, 1);

        if (is_numeric($contact) === false)
        {
            throw new Exception\FieldErrorException(
                'Contact number can only contain digits and + symbol',
                ErrorCode::FIELD_ERROR_INVALID_CONTACT,
                'contact');
        }

        if (strlen($contact) < 10)
        {
            throw new Exception\FieldErrorException(
                'Contact number should have minimum 10 digits',
                ErrorCode::FIELD_ERROR_INVALID_CONTACT,
                'contact');
        }

        if (strlen($contact) > 12)
        {
            throw new Exception\FieldErrorException(
                'Contact number should not be greater than 12 digits, including country code',
                ErrorCode::FIELD_ERROR_INVALID_CONTACT,
                'contact');
        }
    }

    protected function validateDescription($input)
    {
        $desc = $input['description'];

        if (is_string($desc) === false)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_DESCRIPTION_SHOULD_BE_STRING,
                'description');
        }

        if (strlen($desc) > 1000)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_DESCRIPTION_TOO_LARGE,
                'description');
        }
    }

    /**
     * Validates Udf
     *
     * @param  array $input  input array
     * @return void
     */
    protected function validateUdf($input)
    {
        if (isset($input['udf']) === false)
            return;

        $udf = $input['udf'];

        if (is_array($udf) === false)
        {
            throw new Exception\BadRequestException(
                'Udf should be provided as an array',
                null,
                'udf');
        }

        if (count($udf) > 15)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_UDF_TOO_MANY_KEYS,
                'udf');
        }

        foreach ($udf as $key => $value)
        {
            if (is_array($value))
                throw new Exception\BadRequestException(
                    null,
                    ErrorCode::BAD_REQUEST_UDF_VALUE_CANNOT_BE_ARRAY,
                    'udf');

            if (strlen($value) > 256)
                throw new Exception\BadRequestException(
                    null,
                    ErrorCode::BAD_REQUEST_UDF_VALUE_TOO_LARGE,
                    'udf');

            if (strlen($key) > 256)
                throw new Exception\BadRequestException(
                    null,
                    ErrorCode::BAD_REQUEST_UDF_KEY_TOO_LARGE,
                    'udf');
        }
    }

    protected function validateCurrency($input)
    {
        $currency = $input['currency'];

        //
        // Right now only INR is supported.
        //

        if ($currency !== "INR")
        {
            throw new Exception\BadRequestException(
                'Invalid currency: '.$currency.'. Only INR supported.',
                ErrorCode::BAD_REQUEST_CURRENCY_NOT_SUPPORTED,
                'currency');
        }
    }

    public static function checkCardKey($input)
    {
        if ((array_key_exists('card', $input) === false) or
            ($input['card'] === null))
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_TRANSACTION_CARD_NOT_PROVIDED,
                'card');
        }

        if (is_array($input['card']) === false)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_TRANSACTION_CARD_IS_NOT_ARRAY,
                'card');
        }
    }

    public static function captureValidate($txn, $input)
    {
        self::failIfCaptured($txn);

        self::failIfNotAuth($txn);

        self::captureInputValidate($input);

        self::captureAmountValidate($txn, $input);
    }

    public static function captureInputValidate($input)
    {
        try
        {
            $instance = new static;

            $instance->validateInput($input, 'capture');
        }
        catch (Exception\ValidationFailureException $e)
        {
            throw new Exception\BadRequestException($e->getMessageBag(), 0, $e);
        }
    }

    public static function captureAmountValidate($txn, $input)
    {
        if ($input['amount'] > $txn->getAttribute(Transaction\Entity::AMOUNT))
        {
            throw new Exception\BadRequestException(
                null, ErrorCode::BAD_REQUEST_CAPTURE_AMOUNT_GREATER_THAN_AUTH);
        }
    }

    public static function failIfCaptured($txn)
    {
        //
        // Don't continue if already captured
        //
        if ($txn->isCaptured())
        {
            throw new Exception\BadRequestException(
                null, ErrorCode::BAD_REQUEST_TRANSACTION_ALREADY_CAPTURED);
        }
    }

    public static function failIfNotAuth($txn)
    {
        if ($txn->isAuthorized() === false)
        {
            throw new Exception\BadRequestException(
                null, ErrorCode::BAD_REQUEST_TRANSACTION_CAPTURE_ONLY_AUTHORIZED);
        }
    }
}
