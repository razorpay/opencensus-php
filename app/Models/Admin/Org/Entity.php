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

    protected $generateIdOnCreate = false;

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
        self::ENTITY,
        self::DISPLAY_NAME,
        self::BUSINESS_NAME,
        self::HOSTNAME,
        self::EMAIL,
        self::EMAIL_DOMAINS,
        self::LOGIN_LOGO_URL,
        self::MAIN_LOGO_URL,
        self::AUTH_TYPE,
        self::CREATED_AT,
    ];

    protected $defaults = [
        self::HOSTNAME => 'razorpay.com'
    ];

    protected $guarded = [
        self::ID
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($org)
        {
            $org->roles()->delete();
            // $org->policy()->delete();
            $org->admins()->delete();
            $org->groups()->delete();
            $org->permissions()->delete();
        });

        static::restored(function ($org)
        {
            $org->roles()->withTrashed()->restore();
            // $org->policy()->withTrashed()->restore();
            $org->admins()->withTrashed()->restore();
            $org->groups()->withTrashed()->restore();
            $org->permissions()->withTrashed()->restore();
        });
    }

    public function policy()
    {
        return $this->hasOne('RZP\Models\Admin\Org\AuthPolicy\Entity');
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
        return $this->morphToMany('RZP\Models\Admin\Permission\Entity', 'entity', Table::PERMISSION_MAP);
    }

    public function getEmailDomains()
    {
        return $this->getAttribute(self::EMAIL_DOMAINS);
    }

    public function getHostname()
    {
        return $this->getAttribute(self::HOSTNAME);
    }

    public function getDisplayName()
    {
        return $this->getAttribute(self::DISPLAY_NAME);
    }

    public function getAuthType()
    {
        return $this->getAttribute(self::AUTH_TYPE);
    }

    protected function getEmailDomainsAttribute()
    {
        $emailDomains = $this->attributes[self::EMAIL_DOMAINS];

        return explode(',', $emailDomains);
    }

    protected function setEmailDomainsAttribute($emailDomains)
    {
        if (is_array($emailDomains) === true)
        {
            $emailDomains = implode(',', $emailDomains);
        }

        $this->attributes[self::EMAIL_DOMAINS] = $emailDomains;
    }
}
