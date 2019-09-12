<?php

namespace RZP\Models\SubscriptionRegistration;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\Base\Traits\NotesTrait;

/**
 * @property Customer\Token\Entity $token
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;

    use SoftDeletes;

    const CUSTOMER_ID       = 'customer_id';

    //
    // Method can be of card or emandate
    //
    const METHOD            = 'method';
    const ENTITY_TYPE       = 'entity_type';
    const BANK              = 'bank';
    const ENTITY_ID         = 'entity_id';
    const RECURRING_STATUS  = 'recurring_status';
    const FAILURE_REASON    = 'failure_reason';
    const MAX_AMOUNT        = 'max_amount';
    const TOKEN_ID          = 'token_id';
    const TOKEN             = 'token';
    const NOTES             = 'notes';
    const AMOUNT            = 'amount';
    const STATUS            = 'status';
    const ATTEMPTS          = 'attempts';
    const CURRENCY          = 'currency';

    const FIRST_PAYMENT_AMOUNT = 'first_payment_amount';

    const BANK_ACCOUNT      = 'bank_account';
    //
    // Auth Type is aadhaar or netbanking
    //
    const AUTH_TYPE         = 'auth_type';
    const EXPIRE_AT         = 'expire_at';
    const DELETED_AT        = 'deleted_at';

    const METHOD_TYPE_CARD      = 'card';
    const METHOD_TYPE_EMANDATE  = 'emandate';

    protected static $sign = 'subr';

    protected $entity = 'subscription_registration';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::AUTH_TYPE                 => null,
        self::MAX_AMOUNT                => null,
        self::EXPIRE_AT                 => null,
        self::NOTES                     => [],
        self::STATUS                    => Status::CREATED
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::METHOD,
        self::RECURRING_STATUS,
        self::FAILURE_REASON,
        self::MAX_AMOUNT,
        self::AUTH_TYPE,
        self::NOTES,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::TOKEN_ID,
        self::EXPIRE_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $tokenFields = [
        self::METHOD,
        self::EXPIRE_AT,
        self::BANK_ACCOUNT,
        self::RECURRING_STATUS,
        self::FAILURE_REASON,
        self::MAX_AMOUNT,
        self::AUTH_TYPE,
        self::NOTES,
        self::FIRST_PAYMENT_AMOUNT,
        self::CURRENCY,
        self::STATUS
    ];
    protected $public = [
        self::ID,
        self::METHOD,
        self::ENTITY,
        self::NOTES,
        self::FIRST_PAYMENT_AMOUNT,
        self::RECURRING_STATUS,
        self::FAILURE_REASON,
        self::CURRENCY,
        self::MAX_AMOUNT,
        self::AUTH_TYPE,
        self::EXPIRE_AT,
    ];

    protected $fillable = [
        self::METHOD,
        self::CURRENCY,
        self::AMOUNT,
        self::FIRST_PAYMENT_AMOUNT,
        self::MAX_AMOUNT,
        self::AUTH_TYPE,
        self::EXPIRE_AT,
        self::NOTES,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::EXPIRE_AT,
        self::DELETED_AT,
    ];

    public function toArrayTokenFields()
    {
        $flippedArrayTokenFields = array_flip($this->tokenFields);

        $tokenArray = array_intersect_key($this->toArrayPublic(), $flippedArrayTokenFields);

        if ($this->getEntityType() === self::BANK_ACCOUNT)
        {

            $bankAccount = $this->bankAccount;

            $publicArrayBankAccount = $bankAccount->toArrayHosted();

            unset($publicArrayBankAccount[self::ID]);

            unset($publicArrayBankAccount['entity']);

            $tokenArray[self::BANK_ACCOUNT] = $publicArrayBankAccount;
        }

        $tokenArray[self::FIRST_PAYMENT_AMOUNT] = $this->getAmount();

        return $tokenArray;
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getMaxAmount()
    {
        return $this->getAttribute(self::MAX_AMOUNT);
    }

    public function getFirstPaymentAmountAttribute()
    {
        return $this->getAttribute(self::AMOUNT);
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

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getAttempts()
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    public function isMethodCard(): bool
    {
        return ($this->getMethod() === self::METHOD_TYPE_CARD);
    }

    public function isMethodEmandate(): bool
    {
        return ($this->getMethod() === self::METHOD_TYPE_EMANDATE);
    }

    public function hasAutoPayment()
    {
        return ($this->getAmount() > 0);
    }
    

        /**
     * Gets dimensions for metrics around invoice module
     * @param  array $extra Additional key, value pair of dimensions
     * @return array
     */
    public function getMetricDimensions(array $extra = []): array
    {
        return $extra + [
            'method'           => (string) $this->getMethod(),
            'has_auto_payment' => (int) $this->hasAutoPayment()
        ];
    }

    public function incrementAttempts()
    {
        $this->increment(self::ATTEMPTS);
    }

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setFirstPaymentAmountAttribute($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
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

    public function token()
    {
        return $this->belongsTo(Customer\Token\Entity::class);
    }

    //
    // Entity type currently supports bank_account or card.
    //
    public function entity()
    {
        return $this->morphTo();
    }

    public function bankAccount()
    {
        return $this->morphTo('entity');
    }

    public function setBank(string $bank)
    {
        $this->setAttribute(self::BANK, $bank);
    }
}
