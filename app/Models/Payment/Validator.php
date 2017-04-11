<?php

namespace RZP\Models\Payment;

use Cache;
use Carbon\Carbon;
use Lib\PhoneBook;

use RZP\Base;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Upi;
use RZP\Models\Card;
use RZP\Models\Currency\Currency;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Models\Payment\Processor\Wallet;

class Validator extends Base\Validator
{
    protected static $createRules = [
        'amount'                  => 'required|integer',
        'currency'                => 'required|string|size:3',
        'method'                  => 'string|custom',
        'vpa'                     => 'required_if:method,upi|string|max:100|custom',
        'aadhaar'                 => 'required_if:method,aeps|array',
        'aadhaar.number'          => 'required_if:method,aeps|size:12|string',
        'aadhaar.fingerprint'     => 'required_if:method,aeps|max:999|string',
        'aadhaar.session_key'     => 'sometimes_if:method,aeps|size:344|string',
        'aadhaar.hmac'            => 'sometimes_if:method,aeps|size:64|string',
        'aadhaar.cert_expiry'     => 'sometimes_if:method,aeps|size:8|string',
        'card'                    => 'sometimes',
        'bank'                    => 'required_if:method,netbanking,aeps',
        'wallet'                  => 'required_if:method,wallet|custom',
        'emi_duration'            => 'required_if:method,emi|integer|in:3,6,9,12,18,24',
        'description'             => 'sometimes',
        'email'                   => 'sometimes|email',
        'contact'                 => 'sometimes|contact_syntax',
        'signature'               => 'sometimes|string',
        'notes'                   => 'sometimes|notes',
        'notes.merchant_order_id' => 'required_with:signature',
        'callback_url'            => 'sometimes|url',
        'order_id'                => 'sometimes|filled',
        'customer_id'             => 'required_if:wallet,openwallet|public_id|filled',
        'subscription_id'         => 'sometimes|public_id',
        'app_token'               => 'sometimes',
        'token'                   => 'sometimes',
        'save'                    => 'sometimes|in:0,1',
        'recurring'               => 'sometimes_if:method,card|in:0,1',
        'fee'                     => 'sometimes|filled|integer|max:50000000',
        'service_tax'             => 'sometimes|filled|integer|max:50000000',
        'on_hold'                 => 'sometimes_if:method,transfer|boolean',
        'on_hold_until'           => 'sometimes_if:method,transfer|epoch',
        'ip'                      => 'sometimes|ip',
        'referer'                 => 'sometimes|string|max:2083',
        'user_agent'              => 'sometimes|string',
        '_'                       => 'sometimes|array',
    ];

    protected static $editRules = [
        Entity::APPROVAL_CODE     => 'sometimes|string|max:6',
        Entity::REFERENCE1        => 'sometimes|string',
        Entity::REFERENCE2        => 'sometimes|string',
    ];

    protected static $captureRules = [
        Entity::AMOUNT            => 'required|integer',
        Entity::CURRENCY          => 'required|in:INR,USD',
    ];

    protected static $refundRules = [
        'amount'                  => 'sometimes|integer',
        'notes'                   => 'sometimes|notes',
        'reverse_all'             => 'sometimes|boolean',
        'reversals'               => 'sometimes|array',
        'reversals.*.transfer'    => 'required|public_id',
        'reversals.*.amount'      => 'required|integer|min:100',
        'reversals.*.notes'       => 'sometimes|notes',
    ];

    protected static $transferRules = [
        'transfers'                  => 'required|array',
        'transfers.*.customer'       => 'sometimes|public_id',
        'transfers.*.account'        => 'sometimes|public_id',
        'transfers.*.amount'         => 'required|integer|min:100',
        'transfers.*.currency'       => 'required|string|size:3',
        'transfers.*.notes'          => 'sometimes|notes',
        'transfers.*.on_hold'        => 'sometimes|boolean',
        'transfers.*.on_hold_until'  => 'sometimes|epoch',
    ];

    protected static $createValidators = [
        'card_key',
        'amount',
        'bank',
        'currency',
        'description',
        'fee',
        'contact',
        'email',
        'hold_parameters',
    ];

    protected function validateEmail(array $input)
    {
        $allowedPaymentMethods = [
            'aeps',
            Payment\Method::TRANSFER,
        ];

        if ((in_array($input[Entity::METHOD], $allowedPaymentMethods, true) === false) and
            (empty($input[Entity::EMAIL]) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'The email field is required.', Entity::EMAIL);
        }
    }

