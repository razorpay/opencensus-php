<?php

namespace RZP\Models\Workflow\Step;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_step';

    protected $appFetchParamRules = [
        Entity::WORKFLOW_ID   => 'sometimes|string|max:14',
        Entity::ROLE_ID       => 'sometimes|string|max:14',
        Entity::PERMISSION_ID => 'sometimes|string|max:14',
        Entity::LEVEL         => 'sometimes|string|max:14',
    ];

    public function getNumCheckersForAction(string $workflowId)
    {
        return $this->newQuery()
                    ->where(Entity::WORKFLOW_ID, '=', $workflowId)
                    ->sum(Entity::REVIEWER_COUNT);
    }
}
