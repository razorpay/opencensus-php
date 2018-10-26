<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use SoftDeletes;

    const MERCHANT_ID        = 'merchant_id';
    const AMOUNT             = 'amount';
    const CURRENCY           = 'currency';
    const EXPIRE_BY          = 'expire_by';
    const TIMES_PAYABLE      = 'times_payable';
    const TIMES_PAID         = 'times_paid';
    const TOTAL_AMOUNT_PAID  = 'total_amount_paid';
    const STATUS             = 'status';
    const STATUS_REASON      = 'status_reason';
    const SHORT_URL          = 'short_url';
    const USER_ID            = 'user_id';
    const RECEIPT            = 'receipt';
    const TITLE              = 'title';
    const DESCRIPTION        = 'description';
    const NOTES              = 'notes';
    const SUPPORT_CONTACT    = 'support_contact';
    const SUPPORT_EMAIL      = 'support_email';
    const TERMS              = 'terms';

    /**
     * Optional attribute: allows a custom view template ID to be defined
     */
    const HOSTED_TEMPLATE_ID = 'hosted_template_id';

    /**
     * Optional attribute: allows a UDF JSON schema to be defined
     */
    const UDF_JSONSCHEMA_ID  = 'udf_jsonschema_id';

    //
    // Additional request input keys used in various other endpoint calls.
    // TODO: Move 'INPUT' to Base\Entity if possible.
    //
    const INPUT             = 'input';
    const CONTACTS          = 'contacts';
    const EMAILS            = 'emails';
    const CONTACT           = 'contact';
    const EMAIL             = 'email';
    const USER              = 'user';
    const SLUG              = 'slug';

    // Additional general usage input/output constants for the module
    const PAYMENT_ID         = 'payment_id';
    const FROM_STATUS        = 'from_status';
    const FROM_STATUS_REASON = 'from_status_reason';
    const TO_STATUS          = 'to_status';
    const TO_STATUS_REASON   = 'to_status_reason';
    const ERROR              = 'error';
    const REQUEST_PARAMS     = 'request_params';

    const CAPTURED_PAYMENTS_COUNT = 'captured_payments_count';

    // List of keys stored against entity's settings.
    const SETTINGS                     = 'settings';
    const THEME                        = 'theme';
    const UDF_SCHEMA                   = 'udf_schema';
    const UNITS                        = 'units';
    const ALLOW_MULTIPLE_UNITS         = 'allow_multiple_units';
    const ALLOW_SOCIAL_SHARE           = 'allow_social_share';
    const PAYMENT_SUCCESS_REDIRECT_URL = 'payment_success_redirect_url';
    const PAYMENT_SUCCESS_MESSAGE      = 'payment_success_message';

    const SETTINGS_KEYS                = [
        self::THEME,
        self::UDF_SCHEMA,
        self::ALLOW_MULTIPLE_UNITS,
        self::ALLOW_SOCIAL_SHARE,
        self::PAYMENT_SUCCESS_REDIRECT_URL,
        self::PAYMENT_SUCCESS_MESSAGE,
    ];

    /**
     * Merchant having below tags have specific behavior -
     * 1. Settings attribute is expected, if and only
     * 2. New view is rendered for existing or new pages
     */
    const TAG_PAYMENT_PAGE_V2 = 'paymentpagesv2';

    /**
     * expire_by has to be atleast 15 minutes from current timestamp
     */
    const MIN_EXPIRY_SECS = 900;

    protected static $sign        = 'pl';

    protected $entity             = 'payment_link';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::EXPIRE_BY,
        self::TIMES_PAYABLE,
        self::RECEIPT,
        self::TITLE,
        self::DESCRIPTION,
        self::NOTES,
        self::SUPPORT_CONTACT,
        self::SUPPORT_EMAIL,
        self::TERMS,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::EXPIRE_BY,
        self::TIMES_PAYABLE,
        self::TIMES_PAID,
        self::TOTAL_AMOUNT_PAID,
        self::STATUS,
        self::STATUS_REASON,
        self::SHORT_URL,
        self::USER_ID,
        self::RECEIPT,
        self::TITLE,
        self::DESCRIPTION,
        self::NOTES,
        self::SUPPORT_CONTACT,
        self::SUPPORT_EMAIL,
        self::TERMS,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::EXPIRE_BY,
        self::TIMES_PAYABLE,
        self::TIMES_PAID,
        self::TOTAL_AMOUNT_PAID,
        self::STATUS,
        self::STATUS_REASON,
        self::SHORT_URL,
        self::USER_ID,
        self::USER,
        self::RECEIPT,
        self::TITLE,
        self::DESCRIPTION,
        self::NOTES,
        self::SUPPORT_CONTACT,
        self::SUPPORT_EMAIL,
        self::TERMS,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $hosted = [
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::EXPIRE_BY,
        self::STATUS,
        self::SHORT_URL,
        self::RECEIPT,
        self::TITLE,
        self::DESCRIPTION,
        self::SUPPORT_CONTACT,
        self::SUPPORT_EMAIL,
        self::TERMS,
    ];

    protected $casts = [
        self::AMOUNT            => 'int',
        self::TIMES_PAYABLE     => 'int',
        self::TIMES_PAID        => 'int',
        self::TOTAL_AMOUNT_PAID => 'int',
    ];

    protected $dates = [
        self::EXPIRE_BY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $defaults = [
        self::AMOUNT             => null,
        self::CURRENCY           => null,
        self::EXPIRE_BY          => null,
        self::TIMES_PAYABLE      => null,
        self::TIMES_PAID         => 0,
        self::TOTAL_AMOUNT_PAID  => 0,
        self::STATUS             => Status::ACTIVE,
        self::STATUS_REASON      => null,
        self::DESCRIPTION        => null,
        self::NOTES              => [],
        self::HOSTED_TEMPLATE_ID => null,
        self::UDF_JSONSCHEMA_ID  => null,
        self::SUPPORT_CONTACT    => null,
        self::SUPPORT_EMAIL      => null,
        self::TERMS              => null,
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

    public function payments()
    {
        return $this->hasMany(Payment\Entity::class);
    }

    // -------------------------------------- End Relations ---------------------------

    // ----------------------------------------- Getters ------------------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
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
        return (($this->getTimesPayable() !== null) and
                ($this->getTimesPayable() === $this->getTimesPaid()));
    }

    /**
     * Checks if link in it's current state is payable or not.
     * @return boolean
     */
    public function isPayable(): bool
    {
        // Must be 'active' and expire_by must not be past now(CRON might yet to be mark it as expired, in that case)
        return (($this->isActive() === true) and
                ($this->isPastExpireBy() === false));
    }

    /**
     * Payment link's hosted view long url is one of the following formats:
     * - https://pages.razorpay.in/pl_10000000000000/view
     * - https://pages.razorpay.in/AlphaNumMin4Max20Slug
     * Notice the suffix in 1nd kind of route(when there is no slug), we need this distinction for routes to work.
     *
     * @param  string      $plHostedBaseUrl
     * @param  string|null $slug
     * @return string
     */
    public function getHostedViewUrl(string $plHostedBaseUrl, string $slug = null): string
    {
        return $plHostedBaseUrl . '/' . ($slug === null ? $this->getPublicId() . '/view' : $slug);
    }

    public function getCapturedPaymentsCount(): int
    {
        return $this->payments()->whereNotNull(Payment\Entity::CAPTURED_AT)->count();
    }

    /**
     * Enhancement: Have a trait HasSettings which has nice and optimal(i.e. no
     * multiple queries on subsequent calls) interface.
     *
     * Get settings associated with payment link entity.
     *
     * @param  string|null $key
     * @return \Razorpay\Spine\DataTypes\Dictionary|string
     */
    public function getSettings(string $key = null)
    {
        $accessor = $this->getSettingsAccessor();

        return $key === null ? $accessor->all() : $accessor->get($key);
    }

    /**
     * Used when expecting either a scalar string value against settings key or null(instead of Dictionary).
     * In case of 'null', above call returns instance of Dictionary for some reason.
     *
     * @param  string $key
     * @return string|null
     */
    public function getSettingsScalarElseNull(string $key)
    {
        $resp = $this->getSettings($key);

        return (is_string($resp) === true) ? $resp : null;
    }

    public function getSettingsAccessor(): Settings\Accessor
    {
        return Settings\Accessor::for($this, Settings\Module::PAYMENT_LINK);
    }

    // -------------------------------------- End Getters -----------------------------

    // ----------------------------------------- Setters ------------------------------

    public function setStatus(string $status)
    {
        Status::checkStatus($status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setStatusReason(string $statusReason = null)
    {
        if ($statusReason !== null)
        {
            StatusReason::checkStatusReason($statusReason);
        }

        $this->setAttribute(self::STATUS_REASON, $statusReason);
    }

    public function setShortUrl(string $url)
    {
        $this->setAttribute(self::SHORT_URL, $url);
    }

    public function incrementTimesPaidBy(int $incrementValue)
    {
        $this->setAttribute(self::TIMES_PAID, ($this->getTimesPaid() + $incrementValue));
    }

    public function incrementTotalAmountPaidBy(int $incrementValue)
    {
        $this->setAttribute(self::TOTAL_AMOUNT_PAID, ($this->getTotalAmountPaid() + $incrementValue));
    }

    public function setUdfJsonschemaId(string $id)
    {
        $this->setAttribute(self::UDF_JSONSCHEMA_ID, $id);
    }

    // -------------------------------------- End Setters -----------------------------
}
