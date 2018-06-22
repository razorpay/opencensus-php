<?php

namespace RZP\Models\Customer\Token;

use Crypt;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\Merchant\Account;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Card\Entity $card
 * @property Terminal\Entity $terminal
 * @property Merchant\Entity $merchant
 */
class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const MERCHANT_ID               = 'merchant_id';
    const CUSTOMER_ID               = 'customer_id';
    const TERMINAL_ID               = 'terminal_id';
    const TOKEN                     = 'token';
    const METHOD                    = 'method';
    const CARD_ID                   = 'card_id';
    const CARD                      = 'card';
    const BANK                      = 'bank';
    const WALLET                    = 'wallet';
    const ACCOUNT_NUMBER            = 'account_number';
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
    const CONFIRMED_AT              = 'confirmed_at';
    const REJECTED_AT               = 'rejected_at';
    const INITIATED_AT              = 'initiated_at';
    const ACKNOWLEDGED_AT           = 'acknowledged_at';
    const USED_COUNT                = 'used_count';
    const USED_AT                   = 'used_at';
    const EXPIRED_AT                = 'expired_at';
    const CREATED_AT                = 'created_at';
    const UPDATED_AT                = 'updated_at';
    const DELETED_AT                = 'deleted_at';

    //
    // These keys will be under recurring_details
    // Having recurring prepended to status and
    // failure_reason is redundant.
    //
    const RECURRING_STATUS_SHORT            = 'status';
    const RECURRING_FAILURE_REASON_SHORT    = 'failure_reason';

    /**
     * We use this to set the max amount of the token entity.
     * By default, we have chosen ₹ 99,999
     */
    const DEFAULT_MAX_AMOUNT    = 9999900;

    /**
     * We use this to set the number of years after which the
     * emandate token will get expired and cannot be used
     * anymore. Ideally, the merchant sends the expiry time.
     * In case he does not, we add 10 years to the current time.
     */
    const DEFAULT_EXPIRY_YEARS  = 10;

    protected static $sign      = 'token';

    protected $entity           = 'token';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::BANK,
        self::WALLET,
        self::METHOD,
        self::ACCOUNT_NUMBER,
        self::BENEFICIARY_NAME,
        self::IFSC,
        self::TOKEN,
        self::GATEWAY_TOKEN,
        self::GATEWAY_TOKEN2,
        self::RECURRING,
        self::AUTH_TYPE,
        self::AADHAAR_NUMBER,
        self::MAX_AMOUNT,
        self::EXPIRED_AT,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::BANK,
        self::WALLET,
        self::ACCOUNT_NUMBER,
        self::BENEFICIARY_NAME,
        self::IFSC,
        self::TOKEN,
        self::METHOD,
        self::CARD_ID,
        self::CARD,
        self::CUSTOMER_ID,
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
        self::USED_COUNT,
        self::USED_AT,
        self::EXPIRED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::TOKEN,
        self::BANK,
        self::WALLET,
        self::METHOD,
        self::CARD,
        self::RECURRING,
        self::RECURRING_DETAILS,
        self::USED_AT,
        self::CREATED_AT,
        // TODO: uncomment when we start accepting token as input
        // self::MAX_AMOUNT,
    ];

    protected $defaults = [
        self::WALLET                    => null,
        self::CARD_ID                   => null,
        self::ACCOUNT_NUMBER            => null,
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
        self::USED_AT                   => null,
        self::USED_COUNT                => 0,
        self::EXPIRED_AT                => null,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CARD,
        // TODO: Remove this after deciding on how to expose
        self::RECURRING_DETAILS
    ];

    protected $appends = [
        self::RECURRING_DETAILS,
    ];

    protected $casts = [
        self::RECURRING     => 'bool',
        self::MAX_AMOUNT    => 'int',
        self::USED_COUNT    => 'int',
    ];

    protected static $generators = [
        self::TOKEN,
    ];

    protected static $modifiers = [
        self::IFSC,
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

    public function terminal()
    {
        return $this->belongsTo('RZP\Models\Terminal\Entity');
    }

    public function hasCard()
    {
        return $this->isAttributeNotNull(self::CARD_ID);
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

    public function getToken()
    {
        return $this->getAttribute(self::TOKEN);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getGatewayToken()
    {
        return $this->getAttribute(self::GATEWAY_TOKEN);
    }

    public function setGatewayToken($gatewayToken)
    {
        return $this->setAttribute(self::GATEWAY_TOKEN, $gatewayToken);
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

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function getRecurringStatus()
    {
        return $this->getAttribute(self::RECURRING_STATUS);
    }

    public function getRecurringFailureReason()
    {
        return $this->getAttribute(self::RECURRING_FAILURE_REASON);
    }

    public function isLocal()
    {
        return ($this->getMerchantId() !== Account::SHARED_ACCOUNT);
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

    public function setUsedAt($timestamp)
    {
        $this->setAttribute(self::USED_AT, $timestamp);
    }

    public function setExpiredAt($timestamp)
    {
        $this->setAttribute(self::EXPIRED_AT, $timestamp);
    }

    public function incrementUsedCount()
    {
        $this->increment(self::USED_COUNT);
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
     * @param $expiredAt
     */
    protected function setExpiredAtAttribute($expiredAt)
    {
        if ((empty($expiredAt) === true) and
            ($this->getMethod() === Payment\Method::EMANDATE))
        {
            $expiredAt = Carbon::now(Timezone::IST)
                               ->addYears(self::DEFAULT_EXPIRY_YEARS)
                               ->getTimestamp();
        }

        $this->attributes[self::EXPIRED_AT] = $expiredAt;
    }

    protected function setAadhaarNumberAttribute($aadhaarNumber)
    {
        if ($aadhaarNumber !== null)
        {
            $aadhaarNumber = Crypt::encrypt($aadhaarNumber);
        }

        $this->attributes[self::AADHAAR_NUMBER] = $aadhaarNumber;
    }

    protected function setPublicCardAttribute(array & $array)
    {
        if ($this->hasCard())
        {
            $array[self::CARD] = $this->card->toArrayToken();
        }
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

        return Crypt::decrypt($aadhaarNumber);
    }

    public function setPublicRecurringDetailsAttribute(array & $array)
    {
        if ($this->getMethod() === Payment\Method::CARD)
        {
            unset($array[self::RECURRING_DETAILS]);
        }
    }

    protected function generateToken($input)
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
}
