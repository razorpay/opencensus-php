<?php

namespace RZP\Models\Admin\Organization;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const NAME                  = 'name';
    const EMAIL                 = 'email';
    const AUTH                  = 'auth';
    const EMAIL_DOMAINS         = 'email_domains';
    const DELETED_AT            = 'deleted_at';
    const LOGO_URL              = 'logo_url';

    protected static $sign = 'org';

    protected $entity = 'organization';

    protected $table = Table::ORGANIZATION;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NAME,
        self::EMAIL,
        self::AUTH,
        self::EMAIL_DOMAINS,
        self::LOGO_URL,
    ];

    protected $visible = [
        self::NAME,
        self::EMAIL,
        self::AUTH,
        self::EMAIL_DOMAINS,
        self::LOGO_URL,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::EMAIL,
        self::EMAIL_DOMAINS,
        self::LOGO_URL,
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
