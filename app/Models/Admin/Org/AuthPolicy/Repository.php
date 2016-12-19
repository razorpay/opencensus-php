<?php

namespace RZP\Models\Admin\Org\AuthPolicy;

use Carbon\Carbon;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'auth_policy';

    protected $proxyFetchParamRules = array(
        Entity::ORG_ID => 'sometimes'
    );

    protected $appFetchParamRules = array(
        Entity::ORG_ID => 'sometimes'
    );
}
