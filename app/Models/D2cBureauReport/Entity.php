<?php

namespace RZP\Models\D2cBureauReport;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    protected $generateIdOnCreate = true;

    protected static $sign = 'd2c';

    protected $entity = 'd2c_bureau_report';

    const ID_LENGTH = 14;

    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const USER_ID               = 'user_id';
    const D2C_BUREAU_DETAIL_ID  = 'd2c_bureau_detail_id';
    const PROVIDER              = 'provider';
    const SCORE                 = 'score';
    const REPORT                = 'report';
    const UFH_FILE_ID           = 'ufh_file_id';
    const INTERESTED            = 'interested';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';

    protected $public = [
        self::ID,
        self::PROVIDER,
        self::SCORE,
        self::REPORT,
        self::CREATED_AT,
    ];

    protected $fillable = [
        self::ID,
        self::PROVIDER,
        self::SCORE,
        self::REPORT,
        self::UFH_FILE_ID,
        self::CREATED_AT,
    ];

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }

    public function user()
    {
        return $this->belongsTo(\RZP\Models\User\Entity::class);
    }

    public function d2cBureauDetail()
    {
        return $this->belongsTo(\RZP\Models\D2cBureauDetail\Entity::class);
    }
}
