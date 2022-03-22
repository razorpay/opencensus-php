<?php

namespace RZP\Models\Merchant\MerchantApplications;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const APPLICATION_ID = 'application_id';

    const TYPE = 'type';

    const REFERRED = 'referred';

    const MANAGED = 'managed';

    const OAUTH = 'oauth';

    // used for apps that are not under partnerships
    const MERCHANT = 'merchant';

    protected $entity = Constants\Entity::MERCHANT_APPLICATION;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        /**
         * Application id and type are fillable due to
         * application entries that come from external services
         */
        self::APPLICATION_ID,
        self::TYPE,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TYPE,
        self::APPLICATION_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT
    ];

    protected $public = [
        self::MERCHANT_ID,
        self::TYPE,
        self::APPLICATION_ID,
        self::CREATED_AT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function getApplicationId()
    {
        return $this->getAttribute(self::APPLICATION_ID);
    }

    public function getApplicationType()
    {
        return $this->getAttribute(self::TYPE);
    }
}
