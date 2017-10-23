<?php

namespace RZP\Models\Customer\Token;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Merchant\Account;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Card\Entity $card
 * @property Terminal\Entity $terminal
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
    const RECURRING_STATUS          = 'recurring_status';
    const RECURRING_FAILURE_REASON  = 'recurring_failure_reason';
    const RECURRING_DETAILS         = 'recurring_details';
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
     * By default, we have chosen 10000000 paise
     */
    const DEFAULT_MAX_AMOUNT    = 10000000;

    protected static $sign      = 'token';

    protected $entity           = 'token';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::BANK,
        self::WALLET,
        self::METHOD,
        self::ACCOUNT_NUMBER,
        self::TOKEN,
        self::GATEWAY_TOKEN,
        self::GATEWAY_TOKEN2,
        self::RECURRING,
        self::EXPIRED_AT,
        self::MAX_AMOUNT
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::BANK,
        self::WALLET,
        self::ACCOUNT_NUMBER,
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
        self::ACCOUNT_NUMBER            => null,
        self::BANK                      => null,
        self::CARD_ID                   => null,
        self::GATEWAY_TOKEN2            => null,
        self::RECURRING                 => false,
        self::RECURRING_FAILURE_REASON  => null,
        self::RECURRING_STATUS          => null,
        self::MAX_AMOUNT                => null,
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
        self::TOKEN
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
        return $this->getAttribute(self::RECURRING);
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

    public function isExpired()
    {
        $expiredAt = $this->getExpiredAt();

        if ($expiredAt === null)
        {
            return false;
        }

        return ($expiredAt <= time());
    }

    public function setRecurring($recurring)
    {
        $this->setAttribute(self::RECURRING, $recurring);
    }

    public function setRecurringStatus($recurringStatus)
    {
        RecurringStatus::validateRecurringStatus($recurringStatus);

        $this->setAttribute(self::RECURRING_STATUS, $recurringStatus);
    }

    public function setRecurringFailureReason($recurringFailureReason)
    {
        $this->setAttribute(self::RECURRING_FAILURE_REASON, $recurringFailureReason);
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
            $dec = hexdec(bin2hex(openssl_random_pseudo_bytes(5)));

            // Convert the random decimal generated to base 62
            $rand .= self::base62($dec);
        }

        $token = substr($rand, 0, 14);

        $this->setAttribute(self::TOKEN, $token);
    }
}
