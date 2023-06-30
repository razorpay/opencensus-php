<?php

namespace RZP\Models\Customer\Token;

use App;
use Crypt;
use Carbon\Carbon;
use RZP\Base\BuilderEx;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\CardMandate;
use RZP\Models\UpiMandate;
use RZP\Constants\Entity as E;
use RZP\Models\PaymentsUpi\Vpa;
use RZP\Models\Merchant\Account;
use RZP\Models\Address;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\SubscriptionRegistration\SubscriptionRegistrationConstants;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\PaperMandate\FileUploader;
use RZP\Models\PaperMandate\PaperMandateUpload\Entity as PaperMandateUploadEntity;
use RZP\Models\SubscriptionRegistration\Entity as SubscriptionRegistrationEntity;
use RZP\Models\Customer;
use RZP\Constants\Mode;

/**
 * @property Vpa\Entity  $vpa
 * @property Card\Entity $card
 * @property Customer\Entity $customer
 * @property Terminal\Entity $terminal
 * @property Merchant\Entity $merchant
 * @property CardMandate\Entity $cardMandate
 */
class Entity extends Base\PublicEntity
{
    use SoftDeletes, NotesTrait;

    const MERCHANT_ID               = 'merchant_id';
    const CUSTOMER_ID               = 'customer_id';
    const TERMINAL_ID               = 'terminal_id';
    const TOKEN                     = 'token';
    const METHOD                    = 'method';
    const CARD_ID                   = 'card_id';
    const CARD_MANDATE_ID           = 'card_mandate_id';
    const VPA_ID                    = 'vpa_id';
    const CARD                      = 'card';
    const VPA                       = 'vpa';
    const BANK                      = 'bank';
    const BANK_DETAILS              = 'bank_details';
    const WALLET                    = 'wallet';
    const ACCOUNT_NUMBER            = 'account_number';
    const ACCOUNT_IDS               = 'account_ids';
    const ACCOUNT_TYPE              = 'account_type';
    const GATEWAY_TOKEN             = 'gateway_token';
    const GATEWAY_TOKEN2            = 'gateway_token2';
    const RECURRING                 = 'recurring';
    const MAX_AMOUNT                = 'max_amount';
    const AUTH_TYPE                 = 'auth_type';
    const RECURRING_STATUS          = 'recurring_status';
    const RECURRING_FAILURE_REASON  = 'recurring_failure_reason';
    const RECURRING_DETAILS         = 'recurring_details';
    const BENEFICIARY_NAME          = 'beneficiary_name';
    const IFSC                      = 'ifsc';
    const AADHAAR_NUMBER            = 'aadhaar_number';
    const AADHAAR_VID               = 'aadhaar_vid';
    const CONFIRMED_AT              = 'confirmed_at';
    const START_TIME                = 'start_time';
    const REJECTED_AT               = 'rejected_at';
    const INITIATED_AT              = 'initiated_at';
    const ACKNOWLEDGED_AT           = 'acknowledged_at';
    const USED_COUNT                = 'used_count';
    const USED_AT                   = 'used_at';
    const EXPIRED_AT                = 'expired_at';
    const CREATED_AT                = 'created_at';
    const UPDATED_AT                = 'updated_at';
    const DELETED_AT                = 'deleted_at';
    const MRN                       = 'mrn';
    const DEBIT_TYPE                = 'debit_type';
    const FREQUENCY                 = 'frequency';
    const ENTITY_ID                 = 'entity_id';
    const ENTITY_TYPE               = 'entity_type';
    const STATUS                    = 'status';
    const NOTES                     = 'notes';

    const CUSTOMER                  = 'customer';
    const INTERNAL_ERROR_CODE       = 'internal_error_code';
    const ERROR_DESCRIPTION         = 'error_description';
    const ERROR_CODE                = 'error_code';
    //
    // merchant id index for live and test db
    //
    const TOKENS_MERCHANT_ID_INDEX  = 'tokens_merchant_id_index';

    //
    // These values goes in the account_type field
    //
    const ACCOUNT_TYPE_SAVINGS     = 'savings';
    const ACCOUNT_TYPE_CURRENT     = 'current';
    const ACCOUNT_TYPE_CASH_CREDIT = 'cc';
    const ACCOUNT_TYPE_SB_NRE      = 'nre';
    const ACCOUNT_TYPE_SB_NRO      = 'nro';

    //
    // These keys will be under recurring_details
    // Having recurring prepended to status and
    // failure_reason is redundant.
    //
    const RECURRING_STATUS_SHORT            = 'status';
    const RECURRING_FAILURE_REASON_SHORT    = 'failure_reason';

    /**
     * We use this to set the max amount of the token entity.
     * By default, we have chosen ₹ 1,00,000 for emandate with aadhaar authtype and
     * ₹ 10,00,000 for emandate with Netbanking and DebitCard authtype
     */
    const AADHAAR_EMANDATE_MAX_AMOUNT_LIMIT = 10000000;
    const CARD_MAX_AMOUNT_LIMIT             = 1500000;
    const EMANDATE_MAX_AMOUNT_LIMIT         = 100000000;
    const DEFAULT_MAX_AMOUNT                = 9999900;
    const CARD_RECURRING_MAX_AMOUNT         = 100000000;
    const LEAST_MAX_AMOUNT_LIMIT            = 0;

