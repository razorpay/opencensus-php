<?php

namespace Models\Payment;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Payment;
use Models\Merchant;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'amount'        =>  'required|integer|max:50000000',
        'currency'      =>  'required|size:3',
        'method'        =>  'in:card,netbanking,wallet,emi',
        'card'          =>  'sometimes',
        'bank'          =>  'required_if:method,netbanking',
        'wallet'        =>  'required_if:method,wallet|in:paytm,mobikwik,payzapp',
        'emi_duration'  =>  'required_with:emi|integer|in:3,6,9,12,18,24',
        'description'   =>  'sometimes',
        'email'         =>  'required|email',
        'contact'       =>  'required',
        'notes'         =>  'sometimes',
        'signature'     =>  'sometimes',
        'notes'         =>  'sometimes|notes|contains_merchantorderid_if_signature',
        'callback_url'  =>  'sometimes|url',
        'order_id'      =>  'sometimes',
        'customer_id'   =>  'sometimes',
        'app_id'        =>  'sometimes',
        'token'         =>  'sometimes',
        'save'          =>  'sometimes|boolean',
        'fee'           =>  'sometimes|integer|max:50000000',
        'service_tax'   =>  'sometimes|integer|max:50000000',
        '_'             =>  'sometimes');

    protected static $captureRules = array(
        'amount'        => 'required|integer',
        'currency'      => 'sometimes|in:INR');

    protected static $refundRules = array(
        'amount'        => 'sometimes|integer',
        'notes'         => 'sometimes|notes'
    );

    protected static $createValidators = array(
        'card_key',
        'amount',
        'bank',
        'currency',
        'contact',
        'description',
        'fee');

    protected function validateCardKey($input)
    {
        if (($input['method'] !== Payment\Method::CARD) and
            ($input['method'] !== Payment\Method::EMI))
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
        if ($input['amount'] < 100)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT,
                'amount');
        }

        if(($input['method'] === Payment\Method::EMI) and
           ($input['amount'] < 300000))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT_FOR_EMI,
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

        if (Payment\Processor\Netbanking::isSupportedBank($input['bank']) === false)
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

        if (ctype_digit($contact) === false)
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

    protected function validateFee($input)
    {
        if (isset($input['fee']))
        {
            $merchant = $this->entity->merchant;

            if ($merchant->isFeeBearerCustomer() === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Attribute fee is not allowed and should not be sent');
            }
            else if (empty($input['fee']))
            {
                ;
            }
        }
        if ((isset($input['fee'])) and
            (empty($input['fee'])))
        {
            unset($input['fee']);
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
                ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED,
                'currency');
        }
    }

    public static function validateStatus($status)
    {
        if (Status::isStatusValid($status) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid status: ' . $status);
        }
    }

    public static function validateStatusArray(array $status)
    {
        foreach ($status as $value)
        {
            self::validateStatus($value);
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

    public function cancelValidate($payment)
    {
        $this->failIfNotCreated($payment);
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

    protected function failIfNotCreated($payment)
    {
        if ($payment->isCreated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CANCEL_ONLY_CREATED);
        }
    }

    protected function failIfCaptured($payment)
    {
        //
        // Don't continue if already captured
        //
        if ($payment->hasBeenCaptured())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED);
        }
    }

    protected function failIfNotAuthorized($payment)
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
