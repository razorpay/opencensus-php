<?php

namespace RZP\Models\Workflow\Action\State;

use RZP\Models\Workflow\Base;

class Repository extends Base\Repository
{
    protected $entity = 'action_state';

    protected $appFetchParamRules = [
        Entity::ADMIN_ID  => 'sometimes|string|max:14',
        Entity::ACTION_ID => 'sometimes|string|max:14',
    ];
}
