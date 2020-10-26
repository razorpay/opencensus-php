<?php


namespace RZP\Models\Merchant\Attribute;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    const ID                            = 'id';
    const MERCHANT_ID                   = 'merchant_id';
    const PRODUCT                       = 'product';
    const GROUP                         = 'group';
    const TYPE                          = 'type';
    const VALUE                         = 'value';

    // Groups
    const ONBOARDING                    = 'onboarding';
    const X_MERCHANT_PREFERENCES        = 'x_merchant_preferences';

    // Types
    // ONBOARDING Types
    const MERCHANT_ONBOARDING_CATEGORY  = 'merchant_onboarding_category';
    // PREFERENCES Types
    const BUSINESS_CATEGORY             = 'business_category';
    const TEAM_SIZE                     = 'team_size';
    const MONTHLY_PAYOUT_COUNT          = 'monthly_payout_count';
    // MERCHANT_ONBOARDING_CATEGORY Values
    const SELF_SERVE                    = 'self_serve';
    const NORMAL                        = 'normal';

    const GROUP_TYPE_MAP = [
        Self::ONBOARDING => [
            Self::MERCHANT_ONBOARDING_CATEGORY
        ],

        Self::X_MERCHANT_PREFERENCES => [
            Self::BUSINESS_CATEGORY,
            Self::TEAM_SIZE,
            Self::MONTHLY_PAYOUT_COUNT
        ]
    ];
    protected $entity = 'merchant_attribute';

    protected $table  = Table::MERCHANT_ATTRIBUTE;

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID
    ];

    protected $fillable = [
        self::MERCHANT_ID,
        self::PRODUCT,
        self::GROUP,
        self::TYPE,
        self::VALUE,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::PRODUCT,
        self::GROUP,
        self::TYPE,
        self::VALUE,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function getValue()
    {
        return $this->getAttributeValue(self::VALUE);
    }

    public function getProduct()
    {
        return $this->getAttributeValue(self::PRODUCT);
    }

    public function setValue(string $value)
    {
        $this->setAttribute(self::VALUE, $value);
    }

    public static function isValidGroupAndType(string $group, string $type): bool {
        if (array_key_exists($group, self::GROUP_TYPE_MAP )) {
            if (in_array($type, self::GROUP_TYPE_MAP[$group], true)) {
                return true;
            }
        }
        return false;
    }
}
