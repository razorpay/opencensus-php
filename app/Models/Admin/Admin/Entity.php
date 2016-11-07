<?php

namespace RZP\Models\Admin\Admin;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Admin\Token;

class Entity extends Base\PublicEntity
{
    const NAME                      = 'name';
    const USERNAME                  = 'username';
    const PASSWORD                  = 'password';
    const EMAIL                     = 'email';
    const REMEMBER_TOKEN            = 'remember_token';
    const OAUTH_ACCESS_TOKEN        = 'access_token';
    const OAUTH_PROVIDER_ID         = 'oauth_provider_id';
    const ORG_ID                    = 'org_id';
    const DELETED_AT                = 'deleted_at';

    protected static $sign = 'admin';

    protected $entity = 'admin';

    protected $table = Table::ADMIN;

    protected $generateIdOnCreate = true;

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

    public function org()
    {
        return $this->belongsTo('Org\Entity');
    }

    public function roles()
    {
        return $this->morphToMany('RZP\Models\Admin\Role\Entity', 'entity', Table::ROLE_MAP);
    }

    // Admins can be part of multiple groups
    public function groups()
    {
        return $this->morphToMany('\RZP\Models\Admin\Group\Entity', 'entity', Table::ADMIN_GROUP);
    }

    public function merchants()
    {
        return $this->morphToMany('RZP\Models\Merchant\Entity', 'entity', Table::MERCHANT_MAP);
    }

    public function token()
    {
        return $this->hasMany('Token\Entity');
    }

    public function saveOrFailMerchant(Merchant\Entity $merchant)
    {
        // TODO: Restrict No of merchants per admin to 1.
        $this->merchants()->save($merchant);

        return $merchant;
    }
}