    protected function validateMethod($attribute, $method)
    {
        if (Method::isValid($method) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid payment method given: ' . $method);
        }
    }

    protected function validateVpa($attribute, $vpa, $parameter)
    {
        $vpaParts = explode('@', $vpa);

        if ((count($vpaParts) !== 2) or
            (ProviderCode::validate($vpaParts[1]) === false))
        {
            // Invalid VPA
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA);
        }
    }

    protected function validateWallet($attribute, $value)
    {
        Wallet::validateExists($value);
    }

    protected function validateCardKey(array $input)
    {
        if (($input['method'] !== Payment\Method::CARD) and
            ($input['method'] !== Payment\Method::EMI))
        {
            return;
        }

        if ((isset($input['recurring']) === true) and
            ($input['recurring'] === '1') and
            (empty($input['token']) === false))
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

    protected function validateAmount(array $input)
    {
        $amount = $input['amount'];

        if ($amount < 100)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT,
                'amount');
        }

        if (($input['method'] === Payment\Method::WALLET) and
            ($input['wallet'] === Wallet::AIRTELMONEY) and
            ($amount < 1000))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_10_MIN_AMOUNT,
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
                'amount',
                ['amount' => $amount]);
        }
    }

    public function validateUpiVpaPsp(string $vpa, array $excludedPsps)
    {
        $vpaParts = explode('@', $vpa);

        if (in_array($vpaParts[1], $excludedPsps, true) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_APP_NOT_SUPPORTED);
        }
    }

    public function validateCardAndCvv(array $input)
    {
        if (isset($input['card']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_PROVIDED);
        }

        if (isset($input['card']['cvv']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_CVV_NOT_PROVIDED);
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

    public function validateForPayout(string $mode)
    {
        if ($this->entity->isCaptured() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED);
        }

        if (($mode === MODE::LIVE) and
            ($this->entity->transaction->isSettled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_BEFORE_SETTLEMENT);
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
        $allowedPaymentMethods = [
            'aeps',
            Payment\Method::TRANSFER,
        ];

        if ((in_array($input[Entity::METHOD], $allowedPaymentMethods, true) === false) and
            (empty($input[Entity::CONTACT]) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'The contact field is required.', Entity::CONTACT);
        }

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

        // Right now only INR and USD is supported.
        if (in_array($currency, Currency::SUPPORTED_CURRENCIES, true) === false)
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

    public function validateHoldParameters(array $input)
    {
        if (isset($input[Entity::ON_HOLD]) === false)
        {
            return;
        }

        if (isset($input[Entity::ON_HOLD_UNTIL]) === true)
        {
            if ($input[Entity::ON_HOLD] === '0')
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The on_hold field must be set to 1, if on_hold_until is sent');
            }

            $now = Carbon::now('Asia/Kolkata')->timestamp;

            if ($input[Entity::ON_HOLD_UNTIL] < $now)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The on_hold_until timestamp cannot be less than the current timestamp');
            }
        }
    }

    public function captureValidate(Payment\Entity $payment, int $amount, string $currency)
    {
        $this->failIfCaptured($payment);

        $this->failIfNotAuthorized($payment);

        // Removing this temporarily
        // $this->captureAmountValidate($payment, $amount);

        $this->captureCurrencyValidate($payment, $currency);
    }

    public function cancelValidate($payment)
    {
        $this->failIfNotCreated($payment);
    }

    public function validateIsCaptured()
    {
        if ($this->entity->isCaptured() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED);
        }
    }

    public function captureAmountValidate(Payment\Entity $payment, int $amount)
    {
        if ($amount !== $payment->getAmount())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH,
                Payment\Entity::AMOUNT,
                [
                    'capture_amount' => $amount,
                    'payment_amount' => $payment->getAmount(),
                    'payment_id'     => $payment->getId(),
                ]);
        }
    }

    protected function captureCurrencyValidate($payment, $currency)
    {
        if ($currency !== $payment->getCurrency())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_CURRENCY_MISMATCH,
                Payment\Entity::CURRENCY,
                [
                    'capture_currency' => $currency,
                    'payment_currency' => $payment->getCurrency(),
                    'payment_id'       => $payment->getId(),
                ]);
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
}
