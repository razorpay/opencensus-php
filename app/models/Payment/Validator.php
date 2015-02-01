<?php

namespace Models\Payment;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Payment;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'amount'        =>  'required|numeric|max:50000000',
        'currency'      =>  'required|max:3',
        'method'        =>  'in:card,netbanking',
        'card'          =>  'sometimes',
        'bank'          =>  'required_if:method,netbanking',
        'description'   =>  'sometimes',
        'email'         =>  'required|email',
        'contact'       =>  'required',
        'notes'         =>  'sometimes');

    protected static $captureRules = array(
        'amount'        => 'required|numeric');

    protected static $refundRules = array(
        'amount'        => 'sometimes|numeric');

    protected static $createValidators = array(
        'card_key',
        'amount',
        'bank',
        'currency',
        'contact',
        'description',
        'notes');

    protected function validateCardKey($input)
    {
        if ($input['method'] !== Payment\Method::CARD)
        {
            return;
        }

        if ((array_key_exists('card', $input) === false) or
            ($input['card'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_PROVIDED);
        }

        if (is_array($input['card']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_IS_NOT_ARRAY);
        }
    }

    protected function validateAmount($input)
    {
        if (($input['method'] === Payment\Method::NETBANKING) and
            (((int) $input['amount']) < 5000))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ATOM_NET_BANKING_MIN_AMOUNT_FIFTY,
                'amount');
        }
        else if ($input['amount'] < 100)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT,
                'amount');
        }
    }

    protected function validateBank($input)
    {
        if ($input['method'] !== Payment\Method::NETBANKING)
        {
            return;
        }

        if (isset($input['bank']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_NOT_PROVIDED);
        }

        if (Payment\Processor\NetBanking::isSupportedBank($input['bank']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_INVALID_BANK_CODE,
                'bank');
        }
    }

    protected function validateContact($input)
    {
        $contact = $input['contact'];

        $code = null;
        $message = null;

        $field = Entity::CONTACT;

        if (is_string($contact) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_NOT_DIGITS,
                $field);
        }

        $origContact = $contact;

        // Except digits, only '+' symbol is allowed in the beginning
        if ($contact[0] === '+')
            $contact = substr($contact, 1);

        if (is_numeric($contact) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_NOT_DIGITS,
                $field);
        }

        if (strlen($contact) < 10)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_MIN_TEN_DIGITS,
                $field);
        }

        if (strlen($contact) > 12)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_MAX_TWELVE_DIGITS,
                $field);
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
     * Validates Notes
     *
     * @param  array $input  input array
     * @return void
     */
    protected function validateNotes($input)
    {
        if (isset($input['notes']) === false)
            return;

        $notes = $input['notes'];

        $code = null;

        if (is_array($notes) === false)
        {
            $code = ErrorCode::BAD_REQUEST_NOTES_SHOULD_BE_ARRAY;
        }
        else if (count($notes) > 15)
        {
            $code = ErrorCode::BAD_REQUEST_NOTES_TOO_MANY_KEYS;
        }
        else
        {
            foreach ($notes as $key => $value)
            {
                $code = $this->validateNotesKeyValue($key, $value);

                if ($code !== null)
                    break;
            }
        }

        if ($code !== null)
        {
            throw new Exception\BadRequestException($code, 'notes');
        }
    }

    protected function validateNotesKeyValue($key, $value)
    {
        $code = null;

        if (is_array($value))
        {
            $code = ErrorCode::BAD_REQUEST_NOTES_VALUE_CANNOT_BE_ARRAY;
        }
        else if (strlen($value) > 256)
        {
            $code = ErrorCode::BAD_REQUEST_NOTES_VALUE_TOO_LARGE;
        }
        else if (strlen($key) > 256)
        {
            $code = ErrorCode::BAD_REQUEST_NOTES_KEY_TOO_LARGE;
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
                ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED,
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

        $this->failIfNotAuthorized($payment);

        $this->validateInput('capture', $input);

        $this->captureAmountValidate($payment, $input);
    }

    public function captureAmountValidate($payment, $input)
    {
        $amount = (int) $input['amount'];

        if ($amount !== $payment->getAmount())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH,
                Payment\Entity::AMOUNT);
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

    public function failIfNotAuthorized($payment)
    {
        if ($payment->isAuthorized() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_ONLY_AUTHORIZED);
        }
    }

    // protected function processValidationFailure($messages, $operation, $input)
    // {
    //     $bag = $messages;

    //     $this->checkValidationFailureEmail($bag);

    //     $this->checkValidationFailureContact($bag);

    //     parent::processValidationFailure($messages, $operation, $input);
    // }

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
