<?php

namespace RZP\Models\Admin\Org;

use Illuminate\Database\Eloquent\SoftDeletes;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Admin\Admin;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const AUTH_TYPE         = 'auth_type';
    const BUSINESS_NAME     = 'business_name';
    const DISPLAY_NAME      = 'display_name';
    const EMAIL             = 'email';
    const HOSTNAME          = 'hostname';
    const EMAIL_DOMAINS     = 'email_domains';
    const LOGIN_LOGO_URL    = 'login_logo_url';
    const MAIN_LOGO_URL     = 'main_logo_url';
    const DELETED_AT        = 'deleted_at';

    protected static $sign = 'org';

    protected $entity = 'org';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::DISPLAY_NAME,
        self::BUSINESS_NAME,
        self::EMAIL,
        self::AUTH_TYPE,
        self::HOSTNAME,
        self::EMAIL_DOMAINS,
        self::LOGIN_LOGO_URL,
        self::MAIN_LOGO_URL,
    ];

    protected $visible = [
        self::ID,
        self::DISPLAY_NAME,
        self::BUSINESS_NAME,
        self::EMAIL,
        self::AUTH_TYPE,
        self::HOSTNAME,
        self::EMAIL_DOMAINS,
        self::LOGIN_LOGO_URL,
        self::MAIN_LOGO_URL,
        self::DELETED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::DISPLAY_NAME,
        self::BUSINESS_NAME,
        self::EMAIL,
        self::EMAIL_DOMAINS,
        self::LOGIN_LOGO_URL,
        self::MAIN_LOGO_URL,
        self::AUTH_TYPE,
        self::DELETED_AT,
    ];

    protected $defaults = [
    ];

    protected $guarded = [self::ID];

    public function owners()
    {
        // Returns the list of org's owners
    }

    public function policy()
    {
        return $this->hasOne('RZP\Models\Org\AuthPolicy\Entity');
    }

    public function merchants()
    {
        return $this->hasMany('RZP\Models\Merchant\Entity');
    }

    public function admins()
    {
        return $this->hasMany('RZP\Models\Admin\Admin\Entity');
    }

    public function groups()
    {
        return $this->hasMany('RZP\Models\Admin\Group\Entity');
    }

    public function roles()
    {
        return $this->hasMany('RZP\Models\Admin\Admin\Entity');
    }

    public function permissions()
    {
        return $this->morphedToMany('RZP\Models\Admin\Permission\Entity', 'entity', Table::PERMISSON_MAP);
    }

    public function getEmailDomains()
    {
        return $this->getAttribute(self::EMAIL_DOMAINS);
    }

    public function getHostname()
    {
        return $this->getAttribute(self::HOSTNAME);
    }

    protected function getEmailDomainsAttribute()
    {
        $emailDomains = $this->attributes[self::EMAIL_DOMAINS];

        return explode($emailDomains, ',');
    }
}
