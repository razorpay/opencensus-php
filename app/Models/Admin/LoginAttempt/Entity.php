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
        self::EMAIL,
        self::NAME,
        self::USERNAME,
        self::PASSWORD,
        self::REMEMBER_TOKEN,
        self::OAUTH_ACCESS_TOKEN,
        self::OAUTH_PROVIDER_ID,
        self::ORG_ID
    ];

    protected $visible = [
        self::EMAIL,
        self::NAME,
        self::USERNAME,
        self::REMEMBER_TOKEN,
        self::OAUTH_ACCESS_TOKEN,
        self::OAUTH_PROVIDER_ID,
        self::ORG_ID
    ];

    protected $public = [
        self::EMAIL,
        self::NAME,
        self::USERNAME,
        self::OAUTH_ACCESS_TOKEN,
        self::OAUTH_PROVIDER_ID,
        self::ORG_ID
    ];

    public function admin() {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }
}
