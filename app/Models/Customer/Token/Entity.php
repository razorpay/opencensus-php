<?php

namespace RZP\Models\Customer\Token;

use RZP\Models\Base;
use RZP\Models\Merchant\Account;

use Illuminate\Database\Eloquent\SoftDeletes;

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
    const GATEWAY_TOKEN             = 'gateway_token';
    const GATEWAY_TOKEN2            = 'gateway_token2';
    const RECURRING                 = 'recurring';
    // TODO: Finalize the attribute names.
    const RECURRING_STATUS          = 'recurring_status';
    const RECURRING_FAILURE_REASON  = 'recurring_failure_reason';
    const USED_COUNT                = 'used_count';
    const USED_AT                   = 'used_at';
    const EXPIRED_AT                = 'expired_at';
    const CREATED_AT                = 'created_at';
    const UPDATED_AT                = 'updated_at';
    const DELETED_AT                = 'deleted_at';

    protected static $sign      = 'token';

    protected $entity           = 'token';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::BANK,
        self::WALLET,
        self::METHOD,
        self::TOKEN,
        self::GATEWAY_TOKEN,
        self::GATEWAY_TOKEN2,
        self::RECURRING,
        self::EXPIRED_AT,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::BANK,
        self::WALLET,
        self::TOKEN,
        self::METHOD,
        self::CARD_ID,
        self::CARD,
        self::CUSTOMER_ID,
        self::TERMINAL_ID,
        self::GATEWAY_TOKEN,
        self::GATEWAY_TOKEN2,
        self::RECURRING,
        self::RECURRING_STATUS,
        self::RECURRING_FAILURE_REASON,
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
        self::RECURRING_STATUS,
        self::RECURRING_FAILURE_REASON,
        self::USED_AT,
        self::CREATED_AT
    ];

    protected $defaults = [
        self::WALLET                    => null,
        self::BANK                      => null,
        self::CARD_ID                   => null,
        self::GATEWAY_TOKEN2            => null,
        self::RECURRING                 => false,
        self::USED_AT                   => null,
        self::USED_COUNT                => 0,
        self::EXPIRED_AT                => null,
        self::RECURRING_STATUS          => null,
        self::RECURRING_FAILURE_REASON  => null,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CARD,
        self::RECURRING,
        self::RECURRING_STATUS,
        self::RECURRING_FAILURE_REASON,
    ];

    protected $casts = [
        self::RECURRING     => 'bool',
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

    public function scopeCustomerId($query, $customerId)
    {
        $customerIdColumn = $this->getAttributeWithTableName(Entity::CUSTOMER_ID);

        $query->where($customerIdColumn, '=', $customerId);
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

    protected function setPublicRecurringAttribute(array & $array)
    {
        if ($this->isRecurring() === false)
        {
            unset($array[self::RECURRING]);
        }
    }

    protected function setPublicRecurringStatusAttribute(array & $array)
    {
        if ($this->isRecurring() === false)
        {
            unset($array[self::RECURRING_STATUS]);
        }
    }

    protected function setPublicRecurringFailureReasonAttribute(array & $array)
    {
        if ($this->isRecurring() === false)
        {
            unset($array[self::RECURRING_FAILURE_REASON]);
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
