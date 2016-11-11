<?php

namespace RZP\Models\Admin\Admin\Token;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ADMIN_ID   = 'admin_id';
    const TOKEN      = 'token';
    const EXPIRES_AT = 'expires_at';

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
        self::EXPIRES_AT,
        self::CREATED_AT,
        self::UPDATED_AT
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

    public function getAdminId()
    {
        return $this->getAttribute(self::ADMIN_ID);
    }

    public function getToken()
    {
        return $this->getAttribute(self::TOKEN);
    }
}
