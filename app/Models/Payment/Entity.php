<?php

namespace RZP\Models\Payment;

use Carbon\Carbon;
use Lib\PhoneBook;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use Razorpay\Spine\DataTypes\Dictionary;

use RZP\Models\Emi;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Order;
use RZP\Models\Currency;
use RZP\Models\Customer;
use RZP\Models\Feature;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Models\BankTransfer;
use RZP\Models\Plan\Subscription;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Payment\Processor\Netbanking;

/**
 * @property Subscription\Entity    $subscription
 * @property Invoice\Entity         $invoice
 * @property Merchant\Entity        $merchant
 * @property Card\Entity            $card
 * @property BankTransfer\Entity    $bankTransfer
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const AMOUNT                = 'amount';
    const BASE_AMOUNT           = 'base_amount';
    const AMOUNT_AUTHORIZED     = 'amount_authorized';
    const AMOUNT_REFUNDED       = 'amount_refunded';
    const BASE_AMOUNT_REFUNDED  = 'base_amount_refunded';
    const AMOUNT_TRANSFERRED    = 'amount_transferred';
    const AMOUNT_PAIDOUT        = 'amount_paidout';
    const STATUS                = 'status';
    const TWO_FACTOR_AUTH       = 'two_factor_auth';
    const ORDER_ID              = 'order_id';
    const INVOICE_ID            = 'invoice_id';
    const TRANSFER_ID           = 'transfer_id';
    const INTERNATIONAL         = 'international';
    const METHOD                = 'method';
    const REFUND_STATUS         = 'refund_status';
    const CAPTURED              = 'captured';
    const DISPUTED              = 'disputed';
    const CURRENCY              = 'currency';
    const DESCRIPTION           = 'description';
    const ERROR_CODE            = 'error_code';
    const INTERNAL_ERROR_CODE   = 'internal_error_code';
    const ERROR_DESCRIPTION     = 'error_description';
    const CANCELLATION_REASON   = 'cancellation_reason';
    const CUSTOMER_ID           = 'customer_id';
    const GLOBAL_CUSTOMER_ID    = 'global_customer_id';
    const APP_ID                = 'app_id';
    const APP_TOKEN             = 'app_token';
    const TOKEN                 = 'token';
    const TOKEN_ID              = 'token_id';
    const GLOBAL_TOKEN_ID       = 'global_token_id';
    const VPA                   = 'vpa';
    const ON_HOLD               = 'on_hold';
    const ON_HOLD_UNTIL         = 'on_hold_until';
    const EMAIL                 = 'email';
    const CONTACT               = 'contact';
    const NOTES                 = 'notes';
    const BANK                  = 'bank';
    const CARD                  = 'card';
    const CARD_ID               = 'card_id';
    const WALLET                = 'wallet';
    const EMI_PLAN_ID           = 'emi_plan_id';
    const EMI_DURATION          = 'emi_duration';
    const TRANSACTION_ID        = 'transaction_id';
    const AUTO_CAPTURED         = 'auto_captured';
    const AUTHORIZED_AT         = 'authorized_at';
    const CAPTURED_AT           = 'captured_at';
    const GATEWAY               = 'gateway';
    const TERMINAL_ID           = 'terminal_id';
    const APPROVAL_CODE         = 'approval_code';
    const REFERENCE1            = 'reference1';
    const REFERENCE2            = 'reference2';
    const SIGNED                = 'signed';
    const VERIFIED              = 'verified';
    const GATEWAY_CAPTURED      = 'gateway_captured';
    // This is the bucket for the next verify and not the current verify.
    const VERIFY_BUCKET         = 'verify_bucket';
    const CALLBACK_URL          = 'callback_url';
    const SERVICE_TAX           = 'service_tax';
    const TAX                   = 'tax';
    const OTP_ATTEMPTS          = 'otp_attempts';
    const OTP_COUNT             = 'otp_count';
    const FEE                   = 'fee';
    const RECURRING             = 'recurring';
    const SAVE                  = 'save';
    const LATE_AUTHORIZED       = 'late_authorized';
    const CONVERT_CURRENCY      = 'convert_currency';

    const SUBSCRIPTION_ID       = 'subscription_id';

    const DEFAULT_CURRENCY      = 'INR';

    const ACQUIRER_DATA         = 'acquirer_data';

    // Query params
    const TRANSFERRED           = 'transferred';

    // constants and defaults
    const CURRENCY_LENGTH                   = 3;
    const MIN_PAYMENT_AMOUNT                = 100;
    const PAYMENT_TIMEOUT_DEFAULT_OLD       = 720;      // 12 Mins
    const PAYMENT_TIMEOUT_BILLDESK          = 259200;   // 3 Days
    const PAYMENT_TIMEOUT_NETBANKING        = 4500;     // 75 Mins
    const PAYMENT_TIMEOUT_WALLET            = 4500;     // 75 Mins
    const PAYMENT_TIMEOUT_DEFAULT           = 2700;     // 45 Mins

    const FORMATTED_AMOUNT                  = 'formatted_amount';
    const FORMATTED_CREATED_AT              = 'formatted_created_at';
    const HOSTED_TIME_FORMAT                = 'j M Y';

    protected static $sign      = 'pay';

    protected $entity           = 'payment';

    protected $metadata         = [];

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::METHOD,
        self::EMI_PLAN_ID,
        self::BANK,
        self::WALLET,
        self::CURRENCY,
        self::DESCRIPTION,
        self::VPA,
        self::EMAIL,
        self::CONTACT,
        self::NOTES,
        self::CALLBACK_URL,
        self::FEE,
        self::SERVICE_TAX,
        self::TAX,
        self::RECURRING,
        self::SAVE,
        self::ON_HOLD,
        self::ON_HOLD_UNTIL,
        self::APPROVAL_CODE,
        self::REFERENCE1,
        self::REFERENCE2,
        self::DISPUTED,
    ];

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::METHOD,
        self::AMOUNT,
        self::BASE_AMOUNT,
        self::AMOUNT_AUTHORIZED,
        self::AMOUNT_REFUNDED,
        self::BASE_AMOUNT_REFUNDED,
        self::AMOUNT_TRANSFERRED,
        self::CURRENCY,
        self::AMOUNT_PAIDOUT,
        self::STATUS,
        self::TWO_FACTOR_AUTH,
        self::REFUND_STATUS,
        self::CAPTURED,
        self::DESCRIPTION,
        self::BANK,
        self::WALLET,
        self::EMI_PLAN_ID,
        self::CUSTOMER_ID,
        self::GLOBAL_CUSTOMER_ID,
        self::APP_TOKEN,
        self::TOKEN_ID,
        self::GLOBAL_TOKEN_ID,
        self::VPA,
        self::EMAIL,
        self::CONTACT,
        self::NOTES,
        self::ON_HOLD,
        self::ON_HOLD_UNTIL,
        self::ERROR_CODE,
        self::INTERNAL_ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::CANCELLATION_REASON,
        self::AUTHORIZED_AT,
        self::CAPTURED_AT,
        self::GATEWAY,
        self::CARD_ID,
        self::MERCHANT_ID,
        self::TERMINAL_ID,
        self::APPROVAL_CODE,
        self::REFERENCE1,
        self::REFERENCE2,
        self::ACQUIRER_DATA,
        self::TRANSFER_ID,
        self::TRANSACTION_ID,
        self::AUTO_CAPTURED,
        self::ORDER_ID,
        self::INVOICE_ID,
        self::INTERNATIONAL,
        self::SIGNED,
        self::VERIFIED,
        self::GATEWAY_CAPTURED,
        self::VERIFY_BUCKET,
        self::CALLBACK_URL,
        self::RECURRING,
        self::SAVE,
        self::FEE,
        self::SERVICE_TAX,
        self::TAX,
        self::OTP_ATTEMPTS,
        self::OTP_COUNT,
        self::LATE_AUTHORIZED,
        self::SUBSCRIPTION_ID,
        self::CONVERT_CURRENCY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DISPUTED,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::ORDER_ID,
        self::INVOICE_ID,
        self::INTERNATIONAL,
        self::METHOD,
        self::AMOUNT_REFUNDED,
        self::AMOUNT_TRANSFERRED,
        self::REFUND_STATUS,
        self::CAPTURED,
        self::DESCRIPTION,
        self::CARD_ID,
        self::CARD,
        self::BANK,
        self::WALLET,
        self::VPA,
        self::EMAIL,
        self::CONTACT,
        self::CUSTOMER_ID,
        self::TOKEN_ID,
        self::NOTES,
        self::FEE,
        self::SERVICE_TAX,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::ACQUIRER_DATA,
        // self::SUBSCRIPTION_ID,
        self::CREATED_AT,
        self::TAX,
    ];

    /**
     * Fields exposed to hosted page(invoice, subscriptions etc)
     * where there would mostly be no authentication.
     *
     * @var array
     */
    protected $hosted = [
        self::ID,
        self::STATUS,
        self::METHOD,
        self::AMOUNT,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::ORDER_ID,
        self::INVOICE_ID,
        self::CARD_ID,
        self::CUSTOMER_ID,
        self::TOKEN_ID,
        self::SUBSCRIPTION_ID,
        self::AMOUNT_TRANSFERRED,
        self::ACQUIRER_DATA,
    ];

    protected $appends = [self::PUBLIC_ID, self::CAPTURED, self::ACQUIRER_DATA];

    protected static $modifiers = [
        self::EMAIL,
        self::CONTACT,
        self::BANK,
        'method_based_input',
        'convert_empty_strings_to_null'
    ];

    protected static $generators = [
        'metadata',
    ];

    protected $dates = [
        self::UPDATED_AT,
        self::CREATED_AT,
        self::AUTHORIZED_AT,
        self::CAPTURED_AT
    ];

    protected $hiddenInReport = [self::ACQUIRER_DATA];

    protected $defaults = [
        self::STATUS               => Status::CREATED,
        self::REFUND_STATUS        => RefundStatus::NULL,
        self::NOTES                => [],
        self::DESCRIPTION          => null,
        self::AMOUNT_REFUNDED      => 0,
        self::BASE_AMOUNT_REFUNDED => 0,
        self::AMOUNT_TRANSFERRED   => 0,
        self::AMOUNT_PAIDOUT       => 0,
        self::SIGNED               => 0,
        self::GATEWAY              => null,
        self::VERIFIED             => null,
        self::GATEWAY_CAPTURED     => null,
        self::CAPTURED_AT          => null,
        self::AUTO_CAPTURED        => 0,
        self::ON_HOLD              => 0,
        self::ON_HOLD_UNTIL        => null,
        self::SAVE                 => false,
        self::FEE                  => null,
        self::SERVICE_TAX          => null,
        self::OTP_ATTEMPTS         => null,
        self::OTP_COUNT            => null,
        self::EMI_PLAN_ID          => null,
        self::LATE_AUTHORIZED      => null,
        self::RECURRING            => false,
        self::INTERNATIONAL        => null,
        self::VERIFY_BUCKET        => null,
        self::TERMINAL_ID          => null,
        self::TRANSFER_ID          => null,
        self::DISPUTED             => false,
    ];

    protected $amounts = [
        self::AMOUNT,
        self::BASE_AMOUNT,
        self::BASE_AMOUNT_REFUNDED,
        self::AMOUNT_AUTHORIZED,
        self::AMOUNT_REFUNDED,
        self::AMOUNT_TRANSFERRED,
        self::AMOUNT_PAIDOUT,
        self::FEE,
        self::SERVICE_TAX,
        self::TAX,
    ];

    protected $casts = [
        self::RECURRING            => 'bool',
        self::BASE_AMOUNT          => 'int',
        self::BASE_AMOUNT_REFUNDED => 'int',
        self::AMOUNT_TRANSFERRED   => 'int',
        self::AMOUNT_AUTHORIZED    => 'int',
        self::AMOUNT_REFUNDED      => 'int',
        self::AMOUNT_PAIDOUT       => 'int',
        self::AUTO_CAPTURED        => 'bool',
        self::ON_HOLD              => 'bool',
        self::ON_HOLD_UNTIL        => 'int',
        self::SIGNED               => 'bool',
        self::AMOUNT               => 'int',
        self::FEE                  => 'int',
        self::SERVICE_TAX          => 'int',
        self::TAX                  => 'int',
        self::SAVE                 => 'bool',
        self::INTERNATIONAL        => 'bool',
        self::GATEWAY_CAPTURED     => 'bool',
        self::LATE_AUTHORIZED      => 'bool',
        self::CONVERT_CURRENCY     => 'bool',
        self::DISPUTED             => 'bool',
    ];

    // window in secs, used to fetch payments with same checkout id
    const PAYMENT_WINDOW                = 1800;

    const DUMMY_EMAIL = 'void@razorpay.com';

    const DUMMY_PHONE = '+919999999999';

