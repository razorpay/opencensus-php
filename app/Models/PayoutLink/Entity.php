<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\Merchant;
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
        return $this->hasOne(FundAccount\Entity::class);
    }

    public function contact()
    {
        return $this->hasOne(Contact\Entity::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout\Entity::class);
    }

    // -------------------------------------- End Relations ---------------------------

    // ----------------------------------------- Getters ------------------------------

    // -------------------------------------- End Getters -----------------------------

    // ----------------------------------------- Setters ------------------------------

    public function setStatus($status)
    {
        # validate status

        # set it using setAttribute
    }

    // -------------------------------------- End Setters -----------------------------
}
