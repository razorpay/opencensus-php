<?php

namespace RZP\Models\Application\ApplicationTags;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Base\Traits\HardDeletes;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    use HardDeletes;

    protected $entity = EntityConstants::APPLICATION_MAPPING;
    protected $table  = Table::APPLICATION_MAPPING;

    protected $generateIdOnCreate = true;

    const ID                     = 'id';
    const ID_LENGTH              = 14;
    const TAG                    = 'tag';
    const APP_ID                 = 'app_id';
    const LIST                   = 'list';

    protected $fillable = [
        self::ID,
        self::TAG,
        self::APP_ID,
    ];

    protected $visible = [
        self::ID,
        self::TAG,
        self::APP_ID,
    ];

    protected $public = [
        self::ID,
        self::TAG,
        self::APP_ID,
        self::MERCHANT_ID,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    // ============================= GETTERS =============================

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getTag()
    {
        return $this->getAttribute(self::TAG);
    }

    public function getAppId()
    {
        return $this->getAttribute(self::APP_ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setTag($tag)
    {
        $this->setAttribute(self::TAG, $tag);
    }

    public function setAppId($appId)
    {
        $this->setAttribute(self::APP_ID, $appId);
    }

    // ============================= END SETTERS =============================
}
