<?php

namespace RZP\Models\Workflow;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\Step;
use RZP\Models\Admin\Permission;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME        => 'required|string|max:150',
        Entity::ORG_ID      => 'required|string|size:14',
        Entity::PERMISSIONS => 'required|array|custom',
        Entity::LEVELS      => 'required|array',
    ];

    protected static $editRules = [
        Entity::NAME        => 'sometimes|string|max:150',
        Entity::PERMISSIONS => 'sometimes|array|custom',
        Entity::STEPS       => 'sometimes|array',
        Entity::ORG_ID      => 'sometimes|string|size:14',
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

    public function validatePermissions($attribute, $value)
    {
        $permissions = (new Permission\Repository)->retrieveByIds($value);

        $permissionsWithoutWorkflowEnable = [];

        $permissionWithWorkflowEnable = true;

        foreach ($permissions as $permission)
        {
            if ($permission->canEnableWorkflow() === false)
            {
                $permissionWithWorkflowEnable = false;

                $permissionsWithoutWorkflowEnable[] = $permission;
            }
        }

        if ($permissionWithWorkflowEnable === false)
        {
            throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_WORKFLOW_DISABLED_PERMISSION_PASSED,
                        $permissionsWithoutWorkflowEnable);
        }
    }
}
