<?php

namespace RZP\Models\Payment;

use App;
use Route;
use Cache;
use Carbon\Carbon;
use Lib\PhoneBook;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Vpa;
use Razorpay\IFSC\IFSC;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Payment\Processor\UpiTrait;

class Validator extends Base\Validator
{

    use UpiTrait;

    protected $trace;

    public function __construct($entity = null)
    {
        parent::__construct($entity);

        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];
    }

    /**
     * recurring_token epoch constrains :
     * min : Sat Jan  1 05:30:00 IST 2000 => 946684800
     * max : 17 August 292278994 => 9223372036854775807 - max for 64 bit signed int
     **/

    protected static $createRules = [
        'amount'                        => 'required|integer',
        'currency'                      => 'required|string|size:3|custom',
        'method'                        => 'required|string|custom',
        'vpa'                           => 'sometimes_if:method,upi|string|filled|max:100|custom',
        'aadhaar'                       => 'required_if:method,aeps|array',
        'aadhaar.number'                => 'required_if:method,aeps|size:12|string',
        'aadhaar.fingerprint'           => 'required_if:method,aeps|max:999|string',
        'aadhaar.session_key'           => 'sometimes_if:method,aeps|size:344|string',
        'aadhaar.hmac'                  => 'sometimes_if:method,aeps|size:64|string',
        'aadhaar.cert_expiry'           => 'sometimes_if:method,aeps|size:8|string',
        'card'                          => 'sometimes',
        'bank'                          => 'required_if:method,netbanking,aeps,emandate|string|between:4,6',
        'wallet'                        => 'required_if:method,wallet|custom',
        'emi_duration'                  => 'required_if:method,emi|integer|in:3,6,9,12,18,24',
        'description'                   => 'sometimes|nullable|string|max:255|utf8',
        'email'                         => 'sometimes|nullable|email',
        'upi.vpa'                       => 'sometimes_if:method,upi|filled|string',
        'upi.type'                      => 'sometimes_if:method,upi|filled|string',
        'upi.flow'                      => 'sometimes_if:method,upi|filled|string',
        'upi.start_date'                => 'sometimes_if:method,upi|filled|epoch',
        'upi.end_date'                  => 'sometimes_if:method,upi|filled|epoch',
        'upi_provider'                  => 'sometimes_if:method,upi|filled|string|custom',
        'contact'                       => 'sometimes|nullable|contact_syntax',
        'billing_address'               => 'sometimes',
        'signature'                     => 'sometimes|nullable|string',
        'notes'                         => 'sometimes|notes',
        'notes.merchant_order_id'       => 'required_with:signature',
        'callback_url'                  => 'sometimes|url',
        'order_id'                      => 'sometimes|filled',
        'customer_id'                   => 'sometimes|public_id|filled',
        'subscription_id'               => 'sometimes|public_id',
        'receiver'                      => 'sometimes_if:method,card,upi,bank_transfer|associative_array|filled|custom',
        'receiver.type'                 => 'required_with:receiver|filled|string',
        'receiver.id'                   => 'required_with:receiver|filled|public_id',
        'payment_link_id'               => 'sometimes|public_id|size:17',
        'app_token'                     => 'sometimes',
        'token'                         => 'sometimes',
        'save'                          => 'sometimes|in:0,1',
        'recurring'                     => 'sometimes|in:1,preferred',
        'fee'                           => 'sometimes|filled|integer|max:50000000',
        Entity::TAX                     => 'sometimes|filled|integer|max:50000000',
        'on_hold'                       => 'sometimes_if:method,transfer|boolean',
        'on_hold_until'                 => 'sometimes_if:method,transfer|nullable|epoch',
        'ip'                            => 'sometimes|ip',
        'referer'                       => 'sometimes|string|max:2083',
        'user_agent'                    => 'sometimes|string',
        '_'                             => 'sometimes|array',
        'test_success'                  => 'sometimes|boolean',
        'subscription_card_change'      => 'sometimes|boolean',
        'upi'                           => 'sometimes_if:method,upi|array',
        'upi.expiry_time'               => 'sometimes_if:method,upi|integer|between:5,5760|filled',
        'auth_type'                     => 'sometimes_if:method,emandate,card,emi,nach|string|max:20|filled',
        'preferred_auth'                => 'sometimes_if:method,card,emi|array|max:3|filled',
        'bank_account'                  => 'sometimes_if:method,emandate|associative_array|filled',
        'bank_account.account_number'   => 'required_with:bank_account|filled|alpha_num|between:5,20',
        'bank_account.ifsc'             => 'required_with:bank_account|filled|alpha_num|size:11',
        'bank_account.name'             => 'required_with:bank_account|filled|alpha_space_num|between:4,120',
        'recurring_token'               => 'sometimes_if:method,emandate,upi|associative_array|filled',
        'recurring_token.max_amount'    => 'sometimes_if:method,emandate,upi|filled|integer|min:500',
        'recurring_token.expire_by'     => 'sometimes_if:method,emandate,upi|filled|epoch:946684800,9223372036854775807',
        'offer_id'                      => 'filled|public_id|size:20',
        'provider'                      => 'required_if:method,cardless_emi,paylater|string',
        'ott'                           => 'sometimes_if:method,cardless_emi,paylater|string',
        'payment_id'                    => 'sometimes_if:method,cardless_emi',
        'application'                   => 'sometimes|filled|string|in:google_pay',
        'device'                        => 'sometimes',
        'dcc_currency'                  => 'sometimes|string|max:3',
        'dcc_amount'                    => 'sometimes|integer',
        'currency_request_id'           => 'sometimes|string'
    ];

    protected static $editAcquirerRules = [
        Entity::VPA                  => 'sometimes|string|max:100',
        Entity::REFERENCE1           => 'sometimes|nullable|string',
        Entity::REFERENCE2           => 'sometimes|nullable|string',
        Entity::REFERENCE16          => 'sometimes|nullable|string',
    ];

    protected static $editCpsResponseRules = [
        Entity::AUTH_TYPE               => 'sometimes|nullable|string',
        Entity::AUTHENTICATION_GATEWAY  => 'sometimes|nullable|string',
    ];

    protected static $editRules = [
        Entity::NOTES                => 'sometimes|notes',
    ];

    protected static $captureRules = [
        Entity::AMOUNT               => 'required|integer',
        Entity::CURRENCY             => 'required|custom',
    ];

    protected static $bulkCaptureRules = [
        'payment_ids'                => 'required|sequential_array',
        'payment_ids.*'              => 'required|public_id',
    ];

    protected static $bulkGatewayCaptureRules = [
        'payment_ids'                => 'sometimes|sequential_array',
        'payment_ids.*'              => 'required|public_id',
    ];

    protected static $verifyRules = [
        'bucket'                     => 'sometimes|sequential_array',
        'bucket.*'                   => 'sometimes|integer|max:7',
        'gateway'                    => 'sometimes|string|max:50'
    ];

    protected static $verifyAllRules = [
        'gateway'                    => 'sometimes|string|max:50',
        'delay'                      => 'sometimes|integer|max:2592000',
        'count'                      => 'sometimes|integer|max:10000'
    ];

    protected static $bulkVerifyRules = [
        'payment_ids'                => 'required|sequential_array',
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
        'transfers'                        => 'required|array',
        'transfers.*.customer'             => 'sometimes|public_id',
        'transfers.*.account'              => 'sometimes|public_id',
        'transfers.*.amount'               => 'required|integer|min:100',
        'transfers.*.currency'             => 'required|string|size:3',
        'transfers.*.notes'                => 'sometimes|notes',
        'transfers.*.linked_account_notes' => 'sometimes|array',
        'transfers.*.on_hold'              => 'sometimes|boolean',
        'transfers.*.on_hold_until'        => 'sometimes|epoch',
    ];

    protected static $getFlowsRules = [
        'callback'                  => 'sometimes', // JSONP
        'iin'                       => 'required|numeric|digits:6',
        '_'                         => 'sometimes|array',
        'order_id'                  => 'sometimes|filled',
        'currency'                  => 'sometimes|string',
        'amount'                    => 'sometimes|integer'
    ];

    protected static $postFlowsRules = [
        'card_number'        => 'sometimes|numeric|luhn|digits_between:12,19',
        'iin'                => 'sometimes|numeric|digits:6',
        'currency'           => 'sometimes|string',
        'amount'             => 'sometimes|integer'
    ];

    protected static $pspAmountLimit = [
        'upi'       => 2000000,
    ];

    protected static $validateVpaRules = [
        'vpa' => 'required|string|filled|max:100|custom',
    ];

    protected static $validateEntityRules = [
        'entity'   => 'required|string|in:vpa',
        'value'    => 'required',
    ];

    protected static $callbackUrlValidationRules = [
        'callback_url' => 'sometimes|url|custom',
    ];

    protected static $acknowledgeRules = [
        Entity::NOTES => 'sometimes|notes',
    ];

    protected static $paymentOnholdBulkUpdateRules = [
        'payment_ids'    => 'required|sequential_array',
        'payment_ids.*'  => 'required|public_id',
        'on_hold'        => 'required|boolean',
    ];

    protected static $paymentCardMigrateRules = [
        'limit'                             => 'sometimes|integer',
        'migrate_missing_fingerprint_cards' => 'sometimes|boolean',
        'time_window'                       => 'sometimes|integer',
    ];

    protected static $mandateUpdateRules = [
        'start_time'  => 'sometimes',
        'max_amount'  => 'sometimes',
        'token_id'    => 'sometimes',
        'is_mandate'  => 'sometimes'
    ];

    protected static $createValidators = [
        'card_key',
        'amount',
        'bank',
        'fee',
        'contact',
        'email',
        'hold_parameters',
        'customer_id',
        'test_success',
        'upi_expiry_time',
        // Ideally, we should be using custom. But
        // due to dot notation, we cannot use it.
        'ifsc',
        'order_id',
        // Ideally, we should be using custom. But
        // due to dot notation, we cannot use it.
        'token_max_amount',
        'token_expire_by',
        'auth_type',
        'preferred_auth',
        'payment_provider',
        'upi_block',
    ];

    protected static $minAmountCheckRules = [
        Entity::AMOUNT => 'required|integer|min_amount'
    ];

    protected function validateIfsc(array $input)
    {
        if (isset($input[Entity::BANK_ACCOUNT][Entity::IFSC]) === false)
        {
            return;
        }

        $ifsc = $input[Entity::BANK_ACCOUNT][Entity::IFSC];

        if (IFSC::validate($ifsc) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid IFSC Code in Bank Account');
        }
    }

    protected function validateTokenMaxAmount(array $input)
    {
        if (isset($input[Entity::RECURRING_TOKEN][Entity::MAX_AMOUNT]) === false)
        {
            return;
        }

        $tokenMaxAmount = $input[Entity::RECURRING_TOKEN][Entity::MAX_AMOUNT];

        if ($tokenMaxAmount > Token\Entity::DEFAULT_MAX_AMOUNT)
        {
            throw new Exception\BadRequestValidationFailureException(
                'token_max_amount exceeds maximum amount allowed.',
                'token_max_amount',
                ['token_max_amount' => $tokenMaxAmount]);
        }
    }

    protected function validateTokenExpireBy(array $input)
    {
        if (isset($input[Entity::RECURRING_TOKEN][Entity::EXPIRE_BY]) === false)
        {
            return;
        }

        $tokenExpireBy = $input[Entity::RECURRING_TOKEN][Entity::EXPIRE_BY];

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        if ($tokenExpireBy <= $currentTime)
        {
            throw new Exception\BadRequestValidationFailureException(
                'recurring_token.expire_by should be greater than the current time',
                null,
                [
                    'expire_by'         => $tokenExpireBy,
                    'current_time'      => $currentTime,
                    'payment_id'        => $this->entity->getId(),
                ]);
        }
    }

    protected function validateAuthType(array $input)
    {
        if (isset($input[Entity::AUTH_TYPE]) === false)
        {
            return;
        }

        AuthType::validateAuthType($input[Entity::AUTH_TYPE], $input[Entity::METHOD]);

        $merchant = $this->entity->merchant;

        AuthType::validateFeatureBasedAuth($merchant, $input[Entity::AUTH_TYPE]);
    }

    protected function validatePreferredAuth(array $input)
    {
        if (isset($input[Entity::PREFERRED_AUTH]) === false)
        {
            return;
        }

        $uniqueAuthentications = array_unique($input[Entity::PREFERRED_AUTH]);

        foreach ($uniqueAuthentications as $authentication)
        {
            AuthType::validateAuthType($authentication, $input[Entity::METHOD]);
        }
    }

    protected function validateUpiExpiryTime(array $input)
    {
        if (isset($input['upi']['expiry_time']) === false)
        {
            return;
        }

        $app = App::getFacadeRoot();

        if ($app['basicauth']->isPrivateAuth() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'upi is/are not required and should not be sent');
        }
    }

    protected function validateOrderId(array $input)
    {
        $merchant = $this->entity->merchant;

        $feature = Feature\Constants::ORDER_ID_MANDATORY;

        if (($merchant->isFeatureEnabled($feature) === true) and
            (isset($input[Payment\Entity::ORDER_ID]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED_MISSING_ORDER_ID);
        }
    }

    protected function validateEmail(array $input)
    {
        // The payments received on these receivers are push based. We can't really know the
        // email of person making a payment
        if ((isset($input[Entity::RECEIVER]) === true) and
            (empty($input[Entity::RECEIVER]['type']) === false))
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

    protected function validateUpiProvider($attribute, $upiProvider)
    {
        if (UpiProvider::isValidOmnichannelProvider($upiProvider) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid upi provider given: ' . $upiProvider);
        }
    }

    protected function validateReceiver($attribute, $receiver)
    {
        if (Receiver::areTypesValid([$receiver['type']]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid receiver type: ' . $receiver['type']);
        }

        $requiredLength = 17;

        if ($receiver['type'] === Receiver::VPA)
        {
            $requiredLength = 18;
        }

        if (strlen($receiver['id']) !== $requiredLength)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The receiver.id must be ' . $requiredLength . ' characters for receiver.type.' . $receiver['type']);
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

    protected function validateVpa($attribute, $vpa)
    {
        (new Vpa\Validator)->validateAddress($attribute, $vpa);

        $vpaParts = explode('@', $vpa);

        if (ProviderCode::validate($vpaParts[1]) === false)
        {
            // Invalid VPA
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
                $attribute,
                [
                    'vpa' => $vpa
                ]);
        }
    }

    protected function validateCallbackUrl($attribute, $callbackUrl)
    {
        if (empty($callbackUrl) === true)
        {
            return;
        }

        $app = App::getFacadeRoot();

        $merchant = $app['basicauth']->getMerchant();

        if ($merchant->isFeatureEnabled(Feature\Constants::CALLBACK_URL_VALIDATION) === true)

        {
            $merchantUrlArray = explode(".", parse_url($merchant->getWebsite(), PHP_URL_HOST));
            $callbackUrlArray = explode(".", parse_url($callbackUrl, PHP_URL_HOST));

            // case where https://example.com
            if (count($merchantUrlArray) === 2)
            {
                array_unshift($merchantUrlArray, "");
            }

            // case where https://example.com
            if (count($callbackUrlArray) === 2)
            {
                array_unshift($callbackUrlArray, "");
            }

            if ((empty($callbackUrlArray) === true) or
                ($merchantUrlArray[1] !== $callbackUrlArray[1]) or
                ($merchantUrlArray[2] !== $callbackUrlArray[2]))
            {
                $traceData = [
                    'merchant_website' => $merchant->getWebsite(),
                    'callback_url'     => $callbackUrl,
                ];

                throw new Exception\BadRequestValidationFailureException(
                    'Invalid callback url',
                    'callback_url',
                    $traceData
                );
            }
        }
    }

    protected function validateWallet($attribute, $value)
    {
        Wallet::validateExists($value);
    }

    protected function validatePaymentProvider(array $input)
    {
        switch ($input['method'])
        {
            case Payment\Method::CARDLESS_EMI:
                if (CardlessEmi::exists($input['provider']) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Provider is not supported for cardless emi',
                        'provider',
                        $input['provider']);
                }
                break;

            case Payment\Method::PAYLATER:
                if (Payment\Processor\PayLater::exists($input['provider']) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Provider is not supported for Pay Later',
                        'provider',
                        $input['provider']);
                }
                break;

            default:
                return ;
        }
    }

    protected function validateCardKey(array $input)
    {
        if (($input['method'] !== Payment\Method::CARD) and
            ($input['method'] !== Payment\Method::EMI))
        {
            return;
        }

        /*
         * Checking if the card payment is of Google Pay. If it is of Google Pay then there are no
         * card details.
         */
        if (((isset($input['application'])) === true) and
            ($input['application'] === 'google_pay'))
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
        $amount = (int) $input['amount'];

        $method = $input['method'];

        $receiverType = null;

        // No limit on amount for payments of method defined in Method::$methodsWithoutAmountValidation
        if (in_array($method, Method::$methodsWithoutAmountValidation, true) === true)
        {
            return;
        }

        if (isset($input[Entity::RECEIVER]) === true)
        {
            $receiverType = $input[Entity::RECEIVER]['type'];
        }

        // The payments received on these receivers are push based. We can't really control after
        // we already received a payments. So removing amount validation check on it
        if (empty($receiverType) === false)
        {
            return;
        }

        if (($method === Payment\Method::WALLET) and
            ($input['wallet'] === Wallet::AIRTELMONEY) and
            ($amount < 1000))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_10_MIN_AMOUNT,
                'amount');
        }

        if (($method === Payment\Method::EMI) and ($amount < 200000))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT_FOR_EMI,
                'amount');
        }

        if (($method !== Payment\Method::EMANDATE) and
            ($method !== Payment\Method::NACH) and
            ($this->checkUpiRecurring($input) === false))
        {
            $this->validateInputValues('min_amount_check', $input);
        }

        if ($method === Payment\Method::UPI)
        {
            if ($amount > 10000000)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Amount for UPI payment cannot be greater than ₹100000.00');
            }

            if ($this->isFlowIntent($input))
            {
                return;
            }

            if (isset($input['vpa']) === true)
            {
                $vpa = $input['vpa'];

                $handle = substr($vpa, strpos($vpa, '@') + 1);

                if ((isset(self::$pspAmountLimit[$handle]) === true) and
                    ($amount > self::$pspAmountLimit[$handle]))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Maximum amount for UPI payment can be Rs ' . (self::$pspAmountLimit[$handle] / 100));
                }
            }
        }

        $maxAmountAllowed = $this->entity->merchant->getMaxPaymentAmount();

        if ($amount > $maxAmountAllowed)
        {
            $this->trace->count(Metric::PAYMENT_CREATION_AMOUNT_VALIDATION_FAILURE_COUNT, [
                'business_type' => $this->entity->merchant->merchantDetail->getBusinessType() ?? "",
            ]);

            throw new Exception\BadRequestValidationFailureException(
                'Amount exceeds maximum amount allowed.',
                'amount',
                ['amount' => $amount]);
        }
    }

    protected function checkUpiRecurring($input)
    {
        $method = $input['method'];

        if (($method === Payment\Method::UPI) and
            (isset($input['recurring']) === true) and
            ($input['recurring']) === '1')
        {
            return true;
        }

        return false;
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
        /*
            No card details when the payment is for Google Pay for cards.
        */
        if ((isset($input['application']) === true) and ($input['application'] === 'google_pay'))
        {
            return;
        }

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

        if (($mode === Mode::LIVE) and
            ($this->entity->transaction->isSettled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_BEFORE_SETTLEMENT);
        }
    }

    protected function validateBank($input)
    {
        // @todo: Add validation for UPI method as well for tpv
        if (($input['method'] !== Payment\Method::NETBANKING) and
            ($input['method'] !== Payment\Method::EMANDATE))
        {
            return;
        }

        if (isset($input['bank']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_NOT_PROVIDED);
        }

        $supported = false;
        $bank = $input['bank'];

        $method = $input['method'];

        switch ($method)
        {
            case Payment\Method::EMANDATE:
                $supported = Payment\Gateway::isSupportedEmandateBank($bank);
                break;

            case Payment\Method::UPI:
                $supported = Payment\Processor\Upi::isSupportedUpiBank($bank);
                break;

            case Payment\Method::NETBANKING:
                $supported = Payment\Processor\Netbanking::isSupportedBank($bank);
                break;
        }

        //
        // The bank is validated for emandate in `validateInitialRecurringForEmandate`
        //
        if ($supported === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_INVALID_BANK_CODE,
                'bank');
        }
    }

    protected function validateContact($input)
    {
        // The payments received on these receivers are push based. We can't really know the
        // contact of person making a payment
        if ((isset($input[Entity::RECEIVER]) === true) and
            (empty($input[Entity::RECEIVER]['type']) === false))
        {
            return;
        }

        $allowedPaymentMethods = [
            Payment\Method::AEPS,
            Payment\Method::TRANSFER,
            Payment\Method::BANK_TRANSFER,
        ];

        if ((in_array($input[Entity::METHOD], $allowedPaymentMethods, true) === false) and
            ((empty($input[Entity::CONTACT]) === true) and (empty($input[Entity::UPI_PROVIDER]) === true)))
        {
            throw new Exception\BadRequestValidationFailureException(
                'The contact field is required.', Entity::CONTACT);
        }

        if ($input['method'] === Payment\Method::WALLET)
        {
            if (in_array($input['wallet'], Wallet::$indianContactWallets, true) === true)
            {
                $this->validateIndianContact($input['contact']);
            }
        }

        if (in_array($input['method'], [Payment\Method::CARDLESS_EMI, Payment\Method::PAYLATER], true) === true)
        {
            $this->validateIndianContact($input['contact']);
        }
    }

    protected function validateIndianContact($contact)
    {
        $number = new PhoneBook($contact, true);

        if ($number->isValidNumberForRegion('IN') === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_ONLY_INDIAN_ALLOWED);
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

            if ($merchant->isFeeBearerCustomerOrDynamic() === false)
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

    protected function validateCurrency($attribute, $currency)
    {
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

        $this->captureAmountValidate($payment, $amount);

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
        if ($amount !== $payment->getGatewayAmount())
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
        if ($currency !== $payment->getGatewayCurrency())
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
        if ($payment->hasBeenCaptured() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED,
                null,
                [
                    'payment_id'    => $payment->getId(),
                    'status'        => $payment->getStatus(),
                    'captured_at'   => $payment->getCapturedAt(),
                ]);
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

    /**
     * Validates if a payment can be marked as acknowledged. Only captured payments can be acknowledged.
     * Note: A payment that has been captured and then refunded can be marked as acknowledged;
     *       but a payment authorized and then refunded cannot be marked as acknowledged.
     *
     * @throws Exception\BadRequestException
     */
    public function acknowledgeValidate()
    {
        $payment = $this->entity;

        if ($payment->hasBeenCaptured() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED,
                [
                    Entity::ID              => $payment->getId(),
                    Entity::STATUS          => $payment->getStatus(),
                    Entity::ACKNOWLEDGED_AT => $payment->getAcknowledgedAt(),
                ]);
        }

        if ($payment->isAcknowledged() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_ACKNOWLEDGED,
                [
                    Entity::ID              => $payment->getId(),
                    Entity::STATUS          => $payment->getStatus(),
                    Entity::ACKNOWLEDGED_AT => $payment->getAcknowledgedAt(),
                ]);
        }
    }

    public function validateGatewayForForceAuth()
    {
        $payment = $this->entity;

        $gateway = $payment->getGateway();

        $allowedGateways = Payment\Gateway::FORCE_AUTHORIZE_GATEWAYS;

        if (in_array($gateway, $allowedGateways, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot force authorize on this gateway',
                'gateway',
                $gateway);
        }
    }

    protected function validateUpiBlock($input)
    {
        if (isset($input['upi']['vpa']) === true)
        {
            $this->validateVpa('upi.vpa', $input['upi']['vpa']);
        }
        if ((isset($input['upi']['type']) === true) and
            ($input['upi']['type'] === 'otm'))
        {
            if ((isset($input['upi']['start_date']) === false) or
                (isset($input['upi']['end_date']) === false))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'UPI OTM payments require start_date and end_date',
                    'upi',
                    ['input'=> $input]
                    );
            }

            if ($input['upi']['start_date'] > $input['upi']['end_date'])
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid start_date for UPI OTM Payment, start date cannot be greater than end date',
                    'upi.start_date',
                    ['input'=> $input]
                );
            }
        }
    }
}
