<?php

namespace RZP\Models\Workflow;

use RZP\Base;
use RZP\Error;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME        => 'required|string|max:150',
        Entity::ORG_ID      => 'required|string|max:14',
        Entity::PERMISSIONS => 'required|array',
        Entity::STEPS       => 'required|array',
    ];

    protected static $editRules = [
        Entity::NAME        => 'required|string|max:150',
        Entity::PERMISSIONS => 'required|array',
    ];

    public function validatePermissions($workflow, $input)
    {
        $permissionIds = $workflow->permissions
                                  ->each(function($permission, $key) {
                                        return $permission->getId();
                                    })
                                  ->toArray();

        if (empty(array_diff($permissionIds, $input[Entity::PERMISSIONS])) === false)
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_WORKFLOW_PERMISSIONS_CANNOT_BE_REMOVED);
        }
    }
}
