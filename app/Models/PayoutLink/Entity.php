<?php

namespace RZP\Models\PayoutLink;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Contact;
use RZP\Models\Payout;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Constants\Timezone;
use RZP\Models\FundAccount;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\Traits\NotesTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use SoftDeletes;

    const ID                    = 'id';
    const CONTACT_ID            = 'contact_id';
    const FUND_ACCOUNT_ID       = 'fund_account_id';
    const SHORT_URL             = 'short_url';
    const MERCHANT_ID           = 'merchant_id';
    const USER_ID               = 'user_id';
    const STATUS                = 'status';
    const AMOUNT                = 'amount';
    const NOTES                 = 'notes';
    const BANK_TRANSFER_MODES   = 'bank_transfer_modeS';
    const DESCRIPTION           = 'description';
    const RECEIPT               = 'receipt';
    const CURRENCY              = 'currency';
    const NOTIFICATION_CHANNELS = 'notification_channels';
    const EXPIRE_AT             = 'expire_at';
    const EXPIRED_AT            = 'expired_at';
    const CANCELLED_AT          = 'cancelled_at';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';

    protected $amounts = [
      self::AMOUNT
    ];

    protected $dates = [
        self::EXPIRE_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::CANCELLED_AT
    ];

    protected $public = [
        self::ID,
        self::CONTACT_ID,
        self::AMOUNT,
        self::MERCHANT_ID,
        self::USER_ID,
        self::CURRENCY,
        self::EXPIRE_AT,
        self::NOTIFICATION_CHANNELS,
        self::DESCRIPTION,
        self::RECEIPT,
        self::NOTES,
        self::SHORT_URL,
        self::STATUS,
        self::CREATED_AT,
        self::EXPIRED_AT,
        self::CANCELLED_AT
    ];

    protected $fillable = [
        self::ID,
        self::CONTACT_ID,
        self::FUND_ACCOUNT_ID,
        self::SHORT_URL,
        self::MERCHANT_ID,
        self::USER_ID,
        self::AMOUNT,
        self::BANK_TRANSFER_MODES,
        self::CURRENCY,
        self::EXPIRE_AT,
        self::NOTIFICATION_CHANNELS,
        self::DESCRIPTION,
        self::RECEIPT,
        self::NOTES,
        self::SHORT_URL,
        self::STATUS,
        self::CREATED_AT,
        self::EXPIRED_AT,
        self::CANCELLED_AT,
        self::UPDATED_AT
    ];

    protected $visible = [
        self::ID,
        self::CONTACT_ID,
        self::FUND_ACCOUNT_ID,
        self::SHORT_URL,
        self::MERCHANT_ID,
        self::USER_ID,
        self::AMOUNT,
        self::BANK_TRANSFER_MODES,
        self::CURRENCY,
        self::EXPIRE_AT,
        self::NOTIFICATION_CHANNELS,
        self::DESCRIPTION,
        self::RECEIPT,
        self::NOTES,
        self::SHORT_URL,
        self::STATUS,
        self::CREATED_AT,
        self::EXPIRED_AT,
        self::CANCELLED_AT,
        self::UPDATED_AT
    ];

    protected $hosted = [
        self::ID,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::EXPIRE_AT,
        self::NOTIFICATION_CHANNELS,
        self::BANK_TRANSFER_MODES,
        self::DESCRIPTION,
        self::RECEIPT,
        self::EXPIRED_AT,
        self::CANCELLED_AT
    ];

    protected $publicAuth = [
        self::ID,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::EXPIRE_AT,
        self::NOTIFICATION_CHANNELS,
        self::BANK_TRANSFER_MODES,
        self::DESCRIPTION,
        self::RECEIPT,
        self::EXPIRED_AT,
        self::CANCELLED_AT
    ];

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    protected $defaults = [
        self::CONTACT_ID            => null,
        self::FUND_ACCOUNT_ID       => null,
        self::SHORT_URL             => null,
        self::MERCHANT_ID           => null,
        self::USER_ID               => null,
        self::AMOUNT                => null,
        self::BANK_TRANSFER_MODES   => null,
        self::CURRENCY              => Currency::INR,
        self::EXPIRE_AT             => null,
        self::NOTIFICATION_CHANNELS => [],
        self::DESCRIPTION           => null,
        self::RECEIPT               => null,
        self::NOTES                 => [],
        self::STATUS                => Status::CREATED,
        self::EXPIRED_AT            => null,
        self::CANCELLED_AT          => null,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::DESCRIPTION,
    ];

    // -------------------------------------- Relations -------------------------------

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function user()
    {
        return $this->belongsTo(User\Entity::class);
    }

    public function fundAccount()
    {
        return $this->belongsTo(FundAccount\Entity::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact\Entity::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout\Entity::class);
    }

    // -------------------------------------- End Relations ---------------------------

    // ----------------------------------------- Getters ------------------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        if ($this->getAttribute(self::CURRENCY) === null)
        {
            return Currency::INR;
        }

        return $this->getAttribute(self::CURRENCY);
    }

    public function getStatus(): string
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getStatusReason()
    {
        return $this->getAttribute(self::STATUS_REASON);
    }

    public function getShortUrl()
    {
        return $this->getAttribute(self::SHORT_URL);
    }

    public function getExpireBy()
    {
        return $this->getAttribute(self::EXPIRE_BY);
    }

    public function getTimesPayable()
    {
        return $this->getAttribute(self::TIMES_PAYABLE);
    }

    public function getTimesPaid(): int
    {
        return $this->getAttribute(self::TIMES_PAID);
    }

    public function getTotalAmountPaid(): int
    {
        return $this->getAttribute(self::TOTAL_AMOUNT_PAID);
    }

    public function getHostedTemplateId()
    {
        return $this->getAttribute(self::HOSTED_TEMPLATE_ID);
    }

    public function getUdfJsonschemaId()
    {
        return $this->getAttribute(self::UDF_JSONSCHEMA_ID);
    }

    public function getVersion(): string
    {
        return $this->getSettings()[Entity::VERSION] ?? Version::V1;
    }

    public function isActive(): bool
    {
        return ($this->getStatus() === Status::ACTIVE);
    }

    public function isInactive(): bool
    {
        return ($this->getStatus() === Status::INACTIVE);
    }

    public function isExpired(): bool
    {
        return (($this->getStatus() === Status::INACTIVE) and
                ($this->getStatusReason() === StatusReason::EXPIRED));
    }

    public function isCompleted(): bool
    {
        return (($this->getStatus() === Status::INACTIVE) and
                ($this->getStatusReason() === StatusReason::COMPLETED));
    }

    public function isDeactivated(): bool
    {
        return (($this->getStatus() === Status::INACTIVE) and
                ($this->getStatusReason() === StatusReason::DEACTIVATED));
    }

    public function isPastExpireBy(): bool
    {
        $now = Carbon::now(Timezone::IST)->timestamp;

        return (($this->getExpireBy() !== null) and
                ($now >= $this->getExpireBy()));
    }

    public function isTimesPayableExhausted(): bool
    {
        $isNewPage = (new Core)->isPaymentPageV3Enabled();

        if (($this->getVersion() === Version::V1) and ($isNewPage === false))
        {
            return (($this->getTimesPayable() !== null) and
                ($this->getTimesPayable() === $this->getTimesPaid()));
        }

        $paymentPageItems = $this->paymentPageItems()->get();

        foreach ($paymentPageItems as $paymentPageItem)
        {
            if ($paymentPageItem->isStockLeft() === true)
            {
                return false;
            }
        }

        return true;
    }

    // -------------------------------------- End Getters -----------------------------

    // ----------------------------------------- Setters ------------------------------

    public function setUdfJsonschemaId(string $id)
    {
        $this->setAttribute(self::UDF_JSONSCHEMA_ID, $id);
    }

    // -------------------------------------- End Setters -----------------------------
}
