<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\FundAccount;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\Traits\NotesTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use SoftDeletes;

    const ID              = 'id';
    const CONTACT_ID      = 'contact_id';
    const FUND_ACCOUNT_ID = 'fund_account_id';
    const SHORT_URL       = 'short_url';
    const MERCHANT_ID     = 'merchant_id';
    const USER_ID         = 'user_id';
    const BATCH_ID        = 'batch_id';
    const IDEMPOTENCY_KEY = 'idempotency_key';
    const STATUS          = 'status';
    const AMOUNT          = 'amount';
    const NOTES           = 'notes';
    const DESCRIPTION     = 'description';
    const RECEIPT         = 'receipt';
    const CURRENCY        = 'currency';
    const CANCELLED_AT    = 'cancelled_at';
    const CREATED_AT      = 'created_at';
    const UPDATED_AT      = 'updated_at';

    protected $generateIdOnCreate = true;

    protected $entity = 'payout_link';

    protected static $sign = 'plnk';

    protected $amounts = [
      self::AMOUNT
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::CANCELLED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CONTACT_ID,
        self::FUND_ACCOUNT_ID,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::DESCRIPTION,
        self::RECEIPT,
        self::NOTES,
        self::SHORT_URL,
        self::CREATED_AT,
        self::CANCELLED_AT
    ];

    protected $fillable = [
        self::ID,
        self::SHORT_URL,
        self::AMOUNT,
        self::CURRENCY,
        self::DESCRIPTION,
        self::RECEIPT,
        self::NOTES,
        self::SHORT_URL,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::USER_ID,
        self::CONTACT_ID,
        self::FUND_ACCOUNT_ID,
        self::SHORT_URL,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::DESCRIPTION,
        self::RECEIPT,
        self::NOTES,
        self::SHORT_URL,
        self::CANCELLED_AT,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $hosted = [
        self::ID,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::DESCRIPTION,
        self::RECEIPT,
        self::CANCELLED_AT
    ];

    protected $publicAuth = [
        self::ID,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::DESCRIPTION,
        self::RECEIPT,
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
        self::CURRENCY              => Currency::INR,
        self::DESCRIPTION           => null,
        self::RECEIPT               => null,
        self::NOTES                 => [],
        self::CANCELLED_AT          => null,
    ];

    protected $table  = Table::PAYOUT_LINK;

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
        return $this->hasmany(Payout\Entity::class);
    }

    // -------------------------------------- End Relations ---------------------------

    // ----------------------------------------- Getters ------------------------------

    // -------------------------------------- End Getters -----------------------------

    // ----------------------------------------- Setters ------------------------------

    public function setShortUrl($shortUrl)
    {
        $this->setAttribute(ENTITY::SHORT_URL, $shortUrl);
    }

    // -------------------------------------- End Setters -----------------------------
}
