<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\FundAccount;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Base\Traits\HasBalance;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use HasBalance;
    use SoftDeletes;

    protected $table = Table::PAYOUT_LINK;

    const ID                   = 'id';
    const CONTACT_ID           = 'contact_id';
    const CONTACT_NAME         = 'contact_name';
    const CONTACT_PHONE_NUMBER = 'contact_phone_number';
    const CONTACT_EMAIL        = 'contact_email';
    const FUND_ACCOUNT_ID      = 'fund_account_id';
    const BALANCE_ID           = 'balance_id';
    const SHORT_URL            = 'short_url';
    const MERCHANT_ID          = 'merchant_id';
    const USER_ID              = 'user_id';
    const BATCH_ID             = 'batch_id';
    const IDEMPOTENCY_KEY      = 'idempotency_key';
    const STATUS               = 'status';
    const AMOUNT               = 'amount';
    const NOTES                = 'notes';
    const PURPOSE              = 'purpose';
    const DESCRIPTION          = 'description';
    const RECEIPT              = 'receipt';
    const CURRENCY             = 'currency';
    const CANCELLED_AT         = 'cancelled_at';
    const CREATED_AT           = 'created_at';
    const UPDATED_AT           = 'updated_at';
    const CONTEXT              = 'context';
    const OTP                  = 'otp';
    const TOKEN                = 'token';
    const IMPS                 = 'IMPS';
    const NEFT                 = 'NEFT';
    const UPI                  = 'UPI';
    const ACCOUNT_TYPE         = 'account_type';
    const VPA                  = 'vpa';
    const BANK_ACCOUNT         = 'bank_account';
    const ACCOUNT_NUMBER       = 'account_number';
    const CONTACT              = 'contact';
    const NAME                 = 'name';
    const EMAIL                = 'email';
    const PHONE_NUMBER         = 'contact';


    protected $generateIdOnCreate = true;

    protected $entity = 'payout_link';

    protected static $sign = 'poutlk';

    protected $amounts = [
      self::AMOUNT
    ];

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::DESCRIPTION,
        self::PURPOSE,
        self::RECEIPT,
        self::NOTES,
        self::BALANCE_ID,
        self::CONTACT_NAME,
        self::CONTACT_EMAIL,
        self::CONTACT_PHONE_NUMBER,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::USER_ID,
        self::PURPOSE,
        self::CONTACT_ID,
        self::CONTACT_NAME,
        self::CONTACT_EMAIL,
        self::CONTACT_PHONE_NUMBER,
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

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CONTACT_ID,
        self::CONTACT,
        self::FUND_ACCOUNT_ID,
        self::PURPOSE,
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

    protected $publicSetters = [
        self::STATUS,
        self::ID,
        self::CONTACT_ID,
        self::FUND_ACCOUNT_ID,
        self::CONTACT
    ];

    protected $hosted = [
        self::ID,
        self::STATUS,
        self::AMOUNT,
        self::PURPOSE,
        self::CURRENCY,
        self::DESCRIPTION,
        self::RECEIPT,
        self::CANCELLED_AT
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::CANCELLED_AT
    ];

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    protected $defaults = [
        self::CONTACT_ID           => null,
        self::CONTACT_NAME         => '',
        self::CONTACT_EMAIL        => '',
        self::CONTACT_PHONE_NUMBER => '',
        self::FUND_ACCOUNT_ID      => null,
        self::SHORT_URL            => null,
        self::MERCHANT_ID          => null,
        self::USER_ID              => null,
        self::AMOUNT               => null,
        self::CURRENCY             => Currency::INR,
        self::DESCRIPTION          => null,
        self::RECEIPT              => null,
        self::NOTES                => [],
        self::CANCELLED_AT         => null,
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

    public function getReceipt()
    {
        return $this->getAttribute(self::RECEIPT);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getFundAccountId()
    {
        return $this->getAttribute(self::FUND_ACCOUNT_ID);
    }

    public function getPurpose()
    {
        return $this->getAttribute(self::PURPOSE);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getBalanceId()
    {
        return $this->getAttribute(self::BALANCE_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getContactId()
    {
        return $this->getAttribute(self::CONTACT_ID);
    }

    public function getContactName()
    {
        return $this->getAttribute(self::CONTACT_NAME);
    }

    public function getContactPhoneNumber()
    {
        return $this->getAttribute(self::CONTACT_PHONE_NUMBER);
    }

    public function getContactEmail()
    {
        return $this->getAttribute(self::CONTACT_EMAIL);
    }

    public function setPublicStatusAttribute(array & $attributes)
    {
        $internalStatus = $this->getAttribute(self::STATUS);

        $externalStatus = Status::getPublicStatusFromInternalStatus($internalStatus);

        $attributes[self::STATUS] = $externalStatus;
    }
    // -------------------------------------- End Getters -----------------------------

    // ----------------------------------------- Setters ------------------------------

    public function setShortUrl(string $shortUrl)
    {
        $this->setAttribute(ENTITY::SHORT_URL, $shortUrl);
    }

    public function setCancelledAt(string $timestamp)
    {
        $this->setAttribute(ENTITY::CANCELLED_AT, $timestamp);
    }

    public function setStatus($newStatus)
    {
        $currentStatus = $this->getStatus();

        if ($currentStatus === $newStatus)
        {
            return;
        }

        Status::validateStatusUpdate($newStatus, $currentStatus, $this->getId());

        $this->setAttribute(self::STATUS, $newStatus);
    }

    public function setPublicContactIdAttribute(array & $attributes)
    {
        $attributes[self::CONTACT_ID] = Contact\Entity::getSignedIdOrNull($attributes[self::CONTACT_ID]);
    }

    public function setPublicFundAccountIdAttribute(array & $attributes)
    {
        $attributes[self::FUND_ACCOUNT_ID] = FundAccount\Entity::getSignedIdOrNull($attributes[self::FUND_ACCOUNT_ID]);
    }

    public function setPublicContactAttribute(array & $attributes)
    {
        $attributes[self::CONTACT] = [
            self::NAME         => $this->getContactName(),
            self::EMAIL        => $this->getContactEmail(),
            self::PHONE_NUMBER => $this->getContactPhoneNumber(),
        ];
    }

    // -------------------------------------- End Setters -----------------------------
}
