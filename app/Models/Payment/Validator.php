<?php

namespace RZP\Models\Payment;

use App;
use Route;
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
        'amount'                     => 'required|integer',
        'currency'                   => 'required|string|size:3',
        'method'                     => 'required|string|custom',
        'vpa'                        => 'required_if:method,upi|string|max:100|custom',
        'aadhaar'                    => 'required_if:method,aeps|array',
        'aadhaar.number'             => 'required_if:method,aeps|size:12|string',
        'aadhaar.fingerprint'        => 'required_if:method,aeps|max:999|string',
        'aadhaar.session_key'        => 'sometimes_if:method,aeps|size:344|string',
        'aadhaar.hmac'               => 'sometimes_if:method,aeps|size:64|string',
        'aadhaar.cert_expiry'        => 'sometimes_if:method,aeps|size:8|string',
        'card'                       => 'sometimes',
        'bank'                       => 'required_if:method,netbanking,aeps',
        'wallet'                     => 'required_if:method,wallet|custom',
        'emi_duration'               => 'required_if:method,emi|integer|in:3,6,9,12,18,24',
        'description'                => 'sometimes|string|max:255|utf8',
        'email'                      => 'sometimes|nullable|email',
        'contact'                    => 'sometimes|nullable|contact_syntax',
        'signature'                  => 'sometimes|nullable|string',
        'notes'                      => 'sometimes|notes',
        'notes.merchant_order_id'    => 'required_with:signature',
        'callback_url'               => 'sometimes|url',
        'order_id'                   => 'sometimes|filled',
        'customer_id'                => 'sometimes|public_id|filled',
        'subscription_id'            => 'sometimes|public_id',
        'app_token'                  => 'sometimes',
        'token'                      => 'sometimes',
        'save'                       => 'sometimes|in:0,1',
        'recurring'                  => 'sometimes_if:method,card,netbanking|in:0,1',
        'fee'                        => 'sometimes|filled|integer|max:50000000',
        Entity::TAX                  => 'sometimes|filled|integer|max:50000000',
        'on_hold'                    => 'sometimes_if:method,transfer|boolean',
        'on_hold_until'              => 'sometimes_if:method,transfer|nullable|epoch',
        'ip'                         => 'sometimes|ip',
        'referer'                    => 'sometimes|string|max:2083',
        'user_agent'                 => 'sometimes|string',
        '_'                          => 'sometimes|array',
        'test_success'               => 'sometimes|boolean',
        'subscription_card_change'   => 'sometimes|boolean',
        'account_number'             => 'sometimes|alpha_num|between:5,20|nullable',
    ];

    protected static $editRules = [
        Entity::APPROVAL_CODE        => 'sometimes|string|max:6',
        Entity::REFERENCE1           => 'sometimes|string',
        Entity::REFERENCE2           => 'sometimes|string',
    ];

    protected static $captureRules = [
        Entity::AMOUNT               => 'required|integer',
        Entity::CURRENCY             => 'required|in:INR,USD',
    ];

    protected static $bulkCaptureRules = [
        'payment_ids'                => 'required|array',
        'payment_ids.*'              => 'required|public_id',
    ];

    protected static $refundRules = [
        'amount'                     => 'sometimes|integer',
        'notes'                      => 'sometimes|notes',
        'reverse_all'                => 'sometimes|boolean',
        'reversals'                  => 'sometimes|array',
        'reversals.*.transfer'       => 'required|public_id',
        'reversals.*.amount'         => 'required|integer|min:100',
        'reversals.*.notes'          => 'sometimes|notes',
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
        'fee',
        'contact',
        'email',
        'hold_parameters',
        'customer_id',
        'test_success',
        'account_number',
    ];

    protected function validateAccountNumber(array $input)
    {
        if (isset($input['account_number']) === false)
        {
            return;
        }

        if ($input[Entity::METHOD] !== Method::NETBANKING)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Account Number passed for invalid method: ' . $input[Entity::METHOD]);
        }

        $recurring = $input[Entity::RECURRING] ?? null;

        if ($recurring !== '1')
        {
            throw new Exception\BadRequestValidationFailureException(
                'Account Number passed for non-recurring payment');
        }
    }

    protected function validateEmail(array $input)
    {
        //
        // TODO: To be changed after refactor. No validation required for Bharat qr
        //
        if (Route::currentRouteName() === 'gateway_payment_callback_bharatqr')
        {
            return;
        }

        $allowedPaymentMethods = [
            Payment\Method::AEPS,
            Payment\Method::TRANSFER,
            Payment\Method::BANK_TRANSFER,
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

    protected function validateTestSuccess(array $input)
    {
        $app = App::getFacadeRoot();

        if (isset($input['test_success']) === false)
        {
            return;
        }

        if (($app['rzp.mode'] !== Mode::TEST) or
            ($app['basicauth']->isProxyAuth() === false) or
            (isset($input[Entity::SUBSCRIPTION_ID]) === false) or
            (isset($input[Entity::TOKEN]) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'test_success cannot be sent.',
                'test_success',
                $input['test_success']);
        }
    }

    protected function validateVpa($attribute, $vpa, $parameter)
    {
        $vpaParts = explode('@', $vpa);

        if ((count($vpaParts) !== 2) or
            (ProviderCode::validate($vpaParts[1]) === false) or
            (preg_match('/[^a-z@\.\-0-9]/i', $vpa) === 1))
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

        // No limit on amount for payments made via bank_transfer
        if ($input['method'] === Payment\Method::BANK_TRANSFER)
        {
            return;
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
        //
        // TODO: To be changed after refactor. No validation required for Bharat qr
        //
        if (Route::currentRouteName() === 'gateway_payment_callback_bharatqr')
        {
            return;
        }

        $allowedPaymentMethods = [
            Payment\Method::AEPS,
            Payment\Method::TRANSFER,
            Payment\Method::BANK_TRANSFER,
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

            if ($number->isValidNumberForRegion('IN') === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_ONLY_INDIAN_ALLOWED);
            }
        }
    }

    protected function validateCustomerId($input)
    {
        if (isset($input[Entity::CUSTOMER_ID]) === false)
        {
            //
            // customer_id should always be sent in case of openwallet.
            // It's okay to not send otherwise. Gets handled in the main flow.
            //
            if ((isset($input[Entity::WALLET]) === true) and
                ($input[Entity::WALLET] === Merchant\Methods\Entity::OPENWALLET))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The customer id field is required when wallet is openwallet.',
                    'customer_id',
                    [
                        'wallet' => $input[Entity::WALLET]
                    ]);
            }

            return;
        }

        //
        // Should not send customer_id for a subscription payment,
        // since subscription already has a customer associated.
        //
        if (isset($input[Entity::SUBSCRIPTION_ID]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'customer_id is not required and should not be sent',
                'customer_id',
                [
                    'customer_id'       => $input[Entity::CUSTOMER_ID],
                    'subscription_id'   => $input[Entity::SUBSCRIPTION_ID],
                ]);
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

            $now = Carbon::now()->getTimestamp();

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
