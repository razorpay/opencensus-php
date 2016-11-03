<?php

namespace RZP\Models\Admin\Org;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;

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
        self::DISPLAY_NAME,
        self::BUSINESS_NAME,
        self::EMAIL,
        self::EMAIL_DOMAINS,
        self::LOGO_URL,
        self::AUTH_TYPE,
        self::CREATED_AT,
    ];

    protected $defaults = [
    ];

    protected $guarded = [self::ID];

    public function owners()
    {
        // Returns the list of org's owners
    }
}
