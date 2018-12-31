<?php

namespace RZP\Models\Merchant\AccessMap;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ENTITY_TYPE = 'entity_type';

    const ENTITY_ID   = 'entity_id';

    const APPLICATION = 'application';

    const APPLICATION_ID = 'application_id';

    const PARTNER_ID = 'partner_id';

    protected $entity = Constants\Entity::MERCHANT_ACCESS_MAP;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        /**
         * Entity id and type are fillable due to
         * application entries that come from external services
         */
        self::ENTITY_ID,
        self::ENTITY_TYPE,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::PARTNER_ID,
    ];

    protected $public = [
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::PARTNER_ID,
        self::CREATED_AT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    // --------------- Relation to other entities ------------------------------

    public function entity()
    {
        return $this->morphTo();
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function partnerMerchant()
    {
        return $this->belongsTo(Merchant\Entity::class, self::PARTNER_ID);
    }
}
