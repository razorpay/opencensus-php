<?php

namespace RZP\Models\Workflow\Action\State;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;

class Repository extends Base\Repository
{
    protected $entity = 'action_state';

    protected $appFetchParamRules = [
        Entity::ADMIN_ID  => 'sometimes|string|max:14',
        Entity::ACTION_ID => 'sometimes|string|max:14',
    ];

    public function fetchStateTransitionsByActionId(string $actionId)
    {
        return $this->newQuery()
                    ->where(Entity::ACTION_ID, '=', $actionId)
                    ->orderBy(Entity::CREATED_AT)
                    ->get();
    }

    public function getLatestState(string $actionId)
    {
        return $this->newQuery()
                    ->where(Entity::ACTION_ID, '=', $actionId)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->firstOrFail();
    }
}
