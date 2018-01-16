<?php

namespace RZP\Models\Comment;

use RZP\Models\Base;
use RZP\Models\Workflow\Action\Entity as Action;

class Service extends Base\Service
{
    public function createForWorkflowAction(array $input, string $actionId)
    {
        Action::verifyIdAndStripSign($actionId);

        $action = $this->repo->workflow_action->findOrFailPublic($actionId);

        $admin = $this->app['basicauth']->getAdmin();

        $comment = $this->core()->createForWorkflowAction($input, $action, $admin);

        return $comment->toArrayPublic();
    }
}
