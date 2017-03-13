<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;

class Repository extends Base\Repository
{
    protected $entity = 'action_checker';

    protected $appFetchParamRules = [
        Entity::STEP_ID   => 'sometimes|string|max:14',
        Entity::ADMIN_ID  => 'sometimes|string|max:14',
        Entity::ACTION_ID => 'sometimes|string|max:14',
    ];

    public function fetchByActionId(string $actionId)
    {
        return $this->newQuery()
                    ->where(Entity::ACTION_ID, '=', $actionId)
                    ->whereNotNull(Entity::APPROVED)
                    ->get();
    }
}