    // Mapping of Max Recurring Amount with method and country code.
    const RECURRING_MAX_AMOUNT             = [
        'MY' => [
            Payment\Method::CARD => 3000000
        ],
        'IN' => [
            Payment\Method::CARD => 100000000
        ]
    ];
    /**
     * We use this to set the number of years after which the
     * emandate token will get expired and cannot be used
     * anymore. Ideally, the merchant sends the expiry time.
     * In case he does not, we add 10 years to the current time.
     */
    const DEFAULT_EXPIRY_YEARS  = 10;

    const DCC_ENABLED           = 'dcc_enabled';

    const BILLING_ADDRESS       = 'billing_address';

    const CARDS                 = 'cards';

    /*
     * authentication data key in the input
     */
    const AUTHENTICATION_DATA   = 'authentication_data';
    const AUTHENTICATION        = 'authentication';

    /*
     * Specifies source In case of push token provisioning
     */
    const SOURCE = 'source';
    const BUSINESS = 'business';
    const MERCHANT = 'merchant';
    const ISSUER = 'issuer';

    /**
     * Signifies whether user consent has been taken for a saved card for tokenisation
     * This will be used by checkout to identify consent taken saved cards
     * This will be deprecated after Dec 31st, 2021 once we stop saving cards on razorpay
     */
    public const CONSENT_TAKEN = 'consent_taken';
    public const COMPLIANT_WITH_TOKENISATION_GUIDELINES = 'compliant_with_tokenisation_guidelines';

    public const BULK_TOKENISATION_INPUT_LIMIT = 100000;
    public const MERCHANT_ASYNC_TOKENISATION_QUERY_LIMIT = 100000;

    /** @var int Number of tokens to fetch for global merchant in one SQL query. */
    public const GLOBAL_MERCHANT_ASYNC_TOKENISATION_QUERY_LIMIT = 1000;

    public const PUSH_PROVISIONING_FETCH_MERCHANTS_WITH_TOKEN_LIMIT = 50;

    /*
     * service provider tokens attributes
     */
    const ID            = 'id';
    const ENTITY        = 'entity';
    const PROVIDER_DATA = 'provider_data';
    const INTEROPERABLE = 'interoperable';
    const STATUS_REASON = 'status_reason';

    /*
     * provider data attributes
     */
    const TOKEN_NUMBER           = 'token_number';
    const CRYPTOGRAM_VALUE       = 'cryptogram_value';
    const CVV                    = 'cvv';
    const TOKEN_REFERENCE_NUMBER = 'token_reference_number';
    const TOKEN_IIN              = 'token_iin';
    const TOKEN_EXPIRY_MONTH     = 'token_expiry_month';
    const TOKEN_EXPIRY_YEAR      = 'token_expiry_year';

    /*
    * status field values
    */
    const ACTIVE              = 'active';
    const DEACTIVATED         = 'deactivated';
    const EXPIRED             = 'expired';
    const DEACTIVATED_BY_BANK = 'deactivated_by_bank';

    const PAYMENT_ACCOUNT_REFERENCE = 'payment_account_reference';
    const SERVICE_PROVIDER_TOKENS   = 'service_provider_tokens';

    protected static $sign      = 'token';

