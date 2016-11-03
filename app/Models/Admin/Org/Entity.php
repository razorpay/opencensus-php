<?php

namespace RZP\Models\Admin\Org;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const AUTH                  = 'auth';
    const BUSINESS_NAME         = 'business_name';
    const DISPLAY_NAME          = 'display_name';
    const EMAIL                 = 'email';
    const EMAIL_DOMAINS         = 'email_domains';
    const DELETED_AT            = 'deleted_at';
    const LOGO                  = 'logo';

    protected static $sign = 'org';

    protected $entity = 'org';

    protected $table = Table::ORG;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NAME,
        self::EMAIL,
        self::AUTH,
        self::EMAIL_DOMAINS,
        self::LOGO,
    ];

    protected $visible = [
        self::NAME,
        self::EMAIL,
        self::AUTH,
        self::EMAIL_DOMAINS,
        self::LOGO,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::EMAIL,
        self::EMAIL_DOMAINS,
        self::LOGO,
        self::AUTH,
        self::CREATED_AT,
    ];

    protected $defaults = [
        self::EMAIL_DOMAINS => [],
        self::AUTH => null,
    ];

    protected $guarded = [self::ID];

    public function owners()
    {
        // Returns the list of org's owners
    }
}