// --------------------- Modifiers ---------------------------------------------

    protected function modifyEmail(& $input)
    {
        if (empty($input['email']) === true)
        {
            $isEmailOptional = $this->merchant->isEmailOptional();

            if ($isEmailOptional === true)
            {
                $input['email'] = self::DUMMY_EMAIL;
            }
        }
    }

    protected function modifyContact(& $input)
    {
        if (empty($input['contact']) === true)
        {
            $isPhoneOptional = $this->merchant->isPhoneOptional();

            if ($isPhoneOptional === true)
            {
                $input['contact'] = self::DUMMY_PHONE;
            }
        }

        $contact = & $input['contact'];

        if (is_string($contact) === false)
        {
            return null;
        }

        $contact = str_replace(' ', '', $contact);
        $contact = str_replace('-', '', $contact);
        $contact = str_replace('(', '', $contact);
        $contact = str_replace(')', '', $contact);

        // Remove the 0 at the start
        if ((strlen($contact) > 1) and
            ($contact[0] === '0'))
        {
            $contact = substr($contact, 1);
        }

        return $contact;
    }

    protected function modifyMethodBasedInput(& $input)
    {
        if (isset($input['method']) === false)
        {
            return;
        }

        if (in_array($input['method'], [Method::NETBANKING, Method::AEPS]) === false)
        {
            unset($input['bank']);
        }

        if ($input['method'] !== Method::EMI)
        {
            unset($input['emi_duration']);
        }

        if ($input['method'] !== Method::WALLET)
        {
            unset($input['wallet']);
        }

        if ($input['method'] !== Method::UPI)
        {
            unset($input['vpa']);
        }
    }

    protected function modifyConvertEmptyStringsToNull(& $input)
    {
        $array = [
            Entity::CUSTOMER_ID,
            Entity::TOKEN,
            Entity::APP_TOKEN
        ];

        foreach ($array as $key)
        {
            if (empty($input[$key]))
            {
                unset($input[$key]);
            }
        }
    }

    protected function modifyBank(& $input)
    {
        if ((isset($input['method'])) and
            (in_array($input['method'], [Method::NETBANKING, Method::AEPS]) === false))
        {
            unset($input['bank']);
        }
    }

    protected function modifyWallet(& $input)
    {
        if ((isset($input['method'])) and
            ($input['method'] !== Method::WALLET))
        {
            unset($input['wallet']);
        }
    }

