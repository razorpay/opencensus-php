<?php

namespace RZP\Models\Workflow\Action\Comment;

use RZP\Error;
use RZP\Exception;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'action_comment';

    protected $appFetchParamRules = [
        Entity::ACTION_ID      => 'sometimes|string|size:20',
    ];

    public function fetchByActionId(string $id)
    {
        return $this->newQuery()
                    ->where(Entity::ACTION_ID, '=', $id)
                    ->get();
    }
}
