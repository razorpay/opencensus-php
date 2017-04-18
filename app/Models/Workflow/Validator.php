<?php

namespace RZP\Models\Workflow;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\Step;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME        => 'required|string|max:150',
        Entity::ORG_ID      => 'required|string|max:14',
        Entity::PERMISSIONS => 'required|array',
        Entity::STEPS       => 'required|array',
    ];

    protected static $editRules = [
        Entity::NAME        => 'sometimes|string|max:150',
        Entity::PERMISSIONS => 'sometimes|array',
    ];

    public function validatePermissionHasOneWorkflow(array $perms)
    {
        $workflowIds = (new Repository)->getWorkflowIdsForPermissions($perms);

        if (empty($workflowIds->toArray()) === false)
        {
            $data = [
                'workflow_ids'   => $workflowIds,
                'permission_ids' => $perms,
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_PERMISSION_EXISTS,
                $data);
        }
    }
}
