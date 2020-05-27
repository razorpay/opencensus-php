<?php


namespace RZP\Models\Merchant\Attribute;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const PRODUCT           = 'product';
    const GROUP             = 'group';
    const TYPE              = 'type';
    const VALUE             = 'value';

    // Groups
    const ONBOARDING        = 'onboarding';

    // Types
    const MERCHANT_ONBOARDING_CATEGORY = 'merchant_onboarding_category';

    // Values
    const SELF_SERVE        = 'self_serve';
    const NORMAL            = 'normal';

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
}
