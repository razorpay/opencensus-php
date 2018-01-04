<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'account';

    protected $entityFetchParamRules = [
        Entity::PARENT_ID => 'sometimes|string|max:14',
    ];
}
