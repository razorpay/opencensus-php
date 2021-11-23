<?php

namespace RZP\Models\QrCodeConfig;

use App;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    const ID                 = 'id';
    const MERCHANT_ID        = 'merchant_id';
    const CREATED_AT         = 'created_at';
    const DELETED_AT         = 'deleted_at';
    const UPDATED_AT         = 'updated_at';
    const CUT_OFF_TIME       = 'cut_off_time' ;

    protected $entity = 'qr_code_config';

    protected $fillable = [
        self::MERCHANT_ID,
        self::CREATED_AT,
        self::DELETED_AT,
        self::UPDATED_AT,
        self::CUT_OFF_TIME,
    ];

    protected $visible = [
        self::MERCHANT_ID,
        self::CREATED_AT,
        self::DELETED_AT,
        self::UPDATED_AT,
        self::CUT_OFF_TIME,
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function setDeletedAt($deleted_at)
    {
        $this->setAttribute($deleted_at);
    }

    public function getCutOffTime()
    {
        return $this->getAttribute(self::CUT_OFF_TIME);
    }
}
