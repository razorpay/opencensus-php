<?php

namespace RZP\Models\Workflow;

use RZP\Models\Workflow\Base;

class Repository extends Base\Repository
{
    protected $entity = 'workflow';

    protected $adminFetchParamRules = [
        Entity::ORG_ID        => 'sometimes|string|max:14',
    ];
}
