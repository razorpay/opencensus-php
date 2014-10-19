<?php

namespace Models\Payment;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Payment;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'amount'        =>  'required|numeric|max:50000000|min:100',
        'currency'      =>  'required|max:3',
        'method'        =>  'in:card,net banking',
        'card'          =>  'sometimes',
        'description'   =>  'sometimes',
        'email'         =>  'required|email',
        'contact'       =>  'required',
        'udf'           =>  'sometimes');

    protected static $captureRules = array(
        'amount'        => 'required|numeric|max:50000000|min:100');

    protected static $refundRules = array(
        'amount'        => 'sometimes|numeric');

    protected static $createValidators = array('card_key', 'currency', 'contact', 'description', 'udf');

    public static function validateCardKey($input)
    {
        if ((array_key_exists('card', $input) === false) or
            ($input['card'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_PROVIDED,
                'card');
        }

        if (is_array($input['card']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_IS_NOT_ARRAY,
                'card');
        }
    }

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
            throw new Exception\FieldErrorException($message, $code, Entity::CONTACT);
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
                ErrorCode::BAD_REQUEST_DESCRIPTION_SHOULD_BE_STRING,
                Entity::DESCRIPTION);
        }

        if (strlen($desc) > 1000)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_DESCRIPTION_TOO_LARGE,
                Entity::DESCRIPTION);
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
            throw new Exception\BadRequestException($code, 'udf');
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
                ErrorCode::BAD_REQUEST_CURRENCY_NOT_SUPPORTED,
                'currency');
        }
    }

    public static function bankAcsCallbackValidate($payment, $input)
    {
        if ($payment->isCreated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCCESSED);
        }
    }

    public function captureValidate($payment, $input)
    {
        $this->failIfCaptured($payment);

        $this->failIfNotAuth($payment);

        $this->validateInput('capture', $input);

        $this->captureAmountValidate($payment, $input);
    }

    public function captureAmountValidate($payment, $input)
    {
        if ($input['amount'] > $payment->getAttribute(Payment\Entity::AMOUNT))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CAPTURE_AMOUNT_GREATER_THAN_AUTH, 'amount');
        }
    }

    public function failIfCaptured($payment)
    {
        //
        // Don't continue if already captured
        //
        if ($payment->isCaptured())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED);
        }
    }

    public function failIfNotAuth($payment)
    {
        if ($payment->isAuthorized() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_ONLY_AUTHORIZED);
        }
    }

    protected function processValidationFailure($messages, $operation, $input)
    {
        $bag = $messages;

        $this->checkValidationFailureEmail($bag);

        $this->checkValidationFailureContact($bag);

        parent::processValidationFailure($messages, $operation, $input);
    }

    protected function checkValidationFailureEmail($bag)
    {
        if ($bag->has(Entity::EMAIL))
        {
            $msg = $bag->first(Entity::EMAIL);

            throw new Exception\FieldErrorException(
                $msg,
                ErrorCode::FIELD_ERROR_INVALID_EMAIL,
                Entity::EMAIL);
        }
    }

    protected function checkValidationFailureContact($bag)
    {
        if ($bag->has(Entity::CONTACT))
        {
            $msg = $bag->first(Entity::CONTACT);

            throw new Exception\FieldErrorException(
                $msg,
                ErrorCode::FIELD_ERROR_INVALID_CONTACT,
                Entity::CONTACT);
        }
    }
}
