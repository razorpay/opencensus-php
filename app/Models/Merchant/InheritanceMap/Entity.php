<?php

namespace RZP\Models\Merchant\InheritanceMap;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const MERCHANT_ID          = 'merchant_id';

    const PARENT_MERCHANT_ID   = 'parent_merchant_id';

    const IDEMPOTENCY_KEY      = 'idempotency_key';
    const SUCCESS              = 'success';

    protected $entity = Constants\Entity::MERCHANT_INHERITANCE_MAP;

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::PARENT_MERCHANT_ID,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::PARENT_MERCHANT_ID,
    ];

    public function getParentMerchantId()
    {
        return $this->getAttribute(self::PARENT_MERCHANT_ID);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function parentMerchant()
    {
        return $this->belongsTo(Merchant\Entity::class, self::PARENT_MERCHANT_ID);
    }

}
