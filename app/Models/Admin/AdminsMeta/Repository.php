<?php

namespace RZP\Models\Admin\AdminsMeta;

use RZP\Models\Admin\Base;

class Repository extends Base\Repository
{

    protected $entity = 'admins_meta';

    protected $appFetchParamRules = [
        Entity::UNIQUE_IDENTIFIER => 'sometimes|string',
    ];

}
