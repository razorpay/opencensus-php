<?php

namespace RZP\Models\SubscriptionRegistration;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const MERCHANT_ID       = 'merchant_id';
    const CUSTOMER_ID       = "customer_id";
    const METHOD            = "method";
    const ENTITY_TYPE       = "entity_type";
    const BANK              = "bank";
    const ENTITY_ID         = "entity_id";
    const RECURRING_STATUS  = "recurring_status";
    const FAILURE_REASON    = "failure_reason";
    const MAX_AMOUNT        = "max_amount";
    const AUTH_TYPE         = "auth_type";
    const EXPIRE_AT         = "expire_at";
    const DELETED_AT        = 'deleted_at';

    protected static $sign = 'subr';

    protected $entity = 'subscription_registration';

    protected $primaryKey = self::ID;

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::ENTITY_ID                 => null,
        self::AUTH_TYPE                 => null,
        self::ENTITY_TYPE               => null,
        self::MAX_AMOUNT                => null,
        self::EXPIRE_AT                 => null,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::METHOD,
        self::ENTITY_TYPE,
        self::RECURRING_STATUS,
        self::FAILURE_REASON,
        self::MAX_AMOUNT,
        self::AUTH_TYPE,
        self::EXPIRE_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::METHOD,
        self::RECURRING_STATUS,
        self::FAILURE_REASON,
        self::MAX_AMOUNT,
        self::AUTH_TYPE,
        self::EXPIRE_AT,
    ];

    protected $fillable = [
        self::METHOD,
        self::MAX_AMOUNT,
        self::AUTH_TYPE,
        self::EXPIRE_AT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::EXPIRE_AT,
        self::DELETED_AT,
    ];

    public function getMaxAmount()
    {
        return $this->getAttribute(self::MAX_AMOUNT);
    }

    public function getAuthType()
    {
        return $this->getAttribute(self::AUTH_TYPE);
    }

    public function getExpireAt()
    {
        return $this->getAttribute(self::EXPIRE_AT);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getBank()
    {
        return $this->getAttribute(self::BANK);
    }

    // Relations

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer\Entity::class);
    }

    public function entity()
    {
        return $this->morphTo();
    }

    public function setBank(string $bank)
    {
        $this->setAttribute(self::BANK, $bank);
    }
}