    protected $entity           = 'token';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::BANK,
        self::WALLET,
        self::METHOD,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_TYPE,
        self::BENEFICIARY_NAME,
        self::IFSC,
        self::TOKEN,
        self::GATEWAY_TOKEN,
        self::GATEWAY_TOKEN2,
        self::RECURRING,
        self::AUTH_TYPE,
        self::AADHAAR_NUMBER,
        self::AADHAAR_VID,
        self::MAX_AMOUNT,
        self::EXPIRED_AT,
        self::START_TIME,
        self::VPA_ID,
        self::DEBIT_TYPE,
        self::FREQUENCY,
        self::STATUS,
        self::NOTES,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::BANK,
        self::WALLET,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_TYPE,
        self::BENEFICIARY_NAME,
        self::IFSC,
        self::TOKEN,
        self::METHOD,
        self::CARD_ID,
        self::VPA_ID,
        self::CARD,
        self::VPA,
        self::CUSTOMER_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::TERMINAL_ID,
        self::GATEWAY_TOKEN,
        self::GATEWAY_TOKEN2,
        self::RECURRING,
        self::RECURRING_DETAILS,
        self::RECURRING_FAILURE_REASON,
        self::RECURRING_STATUS,
        self::MAX_AMOUNT,
        self::AUTH_TYPE,
        self::AADHAAR_NUMBER,
        self::AADHAAR_VID,
        self::CARD_MANDATE_ID,
        self::USED_COUNT,
        self::CONFIRMED_AT,
        self::REJECTED_AT,
        self::INITIATED_AT,
        self::ACKNOWLEDGED_AT,
        self::USED_AT,
        self::EXPIRED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::START_TIME,
        self::DEBIT_TYPE,
        self::FREQUENCY,
        self::CONSENT_TAKEN,
        self::COMPLIANT_WITH_TOKENISATION_GUIDELINES,
        self::STATUS,
        self::NOTES,
        self::ERROR_DESCRIPTION,
        self::INTERNAL_ERROR_CODE,
        self::SOURCE
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::TOKEN,
        self::BANK,
        self::WALLET,
        self::METHOD,
        self::CARD,
        self::VPA,
        self::RECURRING,
        self::RECURRING_DETAILS,
        self::AUTH_TYPE,
        self::MRN,
        self::USED_AT,
        self::CREATED_AT,
        self::CUSTOMER,
        self::BANK_DETAILS,
        self::MAX_AMOUNT,
        self::EXPIRED_AT,
        self::START_TIME,
        self::CONSENT_TAKEN,
        self::COMPLIANT_WITH_TOKENISATION_GUIDELINES,
        self::STATUS,
        self::NOTES,
        self::ERROR_DESCRIPTION,
        self::SOURCE
        // TODO: uncomment when we start accepting token as input
        // self::MAX_AMOUNT,
    ];

    protected $defaults = [
        self::WALLET                    => null,
        self::CARD_ID                   => null,
        self::VPA_ID                    => null,
        self::ACCOUNT_NUMBER            => null,
        self::ACCOUNT_TYPE              => null,
        self::IFSC                      => null,
        self::BENEFICIARY_NAME          => null,
        self::BANK                      => null,
        self::GATEWAY_TOKEN2            => null,
        self::RECURRING                 => false,
        self::RECURRING_FAILURE_REASON  => null,
        self::RECURRING_STATUS          => null,
        self::MAX_AMOUNT                => null,
        self::AUTH_TYPE                 => null,
        self::AADHAAR_NUMBER            => null,
        self::AADHAAR_VID               => null,
        self::USED_AT                   => null,
        self::USED_COUNT                => 0,
        self::EXPIRED_AT                => null,
        self::START_TIME                => null,
        self::ENTITY_ID                 => null,
        self::ENTITY_TYPE               => null,
        self::STATUS                    => null,
        self::NOTES                     => null,
        self::INTERNAL_ERROR_CODE       => null,
        self::ERROR_DESCRIPTION         => null,
        self::SOURCE                    => Constants::MERCHANT
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CARD,
        self::VPA,
        self::MRN,
        self::BANK_DETAILS,
        // TODO: Remove this after deciding on how to expose
        self::MAX_AMOUNT,
        self::EXPIRED_AT,
        self::START_TIME,
        self::STATUS,
    ];

    protected $appends = [
        self::RECURRING_DETAILS,
    ];

    protected $casts = [
        self::RECURRING     => 'bool',
        self::MAX_AMOUNT    => 'int',
        self::USED_COUNT    => 'int',
        self::EXPIRED_AT    => 'int',
    ];

    protected static $generators = [
        self::TOKEN,
    ];

    protected static $modifiers = [
        self::IFSC,
    ];

    public static $networkTokenUnsetAttributes = [
        self::TOKEN,
        self::BANK,
        self::WALLET,
        self::RECURRING,
        self::RECURRING_DETAILS,
        self::AUTH_TYPE,
        self::MRN,
        self::DCC_ENABLED,
        self::BILLING_ADDRESS,
        self::USED_AT,
        self::RECURRING_DETAILS,
        self::ERROR_DESCRIPTION,
        self::INTERNAL_ERROR_CODE
    ];

    public static $cryptogramDataServiceProviderTokensUnsetAttributes = [
        self::ID,
        self::ENTITY,
        self::STATUS,
        self::INTEROPERABLE,
    ];

    public static $cryptogramDataProviderDataUnsetAttributes = [
        self::TOKEN_REFERENCE_NUMBER,
        self::PAYMENT_ACCOUNT_REFERENCE,
        self::TOKEN_IIN,
    ];

    public static $providerDataUnsetAttributes = [
        self::TOKEN_NUMBER,
        self::CRYPTOGRAM_VALUE,
    ];

    public static $providerDataUnsetNullAttributes = [
        self::TOKEN_IIN,
        self::TOKEN_EXPIRY_MONTH,
        self::TOKEN_EXPIRY_YEAR,
    ];

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function card()
    {
        return $this->belongsTo('RZP\Models\Card\Entity');
    }

    public function cardMandate()
    {
        return $this->belongsTo('RZP\Models\CardMandate\Entity');
    }

    public function vpa()
    {
        return $this->belongsTo('RZP\Models\PaymentsUpi\Vpa\Entity');
    }

    public function terminal()
    {
        return $this->belongsTo('RZP\Models\Terminal\Entity');
    }

    public function nachPayments()
    {
        return $this->hasMany('RZP\Models\Payment\Entity')
                    ->where(Payment\Entity::METHOD, Payment\Method::NACH)
                    ->limit(5);
    }

    public function upiMandate()
    {
        return $this->hasOne(UpiMandate\Entity::class);
    }

    public function hasCard()
    {
        return $this->isAttributeNotNull(self::CARD_ID);
    }

    public function hasVpa()
    {
        return $this->isAttributeNotNull(self::VPA_ID);
    }

    public function getBank()
    {
        return $this->getAttribute(self::BANK);
    }

    public function getWallet()
    {
        return $this->getAttribute(self::WALLET);
    }

    public function getAccountNumber()
    {
        return $this->getAttribute(self::ACCOUNT_NUMBER);
    }

    public function getAccountType()
    {
        return $this->getAttribute(self::ACCOUNT_TYPE);
    }

    public function getBeneficiaryName()
    {
        return $this->getAttribute(self::BENEFICIARY_NAME);
    }

    public function getIfsc()
    {
        return $this->getAttribute(self::IFSC);
    }

    public function getAadhaarNumber()
    {
        return $this->getAttribute(self::AADHAAR_NUMBER);
    }

    public function getAadhaarVid()
    {
        return $this->getAttribute(self::AADHAAR_VID);
    }

    public function getToken()
    {
        return $this->getAttribute(self::TOKEN);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getSource()
    {
        return $this->getAttribute(self::SOURCE);
    }

    public function setSource($source)
    {
        return $this->setAttribute(self::SOURCE, $source);
    }

    public function getGatewayToken()
    {
        if ($this->getWallet() === Payment\Processor\PayLater::LAZYPAY)
        {
            $encryptedToken = $this->getAttribute(self::GATEWAY_TOKEN);

            // We want to encrypt token for lazypay
            return Crypt::decrypt($encryptedToken, true, $this);
        }

        return $this->getAttribute(self::GATEWAY_TOKEN);
    }

    public function setGatewayToken($gatewayToken)
    {
        if ($this->getWallet() === Payment\Processor\PayLater::LAZYPAY)
        {
            // We want to encrypt token for lazypay
            $encryptedToken = Crypt::encrypt($gatewayToken, true, $this);

            return $this->setAttribute(self::GATEWAY_TOKEN, $encryptedToken);
        }

        return $this->setAttribute(self::GATEWAY_TOKEN, $gatewayToken);
    }

    protected function setGatewayTokenAttribute($gatewayToken)
    {
        if ($this->getWallet() === Payment\Processor\PayLater::LAZYPAY)
        {
            // We want to encrypt token for lazypay
            $encryptedToken = Crypt::encrypt($gatewayToken, true, $this);

            $this->attributes[self::GATEWAY_TOKEN] =  $encryptedToken;
            return;
        }

        $this->attributes[self::GATEWAY_TOKEN] =  $gatewayToken;
    }

    public function getGatewayTokenAttribute()
    {
        if ($this->getWallet() === Payment\Processor\PayLater::LAZYPAY)
        {
            $encryptedToken = $this->attributes[self::GATEWAY_TOKEN];

            // We want to decrypt token for lazypay
            return Crypt::decrypt($encryptedToken, true, $this);
        }

        return $this->attributes[self::GATEWAY_TOKEN] ?? null;
    }

    public function getGatewayToken2()
    {
        return $this->getAttribute(self::GATEWAY_TOKEN2);
    }

    public function getTerminalId()
    {
        return $this->getAttribute(self::TERMINAL_ID);
    }

    public function isRecurring()
    {
        return ($this->getAttribute(self::RECURRING) === true);
    }

    public function getUsedAt()
    {
        return $this->getAttribute(self::USED_AT);
    }

    public function getExpiredAt()
    {
        return $this->getAttribute(self::EXPIRED_AT);
    }

    public function getConfirmedAt()
    {
        return $this->getAttribute(self::CONFIRMED_AT);
    }

    public function getStartTime()
    {
        return $this->getAttribute(self::START_TIME);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getMaxAmount()
    {
        return $this->getAttribute(self::MAX_AMOUNT);
    }

    public function getAuthType()
    {
        return $this->getAttribute(self::AUTH_TYPE);
    }

    public function getCardId()
    {
        return $this->getAttribute(self::CARD_ID);
    }

    public function getVpaId()
    {
        return $this->getAttribute(self::VPA_ID);
    }

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getAcknowledgedAt()
    {
        return $this->getAttribute(self::ACKNOWLEDGED_AT);
    }

    public function hasBeenAcknowledged(): bool
    {
        return (empty($this->getAcknowledgedAt()) === false);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getRecurringStatus()
    {
        return $this->getAttribute(self::RECURRING_STATUS);
    }

    public function getRecurringFailureReason()
    {
        return $this->getAttribute(self::RECURRING_FAILURE_REASON);
    }

    public function getUpiMandate()
    {
        return $this->upiMandate;
    }

    public function getCardMandateId()
    {
        return $this->getAttribute(self::CARD_MANDATE_ID);
    }

    public function getFrequency()
    {
        return $this->getAttribute(self::FREQUENCY);
    }

    public function getInternalErrorCode()
    {
        return $this->getAttribute(self::INTERNAL_ERROR_CODE);
    }

    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    public function getErrorDescription()
    {
        return $this->getAttribute(self::ERROR_DESCRIPTION);
    }

    public function getCardAttribute()
    {
        if ($this->relationLoaded('card') === true)
        {
            return $this->getRelation('card');
        }

        if ($this->hasCard() === true)
        {
            $card = (new Card\Repository)->findOrFail($this->getCardId());

            $this->card()->associate($card);

            return $card;
        }

        return null;
    }

    public function hasCardMandate()
    {
        return $this->isAttributeNotNull(self::CARD_MANDATE_ID);
    }

    public function isLocal()
    {
        return ($this->getMerchantId() !== Account::SHARED_ACCOUNT);
    }

    public function isGlobal(): bool
    {
        return ($this->getMerchantId() === Account::SHARED_ACCOUNT);
    }

    public function isLocalTokenOnGlobalCustomer(): bool
    {
        return (($this->isLocal()) &&
                (isset($this->customer)) &&
                ($this->customer->isGlobal()));
    }

    public function isCard()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::CARD);
    }

    public function isExpired()
    {
        $expiredAt = $this->getExpiredAt();

        if ($expiredAt === null)
        {
            return false;
        }

        return ($expiredAt <= time());
    }

    public function setAuthType($authType)
    {
        $this->setAttribute(self::AUTH_TYPE, $authType);
    }

    public function setRecurring($recurring)
    {
        $this->setAttribute(self::RECURRING, $recurring);
    }

    public function setStartTime($startTime)
    {
        $this->setAttribute(self::START_TIME, $startTime);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setErrorCode($errorCode)
    {
        $this->setAttribute(self::ERROR_CODE, $errorCode);
    }

    public function setErrorDescription($errorDescription)
    {
        $this->setAttribute(self::ERROR_DESCRIPTION, $errorDescription);
    }


    /**
     * Sets the VPA ID
     *
     * @param string|null $vpaId
     */
    public function setVpaId(?string $vpaId)
    {
        if (empty($vpaId) === true)
        {
            return;
        }

        $this->setAttribute(self::VPA_ID, $vpaId);
    }

    public function setRecurringStatus($recurringStatus)
    {
        RecurringStatus::validateRecurringStatus($recurringStatus);

        $currentRecurringStatus = $this->getRecurringStatus();

        //
        // This is added just as a robust check to ensure that we don't
        // update the same status again. If it's changed to the
        // same status, it might cause an issue because we will end up
        // setting `confirmed_at` to the later time, whereas it would have
        // been confirmed earlier itself.
        //
        if ((empty($currentRecurringStatus) === false) and
            ($currentRecurringStatus === $recurringStatus))
        {
            return;
        }

        $this->setAttribute(self::RECURRING_STATUS, $recurringStatus);

        if (RecurringStatus::isTimestampedStatus($recurringStatus) === true)
        {
            $timestampKey = $recurringStatus . '_at';

            $currentTime = Carbon::now()->getTimestamp();

            $this->setAttribute($timestampKey, $currentTime);
        }
    }

    public function setRecurringFailureReason($recurringFailureReason)
    {
        $this->setAttribute(self::RECURRING_FAILURE_REASON, $recurringFailureReason);
    }

    public function setAcknowledgedAt($timestamp)
    {
        $this->setAttribute(self::ACKNOWLEDGED_AT, $timestamp);
    }

    public function setWallet($wallet)
    {
        $this->setAttribute(self::WALLET, $wallet);
    }

    public function setUsedAt($timestamp)
    {
        $this->setAttribute(self::USED_AT, $timestamp);
    }

    public function setUsedCount($usedCount): void
    {
        $this->setAttribute(self::USED_COUNT, $usedCount);
    }

    public function setExpiredAt($timestamp)
    {
        $this->setAttribute(self::EXPIRED_AT, $timestamp);
    }

    public function incrementUsedCount()
    {
        $this->increment(self::USED_COUNT);
    }

    public function setSubscriptionId(string $subscriptionId)
    {
        $this->attributes[self::ENTITY_ID] = $subscriptionId;
        $this->attributes[self::ENTITY_TYPE] = E::SUBSCRIPTION;
    }

    public function setGatewayToken2(string $gatewayToken2)
    {
        $this->attributes[self::GATEWAY_TOKEN2] = $gatewayToken2;
    }


    protected function setUsedAtAttribute($time)
    {
        $usedAt = $this->getAttribute(self::USED_AT);

        if ($time > $usedAt)
        {
            $this->attributes[self::USED_AT] = $time;
        }
    }

    /**
     * Cannot use generators here because we can receive
     * null in max_amount which will get overridden
     * by  fillable. Hence, no use of generator.
     * It needs to be in fillable because
     * merchant can send its value too.
     *
     * @param $maxAmount
     */
    protected function setMaxAmountAttribute($maxAmount)
    {
        $authType = $this->getAuthType();

        if ((empty($maxAmount) === true) and
            ($this->getMethod() === Payment\Method::EMANDATE))
        {
            $maxAmount = self::DEFAULT_MAX_AMOUNT;
        }

        $this->attributes[self::MAX_AMOUNT] = $maxAmount;
    }

    /**
     * Cannot use generators here because we can receive
     * null in expired_at which will get overridden
     * by fillable. Hence, no use of generator.
     * It needs to be in fillable because
     * merchant can send its value too.
     *
     * In case of aadhaar auth type, we will have to keep
     * the expiry as null.
     *
     * @param $expiredAt
     */
    protected function setExpiredAtAttribute($expiredAt)
    {
        if (empty($expiredAt) === false)
        {
            $this->attributes[self::EXPIRED_AT] = $expiredAt;
        }
    }

    protected function setAadhaarNumberAttribute($aadhaarNumber)
    {
        if ($aadhaarNumber !== null)
        {
            $aadhaarNumber = Crypt::encrypt($aadhaarNumber, true, $this);
        }

        $this->attributes[self::AADHAAR_NUMBER] = $aadhaarNumber;
    }

    protected function setAadhaarVidAttribute($aadhaarVid)
    {
        if ($aadhaarVid !== null)
        {
            $aadhaarVid = Crypt::encrypt($aadhaarVid, true, $this);
        }

        $this->attributes[self::AADHAAR_VID] = $aadhaarVid;
    }

    protected function setPublicCardAttribute(array & $array)
    {
        if ($this->hasCard())
        {
            $this->card->overrideIINDetails();
            $array[self::CARD] = $this->card->toArrayToken();
        }
    }

    protected function setPublicVpaAttribute(array & $array)
    {
        if ($this->hasVpa() and ($this->vpa instanceof Vpa\Entity))
        {
            $array[self::VPA] = $this->vpa->toArrayToken();
        }
    }

    protected function setPublicStartTimeAttribute(array & $array)
    {
        if ($this->getMethod() !== Payment\Method::UPI)
        {
            unset($array[self::START_TIME]);
        }
    }

    protected function setPublicBankDetailsAttribute(array & $array)
    {

        $app = App::getFacadeRoot();
        $routeName = $app['api.route']->getCurrentRouteName();
        $isNotCheckoutInternalRoute = ($routeName !== 'customer_fetch_tokens_internal');

        if(($this->getMethod() === Payment\Method::EMANDATE) or
           (((bool) app('basicauth')->isProxyAuth() === true) and
               ($isNotCheckoutInternalRoute === true) and
            ($this->getMethod() === Payment\Method::NACH)))
        {
            $array[self::BANK_DETAILS] = [
                self::BENEFICIARY_NAME => $this->getBeneficiaryName(),
                self::ACCOUNT_NUMBER   => $this->getAccountNumber(),
                self::IFSC             => $this->getIfsc(),
                self::ACCOUNT_TYPE     => $this->getAccountType(),
            ];
        }
    }

    protected function setPublicMaxAmountAttribute(array & $array)
    {
        if($this->getMethod() !== Payment\Method::EMANDATE)
        {
            unset($array[self::MAX_AMOUNT]);
        }
    }

    protected function setPublicExpiredAtAttribute(array & $array)
    {
        if( ($this->getMethod() !== Payment\Method::EMANDATE) and
            ($this->getMethod() !== Payment\Method::CARD) and
            ($this->getWallet() !== Payment\Processor\PayLater::LAZYPAY))
        {
            unset($array[self::EXPIRED_AT]);
        }
    }

    protected function setPublicStatusAttribute(array & $array)
    {
        if ($this->getMethod() !== Payment\Method::CARD)
        {
            unset($array[self::STATUS]);
        }
    }

    protected function setPublicMrnAttribute(array & $array)
    {
        $array[self::MRN] = null;

        if (($this->merchant->isFeatureEnabled(Feature\Constants::EMANDATE_MRN) === true) and
            (($this->getMethod() === Payment\Method::EMANDATE) or
                ($this->getMethod() === Payment\Method::NACH)))
        {
            $array[self::MRN] = $this->getGatewayToken();
        }
    }

    public function setIfsc($ifsc)
    {
        return $this->setAttribute(self::IFSC, $ifsc);
    }

    /**
     * Appending recurring status and recurring
     * failure reason when recurring status is set
     */
    public function getRecurringDetailsAttribute()
    {
        return [
            self::RECURRING_STATUS_SHORT            => $this->getRecurringStatus(),
            self::RECURRING_FAILURE_REASON_SHORT    => $this->getRecurringFailureReason()
        ];
    }

    protected function getAadhaarNumberAttribute($aadhaarNumber)
    {
        if ($aadhaarNumber === null)
        {
            return $aadhaarNumber;
        }

        return Crypt::decrypt($aadhaarNumber, true, $this);
    }

    protected function getAadhaarVidAttribute($aadhaarVid)
    {
        if ($aadhaarVid === null)
        {
            return $aadhaarVid;
        }

        return Crypt::decrypt($aadhaarVid, true, $this);
    }


    public function generateToken($input)
    {
        $rand = '';

        for ($i = 0; $i < 3; $i++)
        {
            $dec = hexdec(bin2hex(random_bytes(5)));

            // Convert the random decimal generated to base 62
            $rand .= self::base62($dec);
        }

        $token = substr($rand, 0, 14);

        $this->setAttribute(self::TOKEN, $token);
    }

    protected function modifyIfsc(& $input)
    {
        if (isset($input[self::IFSC]) === true)
        {
            $input[self::IFSC] = strtoupper($input[self::IFSC]);
        }
    }

    public function scopeWithVpaTokens(BuilderEx $query, bool $withVpa)
    {
        if ($withVpa === true)
        {
            $query->with(self::VPA);
        }
        else
        {
            $query->whereNull(self::VPA_ID);
        }
    }

    //disabled dcc for optimiser merchants because of money leak incidents
    public function isDCCEnabled()
    {
        return $this->hasCard() and (new Payment\Service)->isDccEnabledIIN($this->card->iinRelation, $this->merchant) and ($this->merchant->isFeatureEnabled(Feature\Constants::RAAS) === false);
    }

    public function getBillingAddress()
    {
        if($this->hasCard() === true)
        {
            $app = App::getFacadeRoot();

            return $app['repo']->address->fetchPrimaryAddressOfEntityOfType($this, Address\Type::BILLING_ADDRESS);
        }

        return null;
    }

    public function toArrayPublic()
    {
        $publicArray = parent::toArrayPublic();

        $publicArray[self::DCC_ENABLED] = $this->isDCCEnabled();

        $this->setBillingAddressInTokenReponse($publicArray);

        // For upi recurring tokens, we are not storing max amount, end time in token entity. These are being
        // stored in mandate entity. So, fetching these details from mandate entity.
        if ($this->isUpiRecurringToken() === true)
        {
            $publicArray[self::MAX_AMOUNT] = $this->getUpiMandate()->getMaxAmount();

            $publicArray[self::EXPIRED_AT] = $this->getUpiMandate()->getEndTime();
        }

        if($this->getMethod() === Entity::CARD)
        {
            $app = App::getFacadeRoot();

            if(isset($publicArray[self::SOURCE]))
                $publicArray[self::SOURCE] = $this->getSourcePublic($publicArray[self::SOURCE]);

            if($this->hasCardMandate() === true) {
                $publicArray[self::MAX_AMOUNT] = ($this->cardMandate->getMaxAmount()!==null) ? $this->cardMandate->getMaxAmount():$this->getMaxAmount();
            } else {
                $publicArray[self::MAX_AMOUNT] = $this->getMaxAmount();
            }

            // exposing error code as null in case of it is not set
            $publicArray['error_code'] = $this->getErrorCode() ?? null;

            try {
                $card = $app['repo']->card->fetchForToken($this);

                if ($card->isNetworkTokenisedCard() === true || ($app['rzp.mode'] === Mode::TEST)) {
                    $publicArray[self::COMPLIANT_WITH_TOKENISATION_GUIDELINES] = true;
                } else {
                    $publicArray[self::COMPLIANT_WITH_TOKENISATION_GUIDELINES] = false;
                }

               $publicArray['card']['expiry_month'] = "01";

               $publicArray['card']['expiry_year'] = "2099";

               $publicArray['expired_at'] = $card->getTokenExpiryTimestamp();

               (new Card\Entity())->setDummyCardData($publicArray['card']);

            }
            catch (\Throwable $e)
            {
                $app['trace']->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::CUSTOMER_CARD_FETCH_FAILED,
                    [
                        'token'   => $this->getPublicId(),
                    ]);
            }
        } else {
            unset($publicArray[self::SOURCE]);
        }

        if ($this->isNachToken() === true and (new Merchant\Core)->isRazorxExperimentEnable(
                $this->merchant->getId(),
                Merchant\RazorxTreatment::SEND_NACH_SIGNED_FORM_TO_MERCHANT_IN_RESPONSE_AUTHLINK
            ))
        {
            $app = App::getFacadeRoot();

            $subscriptionRegistration = $app['repo']->subscription_registration
                ->findByTokenIdAndMerchant($this->getId(), $this->merchant->getId());

            if ($subscriptionRegistration !== null) {
                $invoice = $app['repo']->invoice
                    ->findByMerchantAndTokenRegistration($this->merchant, $subscriptionRegistration);

                $publicArray = $subscriptionRegistration
                    ->toArrayTokenFieldsNach($invoice, $publicArray);

                $paperMandateUpload =
                    $app['repo']->paper_mandate_upload
                        ->findLatestByMandateId($subscriptionRegistration->paperMandate->getId())->first();

                if ($paperMandateUpload !== null) {
                    $singedFormUrl = (new FileUploader($subscriptionRegistration->paperMandate))
                        ->getSignedShortUrl($paperMandateUpload[PaperMandateUploadEntity::ENHANCED_FILE_ID]);

                    $publicArray[SubscriptionRegistrationEntity::NACH]
                    [SubscriptionRegistrationEntity::SIGNED_FORM] = $singedFormUrl;
                }
            }
        }

        return $publicArray;
    }

    //This functions stores the appropriate mapping of source database to corresponding source public values.
    public function getSourcePublic($dbSource) {
        $sourcePublic = self::BUSINESS;

        switch($dbSource)
        {
            case self::MERCHANT:
                $sourcePublic = self::BUSINESS;
                break;
            case self::ISSUER:
                $sourcePublic = self::ISSUER;
                break;
        }
        return $sourcePublic;
    }

    public function isNachToken()
    {
        return ($this->getMethod() === Payment\Method::NACH);
    }

    public function getTokenLengthWithNetwork($network)
    {
        $tokenLength = 9;

        switch($network)
        {
            case "rupay" :
                $tokenLength = 8;
                break;

            case "amex" :
                $tokenLength = 6;
        }
        return $tokenLength;
    }

    public function toArrayPublicTokenizedCard($serviceProviderTokens)
    {
        $publicArray = parent::toArrayPublic();

        if(isset($publicArray[self::SOURCE]))
            $publicArray[self::SOURCE] = $this->getSourcePublic($publicArray[self::SOURCE]);

        foreach (self::$networkTokenUnsetAttributes as $attribute)
        {
            unset($publicArray[$attribute]);
        }

        foreach (Card\Entity::$networkTokenCardUnsetAttributes as $attribute)
        {
            unset($publicArray['card'][$attribute]);
        }

        if (empty($this->getCustomerId()) === false)
        {
            $publicArray[self::CUSTOMER_ID] = $this->customer->getPublicId();
        }

        $publicArray['compliant_with_tokenisation_guidelines'] = true;

        if (empty($serviceProviderTokens) === false)
        {
            $serviceProviderTokensArray = array();

            foreach ($serviceProviderTokens as $provider)
            {
                if ($this->isExpired() === true)
                {
                    $provider[self::STATUS] = self::DEACTIVATED;

                    $provider[self::STATUS_REASON] = self::EXPIRED;
                }
                elseif ($provider[self::STATUS] === self::DEACTIVATED)
                {
                    $provider[self::STATUS_REASON] = self::DEACTIVATED_BY_BANK;
                }

                foreach (self::$providerDataUnsetAttributes as $attribute)
                {
                    unset($provider[self::PROVIDER_DATA][$attribute]);
                }

                foreach (self::$providerDataUnsetNullAttributes as $attribute)
                {
                    if(array_key_exists($attribute, $provider[self::PROVIDER_DATA]) && $provider[self::PROVIDER_DATA][$attribute] === NULL)
                    {
                        unset($provider[self::PROVIDER_DATA][$attribute]);
                    }
                }

                if (empty($provider[self::PROVIDER_DATA][self::CARD]))
                {
                    unset($provider[self::PROVIDER_DATA][self::CARD]);
                }

                if (array_key_exists(self::TOKEN_IIN, $provider[self::PROVIDER_DATA]))
                {
                    $provider[self::PROVIDER_DATA][self::TOKEN_IIN] = substr($provider[self::PROVIDER_DATA][self::TOKEN_IIN], 0, $this->getTokenLengthWithNetwork($provider["provider_name"]));
                }

                if(isset($provider['tokenised_terminal_id'])){
                    unset($provider['tokenised_terminal_id']);
                }

                array_push($serviceProviderTokensArray, $provider);
            }

            $publicArray[self::SERVICE_PROVIDER_TOKENS] = $serviceProviderTokensArray;

            // todo: when more than one tokens are come into picture, take union of statuses
            $publicArray[self::STATUS] = $serviceProviderTokensArray[0][self::STATUS];

            if (array_key_exists(self::TOKEN_IIN, $provider[self::PROVIDER_DATA]))
            {
                $publicArray['card']['token_iin'] = $provider[self::PROVIDER_DATA][self::TOKEN_IIN];
            }
            else if(isset($publicArray['card']['token_iin']) == true)
            {
                $publicArray['card']['token_iin'] = substr($publicArray['card']['token_iin'], 0, $this->getTokenLengthWithNetwork($provider["provider_name"]));
            }
        }

        $publicArray[self::NOTES] = $this->getNotes();

        return $publicArray;
    }

    public function isUpiRecurringToken()
    {
        return (($this->isRecurring() === true) and ($this->getMethod() === Payment\Method::UPI));
    }

    public function isSaveVpaToken()
    {
        return (($this->getStartTime() === null) and ($this->getMethod() === Payment\Method::UPI));
    }

    public function mapIFSC($tokens)
    {
        $mappedTokens = collect($tokens)->map(function ($arr) {

            $firstFourOfIFSC = substr($arr['ifsc'], 0, 4);

            $mergedBanks = SubscriptionRegistrationConstants::getMergedBanksPaperNach();

            if (array_key_exists($firstFourOfIFSC, $mergedBanks) === true)
            {
                $arr['ifsc'] = $mergedBanks[$firstFourOfIFSC];
            }

            return $arr;
        });

        return new PublicCollection($mappedTokens);
    }

    // Billing address would be set as part of token object only for the preferences API.

    protected function setBillingAddressInTokenReponse(& $publicArray)
    {
        $app = App::getFacadeRoot();

        $routeName = $app['api.route']->getCurrentRouteName();

         if($routeName == 'merchant_checkout_preferences' || $routeName == 'otp_verify' || $routeName == 'customer_fetch_tokens_internal')
         {
             $billingAddress = $this->getBillingAddress();

             $publicArray[self::BILLING_ADDRESS] = $billingAddress!==null?$billingAddress->getBillingAddress():null;
         }
    }
}
