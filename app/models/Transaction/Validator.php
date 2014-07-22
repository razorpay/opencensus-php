<?php

namespace Models\Transaction;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Transaction;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'merchant_id'   =>  'required|alpha_num|size:24',
        'amount'        =>  'required|numeric|max:500000|min:100',
        'currency'      =>  'required|max:3',
        'description'   =>  'sometimes',
        'email'         =>  'required|email',
        'contact'       =>  'required',
        'udf'           =>  'sometimes');

    protected static $captureRules = array(
        'amount'        => 'required|numeric|max:500000|min:100');

    protected static $createValidators = array('currency', 'contact', 'description', 'udf');

    protected function validateContact($input)
    {
        $contact = $input['contact'];

        $code = null;
        $message = null;

        if (is_string($contact) === false)
        {
            $message = 'Contact number can only contain digits and + symbol';
            $code = ErrorCode::FIELD_ERROR_INVALID_CONTACT;
        }

        $origContact = $contact;

        if ($contact[0] === '+')
            $contact = substr($contact, 1);

        if (is_numeric($contact) === false)
        {
            $message = 'Contact number can only contain digits and + symbol';
            $code = ErrorCode::FIELD_ERROR_INVALID_CONTACT;
        }

        if (strlen($contact) < 10)
        {
            $message = 'Contact number should be at least 10 digits';
            $code = ErrorCode::FIELD_ERROR_INVALID_CONTACT;
        }

        if (strlen($contact) > 12)
        {
            $message = 'Contact number should not be greater than 12 digits, including country code';
            $code = ErrorCode::FIELD_ERROR_INVALID_CONTACT;
        }

        if ($code !== null)
        {
            throw new Exception\FieldErrorException($message, $code, 'contact');
        }
    }

    protected function validateDescription($input)
    {
        if (isset($input['description']) === false)
            return;

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

        $code = null;

        if (is_array($udf) === false)
        {
            $code = ErrorCode::BAD_REQUEST_UDF_SHOULD_BE_ARRAY;
        }
        else if (count($udf) > 15)
        {
            $code = ErrorCode::BAD_REQUEST_UDF_TOO_MANY_KEYS;
        }
        else
        {
            foreach ($udf as $key => $value)
            {
                $code = $this->validateUdfKeyValue($key, $value);

                if ($code !== null)
                    break;
            }
        }

        if ($code !== null)
        {
            throw new Exception\BadRequestException(null, $code, 'udf');
        }
    }

    protected function validateUdfKeyValue($key, $value)
    {
        $code = null;

        if (is_array($value))
        {
            $code = ErrorCode::BAD_REQUEST_UDF_VALUE_CANNOT_BE_ARRAY;
        }
        else if (strlen($value) > 256)
        {
            $code = ErrorCode::BAD_REQUEST_UDF_VALUE_TOO_LARGE;
        }
        else if (strlen($key) > 256)
        {
            $code = ErrorCode::BAD_REQUEST_UDF_KEY_TOO_LARGE;
        }

        return $code;
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
                null,
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

    public static function bankAcsCallbackValidate($txn, $input)
    {
        if ($txn->isOpen() === false)
        {
            throw new Exception\BadRequestException(
                null, ErrorCode::BAD_REQUEST_TRANSACTION_ALREADY_PROCCESSED);
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
                null, ErrorCode::BAD_REQUEST_CAPTURE_AMOUNT_GREATER_THAN_AUTH, 'amount');
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
