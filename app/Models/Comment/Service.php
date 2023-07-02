<?php

namespace RZP\Models\Comment;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Workflow\Action\Entity as Action;
use RZP\Trace\TraceCode;
use RZP\Http\Controllers\WorkflowController ;

class Service extends Base\Service
{
    public function createForWorkflowAction(array $input, string $actionId, $admin = null)
    {
        Action::verifyIdAndStripSign($actionId);

        $action = $this->repo->workflow_action->findOrFailPublic($actionId);

        if ($admin === null)
        {
            $admin = $this->app['basicauth']->getAdmin();
        }

        $comment = $this->core()->createForWorkflowAction($input, $action, $admin);

        return $comment->toArrayPublic();
    }
}
