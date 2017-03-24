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
