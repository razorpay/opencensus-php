<?php

namespace RZP\Models\TrustedBadge;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant\Entity as Merchant;

/**
 * @property string $merchant_id     The Primary Key of the Merchant (Also Primary Key of this Entity Table)
 * @property string $merchant_status The Merchant Controlled Status of RTB (optin/optout/waitlist)
 * @property string $status          The Razorpay Controlled Status of RTB (eligible/ineligible/blacklist)
 * @property Carbon $created_at      The Created At Unix Timestamp
 * @property Carbon $updated_at      The Updated At Unix Timestamp
 * @property Merchant $merchant
 */
class Entity extends Base\PublicEntity
{
    const MERCHANT_ID       = 'merchant_id';
    const STATUS            = 'status';
    const MERCHANT_STATUS   = 'merchant_status';

    protected $entity      = 'trusted_badge';

    const STATUS_LENGTH          = 30;
    const MERCHANT_STATUS_LENGTH = 30;

    // merchant status constants
    const OPTIN         = 'optin';
    const OPTOUT        = 'optout';
    const WAITLIST      = 'waitlist';

    // status constants
    const ELIGIBLE      = 'eligible';
    const INELIGIBLE    = 'ineligible';
    const BLACKLIST     = 'blacklist';
    const WHITELIST     = 'whitelist';

    // eligibility check constants
    public const STANDARD_CHECKOUT_ELIGIBLE                = 'standardCheckoutEligible';
    public const IS_DMT_MERCHANT                           = 'isDmtMerchant';
    public const IS_DISPUTE_MERCHANT                       = 'isDisputedMerchant';
    public const LOW_TRANSACTING_BUT_RTB_ELIGIBLE_MERCHANT = 'lowTransactingButRTBEligibleMerchant';
    public const HIGH_TRANSACTING_VOLUME_MERCHANT          = 'highTransactingVolumeMerchant';
    public const IS_RISK_MERCHANT                          = 'isRiskMerchant';

    const REDIS_EXPERIMENT_KEY = 'RTB_experiment_merchants';

    protected $fillable = [
        self::MERCHANT_ID,
        self::STATUS,
        self::MERCHANT_STATUS,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::STATUS,
        self::MERCHANT_STATUS,
    ];

    protected $defaults = [
        self::STATUS            => self::INELIGIBLE,
        self::MERCHANT_STATUS   => '',
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function build(array $input = [], string $operation = 'create')
    {
        $this->getValidator()->validateInput($operation, $input);

        $this->generate($input);

        $this->fill($input);

        return $this;
    }

    // ----------------------- Getters ---------------------------------------------

    public function getStatus(): string
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getMerchantStatus(): string
    {
        return $this->getAttribute(self::MERCHANT_STATUS);
    }

    // ----------------------- Setters ---------------------------------------------

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setMerchantStatus(string $merchantStatus)
    {
        $this->setAttribute(self::MERCHANT_STATUS, $merchantStatus);
    }

    public  function setMerchantId(string $merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function isLive(): bool
    {
        return (
            ($this->status === self::ELIGIBLE || $this->status === self::WHITELIST) &&
            $this->merchant_status !== self::OPTOUT
        );
    }
}