// --------------------- Modifiers Ends ----------------------------------------

// --------------------- Generators Ends ---------------------------------------

    protected function generateMetadata(&$input)
    {
        $this->metadata = $input['_'] ?? [];

        // Overriding extra attributes for S2S integration
        $this->metadata['ip'] = $input['ip'] ?? null;
        $this->metadata['user_agent'] = $input['user_agent'] ?? null;

        // We should only set referer if input['referer'] is defined
        // and metadata['referer'] is false because checkout also
        // sends us the referer info and we don't want to override it
        if ((isset($input['referer']) === true) and
            (isset($this->metadata['referer']) === false))
        {
            $this->metadata['referer'] = $input['referer'];
        }
    }

// --------------------- Generators Ends ---------------------------------------

// ----------------------- Setters ---------------------------------------------

    public function setInternational()
    {
        $isInternational = $this->isMethodCardOrEmi() ? $this->card->isInternational() : false;

        $this->setAttribute(self::INTERNATIONAL, $isInternational);
    }

    public function setBaseAmount(int $amount)
    {
        $this->setAttribute(self::BASE_AMOUNT, $amount);
    }

    public function setAmountAuthorized()
    {
        $authAmount = $this->getAttribute(self::AMOUNT);

        $this->setAttribute(self::AMOUNT_AUTHORIZED, $authAmount);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setTwoFactorAuth($status)
    {
        $this->setAttribute(self::TWO_FACTOR_AUTH, $status);
    }

    public function setRefundStatus($status)
    {
        $this->setAttribute(self::REFUND_STATUS, $status);
    }

    public function setAmountRefunded($amount)
    {
        $this->setAttribute(self::AMOUNT_REFUNDED, $amount);
    }

    public function setBaseAmountRefunded($amount)
    {
        $this->setAttribute(self::BASE_AMOUNT_REFUNDED, $amount);
    }

    public function setAmountPaidout(int $amount)
    {
        $this->setAttribute(self::AMOUNT_PAIDOUT, $amount);
    }

    public function setGatewayViaQr(string $gateway)
    {
        $this->setGateway($gateway);
    }

    /**
     * This should be kept as protected so the gateway is only
     * set via associateTerminal function
     */
    protected function setGateway($gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }

    public function setError($errorCode, $errorDesc, $internalErrorCode)
    {
        $this->setAttribute(self::ERROR_CODE, $errorCode);
        $this->setAttribute(self::ERROR_DESCRIPTION, $errorDesc);
        $this->setAttribute(self::INTERNAL_ERROR_CODE, $internalErrorCode);
    }

    public function setInternalErrorCode($internalErrorCode)
    {
        $this->setAttribute(self::INTERNAL_ERROR_CODE, $internalErrorCode);
    }

    public function setCancellationReason($cancellationReason)
    {
        $this->setAttribute(self::CANCELLATION_REASON, $cancellationReason);
    }

    public function setCaptureTimestamp()
    {
        $this->setAttribute(self::CAPTURED_AT, time());
    }

    public function setAuthorizeTimestamp($authTimestamp = null)
    {
        if (is_null($authTimestamp))
        {
            $this->setAttribute(self::AUTHORIZED_AT, time());
        }
        else
        {
            $this->setAttribute(self::AUTHORIZED_AT, $authTimestamp);
        }
    }

    public function setAuthorizedAtNull()
    {
        $this->setAttribute(self::AUTHORIZED_AT, null);
    }

    public function setBank($bank)
    {
        $this->setAttribute(self::BANK, $bank);
    }

    public function setSigned($signed = true)
    {
        $this->setAttribute(self::SIGNED, $signed);
    }

    public function setOnHold(bool $onHold)
    {
        $this->setAttribute(self::ON_HOLD, $onHold);
    }

    public function setOnHoldUntil($holdUntil)
    {
        $this->setAttribute(self::ON_HOLD_UNTIL, $holdUntil);
    }

    public function setAutoCapturedTrue()
    {
        $this->setAttribute(self::AUTO_CAPTURED, true);
    }

    public function setAutoCaptured($autoCaptured)
    {
        $this->setAttribute(self::AUTO_CAPTURED, $autoCaptured);
    }

    public function setVerifyBucket($verifyBucket = 0)
    {
        $this->setAttribute(self::VERIFY_BUCKET, $verifyBucket);
    }

    public function setVerified($verified)
    {
        $this->setAttribute(self::VERIFIED, $verified);
    }

    public function setGatewayCaptured($gatewayCaptured)
    {
        $this->setAttribute(self::GATEWAY_CAPTURED, $gatewayCaptured);
    }

    public function setServiceTax($serviceTax)
    {
        $this->setAttribute(self::SERVICE_TAX, $serviceTax);
    }

    public function setTax($tax)
    {
        $this->setAttribute(self::TAX, $tax);
    }

    public function setFee($fee)
    {
        $this->setAttribute(self::FEE, $fee);
    }

    public function setRecurring($recurring)
    {
        $this->setAttribute(self::RECURRING, $recurring);
    }

    public function setErrorNull()
    {
        $this->setAttribute(self::ERROR_CODE, null);
        $this->setAttribute(self::INTERNAL_ERROR_CODE, null);
        $this->setAttribute(self::ERROR_DESCRIPTION, null);
    }

    public function setEmiPlanId($planId)
    {
        $this->setAttribute(self::EMI_PLAN_ID, $planId);
    }

    public function setOtpAttempts($attempts)
    {
        $this->setAttribute(self::OTP_ATTEMPTS, $attempts);
    }

    public function setOtpCount($count)
    {
        $this->setAttribute(self::OTP_COUNT, $count);
    }

    public function setEmailAttribute($email)
    {
        $this->attributes[self::EMAIL] = mb_strtolower($email);
    }

    public function setSave($save)
    {
        $this->setAttribute(self::SAVE, $save);
    }

    public function incrementOtpAttempts()
    {
        $attempts = $this->getOtpAttemptsAttribute() + 1;

        $this->setOtpAttempts($attempts);
    }

    public function incrementOtpCount()
    {
        $count = $this->getOtpCountAttribute() + 1;

        $this->setOtpCount($count);
    }

    public function setLateAuthorized($lateAuthorized)
    {
        $this->setAttribute(self::LATE_AUTHORIZED, $lateAuthorized);
    }

    public function setConvertCurrency($convert)
    {
        $this->setAttribute(self::CONVERT_CURRENCY, $convert);
    }

    public function setMetadataKey($key, $value)
    {
        $this->metadata[$key] = $value;
    }

    public function setMetadata($input)
    {
        $this->metadata = $input['_'] ?? null;
    }

    public function setDisputed($disputed)
    {
        $this->setAttribute(self::DISPUTED, $disputed);
    }

    public function decrementAmountTransferred(int $amount)
    {
        $this->decrement(self::AMOUNT_TRANSFERRED, $amount);
    }

// ----------------------- Setters Ends-----------------------------------------

// ----------------------- Mutator ---------------------------------------------

    protected function setAmountAttribute($amount)
    {
        $this->attributes[self::AMOUNT] = (int) $amount;
    }

    protected function setContactAttribute($contact)
    {
        if ($contact === null)
        {
            $this->attributes[self::CONTACT] = null;

            return;
        }

        $number = new PhoneBook($contact, true);

        if ($number->isValidNumber() === true)
        {
            $this->attributes[self::CONTACT] = $number->format();
        }
        else
        {
            $normalizedNumber = $number->getRawInput();

            // Hack for tracing new invalid numbers
            // to get the stats
            $app = \App::getFacadeRoot();

            $app['trace']->info(
                TraceCode::PAYMENT_INVALID_CONTACT_NUMBER,
                ['number' => $contact, 'normalized_number' => $normalizedNumber]);

            $this->attributes[self::CONTACT] = $normalizedNumber;
        }
    }

    protected function setCancellationReasonAttribute(string $reason)
    {
        $reason = mb_strtolower($reason);

        $this->attributes[self::CANCELLATION_REASON] = mb_substr($reason, 0, 255);
    }

// ----------------------- Mutator Ends ----------------------------------------

// ----------------------- Accessor --------------------------------------------

    // TODO: Return a phonebook instance (like carbon) instead of string
    protected function getContactAttribute()
    {
        $contact = $this->attributes[self::CONTACT];

        if ($contact === null)
        {
            return null;
        }

        $phoneBook = new PhoneBook($contact, true);

        return (string) $phoneBook;
    }

    protected function getVerifiedAttribute()
    {
        $verified = $this->attributes[self::VERIFIED];

        if ($verified !== null)
        {
            $verified = (int) $verified;
        }

        return $verified;
    }

    protected function getCapturedAttribute()
    {
        return ($this->attributes[self::CAPTURED_AT] !== null);
    }

    protected function getAcquirerDataAttribute()
    {
        $acquirerData = [];

        switch ($this->getAttribute(self::METHOD))
        {
            case Method::CARD:

                $acquirerData = [];
                break;

            case Method::NETBANKING:

                $acquirerData = [
                    'bank_transaction_id' => $this->getAttribute(self::REFERENCE1)
                ];
                break;

            case Method::WALLET:

                $acquirerData = [];
                break;

            case Method::UPI:

                $acquirerData = [];
                break;
        }

        return (new Dictionary($acquirerData));
    }

    protected function getOtpAttemptsAttribute()
    {
        $attempts = $this->attributes[self::OTP_ATTEMPTS];

        if ($attempts !== null)
        {
            $attempts = (int) $attempts;
        }

        return $attempts;
    }

    protected function getOtpCountAttribute()
    {
        $count = $this->attributes[self::OTP_COUNT];

        if ($count !== null)
        {
            $count = (int) $count;
        }

        return $count;
    }

    public function getMetadata($key = null, $default = null)
    {
        if ($key === null)
        {
            return $this->metadata;
        }

        return $this->metadata[$key] ?? $default;
    }

    public function getRefundStatus()
    {
        return $this->getAttribute(self::REFUND_STATUS);
    }

    public function getOnHold()
    {
        return $this->getAttribute(self::ON_HOLD);
    }

    public function getOnHoldUntil()
    {
        return $this->getAttribute(self::ON_HOLD_UNTIL);
    }

// ----------------------- Accessor Ends ---------------------------------------

    public function isCreated()
    {
        return ($this->getAttribute(self::STATUS) === Status::CREATED);
    }

    /**
     * A payment is considered just created for 5
     * minutes since creation
     * @return bool
     */
    public function justCreated()
    {
        $currentTime = time();

        $secondsSinceCreated = $currentTime - $this->getAttribute(self::CREATED_AT);

        return (bool) ($secondsSinceCreated <= (Processor\Processor::ASYNC_PAYMENT_TIMEOUT));
    }

    public function isAeps()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::AEPS);
    }

    public function isAuthorized()
    {
        return ($this->getAttribute(self::STATUS) === Status::AUTHORIZED);
    }

    public function isCreatedOrAuthorized()
    {
        return ($this->isCreated() or $this->isAuthorized());
    }

    public function hasBeenAuthorized()
    {
        return ($this->isAttributeNotNull(self::AUTHORIZED_AT));
    }

    public function hasNotBeenAuthorized()
    {
        return ($this->isAttributeNull(self::AUTHORIZED_AT));
    }

    public function hasTransaction()
    {
        return ($this->isAttributeNotNull(self::TRANSACTION_ID));
    }

    public function hasCard()
    {
        return ($this->isAttributeNotNull(self::CARD_ID));
    }

    public function hasOrder()
    {
        return ($this->isAttributeNotNull(self::ORDER_ID));
    }

    public function hasSubscription()
    {
        return ($this->isAttributeNotNull(self::SUBSCRIPTION_ID));
    }

    public function hasInvoice()
    {
        return ($this->isAttributeNotNull(self::INVOICE_ID));
    }

    public function hasTransfer()
    {
        return ($this->isAttributeNotNull(self::TRANSFER_ID));
    }

    public function hasMetadata($key = null)
    {
        if ($key === null)
        {
            return false;
        }

        return (isset($this->metadata[$key]) === true);
    }

    public function isCaptured()
    {
        return ($this->getAttribute(self::STATUS) === Status::CAPTURED);
    }

    public function isPartiallyOrFullyRefunded()
    {
        return ! ($this->getAttribute(self::REFUND_STATUS) === RefundStatus::NULL);
    }

    public function isFullyRefunded()
    {
        return ($this->getAttribute(self::REFUND_STATUS) === RefundStatus::FULL);
    }

    public function isPartiallyRefunded()
    {
        return ($this->getAttribute(self::REFUND_STATUS) === RefundStatus::PARTIAL);
    }

    public function isTransferred()
    {
        return (($this->getAttribute(self::AMOUNT_TRANSFERRED) > 0) === true);
    }

    public function isFailed()
    {
        return ($this->getAttribute(self::STATUS) === Status::FAILED);
    }

    public function isLateAuthorized()
    {
        return ($this->getAttribute(self::LATE_AUTHORIZED) === true);
    }

    protected function isStatus($status)
    {
        return ($this->getAttribute(self::STATUS) === $status);
    }

    public function isStatusCreatedOrFailed()
    {
        return (($this->isFailed()) or
                ($this->isCreated()));
    }

    public function hasBeenCaptured()
    {
        return ($this->getAttribute(self::CAPTURED_AT) !== null);
    }

    public function isGatewayCaptured()
    {
        return ($this->getAttribute(self::GATEWAY_CAPTURED) === true);
    }

    public function isCard()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::CARD);
    }

    public function isNetbanking()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::NETBANKING);
    }

    public function isWallet()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::WALLET);
    }

    public function isEmi()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::EMI);
    }

    public function isUpi()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::UPI);
    }

    public function isTransfer()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::TRANSFER);
    }

    public function isBankTransfer()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::BANK_TRANSFER);
    }

    public function isGateway($gateway)
    {
        return ($this->getAttribute(self::GATEWAY) === $gateway);
    }

    public function isMethod($method)
    {
        return ($this->getAttribute(self::METHOD) === $method);
    }

    public function isMethodCardOrEmi()
    {
        return (($this->isMethod(Payment\Method::CARD)) or
                ($this->isMethod(Payment\Method::EMI)));
    }

    public function isSigned()
    {
        return ($this->getAttribute(self::SIGNED) === true);
    }

    public function isOnHold()
    {
        return $this->getOnHold();
    }

    public function isInternational()
    {
        // return $this->getAttribute(self::INTERNATIONAL);
        return $this->card->isInternational();
    }

    public function isOpenWalletPayment()
    {
        return ($this->getWallet() === Processor\Wallet::OPENWALLET);
    }

    public function isCustomerMailAbsent(): bool
    {
        $email = $this->getEmail();

        return ((empty($email) === true) or ($email === self::DUMMY_EMAIL));
    }

    /**
     * Checks if card should be saved depending on if the payment is emi or
     * the payment was a card payment and has an associated order on which an offer
     * was applied
     *
     * @return bool
     */
    public function shouldSaveCard(): bool
    {
        if (($this->isEmi() === true) or ($this->hasCardOffer() === true))
        {
            return true;
        }

        return false;
    }

    public function hasCardOffer(): bool
    {
        if (($this->isCard() === true) and ($this->hasOrder() === true))
        {
            $order = $this->order;

            if ($order->hasOffer() === true)
            {
                return true;
            }
        }

        return false;
    }

    public function isDisputed(): bool
    {
        return $this->getAttribute(self::DISPUTED);
    }

