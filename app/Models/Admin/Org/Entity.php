<?php

namespace RZP\Models\Admin\Org;

use App;
use RZP\Constants\Table;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Admin;
use RZP\Models\Base\Traits\RevisionableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\Entity
{
    use SoftDeletes;
    use RevisionableTrait;

    const AUTH_TYPE        = 'auth_type';
    const BUSINESS_NAME    = 'business_name';
    const DISPLAY_NAME     = 'display_name';
    const EMAIL            = 'email';
    const EMAIL_DOMAINS    = 'email_domains';
    const ALLOW_SIGN_UP    = 'allow_sign_up';
    const LOGIN_LOGO_URL   = 'login_logo_url';
    const MAIN_LOGO_URL    = 'main_logo_url';
    const INVOICE_LOGO_URL = 'invoice_logo_url';
    const DELETED_AT       = 'deleted_at';
    const CUSTOM_CODE      = 'custom_code';
    const ADMIN            = 'admin';
    const FROM_EMAIL       = 'from_email';
    const SIGNATURE_EMAIL  = 'signature_email';
    const CROSS_ORG_ACCESS = 'cross_org_access';

    const WORKFLOW_PERMISSIONS = 'workflow_permissions';

    /**
     * Holds all the permissions as relation key.
     */
    const PERMISSIONS       = 'permissions';

    const RAZORPAY_ORG_ID = '100000razorpay';

    protected static $sign = 'org';

    protected $entity = 'org';

    protected $generateIdOnCreate = false;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $embeddedRelations = [
        self::PERMISSIONS,
        self::WORKFLOW_PERMISSIONS,
    ];

    protected $fillable = [
        self::DISPLAY_NAME,
        self::BUSINESS_NAME,
        self::EMAIL,
        self::AUTH_TYPE,
        self::EMAIL_DOMAINS,
        self::ALLOW_SIGN_UP,
        self::LOGIN_LOGO_URL,
        self::MAIN_LOGO_URL,
        self::INVOICE_LOGO_URL,
        self::CROSS_ORG_ACCESS,
        self::CUSTOM_CODE,
        self::FROM_EMAIL,
        self::SIGNATURE_EMAIL,
    ];

    protected $visible = [
        self::ID,
        self::DISPLAY_NAME,
        self::BUSINESS_NAME,
        self::EMAIL,
        self::AUTH_TYPE,
        self::EMAIL_DOMAINS,
        self::ALLOW_SIGN_UP,
        self::LOGIN_LOGO_URL,
        self::MAIN_LOGO_URL,
        self::INVOICE_LOGO_URL,
        self::DELETED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::CUSTOM_CODE,
        self::FROM_EMAIL,
        self::SIGNATURE_EMAIL,
        self::PERMISSIONS,
        self::WORKFLOW_PERMISSIONS,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::DISPLAY_NAME,
        self::BUSINESS_NAME,
        self::EMAIL,
        self::EMAIL_DOMAINS,
        self::ALLOW_SIGN_UP,
        self::LOGIN_LOGO_URL,
        self::MAIN_LOGO_URL,
        self::INVOICE_LOGO_URL,
        self::AUTH_TYPE,
        self::CREATED_AT,
        self::CUSTOM_CODE,
        self::FROM_EMAIL,
        self::SIGNATURE_EMAIL,
        self::PERMISSIONS,
        self::WORKFLOW_PERMISSIONS,
    ];

    protected $guarded = [
        self::ID
    ];

    protected $casts = [
        self::ALLOW_SIGN_UP    => 'bool',
        self::CROSS_ORG_ACCESS => 'bool',
    ];

    protected $defaults = [
        self::CROSS_ORG_ACCESS => false,
    ];

    protected $publicSetters = [
        self::ID
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($org)
        {
            $org->hostnames()->delete();
            $org->roles()->delete();
            $org->admins()->delete();
            $org->groups()->delete();
            $org->permissions()->delete();
        });

        static::restored(function ($org)
        {
            $org->hostnames()->withTrashed()->restore();
            $org->roles()->withTrashed()->restore();
            $org->admins()->withTrashed()->restore();
            $org->groups()->withTrashed()->restore();
            $org->permissions()->withTrashed()->restore();
        });
    }

    public function hostnames()
    {
        return $this->hasMany('RZP\Models\Admin\Org\Hostname\Entity');
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

    public function workflow_permissions()
    {
        return $this->permissions()->where('enable_workflow', '=', 1);
    }

    public function getAllowSignUp()
    {
        return $this->getAttribute(self::ALLOW_SIGN_UP);
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getFromEmail()
    {
        return $this->getAttribute(self::FROM_EMAIL) ?? "support@razorpay.com";
    }

    public function getSignatureEmail()
    {
        return $this->getAttribute(self::SIGNATURE_EMAIL);
    }

    public function getEmailDomains()
    {
        return $this->getAttribute(self::EMAIL_DOMAINS);
    }

    public function getDisplayName()
    {
        return $this->getAttribute(self::DISPLAY_NAME);
    }

    public function getBusinessName()
    {
        return $this->getAttribute(self::BUSINESS_NAME);
    }

    public function getAuthType()
    {
        return $this->getAttribute(self::AUTH_TYPE);
    }

    public function getCustomCode()
    {
        return $this->getAttribute(self::CUSTOM_CODE);
    }

    public function isCrossOrgAccessEnabled()
    {
        return $this->getAttribute(self::CROSS_ORG_ACCESS);
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

    public function getInputFields()
    {
        return $this->fillable;
    }

    public function getPrimaryHostName()
    {
        return $this->hostnames()->first()->getHostName();
    }
}
