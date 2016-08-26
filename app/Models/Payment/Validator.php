<?php

namespace RZP\Models\Payment;

use Cache;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Lib\PhoneBook;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Payment\Processor\Wallet;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'amount'                  =>  'required|integer',
        'currency'                =>  'required|size:3',
        'method'                  =>  'in:card,netbanking,wallet,emi',
        'card'                    =>  'sometimes',
        'bank'                    =>  'required_if:method,netbanking',
        'wallet'                  =>  'required_if:method,wallet|in:paytm,payzapp,mobikwik,payumoney,olamoney',
        'emi_duration'            =>  'required_if:method,emi|integer|in:3,6,9,12,18,24',
        'description'             =>  'sometimes',
        'email'                   =>  'required|email',
        'contact'                 =>  'required|contact_syntax',
        'signature'               =>  'sometimes',
        'notes'                   =>  'sometimes|notes',
        'notes.merchant_order_id' =>  'required_with:signature',
        'callback_url'            =>  'sometimes|url',
        'order_id'                =>  'sometimes',
        'customer_id'             =>  'sometimes',
        'app_token'               =>  'sometimes',
        'token'                   =>  'sometimes',
        'save'                    =>  'sometimes|in:0,1',
        'fee'                     =>  'sometimes|integer|max:50000000',
        'service_tax'             =>  'sometimes|integer|max:50000000',
        '_'                       =>  'sometimes');

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
        'description',
        'fee',
        'contact',
        'wallet');

    protected function validateWallet($input)
    {
        if ($input['method'] !== Payment\Method::WALLET)
        {
            return true;
        }

        if (isset($input['wallet']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_PROVIDED);
        }

        if (Wallet::exists($input['wallet']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED);
        }

        return true;
    }

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
        $amount = $input['amount'];

        if ($amount < 100)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT,
                'amount');
        }

        if (($input['method'] === Payment\Method::EMI) and ($amount < 200000))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT_FOR_EMI,
                'amount');
        }

        $maxAmountAllowed = $this->entity->merchant->getMaxPaymentAmount();

        if ($amount > $maxAmountAllowed)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount exceeds maximum amount allowed.',
                'amount');
        }
    }

    public function validateMinAmountWithEmiPlanAmount($emiPlan)
    {
        if ($this->entity->getAmount() < $emiPlan->getMinAmount())
        {
            // We need to do this check here because currently amex has a higher limit of 5k.
            throw new Exception\BadRequestValidationFailureException(
                'Minimum amount allowed for EMI payment on this card must be ' . $emiPlan->getMinAmount());
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
        if ($input['method'] === Payment\Method::WALLET)
        {
            $number = new PhoneBook($input['contact'], true);
            $country = $number->getRegionCodeForNumber();

            if ($country !== 'IN')
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_ONLY_INDIAN_ALLOWED);
            }
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

        if (strlen($desc) > 255)
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

    public function captureValidate($payment, $input)
    {
        $this->failIfCaptured($payment);

        $this->failIfNotAuthorized($payment);

        $this->validateInput('capture', $input);

        $this->captureAmountValidate($payment, $input);

        $this->failIfCaptureInProgress($payment);
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
            $e = new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH,
                Payment\Entity::AMOUNT);

            $e->setData(['amount' => $input['amount']]);

            throw $e;
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

    protected function failIfCaptureInProgress($payment)
    {
        $key = $payment->getId() . '_captureInProgress';

        //
        // Don't continue if capture is in progress
        //
        if (Cache::get($key) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_DUPLICATE_CAPTURE_REQUEST);
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
}
