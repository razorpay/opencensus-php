<?php

namespace RZP\Models\Application\ApplicationMerchantMaps;

use RZP\Models\Application;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    protected $entity = EntityConstants::APPLICATION_MERCHANT_MAPPING;
    protected $table  = Table::APPLICATION_MERCHANT_MAPPING;

    protected $generateIdOnCreate = true;

    const ID                     = 'id';
    const ID_LENGTH              = 14;
    const MERCHANT_ID            = 'merchant_id';
    const APP_ID                 = 'app_id';
    const ENABLED                = 'enabled';
    const USED_APP               = 'used_app';
    const SUGGESTED_APP          = 'suggested_app';
    const AVAILABLE_APPS         = 'available_apps';

    protected $fillable = [
        self::ID,
        self::MERCHANT_ID,
        self::APP_ID,
        self::ENABLED,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::APP_ID,
        self::ENABLED,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::APP_ID,
        self::ENABLED,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    // ============================= RELATIONS =============================

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function app()
    {
        return $this->belongsTo(Application\Entity::class);
    }

    // ============================= END RELATIONS =============================

    // ============================= GETTERS =============================

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getAppId()
    {
        return $this->getAttribute(self::APP_ID);
    }

    public function isEnabled()
    {
        return $this->getAttribute(self::ENABLED);
    }


    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setMerchantId($merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setAppId($appId)
    {
        $this->setAttribute(self::APP_ID, $appId);
    }

    public function setEnabled($enabled)
    {
        $this->setAttribute(self::ENABLED, $enabled);
    }

    // ============================= END SETTERS =============================
}
