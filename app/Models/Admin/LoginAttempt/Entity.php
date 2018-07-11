<?php

namespace RZP\Models\Admin\LoginAttempt;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ADMIN_ID                  = 'admin_id';
    const VALID                     = 'valid';
    const USER_AGENT                = 'user_agent';
    const IP_ADDRESS                = 'ip_address';

    protected static $sign = 'login_attempt';

    protected $entity = 'login_attempt';

    protected $table = Table::LOGIN_ATTEMPT;

    protected $fillable = [
        self::VALID,
        self::USER_AGENT,
        self::IP_ADDRESS
    ];

    protected $visible = [
        self::ADMIN_ID,
        self::VALID,
        self::USER_AGENT,
        self::IP_ADDRESS
    ];

    protected $public = [
        self::ADMIN_ID,
        self::VALID,
        self::USER_AGENT,
        self::IP_ADDRESS
    ];

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }
}
