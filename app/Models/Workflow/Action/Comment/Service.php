<?php

namespace RZP\Models\Workflow\Action\Comment;

use RZP\Error;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Workflow\Action\Entity as Action;

class Service extends Base\Service
{
    public function create(string $actionId, array $input)
    {
        Action::verifyIdAndStripSign($actionId);

        $input[Entity::ACTION_ID] = $actionId;

        $comment = $this->core()->create($input);

        return $comment->toArrayPublic();
    }

    public function fetchByActionId(string $actionId)
    {
        Action::verifyIdAndStripSign($actionId);

        return $this->repo->action_comment->fetchByActionId($actionId);
    }
}
