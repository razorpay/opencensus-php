<?php

namespace RZP\Models\Workflow\Action\Comment;

use RZP\Exception;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'action_comment';

    protected $appFetchParamRules = [
        Entity::ACTION_ID      => 'sometimes|string|size:20',
    ];

    public function fetchByActionId(string $actionId)
    {
        return $this->newQuery()
                    ->where(Entity::ACTION_ID, '=', $actionId)
                    ->get();
    }
}
