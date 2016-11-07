<?php

namespace RZP\Models\Admin\Org;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Admin\Admin;

class Entity extends Base\PublicEntity
{
    const AUTH_TYPE             = 'auth_type';
    const BUSINESS_NAME         = 'business_name';
    const DISPLAY_NAME          = 'display_name';
    const EMAIL                 = 'email';
    const EMAIL_DOMAINS         = 'email_domains';
    const DELETED_AT            = 'deleted_at';
    const LOGO_URL              = 'logo_url';

    protected static $sign = 'org';

    protected $entity = 'org';

    protected $table = Table::ORG;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::DISPLAY_NAME,
        self::BUSINESS_NAME,
        self::EMAIL,
        self::AUTH_TYPE,
        self::EMAIL_DOMAINS,
        self::LOGO_URL,
    ];

    protected $visible = [
        self::ID,
        self::DISPLAY_NAME,
        self::BUSINESS_NAME,
        self::EMAIL,
        self::AUTH_TYPE,
        self::EMAIL_DOMAINS,
        self::LOGO_URL,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::DISPLAY_NAME,
        self::BUSINESS_NAME,
        self::EMAIL,
        self::EMAIL_DOMAINS,
        self::LOGO_URL,
        self::AUTH_TYPE
    ];

    protected $defaults = [
    ];

    protected $guarded = [self::ID];

    public function owners()
    {
        // Returns the list of org's owners
    }

    public function admins()
    {
        return $this->hasMany('Admin\Entity');
    }

    public function roles()
    {
        return $this->hasMany('RZP\Models\Admin\Admin\Entity');
    }

    public function permissions()
    {
        return $this->morphedToMany('RZP\Models\Admin\Permission\Entity', 'entity', Table::PERMISSON_MAP);
    }
}
