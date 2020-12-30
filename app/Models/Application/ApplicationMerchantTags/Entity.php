<?php

namespace RZP\Models\Application\ApplicationMerchantTags;

use RZP\Models\Application;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    protected $entity = EntityConstants::APPLICATION_MERCHANT_TAG;
    protected $table  = Table::APPLICATION_MERCHANT_TAG;

    protected $generateIdOnCreate = true;

    const ID                     = 'id';
    const ID_LENGTH              = 14;
    const MERCHANT_ID            = 'merchant_id';
    const TAG                    = 'tag';

    protected $fillable = [
        self::ID,
        self::MERCHANT_ID,
        self::TAG,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TAG,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::TAG,
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

    public function getTag()
    {
        return $this->getAttribute(self::TAG);
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setMerchantId($merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setTag($tag)
    {
        $this->setAttribute(self::TAG, $tag);
    }

    // ============================= END SETTERS =============================
}
