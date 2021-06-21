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
        if (array_key_exists($group, GroupType::GROUP_TYPE_MAP )) {
            if (in_array($type, GroupType::GROUP_TYPE_MAP[$group], true)) {
                return true;
            }
        }
        return false;
    }
}
