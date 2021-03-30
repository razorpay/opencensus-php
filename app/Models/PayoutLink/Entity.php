<?php

namespace RZP\Models\PayoutLink;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\FundAccount;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Base\Traits\HasBalance;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Merchant\Entity        $merchant
 * @property User\Entity            $user
 * @property FundAccount\Entity     $fundAccount
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use HasBalance;
    use SoftDeletes;

    const PAYOUT_ID             = 'payout_id';
    const PAYOUT                = 'payout';
    const TIMELINE              = 'timeline';
    const API_POUT_LNK_SRC      = 'api.pout_l';
    const SOURCE                = 'source';
    const TEMPLATE              = 'template';
    const PARAMS                = 'params';
    const RECEIVER              = 'receiver';
    const PAYOUTS               = 'payouts';
    const BRANDING_COMPLETED    = 'branding_completed';
    const ATTEMPTED_LINKS_COUNT = 'attempted_links_count';
    const LINK_PROCESSED        = 'link_processed';
    const TOTAL_COUNT           = 'total_count';
    const ISSUED_LINKS_COUNT    = 'issued_links_count';
    const LINK_CREATED          = 'link_created';

    protected $table = Table::PAYOUT_LINK;

    // Payout Link Columns
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
    // This purpose will be used in creating payouts. So validation will be same as that on Payout Purpose
    const PURPOSE              = 'purpose';
    // This description is text that the merchant wants to add while creating payout-link id.
    // This will be shown to the customer while entering bank account details
    const DESCRIPTION          = 'description';
    const RECEIPT              = 'receipt';
    const CURRENCY             = 'currency';
    const CANCELLED_AT         = 'cancelled_at';
    const CREATED_AT           = 'created_at';
    const UPDATED_AT           = 'updated_at';
    const ATTEMPT_COUNT        = 'attempt_count';

    // Strings used in Core / Validators
    const CONTEXT              = 'context';
    const OTP                  = 'otp';
    const TOKEN                = 'token';
    const IMPS                 = 'IMPS';
    const NEFT                 = 'NEFT';
    const UPI                  = 'UPI';
    const AMAZON_PAY           = "AMAZONPAY";
    const SUPPORT_URL          = 'support_url';
    const SUPPORT_CONTACT      = 'support_contact';
    const SUPPORT_EMAIL        = 'support_email';
    const CUSTOM_MESSAGE       = 'payout_links_custom_message';
    const TICKET_ID            = 'ticket_id';
    const ACCOUNT_TYPE         = 'account_type';
    const VPA                  = 'vpa';
    const BANK_ACCOUNT         = 'bank_account';
    const ACCOUNT_NUMBER       = 'account_number';
    const CONTACT              = 'contact';
    const NAME                 = 'name';
    const EMAIL                = 'email';
    const PHONE_NUMBER         = 'contact';
    const SEND_SMS             = 'send_sms';
    const SEND_EMAIL           = 'send_email';

    const USER                 = 'user';

    const MAX_PAYOUT_LIMIT     = Payout\Entity::MAX_PAYOUT_LIMIT;
    const MERCHANT_NAME        = 'merchant_name';
    const PAYOUT_PURPOSE       = 'payout_purpose';
    const CUSTOMER_NAME        = 'customer_name';
    const SUCCESS              = 'success';
    const OK                   = 'OK';
    const TO_EMAIL             = 'to_email';

    protected $generateIdOnCreate = true;

    protected $entity = 'payout_link';

    protected static $sign = 'poutlk';

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
        self::SEND_EMAIL,
        self::SEND_SMS,
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
        self::SEND_SMS,
        self::SEND_EMAIL,
        self::USER,
        self::CANCELLED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::PAYOUTS
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::PAYOUTS,
        self::CONTACT_ID,
        self::CONTACT,
        self::FUND_ACCOUNT_ID,
        self::PURPOSE,
        self::STATUS,
        self::AMOUNT,
        self::USER,
        self::CURRENCY,
        self::DESCRIPTION,
        self::ATTEMPT_COUNT,
        self::RECEIPT,
        self::NOTES,
        self::USER_ID,
        self::SHORT_URL,
        self::SEND_SMS,
        self::SEND_EMAIL,
        self::CANCELLED_AT,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::STATUS,
        self::ENTITY,
        self::ID,
        self::CONTACT_ID,
        self::FUND_ACCOUNT_ID,
        self::USER_ID,
        self::USER,
        self::PAYOUTS,
        self::CONTACT,
        self::ATTEMPT_COUNT
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

    protected $amounts = [
      self::AMOUNT
    ];

    protected $casts = [
        self::AMOUNT => 'int',
        self::SEND_SMS => 'bool',
        self::SEND_EMAIL => 'bool',
    ];

    protected $defaults = [
        self::CONTACT_NAME         => null,
        self::CONTACT_EMAIL        => null,
        self::CONTACT_PHONE_NUMBER => null,
        self::FUND_ACCOUNT_ID      => null,
        self::SHORT_URL            => null,
        self::USER_ID              => null,
        self::CURRENCY             => Currency::INR,
        self::DESCRIPTION          => null,
        self::RECEIPT              => null,
        self::NOTES                => [],
        self::CANCELLED_AT         => null,
        self::SEND_EMAIL           => false,
        self::SEND_SMS             => false
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
    /**
     * There can be multiple payouts associated with a Payout Link
     * We fetch the latest payout associated with it.
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     */
    public function payout()
    {
        return $this->payouts()->orderBy(Entity::CREATED_AT, 'desc')->first();
    }

    public function getReceipt()
    {
        return $this->getAttribute(self::RECEIPT);
    }

    public function getCancelledAt()
    {
        return $this->getAttribute(self::CANCELLED_AT);
    }

    public function getCreatedAt()
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getFormattedAmount()
    {
        $amount = $this->getAttribute(self::AMOUNT);

        $amount = (float) sprintf('%0.2f', ((int) $amount / 100));

        return $amount;
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

    public function getShortUrl()
    {
        return $this->getAttribute(self::SHORT_URL);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    // to handle payouts creation
    public function getTrimmedDescription()
    {
        $description = $this->getAttribute(self::DESCRIPTION);

        $description = trim(preg_replace("/[^A-Za-z0-9]+/"," ", $description));

        return substr($description, 0, 30);
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

    public function getSendSms()
    {
        return $this->getAttribute(self::SEND_SMS);
    }

    public function getSendEmail()
    {
        return $this->getAttribute(self::SEND_EMAIL);
    }

    public function getPayoutUtr()
    {
        if ($this->payout() !== null)
        {
            return $this->payout()->getUtr();
        }
    }

    public function getPayoutMode()
    {
        if ($this->payout() !== null)
        {
            return $this->payout()->getMode();
        }
    }

    // -------------------------------------- End Getters -----------------------------

    // ----------------------------------------- Setters ------------------------------

    public function setShortUrl(string $shortUrl)
    {
        $this->setAttribute(self::SHORT_URL, $shortUrl);
    }

    public function setPayout()
    {
        $payout_array = $this->payout() != null ? array($this->payout()) : array();

        $this->setAttribute(self::PAYOUTS,$this->newCollection($payout_array)->toArrayPublic());
    }

    public function setContactPhoneNumber(string $phoneNumber)
    {
        $this->setAttribute(self::CONTACT_PHONE_NUMBER, $phoneNumber);
    }

    public function setContactEmail(string $email)
    {
        $this->setAttribute(self::CONTACT_EMAIL, $email);
    }

    public function setSendSms(bool $sendSms)
    {
        $this->setAttribute(self::SEND_SMS, $sendSms);
    }

    public function setSendEmail(bool $sendEmail)
    {
        $this->setAttribute(self::SEND_EMAIL, $sendEmail);
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

        if ($newStatus === Status::CANCELLED)
        {
            $currentTime = Carbon::now()->getTimestamp();

            $this->setAttribute(self::CANCELLED_AT, $currentTime);
        }
    }

    public function setDescription(string $description)
    {
        $this->setAttribute(self::DESCRIPTION, $description);
    }

    // -------------------------------------- End Setters -----------------------------

    // ----------------------------------------- Mutators ------------------------------

    public function setPublicStatusAttribute(array & $attributes)
    {
        $internalStatus = $this->getAttribute(self::STATUS);

        $externalStatus = Status::getPublicStatusFromInternalStatus($internalStatus);

        $attributes[self::STATUS] = $externalStatus;
    }

    public function setPublicContactIdAttribute(array & $attributes)
    {
        $attributes[self::CONTACT_ID] = Contact\Entity::getSignedIdOrNull($attributes[self::CONTACT_ID]);
    }

    public function setPublicFundAccountIdAttribute(array & $attributes)
    {
        $attributes[self::FUND_ACCOUNT_ID] = FundAccount\Entity::getSignedIdOrNull($attributes[self::FUND_ACCOUNT_ID]);
    }

    public function setPublicPayoutsAttribute(array & $attributes)
    {
        //
        // We never want to expose Payouts on private.
        // The correct way to do this would be to not add it in $public array.
        // But, we want to expose it in proxy auth (via expands). Hence, we
        // cannot remove it from $public array.
        // It's possible that the payouts is loaded in some flow. This check
        // ensures that it's always removed before sending out the response.
        //
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            array_forget($attributes, self::PAYOUTS);

            return;
        }
    }

    public function setPublicUserAttribute(array & $attributes)
    {
        //
        // We never want to expose User on private.
        // The correct way to do this would be to not add it in $public array.
        // But, we want to expose it in proxy auth (via expands). Hence, we
        // cannot remove it from $public array.
        // It's possible that the user is loaded in some flow. This check
        // ensures that it's always removed before sending out the response.
        //
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            array_forget($attributes, self::USER);

            return;
        }
    }

    public function setPublicUserIdAttribute(array & $attributes)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        if ($basicAuth->isStrictPrivateAuth() === true)
        {
            unset($attributes[self::USER_ID]);
        }
    }

    public function setPublicContactAttribute(array & $attributes)
    {
        $attributes[self::CONTACT] = [
            self::NAME         => $this->getContactName(),
            self::EMAIL        => $this->getContactEmail(),
            self::PHONE_NUMBER => $this->getContactPhoneNumber(),
        ];
    }

    public function setPublicAttemptCountAttribute(array & $attributes)
    {
        $attributes[self::ATTEMPT_COUNT] = $this->payouts()->count();
    }

    // -------------------------------------- End Mutators -----------------------------

    public function shouldSendSms()
    {
        return boolval($this->getSendSms());
    }

    public function shouldSendEmail()
    {
        return boolval($this->getSendEmail());
    }
}
