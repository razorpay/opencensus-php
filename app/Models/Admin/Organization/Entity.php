<?php

namespace RZP\Models\Admin\Organization;

use App;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Address;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    const NAME                  = 'name';
    const OWNER_ID              = 'owner_id';
    const EMAIL                 = 'email';
    const AUTH_TYPE             = 'auth_type';
    const ALLOWED_EMAIL_DOMAINS = 'allowed_email_domains';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const DELETED_AT            = 'deleted_at';
    const LOGO_URL              = 'logo_url';

    protected static $sign = 'org';

    protected $entity = 'organization';

    protected $table = Table::ORGANIZATION;

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::NAME,
        self::OWNER_ID,
        self::EMAIL,
        self::AUTH_TYPE,
        self::ALLOWED_TYPE_DOMAINS,
        self::LOGO_URL,
    );

    protected $visible = array(
        self::NAME,
        self::OWNER_ID,
        self::EMAIL,
        self::AUTH_TYPE,
        self::ALLOWED_TYPE_DOMAINS,
        self::LOGO_URL,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    );

    protected $public = array(
        self::ID,
        self::NAME,
        self::EMAIL,
        self::OWNER_ID,
        self::ALLOWED_EMAIL_DOMAINS,
        self::LOGO_URL,
        self::AUTH_TYPE,
        self::CREATED_AT,
    );

    protected $defaults = array(
        self::ALLOWED_EMAIL_DOMAINS => [],
        self::AUTH_TYPE => [],
    );

    protected $guarded = array(self::ID);
}
