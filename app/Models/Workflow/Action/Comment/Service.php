<?php

namespace RZP\Models\Workflow\Action\Comment;

use RZP\Models\Base;
use RZP\Models\Workflow\Action\Entity as Action;

class Service extends Base\Service
{
    public function create(string $actionId, array $input)
    {
        Action::verifyIdAndStripSign($actionId);

        $input[Entity::ACTION_ID] = $actionId;

        $comment = $this->core()->create($input);

        // Get relations

        $admin = $comment->admin;

        // Resolve final array to return

        $comment = $comment->toArrayPublic();

        $comment['admin'] = $admin;

        return $comment;
    }
}
