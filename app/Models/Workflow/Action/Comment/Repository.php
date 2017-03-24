<?php

namespace RZP\Models\Workflow\Action\Comment;

use RZP\Models\Workflow\Base;

class Repository extends Base\Repository
{
    protected $entity = 'action_comment';

    protected $adminFetchParamRules = [
        Entity::ACTION_ID      => 'sometimes|string|size:20',
    ];

    public function fetchByActionId(string $actionId)
    {
        return $this->newQuery()
                    ->where(Entity::ACTION_ID, '=', $actionId)
                    ->get();
    }
}
