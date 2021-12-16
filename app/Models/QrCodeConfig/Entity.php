<?php

namespace RZP\Models\QrCodeConfig;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    const ID                 = 'id';
    const MERCHANT_ID        = 'merchant_id';
    const CREATED_AT         = 'created_at';
    const DELETED_AT         = 'deleted_at';
    const UPDATED_AT         = 'updated_at';
    const KEY                = 'config_key';
    const VALUE              = 'config_value';

    protected $entity = Constants\Entity::QR_CODE_CONFIG;

    protected $generateIdOnCreate = true;

    protected static $sign = 'qcc';

    protected $fillable = [
        self::MERCHANT_ID,
        self::CREATED_AT,
        self::DELETED_AT,
        self::UPDATED_AT,
        self::KEY,
        self::VALUE,
    ];

    protected $visible = [
        self::MERCHANT_ID,
        self::CREATED_AT,
        self::DELETED_AT,
        self::UPDATED_AT,
        self::KEY,
        self::VALUE,
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function setDeletedAt($deleted_at)
    {
        $this->setAttribute(self::DELETED_AT, $deleted_at);
    }

    public function getValue()
    {
        return $this->getAttribute(self::VALUE);
    }
}
