<?php

namespace RZP\Models\Admin\Admin\Token;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Admin\Admin;

class Entity extends Base\PublicEntity
{
    const ADMIN_ID                  = 'admin_id';
    const TOKEN                     = 'token';
    const CREATED_AT                = 'created_at';
    const EXPIRES_AT                = 'expires_at';

    protected static $sign = 'token';

    protected $entity = 'admin_token';

    protected $fillable = [
        self::ADMIN_ID,
        self::TOKEN,
        self::EXPIRES_AT
    ];

    protected $visible = [
        self::ADMIN_ID,
        self::TOKEN,
        self::EXPIRES_AT
    ];

    protected $public = [
        self::ADMIN_ID,
        self::TOKEN,
        self::EXPIRES_AT
    ];

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }

}
