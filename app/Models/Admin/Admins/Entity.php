<?php

namespace RZP\Models\Admin\Admins;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const EMAIL                     = 'email';
    const NAME                      = 'name';
    const USERNAME                  = 'username';
    const PASSWORD                  = 'password';
    const REMEMBER_TOKEN            = 'remember_token';
    const OAUTH_ACCESS_TOKEN        = 'access_token';
    const OAUTH_PROVIDER_ID         = 'oauth_provider_id';
    const ORG_ID                    = 'org_id';
    const DELETED_AT                = 'deleted_at';
}
