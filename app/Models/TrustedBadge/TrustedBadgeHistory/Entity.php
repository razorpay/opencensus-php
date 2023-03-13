<?php

namespace RZP\Models\TrustedBadge\TrustedBadgeHistory;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant\Entity as Merchant;

/**
 * @property string $id              The Primary Key of this TrustedBadgeHistory Entity
 * @property string $merchant_id     The Primary Key of the Merchant
 * @property string $merchant_status The Merchant Controlled Status of RTB (optin/optout/waitlist)
 * @property string $status          The Razorpay Controlled Status of RTB (eligible/ineligible/blacklist)
 * @property Carbon $created_at      The Created At Unix Timestamp
 * @property Merchant $merchant
 */
class Entity extends Base\PublicEntity
{
    const MERCHANT_ID       = 'merchant_id';
    const STATUS            = 'status';
    const MERCHANT_STATUS   = 'merchant_status';

    const STATUS_LENGTH          = 30;
    const MERCHANT_STATUS_LENGTH = 30;

    protected $entity      = 'trusted_badge_history';

    // merchant status constants
    const OPTIN         = 'optin';
    const OPTOUT        = 'optout';
    const WAITLIST      = 'waitlist';

    // status constants
    const ELIGIBLE      = 'eligible';
    const INELIGIBLE    = 'ineligible';
    const BLACKLIST     = 'blacklist';
    const WHITELIST     = 'whitelist';

    protected $fillable = [
        self::MERCHANT_ID,
        self::STATUS,
        self::MERCHANT_STATUS,
    ];

    protected $public = [
        self::ENTITY,
        self::MERCHANT_ID,
        self::STATUS,
        self::CREATED_AT,
    ];

    protected $defaults = [
        self::STATUS            => self::INELIGIBLE,
        self::MERCHANT_STATUS   => '',
    ];

    protected $dates = [
        self::CREATED_AT,
    ];

    // Added this to prevent errors on absence of updated_at field
    const UPDATED_AT = null;

    public function build(array $input = [], string $operation = 'create')
    {
        $this->getValidator()->validateInput($operation, $input);

        $this->generate($input);

        $this->fillAndGenerateId($input);

        return $this;
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