// ----------------------- Getters ---------------------------------------------

    public function getTransferId()
    {
        return $this->getAttribute(self::TRANSFER_ID);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getBaseAmount()
    {
        return $this->getAttribute(self::BASE_AMOUNT);
    }

    public function getAmountRefunded()
    {
        return $this->getAttribute(self::AMOUNT_REFUNDED);
    }

    public function getBaseAmountRefunded()
    {
        return $this->getAttribute(self::BASE_AMOUNT_REFUNDED);
    }

    public function getAmountUnrefunded()
    {
        return $this->getAmount() - $this->getAmountRefunded();
    }

    public function getBaseAmountUnrefunded()
    {
        return $this->getBaseAmount() - $this->getBaseAmountRefunded();
    }

    public function getAmountTransferred()
    {
        return $this->getAttribute(self::AMOUNT_TRANSFERRED);
    }

    public function getAmountUntransferred()
    {
        return $this->getAmount() - $this->getAmountTransferred();
    }

    /**
     * Gets adjusted amount with respect to customer fee bearer merchants.
     * This amount is compared against the requested capture amount by merchant
     * and a few other places.
     *
     * @return int
     */
    public function getAdjustedAmountWrtCustFeeBearer(): int
    {
        $amount = $this->getAmount();

        if ($this->merchant->isFeeBearerCustomer() === true)
        {
            $amount -= $this->getFee();
        }

        return $amount;
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getAmountPaidout()
    {
        return $this->getAttribute(self::AMOUNT_PAIDOUT);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getBank()
    {
        return $this->getAttribute(self::BANK);
    }

    public function getCallbackUrl()
    {
        return $this->getAttribute(self::CALLBACK_URL);
    }

    public function getCaptureTimestamp()
    {
        return $this->getAttribute(self::CAPTURED_AT);
    }

    public function getAuthorizeTimestamp()
    {
        return $this->getAttribute(self::AUTHORIZED_AT);
    }

    public function getUpdatedAt()
    {
        return $this->getAttribute(self::UPDATED_AT);
    }

    public function getBankName()
    {
        $bankId = $this->getBank();

        if ($bankId !== null)
        {
            return Netbanking::getName($bankId);
        }
    }

    public function getWallet()
    {
        return $this->getAttribute(self::WALLET);
    }

    public function getFormattedCard()
    {
        return $this->card->getFormatted();
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getVpa()
    {
        return $this->getAttribute(self::VPA);
    }

    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getAutoCaptured()
    {
        return $this->getAttribute(self::AUTO_CAPTURED);
    }

    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    public function getInternalErrorCode()
    {
        return $this->getAttribute(self::INTERNAL_ERROR_CODE);
    }

    public function getErrorDescription()
    {
        return $this->getAttribute(self::ERROR_DESCRIPTION);
    }

    public function getFee()
    {
        return $this->getAttribute(self::FEE);
    }

    public function getServiceTax()
    {
        return $this->getAttribute(self::SERVICE_TAX);
    }

    public function getTax()
    {
        return $this->getAttribute(self::TAX);
    }

    public function getTokenId()
    {
        return $this->getAttribute(self::TOKEN_ID);
    }

    public function getGlobalCustomerId()
    {
        return $this->getAttribute(self::GLOBAL_CUSTOMER_ID);
    }

    public function getGlobalTokenId()
    {
        return $this->getAttribute(self::GLOBAL_TOKEN_ID);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getDaysSinceAuthorized()
    {
        $now = Carbon::now()->getTimestamp();

        $at = $this->getAuthorizeTimestamp();
        $diff = $now - $at;

        return floor($diff / (60 * 24 * 24));
    }

    public function getEmiPlanId()
    {
        return $this->getAttribute(self::EMI_PLAN_ID);
    }

    public function getSave()
    {
        return $this->getAttribute(self::SAVE);
    }

    public function isRecurring()
    {
        return $this->getAttribute(self::RECURRING);
    }

    public function getCardId()
    {
        return $this->getAttribute(self::CARD_ID);
    }

    public function getTwoFactorAuth()
    {
        return $this->getAttribute(self::TWO_FACTOR_AUTH);
    }
    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getOtpCount()
    {
        return $this->getAttribute(self::OTP_COUNT);
    }

    public function getOtpAttempts()
    {
        return $this->getAttribute(self::OTP_ATTEMPTS);
    }

    public function getVerifyBucket()
    {
        return $this->getAttribute(self::VERIFY_BUCKET);
    }

    public function getTerminalId()
    {
        return $this->getAttribute(self::TERMINAL_ID);
    }

    public function isSecondRecurring()
    {
        $app = \App::getFacadeRoot();

        $token = $this->getGlobalOrLocalTokenEntity();

        if ($this->isRecurring() === false)
        {
            return false;
        }

        $reference = $this->getReferenceForGatewayToken();

        $existingGatewayTokens = $app['repo']->gateway_token->findByTokenAndReference($token, $reference);

        return ($existingGatewayTokens->count() === 1);
    }

    public function getConvertCurrency()
    {
        return $this->getAttribute(self::CONVERT_CURRENCY);
    }

    public function getGatewayCaptured()
    {
        return $this->getAttribute(self::GATEWAY_CAPTURED);
    }

    /**
     * Get the rate at which currency conversion was applied to
     * the payment amount
     */
    public function getCurrencyConversionRate()
    {
        $baseAmount = $this->getBaseAmount();

        $paymentAmount = $this->getAmount();

        return $baseAmount / $paymentAmount;
    }

    /**
     * This function returns the current payment method
     * and a detail string for that particular method
     * as a 2 length array. The array is numeric, instead
     * of associative because the detail key would be dependent
     * on the method itself otherwise (card.number, wallet.name, bank.name)
     * for eg.
     *
     * As such, we send a numeric array with the following details:
     *
     * ['card', $formattedCardNumber] (Just last 4 digits)
     * ['netbanking', $bankName] (Readable name for the bank)
     * ['wallet', $walletName] (Readable wallet name like PayTM)
     * @return array Payment Method Details
     */
    public function getMethodWithDetail()
    {
        $method = Method::formatted($this->getMethod());

        switch($this->getMethod())
        {
            case Method::CARD:
                return [$method, $this->getFormattedCard()];
            case Method::EMI:
                return [$method, $this->getFormattedCard()];
            case Method::NETBANKING:
                return [$method, $this->getBankName()];
            case Method::WALLET:
                return [$method, ucfirst($this->getWallet())];
            case Method::UPI:
                return [$method, $this->getVpa()];
            case Method::AEPS:
                return [$method, ''];
            case Method::BANK_TRANSFER:
                return [$method, ''];
        }
    }

    public function getErrorDetails()
    {
        return [
            self::ERROR_CODE => $this->getAttribute(self::ERROR_CODE),
            self::ERROR_DESCRIPTION => $this->getAttribute(self::ERROR_DESCRIPTION),
        ];
    }

    public function getNetbankingReferenceId()
    {
        $netbankingRefId = null;

        if ($this->isNetbanking() === true)
        {
            $netbankingRefId = $this->getAttribute(self::REFERENCE1);
        }

        return $netbankingRefId;
    }

    /**
     * This is a heuristic method that tries to find
     * an order id the notes section
     * As of now, order_id is the first field inside notes
     * that ends with `_order_id`
     * We will shift to a standard field called `merchant_order_id`
     * as our ecommerce plugins are migrated
     * @return String order_id for the payment
     */
    public function getOrderId()
    {
        $notes = $this->getNotes();

        // Shortcut for direct order_id being set
        if (isset($notes['order_id']))
        {
            return $notes['order_id'];
        }

        foreach ($notes as $key => $value)
        {
            $orderIdSuffix = '_order_id';
            $ix = -1 * strlen($orderIdSuffix); // index from back
            if (substr($key, $ix) === $orderIdSuffix)
            {
                return $value;
            }
        }
        return false;
    }

    public function getApiOrderId()
    {
        return $this->getAttribute(self::ORDER_ID);
    }

    public function getInvoiceId()
    {
        return $this->getAttribute(self::INVOICE_ID);
    }

    public function getSubscriptionId()
    {
        return $this->getAttribute(self::SUBSCRIPTION_ID);
    }

    public function getGlobalOrLocalTokenEntity()
    {
        $token = null;

        if ($this->getTokenId() !== null)
        {
            $token = $this->getAttribute('localToken');
        }
        else if ($this->getGlobalTokenId() !== null)
        {
            $token = $this->getAttribute('globalToken');
        }

        return $token;
    }

    public function getReferenceForGatewayToken()
    {
        if ($this->hasSubscription() === true)
        {
            $reference = $this->getSubscriptionId();
        }
        else
        {
            //
            // TODO: Get reference from checkout input.
            // This is required for charge at will local
            // recurring. Merchant passes this for every
            // new subscription.
            //
            // For subsequent charges, the reference won't
            // come from checkout input. It has to come from
            // the payment or something like that. So, for the
            // 2FA txns also, we should get from payment itself.
            // This requires us to store reference at a payment level,
            // like how we store subscription_id.
            //
            // For now, just returning back null.
            // Also, for Zoho, accept null, but for any
            // other merchant, throw an exception if it's null.
            // It should probably go in validateRecurringInput though.
            //

            $reference = null;
        }

        return $reference;
    }

    public function setPublicOrderIdAttribute(array & $array)
    {
        if (isset($array[self::ORDER_ID]))
        {
            $array[self::ORDER_ID] = Order\Entity::getSignedId($array[self::ORDER_ID]);
        }
    }

    public function setPublicInvoiceIdAttribute(array & $array)
    {
        if (isset($array[self::INVOICE_ID]))
        {
            $array[self::INVOICE_ID] = Invoice\Entity::getSignedId($array[self::INVOICE_ID]);
        }
    }

    public function getPublicOrderId()
    {
        if ($this->hasOrder() === true)
        {
            return Order\Entity::getSignedId($this->getApiOrderId());
        }
    }

    public function setPublicCardIdAttribute(array & $array)
    {
        if (isset($array[self::CARD_ID]))
        {
            $array[self::CARD_ID] =
                Card\Entity::getIdPrefix() . $this->getAttribute(self::CARD_ID);
        }
    }

    public function setPublicCustomerIdAttribute(array & $array)
    {
        if (isset($array[self::CUSTOMER_ID]))
        {
            $customerId = $this->getAttribute(self::CUSTOMER_ID);

            $array[self::CUSTOMER_ID] = Customer\Entity::getSignedId($customerId);
        }
        else
        {
            unset($array[self::CUSTOMER_ID]);
        }
    }

    public function setPublicTokenIdAttribute(array & $array)
    {
        if (isset($array[self::TOKEN_ID]))
        {
            $tokenId = $this->getAttribute(self::TOKEN_ID);

            $array[self::TOKEN_ID] = Customer\Token\Entity::getSignedId($tokenId);
        }
        else
        {
            unset($array[self::TOKEN_ID]);
        }
    }

    public function setPublicSubscriptionIdAttribute(array & $array)
    {
        $subscriptionId = $this->getSubscriptionId();

        if (empty($subscriptionId) === false)
        {
            $array[self::SUBSCRIPTION_ID] = Subscription\Entity::getSignedId($subscriptionId);
        }
        else
        {
            unset($array[self::SUBSCRIPTION_ID]);
        }
    }

    public function setPublicAmountTransferredAttribute(array & $attributes)
    {
        //
        // The `amount_transferred` attributes is only needed for
        // for dashboard and should be hidden in private API
        // requests
        //
        $app = \App::getFacadeRoot();

        if ($app['basicauth']->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::AMOUNT_TRANSFERRED]);
        }
    }

    public function setPublicAcquirerDataAttribute(array & $array)
    {
        // Adding test merchants PolicyBazaar, DSP Blackrock merchant ID's
        $merchantIds = ['10000000000000', '6ZJzxyLFWrGs74', '7LAuMvKMcy7s0f', '7thBRSDflu7NHL'];

        $currentMerchantId = $this->getMerchantId();

        // We are hardcoding the merchant ids for now.
        // Will move this to feature flag.
        if (in_array($currentMerchantId, $merchantIds, true) === false)
        {
            unset($array[self::ACQUIRER_DATA]);
        }
    }

    public function associateTerminal($terminal)
    {
        if ($terminal === null)
        {
            throw new Exception\RuntimeException(
                'Terminal should not be null',
                ['payment' => $this->toArrayAdmin()]);
        }

        $this->terminal()->associate($terminal);

        $this->setGateway($terminal->getGateway());

        $this->setRelation('terminal', $terminal);
    }

// ----------------------- Getters Ends-----------------------------------------

    public function toArrayWithCard()
    {
        $data = $this->getAttributes();

        $card = $this->card()->first();

        if ($card === null)
        {
            throw new Exception\LogicException(
                'Associated card not found for the current payment entity',
                null,
                [
                    'payment_id'    => $this->getId(),
                ]);
        }

        $cardData = $card->getAttributes();

        $data['card'] = $cardData;

        return $data;
    }

    public function toArrayDashboard()
    {
        $data = $this->toArray();

        $data[self::ID] = $this->getPublicId();

        if ($this->isMethodCardOrEmi())
        {
            $card = $this->card()->firstOrFail();

            $network = $card->getNetwork();

            $data['network'] = $network;
        }

        return $data;
    }

    public function toArrayReport()
    {
        $data = parent::toArrayReport();

        $tax = $data[self::TAX];

        // Add tax key at the end to maintain order of columns in the report
        unset($data[self::TAX]);

        unset($data[self::CUSTOMER_ID]);
        unset($data[self::TOKEN_ID]);

        $data[self::NOTES] = $this->getNotesJson();

        $data['card_type'] = null;
        $data['card_network'] = null;
        $data['invoice_id'] = null;

        if ($this->isMethodCardOrEmi())
        {
            $data['card_type'] = $this->card->getType();
            $data['card_network'] = $this->card->getNetwork();
        }

        if ($this->getInvoiceId() !== null)
        {
            $data['invoice_id'] = $this->getInvoiceId();
        }

        $data[self::TAX] = $tax;

        return $data;
    }

    public function toArrayGateway()
    {
        $data = $this->toArray();

        if (($this->isCard()) and
            ($this->getConvertCurrency() === true))
        {
            $data['amount'] = $this->getBaseAmount();
            $data['currency'] = Currency\Currency::INR;
            $data['amount_refunded'] = $this->getBaseAmountRefunded();
        }

        return $data;
    }

    public function toArrayHosted()
    {
        $data = parent::toArrayHosted();

        $data[self::FORMATTED_AMOUNT] = $this->getFormattedAmount();

        $createdAt = Carbon::createFromTimestamp($this->getCreatedAt(), Timezone::IST);

        $data[self::FORMATTED_CREATED_AT] = $createdAt->format(self::HOSTED_TIME_FORMAT);

        return $data;
    }

// --------------- Relation to other entities ----------------------------------

    public function card()
    {
        return $this->belongsTo('RZP\Models\Card\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function terminal()
    {
        return $this->belongsTo('RZP\Models\Terminal\Entity')->withTrashed();
    }

    public function refunds()
    {
        return $this->hasMany('RZP\Models\Payment\Refund\Entity');
    }

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function hdfc()
    {
        return $this->hasOne('hdfc', 'trackid', 'id');
    }

    public function order()
    {
        return $this->belongsTo('RZP\Models\Order\Entity');
    }

    public function subscription()
    {
        return $this->belongsTo('RZP\Models\Plan\Subscription\Entity');
    }

    public function invoice()
    {
        return $this->belongsTo('RZP\Models\Invoice\Entity');
    }

    public function analytics()
    {
        return $this->hasOne('RZP\Models\Payment\Analytics\Entity');
    }

    public function bankTransfer()
    {
        return $this->hasOne('RZP\Models\BankTransfer\Entity');
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function globalCustomer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity', self::GLOBAL_CUSTOMER_ID);
    }

    public function localToken()
    {
        return $this->belongsTo('RZP\Models\Customer\Token\Entity', self::TOKEN_ID)->withTrashed();
    }

    public function globalToken()
    {
        return $this->belongsTo('RZP\Models\Customer\Token\Entity', self::GLOBAL_TOKEN_ID)->withTrashed();
    }

    public function app()
    {
        return $this->belongsTo('RZP\Models\Customer\AppToken\Entity', self::APP_TOKEN);
    }

    public function emiPlan()
    {
        return $this->belongsTo('RZP\Models\Emi\Entity');
    }

    public function transfers()
    {
        return $this->morphMany('RZP\Models\Transfer\Entity', 'source');
    }

    public function netbanking()
    {
        return $this->hasOne('RZP\Gateway\Netbanking\Base\Entity');
    }

    // using hasOne here as we need only the first billdesk entity, actual relation can be one-to-many
    public function billdesk()
    {
        return $this->hasOne('RZP\Gateway\Billdesk\Entity');
    }

    public function transfer()
    {
        return $this->belongsTo('RZP\Models\Transfer\Entity', self::TRANSFER_ID);
    }

    public function disputes()
    {
        return $this->hasMany(\RZP\Models\Dispute\Entity::class);
    }

// --------------- Relation to other entity section ends -----------------------

    public function refundAmount($amount, $baseAmount)
    {
        if ((is_int($amount) === false) or
            (is_int($baseAmount) === false))
        {
            throw new Exception\InvalidArgumentException(
                'amount should be an integer ' . $amount);
        }

        $amount = (int) $amount;

        $baseAmount = (int) $baseAmount;

        $amountUnrefunded = $this->getAmountUnrefunded();

        if ($amount < $amountUnrefunded)
        {
            $this->setRefundStatus(RefundStatus::PARTIAL);
        }
        else if ($amount === $amountUnrefunded)
        {
            $this->setRefundStatus(RefundStatus::FULL);

            $this->setStatus(Payment\Status::REFUNDED);
        }
        else
        {
            throw new Exception\LogicException(
                'Refund amount should be less than or equal to amount not refunded yet',
                null,
                [
                    'amount'            => $amount,
                    'amount_unrefunded' => $amountUnrefunded,
                    'payment_id'        => $this->getId(),
                ]);
        }

        $amountRefunded = $this->getAmountRefunded() + $amount;

        $baseAmountRefunded = $this->getBaseAmountRefunded() + $baseAmount;

        $this->setAttribute(self::AMOUNT_REFUNDED, $amountRefunded);

        $this->setAttribute(self::BASE_AMOUNT_REFUNDED, $baseAmountRefunded);
    }

    public function transferAmount(int $amount)
    {
        $amountUntransferred = $this->getAmountUntransferred();

        if ($amount > $amountUntransferred)
        {
            throw new Exception\LogicException(
                'Transfer amount should be less than or equal to amount not transferred yet',
                null,
                [
                    'amount'                => $amount,
                    'amount_untransferred'  => $amountUntransferred,
                    'payment_id'            => $this->getId(),
                ]);
        }

        $amountTransferred = $this->getAmountTransferred() + $amount;

        $this->setAttribute(self::AMOUNT_TRANSFERRED, $amountTransferred);
    }

    /**
     * Updates Payment amount_paidout field
     *
     * @param  int $amount
     *
     * @throws Exception\LogicException
     */
    public function payoutAmount(int $amount)
    {
        $amountPaidout = $this->getAmountPaidout() + $amount;

        $paymentAmount = $this->getAmount();

        if ($amountPaidout > $paymentAmount)
        {
            throw new Exception\LogicException(
                'Payment payout: Payout total greater than payment amount',
                null,
                [
                    'payout'            => $amount,
                    'payment_amount'    => $paymentAmount,
                    'payment_id'        => $this->getId(),
                ]
            );
        }

        $this->setAmountPaidout($amountPaidout);
    }

    public function toArrayTraceRelevant()
    {
        $fields = array(
            self::ID,
            self::MERCHANT_ID,
            self::CARD_ID,
            self::STATUS,
            self::AMOUNT,
            self::AUTO_CAPTURED,
            self::ERROR_CODE,
            self::GATEWAY);

        $relevantData = array_intersect_key($this->attributes, array_flip($fields));

        return $relevantData;
    }

    public function shouldTimeout(int $now)
    {
        $timeoutPeriod = $this->getTimeoutWindow();

        $diff = $now - $this->getCreatedAt();

        return ($diff >= $timeoutPeriod);
    }

// --------------------- Query scopes section begin ----------------------------

    public function scopeStatus($query, $status)
    {
        return $query->where(Payment\Entity::STATUS, '=', $status);
    }

    public function scopeStatusSuccess($query)
    {
        return $query->whereNotIn(Entity::STATUS, [Status::FAILED, Status::CREATED]);
    }

// --------------------- Query scopes section ends -----------------------------

    public function resetOtpAttempts()
    {
        $this->setOtpAttempts(null);
    }

    /**
     * List of all features based on various conditions
     */
    public function getPricingFeatures()
    {
        $features = [];

        if ($this->isRecurring() === true)
        {
            $features[] = Pricing\Feature::RECURRING;
        }

        if (($this->isEmi() === true) and
            ($this->merchant->getEmiSubvention() === Emi\Subvention::MERCHANT))
        {
            $features[] = Pricing\Feature::EMI;
        }

        return $features;
    }

    protected function getTimeoutWindow()
    {
        $gateway = $this->getGateway();

        // default is 9 mins
        $timeWindow = self::PAYMENT_TIMEOUT_DEFAULT_OLD;

        if ($this->merchant->isFeatureEnabled(Feature\Constants::CREATED_FLOW) === true)
        {
            // for new flow, default is 30 mins
            $timeWindow = self::PAYMENT_TIMEOUT_DEFAULT;

            if ($gateway === Payment\Gateway::BILLDESK)
            {
                $timeWindow = self::PAYMENT_TIMEOUT_BILLDESK;
            }
            else if ($this->isNetbanking() === true)
            {
                // for direct netbanking 1 hour is good enough
                $timeWindow = self::PAYMENT_TIMEOUT_NETBANKING;
            }
            else if ($this->isWallet() === true)
            {
                // for direct netbanking 1 hour is good enough
                $timeWindow = self::PAYMENT_TIMEOUT_WALLET;
            }
        }

        $autoRefundDelay = $this->merchant->getAutoRefundDelay();

        return min($timeWindow, $autoRefundDelay);
    }

    public function shouldRunFraudChecks()
    {
        if ($this->isCard() === true)
        {
            if (($this->card->isInternational() === true) or
                ($this->card->isAmex() === true))
            {
                return true;
            }
        }

        return false;
    }
}
